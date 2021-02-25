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
