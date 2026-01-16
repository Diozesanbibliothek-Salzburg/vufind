<?php

namespace DbSbgConsole\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'command' => [
                'factories' => [
                    'DbSbgConsole\Command\Export\ExportCsvCommand' => 'DbSbgConsole\Command\Export\ExportCsvCommandFactory',
                ],
                'aliases' => [
                    'export/export_csv' => 'DbSbgConsole\Command\Export\ExportCsvCommand',
                ]
            ],
        ],
    ],
];

return $config;
