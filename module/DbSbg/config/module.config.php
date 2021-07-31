<?php
namespace DbSbg\Module\Configuration;

$config = [
  'controllers' => [
    'factories' => [
        'DbSbg\Controller\BrowseController' => 'VuFind\Controller\AbstractBaseWithConfigFactory'
    ],
    'aliases' => [
        'VuFind\Controller\BrowseController' => 'DbSbg\Controller\BrowseController'
    ]
  ],
  'service_manager' => [
    'factories' => [
      'DbSbg\Config\AccountCapabilities' => 'VuFind\Config\AccountCapabilitiesFactory',
      'DbSbg\ILS\Logic\Holds' => 'VuFind\ILS\Logic\LogicFactory',
    ],
    'aliases' => [
      'VuFind\Config\AccountCapabilities' => 'DbSbg\Config\AccountCapabilities',
      'VuFind\ILS\HoldLogic' => 'DbSbg\ILS\Logic\Holds',
    ]
  ],
  'vufind' => [
    'plugin_managers' => [
        'ils_driver' => [
            'factories' => [
                'DbSbg\ILS\Driver\Alma' => 'VuFind\ILS\Driver\AlmaFactory'
            ],
            'aliases' => [
                'VuFind\ILS\Driver\Alma' => 'DbSbg\ILS\Driver\Alma'
            ]
        ],
        'recorddriver' => [
            'factories' => [
                'DbSbg\RecordDriver\SolrMarc' => 'VuFind\RecordDriver\SolrDefaultFactory'
            ],
            'aliases' => [
                'VuFind\RecordDriver\SolrMarc' => 'DbSbg\RecordDriver\SolrMarc',
                'solrmarc' => 'DbSbg\RecordDriver\SolrMarc'
            ],
            'delegators' => [
                'DbSbg\RecordDriver\SolrMarc' => [
                    'DbSbg\RecordDriver\IlsAwareDelegatorFactory'
                ]
            ]
        ],
        'search_backend' => [
            'factories' => [
                'Solr' => 'DbSbg\Search\Factory\SolrDefaultBackendFactory'
            ]
        ]
    ]
  ]
];

return $config;
