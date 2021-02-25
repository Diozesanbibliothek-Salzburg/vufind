<?php
return [
    'extends' => 'bootstrap3',
    'less' => [
      'active' => true,
      'compiled.less'
    ],
    'favicon' => 'dbsbg-favicon.ico',
    'helpers' => [
      'factories' => [
        'VuFind\View\Helper\Root\RecordDataFormatter' => 'DbSbg\View\Helper\Root\RecordDataFormatterFactory'
      ]
    ]
];
