<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$req = new Illuminate\Http\Request();
$c = new App\Http\Controllers\MachineComponentController();
$res = $c->history('a24d671a-40ec-432f-9f39-a047c10c15e3', $req);
$data = $res->getData(true);
$record = $data['history'][0]['record'] ?? null;
print_r($record ? [
    'approval_status' => $record['approval_status'] ?? null,
    'approval_notes' => $record['approval_notes'] ?? null,
    'approved_by' => $record['approved_by'] ?? null,
    'approval_state' => $record['approval_state'] ?? null,
] : 'no record');
