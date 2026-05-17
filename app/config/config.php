<?php

$GLOBALS['LEAD_FIELDS_MERGE_CONFIG'] = [
    'protected_fields' => [
        'NAME',
        'SECOND_NAME',
        'LAST_NAME',
        'STATUS_ID',
        'UF_CRM_1668339568358', // Дата и время приема
        'UF_CRM_1727328936',    // Диагноз
        'UF_CRM_1668352823231', // Возраст
        'UF_CRM_1635751283979', // Город
        'UF_CRM_1726815456024', // Табличный идентификатор
    ],

    'blocked_fields' => [
        'PHONE',
        'EMAIL',
        'TITLE'
    ],
];