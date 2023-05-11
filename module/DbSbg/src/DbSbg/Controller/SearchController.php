<?php
/**
 * Customized Search Module Controller
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace DbSbg\Controller;

/**
 * Customized SearchController Class
 * Added possibility to export search results
 *
 * @category VuFind
 * @package  Controller
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class SearchController extends \VuFind\Controller\SearchController
{

    /**
     * Export search results
     *
     * @return mixed
     */
    public function exportAction() {

        $view = $this->createViewModel();

        if ($this->formWasSubmitted('submitExportSearchResults')) {

            // Get all request params
            $request = new \Laminas\Stdlib\Parameters(
                $this->getRequest()->getQuery()->toArray()
                + $this->getRequest()->getPost()->toArray()
            );

            // Set page always to 1 for the start
            $request['page'] = '1';

            // Set a higher limit for faster pagination
            $request['limit'] = 200;
            
            // Get search runner
            $runner = $this->serviceLocator->get(\VuFind\Search\SearchRunner::class);

            // Execute search and get results for first page
            $results = $runner->run($request);

            // Get params from results
            $params = $results->getParams();

            // Get total records found
            $totalRecords = $results->getResultTotal();

            // Get limit per page from params
            $recordsPerPage = $params->getLimit();

            // Get OS
            $os = $this->getOs();

            // Get CSV separator based on OS
            $sep = $this->getCsvSeparator($os);

            // Create a unique filename
            $timestamp = time();
            $rand = rand(1,10000);
            $unique = $timestamp.'_'.$rand;
            $folder = '/tmp';
            $filename = 'vufind_export_'.$unique.'.csv';
            $filepath = $folder.'/'.$filename;

            // Open CSV file in "append" mode
            $file = fopen($filepath, 'a');

            // Define the headings for the CSV
            $headings[] = ['mmsid', 'ACNo', 'Title', /*'containerTitle',*/ 'Authors',
                'place', 'Publisher', 'Date'];
            
            // Translate headings
            $headingsTranslated[] = $this->getCsvTranslation(
                $headings, ($os === 'win') ? true : false
            );

            // Write headings to file
            $this->writeToFile($headingsTranslated, $file, $os, $sep);

            // Get results of first page as record driver objects and write to file
            $this->writeToFile($this->getMetadata($results->getResults()), $file,
                $os, $sep);

            // Calculate total number of pages
            $pages = ceil($totalRecords/$recordsPerPage);

            // Get results for other pages (if any)
            for ($page=2; $page <= $pages; $page++) {
                // Reset the execution time limit so that we don't get a timout error
                set_time_limit(30);

                $request['page'] = $page;

                // Execute search and get results
                $results = $runner->run($request);

                // Get results of current page as record driver objects and write to
                // file
                $this->writeToFile($this->getMetadata($results->getResults()), $file,
                    $os, $sep);
            }

            // Close CSV file
            fclose($file);

            // Create message array for "success" page
            $msg = [
                'translate' => false, 'html' => true,
                'msg' => $this->getViewRenderer()->render(
                    'search/export-success.phtml',
                    ['url' => '/localfile/open?filename='.$filename]
                )
            ];

            // Return "success" page with button for downloading the file
            return $this->redirectToSource('success', $msg);
        }

        return $view;
    }

    /**
     * Recirect to another view
     *
     * @param string $flashNamespace
     * @param mixed $flashMsg
     * 
     * @return void
     */
    public function redirectToSource($flashNamespace = null, $flashMsg = null)
    {
        // Set flash message if requested:
        if (null !== $flashNamespace && !empty($flashMsg)) {
            $this->flashMessenger()->addMessage($flashMsg, $flashNamespace);
        }

        // Use current URL as target
        $target = $this->getRequest()->getUriString();

        // Redirect to URL
        return $this->redirect()->toUrl($target);
    }

    /**
     * Get metadata from search results that should be exported
     *
     * @param array $records An array of record driver objects
     * 
     * @return array An array of metadata
     */
    protected function getMetadata($records) {
        $metadata = [];
        foreach ($records as $key => $record) {
            $mmsId = $record->getUniqueID();
            $acNoRaw = $record->getAcNo();
            if (is_array($acNoRaw)) {
                $acNo = (!empty($acNoRaw)) ? implode('; ', $acNoRaw) : '';
            } else {
                $acNo = (!empty($acNoRaw)) ? $acNoRaw : '';
            }
            
            $title = $record->getTitle();
            //$containerTitle = $record->getContainerTitle();
            $authorsRaw = $record->getAllAuthors();
            $authors = (!empty($authorsRaw)) ? implode('; ', $authorsRaw) : '';
            $datesPubRaw = $record->getPublicationDates();
            $datesSpanRaw = $record->getDateSpan();
            $dates = (!empty($datesPubRaw))
                ? implode('; ', $datesPubRaw)
                : (
                    (!empty($datesSpanRaw))
                        ? implode('; ', $datesSpanRaw)
                        : []
                );
            $publishersRaw = $record->getPublishers();
            $publishers = (!empty($publishersRaw))
                ? implode('; ', $publishersRaw)
                : '';
            $placesRaw = $record->getPlacesOfPublication();
            $places = (!empty($placesRaw)) ? implode('; ', $placesRaw) : '';
             

            $metadata[] = [
                'mmsId' => $mmsId,
                'acNo' => $acNo,
                'title' => $title,
                //'containerTitle' => $containerTitle,
                'authors' => $authors,
                'places' => $places,
                'publishers' => $publishers,
                'dates' => $dates
            ];
        }

        return $metadata;
    }

    /**
     * Write metadata to CSV file
     *
     * @param array  $values The values to write as CSV
     * @param stream $file   The CSV file stream
     * @param string $os     The current OS (win, mac, linux)
     * @param string $sep    Separator for CSV
     * 
     * @return void
     */
    protected function writeToFile($values, $file, $os, $sep) {
        $csvValues = [];
        foreach ($values as $key => $values) {

            foreach ($values as $colName => $value) {
                // If null value is given, set to empty string
                $value = (!empty($value)) ? $value : '';

                // Add (Windows encoded) value to result array
                $csvValues[$key][$colName] = ($os === 'win')
                    ? $this->getWinEncodedText($value)
                    : $value;
            }
        }

        try {
            foreach ($csvValues as $csvValue) {
                fputcsv($file, array_values($csvValue), $sep, '"');
            }
            
        } catch (\Exception $e) {
            throw new \VuFind\Exception\ILS('Error while exporting search resuls ' .
                ' | ' .$e->getMessage());
        }        
    }

    /**
     * Get the current OS (win, mac, linux)
     *
     * @return string The current OS: win, mac or linux
     */
    protected function getOs() {
        // Try to get the users operating system
        $ua = $_SERVER['HTTP_USER_AGENT']; // Get the user agent
        $os = 'win'; // Default. Most OS are Windows.

        if (
            stripos($ua, 'linux') !== false
            || stripos($ua, 'CrOS') !== false
            || stripos($ua, 'BSD') !== false
            || stripos($ua, 'SunOS') !== false
            || stripos($ua, 'UNIX') !== false
        ) {
            $os = 'linux';
        } else if (stripos($ua, 'mac') !== false) {
            $os = 'mac';
        } else if (stripos($ua, 'windows') !== false) {
            $os = 'win';
        }

        return $os;
    }

    /**
     * Get CSV separator based on OS
     *
     * @param string $os The OS (win, mac or linux)
     * @return string The CSV separator as string
     */
    protected function getCsvSeparator($os) {
        $sep = ',';  // Default. In Excel (Win) we have to use semi-colon ;
        if ($os == 'win') {
            $sep = ';';
        }
        return $sep;
    }

    /**
     * Translate CSV headings and, if neccessary, encode them for Windows.
     *
     * @param  array     $translate   Associative array of headings
     * @param  boolean   $convert     Convert to Windows encoding (UTF-16LE) if true
     * 
     * @return array                  Acssociative array of translated headings
     */
    protected function getCsvTranslation($translate, $convertToWin)
    {
        $csvHeadings = [];
        $translate = $translate[0];

        if ($convertToWin) {
            foreach ($translate as $key => $text) {
                $csvHeadings[$key] = $this->getWinEncodedText(
                    $this->translate($text)
                );
            }
        } else {
            
            foreach ($translate as $key => $text) {
                $csvHeadings[$key] = $this->translate($text);
            }
        }

        return $csvHeadings;
    }

    /**
     * Encode from UTF-8 to UTF-16LE. This is for CSV export on Windows.
     *
     * @param string  $text The text to encode from UTF-8 to UTF-16LE
     * @return string       The UTF-16LE encoded text
     */
    protected function getWinEncodedText($text)
    {
        return mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
    }

}
