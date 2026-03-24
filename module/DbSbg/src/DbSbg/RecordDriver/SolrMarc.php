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
            $acNo = ($this->getMarcReader()->getField('009'))
                ? $this->getMarcReader()->getField('009')
                : null;
        }
        return $acNo;
    }

    /**
     * RXL: Get parent titles from field 773
     *
     * @return array Array with data from parent titles
     */
    public function get773ParentTitles($ind1FilterValue = null) {
        // The result variable
        $result = [];

        // Get record with marc reader
        $rec = $this->getMarcReader();

        // Get the parent link fields
        // TODO: RXL: Should we search for the parent AC no. to be sure that the
        // record exists in our data? We could do that in the controller for the
        // detail view: module/VuFind/src/VuFind/Controller/AbstractRecord.php
        // and use $this->driver->setExtraDetail() and reuse that in the
        // SolrMarc record driver.
        $fs773 = $rec->getFields('773');

        // Get the data we need from the parent link fields and add them to the
        // result array
        foreach ($fs773 as $f773) {
            // Get the indicator 1 value of the current 773 field
            $ind1 = $f773['i1'];

            // Check if the indicator 1 value matches the value of our config.
            // If not, iterate over the next 773 and check again. If the config
            // is not set (= null), go on with the code below.
            if ($ind1FilterValue != null && $ind1 != $ind1FilterValue) {
                continue;
            }

            // Get the linking ID
            $f773w = $rec->getSubfield($f773, 'w');

            // Get the title
            $f773t = $rec->getSubfield($f773, 't');
            $title = (!empty($f773t)) ? $f773t : null;

            // Check if we have a title in field 773t. If not, search the record
            // in our Solr index by 773w to get the title
            if (!$title) {
                $parentId = str_replace('(AT-OBV)', '', $f773w);
                $savef773w = addcslashes($parentId, '"');

                // Create the query
                $query = new \VuFindSearch\Query\Query(
                    'id:"' . $savef773w . '" || '
                    .'acNo_txt:"' . $savef773w . '"'
                );

                // Create search params. Disable highlighting for efficiency.
                // Return only fields that we need.
                $params = new \VuFindSearch\ParamBag([
                    'hl' => ['false'],
                    'fl' => ['id', 'acNo_txt', 'title', 'fullrecord']
                ]);
                
                // Create the query command
                $command = new \VuFindSearch\Command\SearchCommand(
                    $this->sourceIdentifier, $query, 0, 200, $params
                );

                // Run the query and get the records
                $records = $this->searchService->invoke($command)->getResult()
                    ->getRecords();

                // Check if we have at least one record
                if ($records && count($records) > 0) {
                    // Get the title of the first record
                    $record = $records[0];
                    $title = $this->stripNonSortingChars($record->getTitle());
                } else {
                    $title = $this->translate('No title');
                }
            }
            
            // All display fields
            $f773DisplayArr = array_filter([
                $title,
                $rec->getSubfield($f773, 'a'),
                $rec->getSubfield($f773, 'b'),
                $rec->getSubfield($f773, 'd'),
                $rec->getSubfield($f773, 'g'),
                $rec->getSubfield($f773, 'h'),
                $rec->getSubfield($f773, 'k'),
                $rec->getSubfield($f773, 'l'),
                $rec->getSubfield($f773, 'm'),
                $rec->getSubfield($f773, 'n'),
                $rec->getSubfield($f773, 'o'),
                $rec->getSubfield($f773, 'p'),
                $rec->getSubfield($f773, 'q'),
                $rec->getSubfield($f773, 'r'),
                $rec->getSubfield($f773, 's'),
            ]);

            // Join the display fields to a string, separated by a colon
            $f773display = join('; ', $f773DisplayArr);

            // Remove the prefex from the ID
            $parentId = str_replace('(AT-OBV)', '', $f773w);

            // Create the result array
            $result[] = [
                'title' => (!empty($f773display) ? $f773display :
                    $this->translate('No title')),
                'acNo' => (str_starts_with($parentId, 'AC') || str_starts_with($parentId, '99'))
                    ? $parentId
                    : null
            ];
        }

        // Return the result
        return $result;
    }

    /**
     * RXL: Get linking information from field 787
     *
     * @return array Array with data from linked titles
     */
    public function get787Link() {
        // The result variable
        $result = [];

        // Get record with marc reader
        $rec = $this->getMarcReader();

        // Get the link fields
        // TODO: RXL: Should we search for the parent AC no. to be sure that the
        // record exists in our data? We could do that in the controller for the
        // detail view: module/VuFind/src/VuFind/Controller/AbstractRecord.php
        // and use $this->driver->setExtraDetail() and reuse that in the
        // SolrMarc record driver.
        $fs787 = $rec->getFields('787');

        // Get the data we need from the parent link fields and add them to the
        // result array
        foreach ($fs787 as $f787) {
            // Get the linking ID
            $f787w = $rec->getSubfield($f787, 'w');

            // Get the linking information
            $f787i = $rec->getSubfield($f787, 'i');

            // Get the title
            $f787t = $rec->getSubfield($f787, 't');
            $title = (!empty($f787t))
                ? $this->stripNonSortingChars($f787t)
                : $this->translate('No title');

            // All display fields
            $f787DisplayArr = array_filter([
                $title,
                $rec->getSubfield($f787, 'a'),
                $rec->getSubfield($f787, 'b'),
                $rec->getSubfield($f787, 'c'),
                $rec->getSubfield($f787, 'd'),
                $rec->getSubfield($f787, 'g'),
                $rec->getSubfield($f787, 'h'),
                $rec->getSubfield($f787, 'k'),
                $rec->getSubfield($f787, 'l'),
                $rec->getSubfield($f787, 'm'),
                $rec->getSubfield($f787, 'n'),
                $rec->getSubfield($f787, 'o'),
                $rec->getSubfield($f787, 'r'),
                $rec->getSubfield($f787, 's'),
            ]);

            // Join the display fields to a string, separated by a colon
            $f787display =  (!empty($f787i)) ? $f787i.': '.join('; ', $f787DisplayArr) : join('; ', $f787DisplayArr);

            // Remove the prefex from the ID
            $parentId = str_replace('(AT-OBV)', '', $f787w);

            // Create the result array
            $result[] = [
                'title' => (!empty($f787display) ? $f787display :
                    $this->translate('No title')),
                'acNo' => (str_starts_with($parentId, 'AC') || str_starts_with($parentId, '99'))
                    ? $parentId
                    : null
            ];
        }

        // Return the result
        return $result;
    }

    /**
     * RXL: Get parent titles from field 830
     *
     * @return array Array with data from parent titles
     */
    public function get830ParentTitles() {
        // The result variable
        $result = [];

        // Get record with marc reader
        $rec = $this->getMarcReader();

        // Get all fields 490
        $fs490 = $rec->getFields('490');
        // Get subfields v (volume) and a (title) from all fields 490 and add
        // them to an array indexed by the volume
        $fs490data = [];
        foreach ($fs490 as $f490) {
            $fs490data[$rec->getSubfield($f490, 'v')] =
                $rec->getSubfield($f490, 'a');
        }

        // Get the title from the first field 490. This is a fallback if we
        // can't get the title by volume number.
        $f490 = $rec->getField('490');
        $f490a = $rec->getSubfield($f490, 'a');

        // Get the parent link fields
        $fs830 = $rec->getFields('830');

        foreach ($fs830 as $f830) {
            $f830w = $rec->getSubfield($f830, 'w');
            $f830a = $rec->getSubfield($f830, 'a');
            $f830t = $rec->getSubfield($f830, 't');
            $f830v = $rec->getSubfield($f830, 'v');

            // Get the parent title
            $title = null;
            if ($fs490data[$f830v] ?? false) {
                $title = $fs490data[$f830v];
            } else if (!empty($f490a)) {
                $title = $f490a;
            } else if (!empty($f830t)) {
                $title = $f830t;
            } else if (!empty($f830a)) {
                $title = $f830a;
            } else {
                $title = $this->translate('No title');
            }

            // Remove non sorting characters if a title exists
            if ($title) {
                $title = $this->stripNonSortingChars($title);
            }
            
            // Get all display fields as an array
            $f830DisplayArr = array_filter([
                $title,
                $rec->getSubfield($f830, 'd'),
                $rec->getSubfield($f830, 'f'),
                $rec->getSubfield($f830, 'g'),
                $rec->getSubfield($f830, 'h'),
                $rec->getSubfield($f830, 'k'),
                $rec->getSubfield($f830, 'l'),
                $rec->getSubfield($f830, 'm'),
                $rec->getSubfield($f830, 'n'),
                $rec->getSubfield($f830, 'o'),
                $rec->getSubfield($f830, 'p'),
                $rec->getSubfield($f830, 'r'),
                $rec->getSubfield($f830, 's'),
                $rec->getSubfield($f830, 'v'),
            ]);
            
            // Join the display fields to a string, separated by a colon
            $f830display = join('; ', $f830DisplayArr);

            // Remove the prefix from the ID
            $parentId = str_replace('(AT-OBV)', '', $f830w);

            // Create the result array
            $result[] = [
                'title' => $f830display,
                'acNo' => (str_starts_with($parentId, 'AC') || str_starts_with($parentId, '99'))
                    ? $parentId
                    : null
            ];
        }

        // Return the result
        return $result;
    }

    /**
     * RXL: Get child records
     *
     * @return array
     */
    public function getChilds() {
        // Create result variable
        $result = [];

        // Get AC number of current record
        $acNo = $this->fields['acNo_txt'] ?? null;

        // Get MMS-ID of current record
        $mmsId = $this->fields['id'] ?? null;

        if ($acNo || $mmsId) {
            // Create a query for getting child records
            $safeAcNo = ($acNo) ? addcslashes($acNo, '"') : "";
            $safeMmsId = ($mmsId) ? addcslashes($mmsId, '"') : "";
            // Info: The field is named "parentAcNo_txt_mv", but we query it for
            // AC-No and MMS-ID as both types of ID are indexed to it.
            $query = new \VuFindSearch\Query\Query(
                'parentAcNo_txt_mv:"' . $safeAcNo . '" '
                .'|| parentAcNo_txt_mv:"' . $safeMmsId . '"'
            );

            // Disable highlighting for efficiency. Return only fields that we need.
            $params = new \VuFindSearch\ParamBag([
                'hl' => ['false'],
                'fl' => ['id', 'acNo_txt', 'title', 'fullrecord']
            ]);

            // Create the query command
            // TODO: RXL: We probably should use a paging mechanism. Currently
            // the results are limited to 200
            $command = new \VuFindSearch\Command\SearchCommand(
                $this->sourceIdentifier, $query, 0, 200, $params);
            
            // Run the query and get the child records
            $childRecords = $this->searchService->invoke($command)->getResult()
                ->getRecords();

            // Get some data from the child records
            foreach ($childRecords as $childRecord) {
                // Get a MarcReader from the MarcXML in the fullrecord field
                $marc = new \VuFind\Marc\MarcReader($childRecord
                    ->fields['fullrecord']);
                
                // Get all possible sort numbers
                $f830 = $marc->getField('830');
                $f773 = $marc->getField('773');
                $f787 = $marc->getField('787');
                $f245 = $marc->getField('245');
                $f773q = $marc->getSubfield($f773, 'q');
                $f787i = $marc->getSubfield($f787, 'i');
                $f830v = $marc->getSubfield($f830, 'v');
                $f245n = $marc->getSubfield($f245, 'n');
                $sortNo = 0;
                $displaySortNo = null;
                if (!empty($f773q)) {
                    $sortNo = $f773q;
                    $displaySortNo = $f773q;
                } else if (!empty($f787i)) {
                    $sortNo = $f787i;
                    $displaySortNo = $f787i;
                } else if (!empty($f830v)) {
                    $sortNo = $f830v;
                    $displaySortNo = $f830v;
                } else if (!empty($f245n)) {
                    $sortNo = $f245n;
                }
                $sortNo = str_pad($sortNo, 10, '0', STR_PAD_LEFT);

                // Get the title
                $title = $this->stripNonSortingChars($childRecord->getTitle());

                // Get other display fields
                $f250 = $marc->getField('250');
                $f250a = $marc->getSubfield($f250, 'a');
                $f264 = $marc->getField('264');
                $f264a = $marc->getSubfield($f264, 'c');

                // All display fields
                $childDisplayArr = array_filter([
                    $title,
                    $f245n,
                    // Use sort number only if it doesn't already exist in the
                    // title
                    //((empty($f245n)) ? $displaySortNo : null),
                    $f250a,
                    $f264a,
                ]);

                // Join the display fields to a string, separated by a colon
                $childDisplay =  join('; ', $childDisplayArr);

                // Create the result array with the given data
                $result[] = [
                    'id' => $childRecord->fields['id'],
                    'acNo' => $childRecord->fields['acNo_txt'] ?? null,
                    'title' => $childDisplay,
                    'sortNo' => $sortNo
                ];
            }

            // Sort the result array by sort numbers
            $sortNos = array_column($result, 'sortNo');
            array_multisort($sortNos, SORT_ASC, SORT_NUMERIC, $result);
        }

        // Return the result
        return $result;
    }

    /**
     * Check if the current record has child records in the Solr index.
     *
     * @return boolean true if child records exist, false otherwise
     */
    public function hasChilds() {
        // Get AC number of current record
        $acNo = $this->fields['acNo_txt'] ?? null;

        // Get MMS-ID of current record
        $mmsId = $this->fields['id'] ?? null;

        if ($acNo || $mmsId) {
            // Create a query for getting child records
            $safeAcNo = ($acNo) ? addcslashes($acNo, '"') : "";
            $safeMmsId = ($mmsId) ? addcslashes($mmsId, '"') : "";
            // Info: The field is named "parentAcNo_txt_mv", but we query it for
            // AC-No and MMS-ID as both types of ID are indexed to it.
            $query = new \VuFindSearch\Query\Query(
                'parentAcNo_txt_mv:"' . $safeAcNo . '" '
                .'|| parentAcNo_txt_mv:"' . $safeMmsId . '"'
            );

            // Disable highlighting, spellcheck and fieldlist for efficiency
            $params = new \VuFindSearch\ParamBag([
                'hl' => ['false'],
                'spellcheck' => ['false'],
                'fl' => [],
            ]);

            // Create the search command
            $command = new \VuFindSearch\Command\SearchCommand(
                $this->sourceIdentifier, $query, 0, 0, $params);

            // Get the Solr result
            $solrResult = $this->searchService->invoke($command)->getResult();

            // Get the record count
            $total = $solrResult->getTotal();

            // If we have any child records, return true, otherwise false
            return ($total > 0) ? true : false;
        }
    }

    /**
     * RXL: Remove non-sorting characters "<<" and ">>" from the provided data.
     * The data can be a string or an array.
     *
     * @param   String|array $data That data from which the non-sorting
     * characters should be removed.
     * 
     * @return  String|array String or array without non-sorting-characters
     */
    protected function stripNonSortingChars($data) {
        if (is_string($data)) {
            return preg_replace('/<<|>>/', '', $data);
        }

        if (is_array($data)) {
            return array_map(
                function($value) {
                    return preg_replace('/<<|>>/', '', $value);
                },
                $data
            );
        }

        // Fallback
        return $data;
    }

}