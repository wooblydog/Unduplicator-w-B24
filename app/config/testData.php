<?php




// Тестовый набор для правила возраста
$ageRuleOldLeadYoung = (object)[  // старый, но < 24 ч
    'TITLE' => 'Молодой старый лид (<24ч)',
    'STATUS_ID' => 'UC_VPJXTG',
    'DATE_CREATE' => '2026-03-22T10:00:00+05:00',  // 2 часа назад (текущая дата ~2026-03-20 14:35 EET)
    'ID' => '1001',
    'UF_CRM_1668339568358' => '',
];

$ageRuleOldLeadOld = (object)[   // старый > 24 ч
    'TITLE' => 'Старый лид (>24ч)',
    'STATUS_ID' => '21',
    'DATE_CREATE' => '2026-03-18T12:00:00+05:00',  // 2 дня назад
    'ID' => '1002',
    'UF_CRM_1668339568358' => '',
];

$ageRuleNewLead = (object)[
    'TITLE' => 'Новый лид (триггер)',
    'STATUS_ID' => '1',
    'DATE_CREATE' => '2026-03-20T14:00:00+05:00',  // только что
    'ID' => '1003',
    'UF_CRM_1668339568358' => '',
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
    'DATE_CREATE' => '2026-03-20T14:30:00+05:00',
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
    'UF_CRM_1668339568358' => '2026-04-01T10:00:00+05:00',  // ещё не наступила
];

$pastApptNewLead = (object)[
    'TITLE' => 'Новый лид',
    'STATUS_ID' => '1',
    'DATE_CREATE' => '2026-03-20T14:30:00+05:00',
    'ID' => '3003',
    'UF_CRM_1668339568358' => '',
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

$conflictRulesTestSet = [
    "dup" => [

        // 2. Молодой (<24 ч) + дата в будущем → тоже должен выиграть по первому правилу
        (object) [
            'TITLE' => 'Молодой + запись в будущем',
            'STATUS_ID' => '21',
            'DATE_CREATE' => '2026-03-22T10:30:00+05:00',  // 3.5 часа назад
            'ID' => '4002',
            'UF_CRM_1668339568358' => '2026-05-01T14:00:00+05:00',  // ещё впереди
        ],

        // 3. Старый (>24 ч) + есть дата в прошлом → должен проиграть молодому


        // 4. Старый (>24 ч) + дата в будущем → должен проиграть молодому
        (object) [
            'TITLE' => 'Старый + запись в будущем',
            'STATUS_ID' => '21',
            'DATE_CREATE' => '2026-01-01T12:00:00+05:00',  // давно
            'ID' => '4004',
            'UF_CRM_1668339568358' => '2026-06-01T10:00:00+05:00',  // далеко впереди
        ],
    ],
    "new" => (object) [
        'TITLE' => 'Новый триггерный лид',
        'STATUS_ID' => '1',
        'DATE_CREATE' => '2026-03-20T13:45:00+05:00',  // только что
        'ID' => '4005',
        'UF_CRM_1668339568358' => '',  // без записи
    ],
];