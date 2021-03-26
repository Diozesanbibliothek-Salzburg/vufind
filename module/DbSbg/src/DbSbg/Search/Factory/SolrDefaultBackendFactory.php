<?php
/**
 * Customized factory for the default SOLR backend.
 *
 * PHP version 7
 *
 * Copyright (C) Michael Birkner, 2021.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * @category VuFind
 * @package  Search
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:search_service Wiki
 */

namespace DbSbg\Search\Factory;

/**
 * Customized factory for the default SOLR backend.
 *
 * @category VuFind
 * @package  Search
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:search_service Wiki
 */
class SolrDefaultBackendFactory extends
  \VuFind\Search\Factory\SolrDefaultBackendFactory
{

    /**
     * Create the SOLR connector.
     * DbSbg: Returning the custom Solr connector. Using multiple id fields for
     * retrieving a sinlge record.
     *
     * @return \DbSbgSearch\Backend\Solr\Connector
     */
    protected function createConnector()
    {
        $config = $this->config->get($this->mainConfig);

        // DbSbg: Get configuration for ID fields to use for retrieving single
        // records
        $searchConfig = $this->config->get($this->searchConfig);
        $idFields = (
                isset($searchConfig->RecordIdFields->idFields)
                && !empty($searchConfig->RecordIdFields->idFields)
            )
            ? $searchConfig->RecordIdFields->idFields
            : 'id'; // DbSbg: Default is "id" Solr field
    	$this->uniqueKey = $idFields;

        $handlers = [
            'select' => [
                'fallback' => true,
                'defaults' => ['fl' => '*,score'],
                'appends'  => ['fq' => []],
            ],
            'terms' => [
                'functions' => ['terms'],
            ],
        ];

        foreach ($this->getHiddenFilters() as $filter) {
            array_push($handlers['select']['appends']['fq'], $filter);
        }

        // DbSbg: Use Connector from module DbSbgSearch.
        // ATTENTION: The DbSbgSearch module must be added to the Apache config file
        // setting "SetEnv" like this so that the class can be found:
        // SetEnv VUFIND_LOCAL_MODULES DbSbg,DbSbgSearch,[other modules]
        $connector = new \DbSbgSearch\Backend\Solr\Connector(
            $this->getSolrUrl(),
            new \VuFindSearch\Backend\Solr\HandlerMap($handlers),
            $this->uniqueKey
        );

        $connector->setTimeout(
            isset($config->Index->timeout) ? $config->Index->timeout : 30
        );

        if ($this->logger) {
            $connector->setLogger($this->logger);
        }
        if ($this->serviceLocator->has(\VuFindHttp\HttpService::class)) {
            $connector->setProxy(
                $this->serviceLocator->get(\VuFindHttp\HttpService::class)
            );
        }
        return $connector;
    }
}
