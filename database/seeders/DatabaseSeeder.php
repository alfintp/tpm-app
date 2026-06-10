<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Machine;
use App\Models\MachineComponent;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceAction;
use App\Models\Approval;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $technician = User::create([
            'full_name' => 'Ahmad Fauzi',
            'email' => 'technician@tpm.com',
            'password' => bcrypt('password'),
            'role' => 'technician',
        ]);

        $manager = User::create([
            'full_name' => 'Dewi Susanti',
            'email' => 'manager@tpm.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        // =====================================================================
        // 2. MESIN KOMPLEKS: Mixer Rice Crunch (banyak komponen & riwayat)
        // =====================================================================
        $mixer = Machine::create([
            'name' => 'Mixer Rice Crunch',
            'description' => 'Mesin pencampur beras dengan kontrol suhu dan tekanan',
            'condition_pct' => 0, // Will be recalculated from components
            'location' => 'Line 2 - Pabrik A',
            'status' => 'active',
        ]);

        // Components for Mixer Rice Crunch
        $moldDie = MachineComponent::create([
            'machine_id' => $mixer->id,
            'category' => 'Mechanical',
            'name' => 'Mold Die Set',
            'specification' => 'Titanium Coated Mold 60/70/80 mm, cetakan 3 ukuran',
            'qty' => 1,
            'unit' => 'set',
            'last_replaced_at' => Carbon::now()->subMonths(6),
            'last_condition_pct' => 75.0,
            'maintenance_schedule' => 'W1',
        ]);

        $motorDrive = MachineComponent::create([
            'machine_id' => $mixer->id,
            'category' => 'Technical',
            'name' => 'Motor Drive AC',
            'specification' => '3 Phase 5HP AC Motor 380V, 1450 RPM',
            'qty' => 2,
            'unit' => 'pcs',
            'last_replaced_at' => Carbon::now()->subYear(),
            'last_condition_pct' => 80.0,
            'maintenance_schedule' => 'W2',
        ]);

        $gearbox = MachineComponent::create([
            'machine_id' => $mixer->id,
            'category' => 'Mechanical',
            'name' => 'Gearbox Reducer',
            'specification' => 'Ratio 1:15, Torsi Maks 500 Nm',
            'qty' => 1,
            'unit' => 'pcs',
            'last_replaced_at' => Carbon::now()->subMonths(3),
            'last_condition_pct' => 88.0,
            'maintenance_schedule' => 'W1',
        ]);

        $sensorTemp = MachineComponent::create([
            'machine_id' => $mixer->id,
            'category' => 'Technical',
            'name' => 'Sensor Suhu',
            'specification' => 'PT100 RTD, Range -50°C s/d 300°C',
            'qty' => 3,
            'unit' => 'pcs',
            'last_replaced_at' => Carbon::now()->subMonths(8),
            'last_condition_pct' => 90.0,
            'maintenance_schedule' => 'W4',
        ]);

        $bearing = MachineComponent::create([
            'machine_id' => $mixer->id,
            'category' => 'Mechanical',
            'name' => 'Bearing Set',
            'specification' => 'SKF 6205-2RS, Inner Dia 25mm',
            'qty' => 4,
            'unit' => 'pcs',
            'last_replaced_at' => Carbon::now()->subMonths(2),
            'last_condition_pct' => 65.0,
            'maintenance_schedule' => 'W2',
        ]);

        // Recalculate mixer condition after seeding components
        $avgMixer = $mixer->components()->avg('last_condition_pct');
        $mixer->update(['condition_pct' => round($avgMixer, 1)]);

        // Maintenance Schedules for Mixer
        $mixerSchedule = MaintenanceSchedule::create([
            'machine_id' => $mixer->id,
            'interval_days' => 30,
            'schedule_type' => 'preventive',
            'next_due_date' => Carbon::today()->addDays(15),
            'is_active' => true,
        ]);

        // RIWAYAT 1 - Mixer, 3 bulan lalu
        $rec1 = MaintenanceRecord::create([
            'machine_id' => $mixer->id,
            'technician_id' => $technician->id,
            'maintenance_date' => Carbon::now()->subMonths(3)->setTime(8, 30),
            'condition_before_pct' => 60.0,
            'condition_after_pct' => 80.0,
            'notes' => 'Penggantian bearing set dan pelumasan gearbox. Motor drive dicek kondisi normal.',
            'status' => 'completed',
        ]);
        MaintenanceAction::create(['record_id' => $rec1->id, 'machine_component_id' => $bearing->id, 'action_type' => 'replace', 'condition_before_pct' => 40.0, 'condition_after_pct' => 95.0, 'description' => 'Bearing aus parah, bunyi berisik saat operasi.']);
        MaintenanceAction::create(['record_id' => $rec1->id, 'machine_component_id' => $gearbox->id, 'action_type' => 'lubricate', 'condition_before_pct' => 70.0, 'condition_after_pct' => 88.0, 'description' => 'Ganti oli pelumas SAE 90 sebanyak 2 liter.']);
        MaintenanceAction::create(['record_id' => $rec1->id, 'machine_component_id' => $motorDrive->id, 'action_type' => 'inspect', 'condition_before_pct' => 80.0, 'condition_after_pct' => 80.0, 'description' => 'Kondisi motor baik, tidak ada anomali.']);
        Approval::create(['record_id' => $rec1->id, 'approver_id' => $manager->id, 'decision' => 'approved', 'notes' => 'Pekerjaan sesuai standar.', 'decided_at' => Carbon::now()->subMonths(3)->addDay()]);

        // RIWAYAT 2 - Mixer, 1.5 bulan lalu (bulan berbeda untuk filter test)
        $rec2 = MaintenanceRecord::create([
            'machine_id' => $mixer->id,
            'technician_id' => $technician->id,
            'maintenance_date' => Carbon::now()->subWeeks(6)->setTime(10, 0),
            'condition_before_pct' => 72.0,
            'condition_after_pct' => 82.0,
            'notes' => 'Kalibrasi sensor suhu dan pembersihan mold die set.',
            'status' => 'completed',
        ]);
        MaintenanceAction::create(['record_id' => $rec2->id, 'machine_component_id' => $sensorTemp->id, 'action_type' => 'inspect', 'condition_before_pct' => 85.0, 'condition_after_pct' => 92.0, 'description' => 'Kalibrasi sensor dengan alat standar, deviasi < 0.5°C.']);
        MaintenanceAction::create(['record_id' => $rec2->id, 'machine_component_id' => $moldDie->id, 'action_type' => 'clean', 'condition_before_pct' => 60.0, 'condition_after_pct' => 78.0, 'description' => 'Bersihkan sisa bumbu yang menempel di cetakan.']);
        Approval::create(['record_id' => $rec2->id, 'approver_id' => $manager->id, 'decision' => 'approved', 'notes' => 'OK.', 'decided_at' => Carbon::now()->subWeeks(6)->addDay()]);

        // RIWAYAT 3 - Mixer, 2 minggu lalu
        $rec3 = MaintenanceRecord::create([
            'machine_id' => $mixer->id,
            'technician_id' => $technician->id,
            'maintenance_date' => Carbon::now()->subWeeks(2)->setTime(13, 45),
            'condition_before_pct' => 78.0,
            'condition_after_pct' => 88.0,
            'notes' => 'Perbaikan motor drive unit 2 yang mengeluarkan panas berlebih.',
            'status' => 'completed',
        ]);
        MaintenanceAction::create(['record_id' => $rec3->id, 'machine_component_id' => $motorDrive->id, 'action_type' => 'repair', 'condition_before_pct' => 60.0, 'condition_after_pct' => 80.0, 'description' => 'Kapasitor motor diganti, winding dibersihkan dari debu.']);
        Approval::create(['record_id' => $rec3->id, 'approver_id' => $manager->id, 'decision' => 'approved', 'notes' => 'Sudah dites 2 jam operasi, normal.', 'decided_at' => Carbon::now()->subWeeks(2)->addDays(2)]);

        // =====================================================================
        // 3. MESIN SIMPEL: Mesin Spray Bumbu
        // =====================================================================
        $spray = Machine::create([
            'name' => 'Mesin Spray Bumbu',
            'description' => 'Mesin pelapis bumbu dengan sistem semprotan bertekanan',
            'condition_pct' => 0,
            'location' => 'Line 1 - Pabrik A',
            'status' => 'active',
        ]);

        $nozzle = MachineComponent::create([
            'machine_id' => $spray->id,
            'category' => 'Mechanical',
            'name' => 'Spray Nozzle',
            'specification' => 'Stainless 316L, Diameter 0.5mm, Tekanan 2-6 bar',
            'qty' => 4,
            'unit' => 'pcs',
            'last_replaced_at' => Carbon::now()->subMonths(2),
            'last_condition_pct' => 88.0,
            'maintenance_schedule' => 'W1',
        ]);

        $pump = MachineComponent::create([
            'machine_id' => $spray->id,
            'category' => 'Mechanical',
            'name' => 'Pompa Bumbu',
            'specification' => 'Peristaltic Pump, Flow 0.1-5 L/min',
            'qty' => 1,
            'unit' => 'pcs',
            'last_replaced_at' => Carbon::now()->subMonths(5),
            'last_condition_pct' => 78.0,
            'maintenance_schedule' => 'W2',
        ]);

        $avgSpray = $spray->components()->avg('last_condition_pct');
        $spray->update(['condition_pct' => round($avgSpray, 1)]);

        MaintenanceSchedule::create([
            'machine_id' => $spray->id,
            'interval_days' => 90,
            'schedule_type' => 'predictive',
            'next_due_date' => Carbon::today()->subDays(5), // 5 hari telat!
            'is_active' => true,
        ]);

        $recSpray = MaintenanceRecord::create([
            'machine_id' => $spray->id,
            'technician_id' => $technician->id,
            'maintenance_date' => Carbon::now()->subMonths(2)->setTime(9, 15),
            'condition_before_pct' => 70.0,
            'condition_after_pct' => 85.0,
            'notes' => 'Penggantian nozzle yang tersumbat dan pembersihan jalur pompa.',
            'status' => 'completed',
        ]);
        MaintenanceAction::create(['record_id' => $recSpray->id, 'machine_component_id' => $nozzle->id, 'action_type' => 'replace', 'condition_before_pct' => 55.0, 'condition_after_pct' => 95.0, 'description' => 'Nozzle tersumbat bumbu kering, diganti dengan unit baru.']);
        MaintenanceAction::create(['record_id' => $recSpray->id, 'machine_component_id' => $pump->id, 'action_type' => 'clean', 'condition_before_pct' => 72.0, 'condition_after_pct' => 80.0, 'description' => 'Bersihkan jalur hisap dengan air panas.']);

        // =====================================================================
        // 4. MESIN SIMPEL: Exhaust Fan (kondisi buruk, ada jadwal hari ini)
        // =====================================================================
        $exhaust = Machine::create([
            'name' => 'Exhaust Fan',
            'description' => 'Sistem ventilasi udara pabrik utama',
            'condition_pct' => 0,
            'location' => 'Pabrik Utama - Atap',
            'status' => 'maintenance',
        ]);

        $fanBlade = MachineComponent::create([
            'machine_id' => $exhaust->id,
            'category' => 'Mechanical',
            'name' => 'Fan Blade',
            'specification' => 'Aluminium Alloy 24 inch, 8 blade',
            'qty' => 1,
            'unit' => 'set',
            'last_replaced_at' => Carbon::now()->subYears(2),
            'last_condition_pct' => 40.0,
            'maintenance_schedule' => 'W4',
        ]);

        $motorFan = MachineComponent::create([
            'machine_id' => $exhaust->id,
            'category' => 'Technical',
            'name' => 'Motor Fan',
            'specification' => '1 Phase 1HP 220V, 1400 RPM',
            'qty' => 1,
            'unit' => 'pcs',
            'last_replaced_at' => Carbon::now()->subYear(),
            'last_condition_pct' => 55.0,
            'maintenance_schedule' => 'W4',
        ]);

        $avgExhaust = $exhaust->components()->avg('last_condition_pct');
        $exhaust->update(['condition_pct' => round($avgExhaust, 1)]);

        // Jadwal hari ini!
        MaintenanceSchedule::create([
            'machine_id' => $exhaust->id,
            'interval_days' => 30,
            'schedule_type' => 'preventive',
            'next_due_date' => Carbon::today(),
            'is_active' => true,
        ]);

        $recExhaust = MaintenanceRecord::create([
            'machine_id' => $exhaust->id,
            'technician_id' => $technician->id,
            'maintenance_date' => Carbon::now()->subMonths(1)->setTime(7, 30),
            'condition_before_pct' => 30.0,
            'condition_after_pct' => 50.0,
            'notes' => 'Perbaikan sementara bearing dan pembersihan blade. Perlu penggantian menyeluruh.',
            'status' => 'completed',
        ]);
        MaintenanceAction::create(['record_id' => $recExhaust->id, 'machine_component_id' => $fanBlade->id, 'action_type' => 'clean', 'condition_before_pct' => 30.0, 'condition_after_pct' => 40.0, 'description' => 'Blade dibersihkan dari debu dan kotoran. Terdapat retak di 2 blade.']);
        MaintenanceAction::create(['record_id' => $recExhaust->id, 'machine_component_id' => $motorFan->id, 'action_type' => 'inspect', 'condition_before_pct' => 50.0, 'condition_after_pct' => 55.0, 'description' => 'Motor masih berfungsi meski ada getaran tidak normal. Perlu penggantian.']);
    }
}
