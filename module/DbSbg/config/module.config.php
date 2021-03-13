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
      'DbSbg\Config\AccountCapabilities' => 'VuFind\Config\AccountCapabilitiesFactory'
    ],
    'aliases' => [
      'VuFind\Config\AccountCapabilities' => 'DbSbg\Config\AccountCapabilities'
    ]
  ],
  'vufind' => [
    'plugin_managers' => [
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
              'VuFind\RecordDriver\IlsAwareDelegatorFactory'
          ]
      ]
      ],
    ]
  ]
];

return $config;
