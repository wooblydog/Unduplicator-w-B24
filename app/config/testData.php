<?php

$now = new \DateTimeImmutable();


// Тестовый набор для правила возраста
$ageRuleOldLeadYoung = (object)[  // старый, но < 24 ч
    'TITLE' => 'Молодой старый лид (<24ч)',
    'STATUS_ID' => 'UC_VPJXTG',
    'DATE_CREATE' => $now->modify('-2 hours')->format('Y-m-d\TH:i:sP'),  // 2 часа назад (текущая дата ~2026-03-20 14:35 EET)
    'ID' => '1001',
    'UF_CRM_1668339568358' => '',
    'UF_CRM_1726815456024' => '08ef4300-dbf9-4da8-9756-8d699675b232'
];

$ageRuleOldLeadOld = (object)[   // старый > 24 ч
    'TITLE' => 'Старый лид (>24ч)',
    'STATUS_ID' => '21',
    'DATE_CREATE' => $now->modify('-2 days')->format('Y-m-d\TH:i:sP'),
    'ID' => '1002',
    'UF_CRM_1668339568358' => '',
    'UF_CRM_1726815456024' => '08ef4300-dbf9-4da8-9756-8d699675b232'
];

$ageRuleNewLead = (object)[
    'TITLE' => 'Новый лид (триггер)',
    'STATUS_ID' => '1',
    'DATE_CREATE' => $now->format('Y-m-d\TH:i:sP'),  // только что
    'ID' => '1003',
    'UF_CRM_1668339568358' => '',
    'UF_CRM_1726815456024' => '08ef4300-dbf9-4da8-9756-8d699675b232'
];


// Тестовый набор для правила наличия записи
$hasApptOldLeadWithDate = (object)[  // есть дата записи
    'TITLE' => 'Старый с датой записи',
    'STATUS_ID' => 'UC_VPJXTG',
    'DATE_CREATE' => '2026-01-01T12:00:00+05:00',
    'ID' => '2001',
    'UF_CRM_1668339568358' => '2026-04-15T10:00:00+05:00',  // есть запись
];

$hasApptOldLeadWithoutDate = (object)[  // нет даты записи
    'TITLE' => 'Старый без даты записи',
    'STATUS_ID' => '21',
    'DATE_CREATE' => '2026-02-01T12:00:00+05:00',
    'ID' => '2002',
    'UF_CRM_1668339568358' => '',
];

$hasApptNewLead = (object)[
    'TITLE' => 'Новый лид',
    'STATUS_ID' => '1',
    'DATE_CREATE' => $now->format('Y-m-d\TH:i:sP'),
    'ID' => '2003',
    'UF_CRM_1668339568358' => '',
];

// Тестовый набор для правила "запись в прошлом/настоящем"
$pastApptOldLeadPast = (object)[  // дата в прошлом
    'TITLE' => 'Старый, запись в прошлом',
    'STATUS_ID' => 'UC_VPJXTG',
    'DATE_CREATE' => '2026-01-01T12:00:00+05:00',
    'ID' => '3001',
    'UF_CRM_1668339568358' => '2026-02-01T10:00:00+05:00',  // уже прошла
];

$pastApptOldLeadFuture = (object)[  // дата в будущем
    'TITLE' => 'Старый, запись в будущем',
    'STATUS_ID' => '21',
    'DATE_CREATE' => '2026-02-01T12:00:00+05:00',
    'ID' => '3002',
    'UF_CRM_1668339568358' => $now->modify('+2 days')->format('Y-m-d\TH:i:sP'),
];

$pastApptNewLead = (object)[
    'TITLE' => 'Новый лид',
    'STATUS_ID' => '1',
    'DATE_CREATE' => $now,
    'ID' => '3003',
    'UF_CRM_1668339568358' => '',
];

$newRulesNewLead = (object)[
    'TITLE' => 'Новый лид',
    'STATUS_ID' => '1',
    'DATE_CREATE' => $now,
    'ID' => '3003',
    'UF_CRM_1668339568358' => '',
];

$newRulesOldYoungLeadWOAppt = (object)[
    'TITLE' => 'Старый молодой без записи лид',
    'STATUS_ID' => '1',
    'DATE_CREATE' => $now->modify('-2 hours')->format('Y-m-d\TH:i:sP'),
    'ID' => '3004',
    'UF_CRM_1668339568358' => '',
];

$newRulesOldYoungLeadWAppt = (object)[
    'TITLE' => 'Старый молодой с записью лид',
    'STATUS_ID' => '1',
    'DATE_CREATE' => $now->modify('-1 hours')->format('Y-m-d\TH:i:sP'),
    'ID' => '3005',
    'UF_CRM_1668339568358' => $now->modify('+2 days')->format('Y-m-d\TH:i:sP'),
];


$pastApptTestSet = [
    "dup" => [
        $pastApptOldLeadPast,
        $pastApptOldLeadFuture,
    ],
    "new" => $pastApptNewLead,
];

$ageRuleTestSet = [
    "dup" => [
        $ageRuleOldLeadYoung,
        $ageRuleOldLeadOld,
    ],
    "new" => $ageRuleNewLead,
];

$hasApptTestSet = [
    "dup" => [
        $hasApptOldLeadWithDate,
        $hasApptOldLeadWithoutDate,
    ],
    "new" => $hasApptNewLead,
];

$newRulesTestSet = [
    "dup" => [
        $newRulesOldYoungLeadWOAppt,
        $newRulesOldYoungLeadWAppt
    ],
    "new" => $newRulesNewLead,
];

$conflictRulesTestSet = [
    "dup" => [

        // 2. Молодой (<24 ч) + дата в будущем → тоже должен выиграть по первому правилу
        (object) [
            'TITLE' => 'Молодой + запись в будущем',
            'STATUS_ID' => '21',
            'DATE_CREATE' => $now->modify('-2 hours')->format('Y-m-d\TH:i:sP'),
            'ID' => '4002',
            'UF_CRM_1668339568358' => $now->modify('+2 days')->format('Y-m-d\TH:i:sP'),
            'UF_CRM_1726815456024' => '08ef4300-dbf9-4da8-9756-8d699675b232',
        ],

        (object) [
            'TITLE' => 'Старый + запись в будущем',
            'STATUS_ID' => '21',
            'DATE_CREATE' => '2026-01-01T12:00:00+05:00',  // давно
            'ID' => '4004',
            'UF_CRM_1668339568358' => $now->modify("+3 days")->format('Y-m-d\TH:i:sP'),  // далеко впереди
        ],
    ],
    "new" => (object) [
        'TITLE' => 'Новый триггерный лид',
        'STATUS_ID' => '1',
        'DATE_CREATE' => $now->format('Y-m-d\TH:i:sP'),  // только что
        'ID' => '4005',
        'UF_CRM_1668339568358' => '',  // без записи
    ],
];