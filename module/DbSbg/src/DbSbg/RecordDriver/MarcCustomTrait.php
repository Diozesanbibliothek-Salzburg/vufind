<?php
/**
 * Custom functions to add MARC-driven functionality to already built in traits.
 *
 * PHP version 7
 *
 * Copyright (C) Michael Birkner 2023.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace DbSbg\RecordDriver;

/**
 * Custom functions to add MARC-driven functionality to already built in traits.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait MarcCustomTrait
{

    /**
     * DbSbg: Get the whole title of the record. This is the main title,
     *        subtitle and title parts, all separated by colon.
     *
     * @return string The whole title with it's parts separated by colon
     */
    public function getTitle() {
      $titleMain = $this->getFirstFieldValue('245', ['a']);
      $titleSub = trim($this->getSubtitle());
      $titlePartNumber = trim($this->getTitlePartNumber());
      $titlePartName = trim($this->getTitlePartName());
      return implode(
          ' : ',
          array_filter(
              [$titleMain, $titleSub, $titlePartNumber, $titlePartName],
              array($this, 'filterCallback')
          )
      );
    }

    /**
     * DbSbg: Get name of part of a work
     *
     * @return string|null
     */
    public function getTitlePartName() {
        return $this->getFirstFieldValue('245', ['p']);
    }

    /**
     * DbSbg: Get number of part of a work
     *
     * @return string|null
     */
    public function getTitlePartNumber() {
        return $this->getFirstFieldValue('245', ['n']);
    }

    /**
     * DbSbg: Get some data for the data export functionality
     *
     * @return array
     */
    public function getExportData() {
        $exportData = [];

        $hols = $this->getMarcRecord()->getFields('HOL');
        if (!empty($hols)) {
            foreach ($hols as $hol) {
                $holPid = $this->getSubfieldData($hol->getSubfield('8'));

                $ind1 = $hol->getIndicator('1');

                if ($ind1 == '8') {
                    $locationCode =
                        $this->getSubfieldData($hol->getSubfield('c'));
                    if ($locationCode != 'UNASSIGNED') {
                        $libraryCode =
                            $this->getSubfieldData($hol->getSubfield('b'));
                        $callnumber =
                            $this->getSubfieldData($hol->getSubfield('h'));

                        // Get human readable library and location name
                        $library = null;
                        $location = null;
                        if (!empty($libraryCode) && !empty($locationCode)) {
                            $locationData = $this->ils
                                ->getLocationData($libraryCode, $locationCode);
                            if (!empty($locationData)) {
                                $library =
                                    $locationData['library_name'] ?? null;
                                $location =
                                    (!empty($locationData['external_name']))
                                        ? $locationData['external_name']
                                        : (
                                            (!empty($locationData['name']))
                                                ? $locationData['name']
                                                : null
                                        );
                            }
                        }

                        $exportData[$holPid]['library'] = $library;
                        $exportData[$holPid]['location'] = $location;
                        $exportData[$holPid]['callnumber'] = $callnumber;
                    }
                }

                if ($ind1 == '3') {
                    $holding = $this->getSubfieldData($hol->getSubfield('a'));
                    $gaps = $this->getSubfieldData($hol->getSubfield('z'));

                    $exportData[$holPid]['holding'] = $holding;
                    $exportData[$holPid]['gaps'] = $gaps;
                }
            }
        }

        return $exportData;

        // Get item data - keep for reference
        // TEST BEFORE USE IN PRODUCTION!
        /*
        $itms = $this->getMarcRecord()->getFields('ITM');
        if (!empty($itms)) {
            foreach ($itms as $itm) {
                $holPid = $this->getSubfieldData($itm->getSubfield('G'));
                $itmPid = $this->getSubfieldData($itm->getSubfield('a'));
                $libraryCode = $this->getSubfieldData($itm->getSubfield('o'));
                $locationCode = $this->getSubfieldData($itm->getSubfield('p'));
                $callnumber = $this->getSubfieldData($itm->getSubfield('t'));

                // Get human readable location name
                $location = null;
                if (!empty($libraryCode) && !empty($locationCode)) {
                    $locationData = $this->ils->getLocationData($libraryCode,
                        $locationCode);
                    if (!empty($locationData)) {
                        $location = $locationData['name'];
                    }
                }

                $exportData[$holPid]['items'][$itmPid] = [
                    'location' => $location,
                    'callnumber' => $callnumber,
                ];
            }
        }
        */
    }

    /**
     * Get data from a given single subfield as string or null if subfield
     * doesn't exist.
     *
     * @param \File_MARC_Subfield $subfield
     * 
     * @return string|null
     */
    protected function getSubfieldData($subfield) {
        if ($subfield) {
            return $subfield->getData();
        }
        return null;
    }

    /**
     * DbSbg: Callback function for array_filter function in getWholeTitle
     * method. Default array_filter would not only filter out empty or null
     * values, but also the number "0" (as it evaluates to false). So if a title
     * would just be "0" it would not be displayed.
     *
     * @param   string $var The value of an array (strings).
     * 
     * @return  boolean     False if $var is null or empty, true otherwise.
     */
    protected function filterCallback($var) {
      // Return false if $var is null or empty
      if ($var == null || trim($var) == '') {
          return false;
      }
      return true;
    }
}
