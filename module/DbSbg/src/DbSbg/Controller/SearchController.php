<?php
/**
 * Customized Search Module Controller
 *
 * PHP version 8
 *
 * Copyright (C) Radix Lab - Michael Birkner-Tröger 2026.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Michael Birkner-Tröger <office@radix-lab.at>
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
 * @author   Michael Birkner-Tröger <office@radix-lab.at>
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
        // Create view model
        $view = $this->createViewModel();

        // Capture the current GET parameters (search query, filters)
        $query = $this->getRequest()->getQuery()->toArray();
        
        // Also capture POST parameters (often used in Lightbox context)
        $post = $this->getRequest()->getPost()->toArray();
        
        // Merge them (POST overwrites GET if conflict, which is usually correct for VuFind)
        $params = array_merge($query, $post);
        
        // Pass to view
        $view->query = $params;
        
        if ($this->formWasSubmitted('submitExportSearchResults')) {
            // Define path to debug file
            $debugFile = '/opt/exports/export_debug.log';

            // Get all request params
            $requestRaw = $this->getRequest()->getQuery()->toArray()
                + $this->getRequest()->getPost()->toArray();
            $httpUri = new \Laminas\Uri\Http($requestRaw['lightboxParent']);
            $request = $httpUri->getQueryAsArray();

            $jobId = uniqid();
            $paramsFile = "/opt/exports/export_params_{$jobId}.txt";
            $outputFile = "/opt/exports/vufind_export_{$jobId}.csv";
            
            // Save params for the worker
            file_put_contents($paramsFile, serialize($request));

            // Launch background process (Fire and forget)
            // "nohup" ensures it keeps running if the web request ends
            // "> /dev/null 2>&1 &" ensures PHP doesn't wait for output
            $cmd = "VUFIND_HOME=/opt/vufind VUFIND_LOCAL_DIR=/opt/vufind/local "
                . "VUFIND_LOCAL_MODULES=\"DbSbg,DbSbgSearch,DbSbgTheme,DbSbgConsole\" "
                . "nohup nice -n 19 php " . APPLICATION_PATH . "/public/index.php export/export_csv "
                . escapeshellarg($paramsFile) . " "
                . escapeshellarg($outputFile) . " "
                . escapeshellarg($jobId) . " > " . escapeshellarg($debugFile)
                . " 2>&1 &";
            
            // Execute command
            $cmdResult = [];
            $cmdResultCode = 0;
            exec($cmd, $cmdResult, $cmdResultCode);

            // Set job ID to view and set status page template
            $view->jobId = $jobId;
            $view->setTemplate('search/export-status');
        }

        return $view;
    }

}
