<?php
/**
 * Customized Hold Logic Class
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
 * @package  ILS_Logic
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace DbSbg\ILS\Logic;

/**
 * Customized Hold Logic Class
 *
 * @category VuFind
 * @package  ILS_Logic
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Holds extends \VuFind\ILS\Logic\Holds
{
    
    /**
     * Support method to rearrange the holdings array for displaying convenience.
     * DbSbg: Add some more information from the ILS
     *
     * @param array $holdings An associative array of location => item array
     *
     * @return array          An associative array keyed by location with each
     * entry being an array with 'notes', 'summary' and 'items' keys.  The 'notes'
     * and 'summary' arrays are note/summary information collected from within the
     * items.
     */
    protected function formatHoldings($holdings)
    {
        $retVal = [];

        $textFieldNames = $this->catalog->getHoldingsTextFieldNames();

        foreach ($holdings as $groupKey => $items) {
            $retVal[$groupKey] = [
                'items' => $items,
                'location' => $items[0]['location'] ?? '',
                'locationhref' => $items[0]['locationhref'] ?? '',
                // DbSbg: Added library
                'library' => $items[0]['library'] ?? ''
            ];
            // Copy all text fields from the item to the holdings level
            foreach ($items as $item) {
                foreach ($textFieldNames as $fieldName) {
                    if (in_array($fieldName, ['notes', 'holdings_notes'])) {
                        if (empty($item[$fieldName])) {
                            // begin aliasing
                            if ($fieldName == 'notes'
                                && !empty($item['holdings_notes'])
                            ) {
                                // using notes as alias for holdings_notes
                                $item[$fieldName] = $item['holdings_notes'];
                            } elseif ($fieldName == 'holdings_notes'
                                && !empty($item['notes'])
                            ) {
                                // using holdings_notes as alias for notes
                                $item[$fieldName] = $item['notes'];
                            }
                        }
                    }

                    if (!empty($item[$fieldName])) {
                        $targetRef = & $retVal[$groupKey]['textfields'][$fieldName];
                        foreach ((array)$item[$fieldName] as $field) {
                            if (empty($targetRef) || !in_array($field, $targetRef)) {
                                $targetRef[] = $field;
                            }
                        }
                    }
                }

                // Handle purchase history
                if (!empty($item['purchase_history'])) {
                    $targetRef = & $retVal[$groupKey]['purchase_history'];
                    foreach ((array)$item['purchase_history'] as $field) {
                        if (empty($targetRef) || !in_array($field, $targetRef)) {
                            $targetRef[] = $field;
                        }
                    }
                }
            }
        }

        return $retVal;
    }

}
