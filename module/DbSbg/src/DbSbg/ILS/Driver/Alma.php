<?php
/**
 * Customized Alma ILS Driver
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
 * @package  ILS_Drivers
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace DbSbg\ILS\Driver;

use Laminas\Http\Headers;
use SimpleXMLElement;
use VuFind\Exception\ILS as ILSException;

/**
 * Customized Alma ILS Driver
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class Alma extends \VuFind\ILS\Driver\Alma
{

    /**
     * Get Holding
     *
     * This is responsible for retrieving the holding information of a certain
     * record.
     * 
     * DbSbg: Get additional information from Alma
     *
     * @param string $id      The record id to retrieve the holdings for
     * @param array  $patron  Patron data
     * @param array  $options Additional options
     *
     * @return array On success an array with the key "total" containing the total
     * number of items for the given bib id, and the key "holdings" containing an
     * array of holding information each one with these keys: id, source,
     * availability, status, location, reserve, callnumber, duedate, returnDate,
     * number, barcode, item_notes, item_id, holding_id, addLink, description
     */
    public function getHolding($id, $patron = null, array $options = [])
    {
        // DbSbg: Get item policies that should be hidden
        $itemPoliciesToHide = $this->config['Holdings']['itemPolicyToHide'] ?? null;

        // Prepare result array with default values. If no API result can be received
        // these will be returned.
        $results['total'] = 0;
        $results['holdings'] = [];

        // Correct copy count in case of paging
        $copyCount = $options['offset'] ?? 0;

        // Paging parameters for paginated API call. The "limit" tells the API how
        // many items the call should return at once (e. g. 10). The "offset" defines
        // the range (e. g. get items 30 to 40). With these parameters we are able to
        // use a paginator for paging through many items.
        $apiPagingParams = '';
        if ($options['itemLimit'] ?? null) {
            $apiPagingParams = 'limit=' . urlencode($options['itemLimit'])
                . '&offset=' . urlencode($options['offset'] ?? 0);
        }

        // The path for the API call. We call "ALL" available items, but not at once
        // as a pagination mechanism is used. If paging params are not set for some
        // reason, the first 10 items are called which is the default API behaviour.
        $itemsPath = '/bibs/' . rawurlencode($id) . '/holdings/ALL/items?'
            . $apiPagingParams
            . '&order_by=library,location,enum_a,enum_b&direction=desc'
            . '&expand=due_date';

        if ($items = $this->makeRequest($itemsPath)) {
            // Get the total number of items returned from the API call and set it to
            // a class variable. It is then used in VuFind\RecordTab\HoldingsILS for
            // the items paginator.
            $results['total'] = (int)$items->attributes()->total_record_count;

            foreach ($items->item as $item) {
                $number = ++$copyCount;
                $holdingId = (string)$item->holding_data->holding_id;
                $itemId = (string)$item->item_data->pid;
                $barcode = (string)$item->item_data->barcode;
                $status = (string)$item->item_data->base_status[0]
                    ->attributes()['desc'];
                $duedate = $item->item_data->due_date
                    ? $this->parseDate((string)$item->item_data->due_date) : null;
                if ($duedate && 'Item not in place' === $status) {
                    $status = 'Checked Out';
                }

                $itemNotes = !empty($item->item_data->public_note)
                    ? [(string)$item->item_data->public_note] : null;

                $processType = (string)($item->item_data->process_type ?? '');
                if ($processType && 'LOAN' !== $processType) {
                    $status = $this->getTranslatableStatusString(
                        $item->item_data->process_type
                    );
                }

                $description = null;
                if (!empty($item->item_data->description)) {
                    $description = (string)$item->item_data->description;
                }

                // DbSbg: Get item policy code and check if it should be hidden
                $itemPolicyCode = (string)$item->item_data->policy ?: null;
                $itemPolicyHide = false;
                if ($itemPoliciesToHide && $itemPolicyCode) {
                    $itemPolicyHide = in_array($itemPolicyCode, $itemPoliciesToHide);
                }

                $results['holdings'][] = [
                    'id' => $id,
                    'source' => 'Solr',
                    'availability' => $this->getAvailabilityFromItem($item),
                    'status' => $status,
                    'location' => $this->getItemLocation($item),
                    'reserve' => 'N',   // TODO: support reserve status
                    'callnumber' => $this->getTranslatableString(
                        $item->holding_data->call_number
                    ),
                    'duedate' => $duedate,
                    'returnDate' => false, // TODO: support recent returns
                    'number' => $number,
                    'barcode' => empty($barcode) ? null : $barcode,
                    'item_notes' => $itemNotes ?? null,
                    'item_id' => $itemId,
                    'holding_id' => $holdingId,
                    'holdtype' => 'auto',
                    'addLink' => $patron ? 'check' : false,
                    // For Alma title-level hold requests
                    'description' => $description ?? null,
                    // DbSbg: Add some more information from Alma
                    'item_policy_code' => $itemPolicyCode,
                    'item_policy_desc' => (string)$item->item_data->policy
                        ->attributes()['desc'] ?: null,
                    'item_policy_hide' => $itemPolicyHide,
                    'library' => $this->getTranslatableString($item->item_data
                        ->library)
                ];
            }
        }

        // Fetch also digital and/or electronic inventory if configured
        $types = $this->getInventoryTypes();
        if (in_array('d_avail', $types) || in_array('e_avail', $types)) {
            // No need for physical items
            $key = array_search('p_avail', $types);
            if (false !== $key) {
                unset($types[$key]);
            }
            $statuses = $this->getStatusesForInventoryTypes((array)$id, $types);
            $electronic = [];
            foreach ($statuses as $record) {
                foreach ($record as $status) {
                    $electronic[] = $status;
                }
            }
            $results['electronic_holdings'] = $electronic;
        }

        // DbSbg: If we have no items in the holdings, check if there are summarized
        // holdings (which are very specific to austrian libraries)
        if (empty($results['holdings'])) {
            $summarizedHoldings = $this->getSummarizedHoldings($id);
            $results['summarizedHoldings'] = $summarizedHoldings;
        }
        
        return $results;
    }

    /**
     * Get summarized holdings and add it to the holdings array that is returned from
     * the default Alma ILS driver. This is quite specific to Austrian libraries.
     * See below for information on used MARC fields
     * 
     * TODO:
     *  - Less nesting in code below.
     *  - Fields 852b and 852c are not repeated in Austrian libraries, but we should
     *    consider the fact that these fields are repeatable according to the
     *    official Marc21 documentation.
     *  
     * Marc holding field 852
     * See https://wiki.obvsg.at/Katalogisierungshandbuch/KategorienuebersichtB852FE
     * - Library Code:      tag=852 ind1=8 ind2=1|# subfield=b
     * - Location:          tag=852 ind1=8 ind2=1|# subfield=c
     * - Call No.:          tag=852 ind1=8 ind2=1|# subfield=h
     * - Note on call no.:  tag=852 ind1=8 ind2=1|# subfield=z
     * 
     * Marc holding field 866
     * See https://wiki.obvsg.at/Katalogisierungshandbuch/KategorienuebersichtB866FE
     * - Summarized holdings:   tag=866 ind1=3 ind2=0 subfield=a
     * - Gaps:                  tag=866 ind1=3 ind2=0 subfield=z
     * - Prefix text for summarized holdings:
     *                          tag=866 ind1=# ind2=0 subfield=a
     * - Note for summarized holdings:
     *                          tag=866 ind1=# ind2=0 subfield=z
     * 
     * @param string $id
     * 
     * @return array
     */
    public function getSummarizedHoldings($id)
    {
        // Initialize variables
        $summarizedHoldings = [];

        // Path to Alma holdings API
        $holdingsPath = '/bibs/' . urlencode($id) . '/holdings';

        // Get holdings from Alma API
        if ($almaApiResult = $this->makeRequest($holdingsPath)) {
            // Get the holding details from the API result
            $almaHols = $almaApiResult->holding ?? null;

            // Check if the holding details object is emtpy
            if (!empty($almaHols)) {
                foreach ($almaHols as $almaHol) {

                    // Get the holding IDs
                    $holId = (string)$almaHol->holding_id;

                    // Get the single MARC holding record based on the holding ID
                    if ($marcHol = $this->makeRequest($holdingsPath.'/'.$holId)) {
                        if ($marcHol != null && !empty($marcHol)) {
                            
                            if (isset($marcHol->record)) {
                                // Get the holdings record from the API as a
                                // File_MARCXML object for better processing below.
                                $marc = new \File_MARCXML(
                                    $marcHol->record->asXML(),
                                    \File_MARCXML::SOURCE_STRING
                                );

                                // Read the Marc holdings record
                                if ($marcRec = $marc->next()) {
                                    
                                    // Get values only if we have an 866 field.
                                    if ($fs866 = $marcRec->getFields('866')) {
                                        $libCodes = null;
                                        $locCodes = null;
                                        $callNo = null;
                                        $callNoNote = null;
                                        $sumHoldings = null;
                                        $gaps = null;
                                        $sumHoldingsPrefix = null;
                                        $sumHoldingsNote = null;
                                        
                                        // Process 852 field(s)
                                        if ($fs852 = $marcRec->getFields('852')) {
                                            // Iterate over all 852 fields available
                                            foreach ($fs852 as $f852) {
                                                // Check if ind1 is '8'. We only
                                                // process these fields
                                                if ($f852->getIndicator('1')=='8') {
                                                    // Add data from subfields to
                                                    // arrays as their key for having
                                                    // unique values. We just use
                                                    // 'true' as array values.
                                                    foreach ($f852->getSubfields('b')
                                                        as $f852b) {
                                                        $libCodes[$f852b
                                                            ->getData()] = true;
                                                    }
                                                    foreach ($f852->getSubfields('c')
                                                        as $f852c) {
                                                        $locCodes[$f852c
                                                            ->getData()] = true;
                                                    }
                                                    foreach ($f852->getSubfields('h')
                                                        as $f852h) {
                                                        $callNo[$f852h
                                                            ->getData()] = true;
                                                    }
                                                    foreach ($f852->getSubfields('z')
                                                        as $f852z) {
                                                        $callNoNote[$f852z
                                                            ->getData()] = true;
                                                    }
                                                }
                                            }
                                        }

                                        // Iterate over all 866 fields available
                                        foreach ($fs866 as $f866) {
                                            // Check if ind1 is '3'
                                            if ($f866->getIndicator('1') == '3') {
                                                foreach ($f866->getSubfields('a')
                                                    as $f86630a) {
                                                    $sumHoldings[$f86630a
                                                        ->getData()] = true;
                                                }
                                                foreach ($f866->getSubfields('z')
                                                    as $f86630z) {
                                                    $gaps[$f86630z
                                                        ->getData()] = true;
                                                }
                                            }
                                            // Check if ind1 is 'blank'
                                            if ($f866->getIndicator('1') == ' ') {
                                                foreach ($f866->getSubfields('a')
                                                    as $f866_0a) {
                                                    $sumHoldingsPrefix[$f866_0a
                                                        ->getData()] = true;
                                                }
                                                foreach ($f866->getSubfields('z')
                                                    as $f866_0z) {
                                                    $sumHoldingsNote[$f866_0z
                                                        ->getData()] = true;
                                                }
                                            }
                                        }

                                        $summarizedHoldings[] = [
                                            'library' => ($libCodes) ? implode(', ', array_keys($libCodes)) : null,
                                            'location' => ($locCodes) ? implode(', ', array_keys($locCodes)) : 'UNASSIGNED',
                                            'callnumber' => ($callNo) ? implode(', ', array_keys($callNo)) : null,
                                            'callnumber_notes' => ($callNoNote) ? array_keys($callNoNote) : null,
                                            'holdings_available' => ($sumHoldings) ? implode(', ', array_keys($sumHoldings)) : null,
                                            'gaps' => ($gaps) ? array_keys($gaps) : null,
                                            'holdings_prefix' => ($sumHoldingsPrefix) ? implode(', ', array_keys($sumHoldingsPrefix)) : null,
                                            'holdings_notes' => ($sumHoldingsNote) ? array_keys($sumHoldingsNote) : null,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return empty(array_filter($summarizedHoldings)) ? [] : $summarizedHoldings;
    }

}
