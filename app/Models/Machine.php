<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Machine extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'kode',
        'name',
        'description',
        'condition_pct',
        'location',
        'kota',
        'import_order',
        'status',
        'is_locked',
        'last_unlock_request_at',
        'unlock_reason',
        'unlock_requested_period',
        'unlock_requested_by_id',
        'unlock_status',
        'unlock_expires_at',
        'unlock_approved_by_id',
        'unlock_approved_at',
        'unlock_approval_notes',
        'pic_mesin_id',
        'maintenance_duration',
    ];

    protected $casts = [
        'condition_pct' => 'integer',
        'is_locked' => 'boolean',
        'last_unlock_request_at' => 'datetime',
        'unlock_expires_at' => 'datetime',
        'unlock_approved_at' => 'datetime',
    ];

    protected $appends = ['unlock_status_label'];

    public function getIsOpenAttribute()
    {
        return $this->isOpenForMaintenance();
    }

    public function getUnlockStatusLabelAttribute()
    {
        switch ($this->unlock_status) {
            case 'pending': return 'Menunggu Persetujuan';
            case 'approved': return 'Disetujui';
            case 'rejected': return 'Ditolak';
            default: return 'None';
        }
    }

    public function getCheckedComponentsCountAttribute()
    {
        $today = \Carbon\Carbon::today();

        $occurrence = $this->nearestOccurrence(30);

        if (!$occurrence) {
            return 0;
        }

        $dueDate = \Carbon\Carbon::parse($occurrence->due_date)->startOfDay();
        $periodStart = $dueDate->copy()->startOfMonth();

        $periodRecords = $this->records()
            ->where('status', 'completed')
            ->whereDate('maintenance_date', '>=', $periodStart)
            ->whereDate('maintenance_date', '<=', $today)
            ->get();

        $checkedIds = [];
        foreach ($periodRecords as $r) {
            foreach ($r->actions as $a) {
                if ($a->machine_component_id) $checkedIds[] = $a->machine_component_id;
            }
        }
        return count(array_unique($checkedIds));
    }

    public function getMaintenanceProgressAttribute()
    {
        $totalComponents = $this->components()->count();
        if ($totalComponents === 0) return 0;
        
        return round(($this->checked_components_count / $totalComponents) * 100, 1);
    }

    /**
     * Check if machine is currently open for maintenance reporting.
     * Rules: 
     * 1. If is_locked is true (manually locked/expired), it's locked unless authorized.
     * 2. Open from the admin-configured window before/after the due date (H-x to H+y).
     * 3. If maintenance progress is not 100%, it should remain "submittable" even if expired (handled in controller).
     */
    public function isOpenForMaintenance()
    {
        // Check for manual unlock approval — valid for the entire month of approval
        if ($this->unlock_status === 'approved') {
            if ($this->unlock_approved_at) {
                $approvedMonth = $this->unlock_approved_at->format('Y-m');
                $currentMonth = \Carbon\Carbon::today()->format('Y-m');
                if ($approvedMonth === $currentMonth) {
                    return true;
                }
            } elseif ($this->unlock_expires_at && $this->unlock_expires_at->isFuture()) {
                // Fallback for old approvals without unlock_approved_at
                return true;
            }
        }

        if ($this->is_locked) {
            return false;
        }

        $window = \App\Models\MaintenanceWindowSetting::current();
        $daysBefore = $window->days_before;
        $daysAfter = $window->days_after;

        $today = \Carbon\Carbon::today();
        $lookBack = max(7, $daysAfter);

        // Get all occurrences in the lookback window (not just the nearest one).
        // A machine may have two occurrences close together (e.g. Aug 31 and Sep 2);
        // the nearest (Aug 31) may have an expired window while a later one (Sep 2)
        // is still open. We must check all of them.
        $occurrences = $this->scheduleOccurrences()
            ->whereHas('schedule', fn ($q) => $q->where('is_active', true))
            ->whereDate('due_date', '>=', $today->copy()->subDays($lookBack))
            ->orderBy('due_date', 'asc')
            ->get();

        if ($occurrences->isEmpty()) {
            return false;
        }

        // Check each occurrence: if any has a window that includes today, machine is open.
        foreach ($occurrences as $occ) {
            $dueDate = \Carbon\Carbon::parse($occ->due_date)->startOfDay();
            $openDate = $dueDate->copy()->subDays($daysBefore);
            $closeDate = $dueDate->copy()->addDays($daysAfter);

            if ($today->between($openDate, $closeDate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the nearest occurrence (canonical due date) for this machine's active schedules,
     * looking back up to $lookBackDays days to still catch recently-overdue occurrences.
     */
    protected function nearestOccurrence(int $lookBackDays)
    {
        $today = \Carbon\Carbon::today();

        return $this->scheduleOccurrences()
            ->whereHas('schedule', fn ($q) => $q->where('is_active', true))
            ->whereDate('due_date', '>=', $today->copy()->subDays($lookBackDays))
            ->orderBy('due_date', 'asc')
            ->first();
    }

    public function schedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function scheduleOccurrences()
    {
        return $this->hasMany(ScheduleOccurrence::class);
    }

    public function records()
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function components()
    {
        return $this->hasMany(MachineComponent::class);
    }

    public function picMesin()
    {
        return $this->belongsTo(User::class, 'pic_mesin_id');
    }

    public function unlockRequester()
    {
        return $this->belongsTo(User::class, 'unlock_requested_by_id');
    }

    public function unlockApprover()
    {
        return $this->belongsTo(User::class, 'unlock_approved_by_id');
    }
}
