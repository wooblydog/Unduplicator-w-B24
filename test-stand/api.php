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

    $input = normalizeTestDates($input);

    $newLead = (object)$input['newLead'];
    $duplicates = array_map(fn($d) => (object)$d, $input['duplicates']);

    if (!$input || !isset($input['newLead'], $input['duplicates'])) {
        echo json_encode(['error' => 'Invalid input data']);
        exit;
    }

    $newLead = (object)$input['newLead'];
    $duplicates = array_map(fn($d) => (object)$d, $input['duplicates']);

    $selector = new LeadSelector();
    $selector->setRules([
        new AppointmentInFutureRule(),
        new CreatedLessThan24hRule(),
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
function normalizeTestDates($data) {
    $now = time();

    $replace = function($dateStr) use ($now) {
        if ($dateStr === "CURRENT") {
            return date('Y-m-d\TH:i:s+05:00', $now);
        }

        if (preg_match('/^CURRENT_(PLUS|MINUS)_(\d+)_(HOURS|DAYS)(?:_(\d+):(\d+))?$/', $dateStr, $m)) {
            $sign   = $m[1] === 'PLUS' ? 1 : -1;
            $amount = (int)$m[2];
            $unit   = $m[3] === 'DAYS' ? 86400 : 3600;

            $timestamp = $now + $sign * $amount * $unit;

            if (!empty($m[4])) {
                return date('Y-m-d', $timestamp) . sprintf('T%02d:%02d:00+05:00', $m[4], $m[5]);
            }

            return date('Y-m-d\TH:i:s+05:00', $timestamp);
        }

        return $dateStr;
    };

    array_walk_recursive($data, function(&$value) use ($replace) {
        if (is_string($value) && strpos($value, 'CURRENT') === 0) {
            $value = $replace($value);
        }
    });

    return $data;
}

echo json_encode(['error' => 'Unknown action']);