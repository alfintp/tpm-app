<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\NotificationRead;
use App\Models\MachineUnlockRequest;
use App\Models\MaintenanceRecord;
use App\Models\Role;
use App\Models\ApprovalFlowStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = AppNotification::where('is_active', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($n) use ($user) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'body' => $n->body,
                    'created_at' => $n->created_at->toIso8601String(),
                    'is_read' => $n->isReadBy($user->id),
                ];
            });

        $unreadCount = $notifications->where('is_read', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();

        NotificationRead::firstOrCreate([
            'notification_id' => $id,
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $notificationIds = AppNotification::where('is_active', true)->pluck('id');

        foreach ($notificationIds as $nid) {
            NotificationRead::firstOrCreate([
                'notification_id' => $nid,
                'user_id' => $user->id,
            ], [
                'read_at' => now(),
            ]);
        }

        return response()->json(['message' => 'All notifications marked as read']);
    }

    // Admin CRUD
    public function list(Request $request)
    {
        $notifications = AppNotification::orderByDesc('created_at')->get();

        return response()->json($notifications);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification = AppNotification::create([
            'title' => $request->title,
            'body' => $request->body,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json($notification, 201);
    }

    public function update(Request $request, $id)
    {
        $notification = AppNotification::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification->update([
            'title' => $request->title,
            'body' => $request->body,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json($notification);
    }

    public function destroy($id)
    {
        $notification = AppNotification::findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    public function dynamic(Request $request)
    {
        $user = $request->user();
        $items = [];

        // 1. Pending approvals for approvers
        $approvingRoles = Role::active()->canApprove()->pluck('name')->toArray();
        $isApprover = $user->role === 'admin' || in_array($user->role, $approvingRoles);

        if ($isApprover) {
            $flowSteps = ApprovalFlowStep::active()->ordered()->get();

            $records = MaintenanceRecord::with(['approvals', 'machine:id,name', 'technician:id,full_name,role'])
                ->where('status', 'completed')
                ->orderByDesc('maintenance_date')
                ->get();

            $pendingCount = 0;
            foreach ($records as $record) {
                $state = $this->recordApprovalState($record, $flowSteps);
                if (($state['approval_status'] ?? null) === 'pending') {
                    $pendingRole = $state['pending_role'] ?? null;
                    if ($user->role === 'admin' || $pendingRole === $user->role) {
                        $pendingCount++;
                    }
                }
            }

            if ($pendingCount > 0) {
                $items[] = [
                    'id' => 'dynamic_pending_approvals',
                    'type' => 'approval',
                    'title' => "{$pendingCount} Laporan Menunggu Approval",
                    'body' => "Ada {$pendingCount} laporan yang memerlukan persetujuan Anda.",
                    'link' => '/approvals',
                    'created_at' => now()->toIso8601String(),
                    'is_read' => false,
                ];
            }
        }

        // 2. Pending unlock requests for approvers
        $canApproveUnlock = $user->role === 'admin' || Role::where('name', $user->role)->value('can_approve_unlock');
        if ($canApproveUnlock) {
            $pendingUnlockQuery = MachineUnlockRequest::where('status', 'pending');
            if (isset($user->city) && $user->city !== 'both') {
                $pendingUnlockQuery->whereHas('machine', fn($q) => $q->where('kota', $user->city));
            }
            $pendingUnlockCount = $pendingUnlockQuery->count();

            if ($pendingUnlockCount > 0) {
                $items[] = [
                    'id' => 'dynamic_pending_unlock',
                    'type' => 'unlock_pending',
                    'title' => "{$pendingUnlockCount} Pengajuan Buka Kunci Menunggu",
                    'body' => "Ada {$pendingUnlockCount} pengajuan buka kunci yang menunggu persetujuan Anda.",
                    'link' => '/approvals',
                    'created_at' => now()->toIso8601String(),
                    'is_read' => false,
                ];
            }
        }

        // 3. Unlock request status updates for the requester
        $recentUnlockUpdates = MachineUnlockRequest::with(['machine:id,name'])
            ->where('requested_by_id', $user->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('approved_at', '>=', now()->subDays(7))
            ->orderByDesc('approved_at')
            ->limit(5)
            ->get();

        foreach ($recentUnlockUpdates as $req) {
            $machineName = $req->machine?->name ?? 'mesin';
            $statusLabel = $req->status === 'approved' ? 'disetujui' : 'ditolak';
            $items[] = [
                'id' => 'dynamic_unlock_status_' . $req->id,
                'type' => 'unlock_status',
                'title' => "Pengajuan Buka Kunci {$statusLabel}",
                'body' => "Pengajuan buka kunci untuk mesin {$machineName} telah {$statusLabel}." . ($req->approval_notes ? " Catatan: {$req->approval_notes}" : ''),
                'link' => '/approvals',
                'created_at' => $req->approved_at?->toIso8601String() ?? $req->updated_at->toIso8601String(),
                'is_read' => false,
            ];
        }

        usort($items, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return response()->json([
            'notifications' => $items,
            'unread_count' => count($items),
        ]);
    }

    private function recordApprovalState(MaintenanceRecord $record, $flowSteps)
    {
        $reporterRole = $record->technician?->role ?? 'default';

        $snapshot = $record->approval_flow_snapshot;
        if (!empty($snapshot)) {
            $steps = collect($snapshot)
                ->filter(fn ($s) => ($s['reporter_role'] ?? null) === $reporterRole)
                ->sortBy('step_order')
                ->values();
            if ($steps->isEmpty() && $reporterRole !== 'default') {
                $steps = collect($snapshot)
                    ->filter(fn ($s) => ($s['reporter_role'] ?? null) === 'default')
                    ->sortBy('step_order')
                    ->values();
            }
        }

        if (empty($steps) || $steps->isEmpty()) {
            $steps = $flowSteps->filter(fn ($s) => $s->reporter_role === $reporterRole)
                ->sortBy('step_order')
                ->values();
            if ($steps->isEmpty() && $reporterRole !== 'default') {
                $steps = $flowSteps->filter(fn ($s) => $s->reporter_role === 'default')
                    ->sortBy('step_order')
                    ->values();
            }
        }

        $totalSteps = $steps->count();
        $decisions = $record->approvals->sortBy('step_order');

        $rejected = $decisions->firstWhere('decision', 'rejected');
        if ($rejected) {
            return ['approval_status' => 'rejected', 'pending_role' => null];
        }

        $approvedOrders = $decisions->where('decision', 'approved')->pluck('step_order')->sort()->values();
        $completedSteps = $approvedOrders->count();
        $currentStep = $completedSteps + 1;
        $isFullyApproved = $completedSteps >= $totalSteps && $totalSteps > 0;

        if ($isFullyApproved) {
            return ['approval_status' => 'approved', 'pending_role' => null];
        }

        $currentStepData = $steps->firstWhere('step_order', $currentStep) ?? ($steps[$completedSteps] ?? null);
        $pendingRole = is_array($currentStepData) ? ($currentStepData['role'] ?? null) : ($currentStepData?->role ?? null);

        return [
            'approval_status' => 'pending',
            'pending_role' => $pendingRole,
        ];
    }
}
