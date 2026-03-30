<?php

use App\Rules\AppointmentInFutureRule;
use App\Rules\CreatedLessThan24hRule;
use App\Services\Lead\LeadSelector;

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';

$action = $_GET['action'] ?? '';

if ($action === 'getTests') {
    echo file_get_contents(__DIR__ . '/data.json');
    exit;
}

if ($action === 'process') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['newLead'], $input['duplicates'])) {
        echo json_encode(['error' => 'Invalid input data']);
        exit;
    }

    $newLead = (object)$input['newLead'];
    $duplicates = array_map(fn($d) => (object)$d, $input['duplicates']);

    $selector = new LeadSelector();
    $selector->setRules([
        new CreatedLessThan24hRule(),
        new AppointmentInFutureRule(),
    ]);

    try {
        $result = $selector->chooseMainLead($duplicates, $newLead);

        echo json_encode([
            'success' => true,
            'mainLead'     => $result['mainLead'] ?? null,
            'leadsToMerge' => $result['leadsToMerge'] ?? [],
            'duplicateIds' => $result['duplicateIds'] ?? []
        ]);
    } catch (\Throwable $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode(['error' => 'Unknown action']);