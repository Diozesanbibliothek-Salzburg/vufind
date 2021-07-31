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
                    $number = (string)$item->item_data->description;
                    $description = (string)$item->item_data->description;
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
                    'barcode' => empty($barcode) ? 'n/a' : $barcode,
                    'item_notes' => $itemNotes ?? null,
                    'item_id' => $itemId,
                    'holding_id' => $holdingId,
                    'holdtype' => 'auto',
                    'addLink' => $patron ? 'check' : false,
                    // For Alma title-level hold requests
                    'description' => $description ?? null,
                    // DbSbg: Add some more information from Alma
                    'item_policy_code' => (string)$item->item_data->policy ?: null,
                    'item_policy_desc' => (string)$item->item_data->policy
                        ->attributes()['desc'] ?: null,
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

        return $results;
    }

}
