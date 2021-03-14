<?php
/**
 * Custom functions to add MARC-driven functionality to already built in traits.
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
     * DbSbg: Get the whole title of the record. This is the main title, subtitle and
     *        title parts, all separated by colon.
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
     * @return void
     */
    public function getTitlePartName() {
        return $this->getFirstFieldValue('245', ['p']);
    }

    /**
     * DbSbg: Get number of part of a work
     *
     * @return void
     */
    public function getTitlePartNumber() {
      return $this->getFirstFieldValue('245', ['n']);
    }

    /**
     * DbSbg: Callback function for array_filter function in getWholeTitle method.
     * Default array_filter would not only filter out empty or null values, but also
     * the number "0" (as it evaluates to false). So if a title would just be "0" it
     * would not be displayed.
     *
     * @param   string $var The value of an array. In our case these are strings.
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
