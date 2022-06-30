<?php
/**
 * Customized model for MARC records in Solr.
 *
 * PHP version 7
 *
 * Copyright (C) Michael Birkner 2021.
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
 * @package  RecordDrivers
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace DbSbg\RecordDriver;

/**
 * Customized model for MARC records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrMarc extends \VuFind\RecordDriver\SolrMarc
{
    
    /**
     * DbSbg: Use custom trait
     */
    use MarcCustomTrait;

    /**
     * DbSbg: Get all authors
     *
     * @return array All authors as array
     */
    public function getAllAuthors() {
      $a1 = isset($this->fields['author']) ? (array)$this->fields['author'] : [];
      $a2 = isset($this->fields['author2']) ? (array)$this->fields['author2'] : [];
      $c = isset($this->fields['author_corporate'])
        ? (array)$this->fields['author_corporate'] : [];
      
        return array_merge($a1, $c, $a2);
    }

    /**
     * DbSbg: Get AC number (Austrian Catalogue number) from Solr field acNo_txt.
     * Fallback to MarcXML if there is no such field. This is very specific to
     * Austrian libraries.
     *
     * @return string|array|null  The AC number as string or array or null
     */
    public function getAcNo() {
        $acNo = $this->fields['acNo_txt'] ?? null;
        if ($acNo == null || empty($acNo)) {
            $acNo = ($this->getMarcRecord()->getField('009'))
                ? $this->getMarcRecord()->getField('009')->getData()
                : null;
        }
        return $acNo;
    }

}