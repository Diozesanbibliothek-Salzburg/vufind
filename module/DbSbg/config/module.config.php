<?php
namespace DbSbg\Module\Configuration;

$config = [
  'controllers' => [
    'factories' => [
        'DbSbg\Controller\BrowseController' => 'VuFind\Controller\AbstractBaseWithConfigFactory',
        'DbSbg\Controller\LocalFileController' => 'VuFind\Controller\AbstractBaseFactory',
        'DbSbg\Controller\SearchController' => 'VuFind\Controller\AbstractBaseFactory'
    ],
    'aliases' => [
        'LocalFile' => 'DbSbg\Controller\LocalFileController',
        'localfile' => 'DbSbg\Controller\LocalFileController',
        'VuFind\Controller\BrowseController' => 'DbSbg\Controller\BrowseController',
        'VuFind\Controller\SearchController' => 'DbSbg\Controller\SearchController'
    ]
  ],
  'router' => [
    'routes' => [
        'localfile-open' => [
            'type' => 'Laminas\Router\Http\Literal',
            'options' => [
                'route' => '/LocalFile/Open',
                'defaults' => [
                    'controller' => 'LocalFile',
                    'action' => 'Open',
                ]
            ]
        ]
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

$staticRoutes = [
    'Search/Export'
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;
