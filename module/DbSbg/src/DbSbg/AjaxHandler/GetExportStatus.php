<?php

/**
 * "Get Export Status" AJAX handler
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  AJAX
 * @author   Michael Birkner-Tröger <office@radix-lab.at>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace DbSbg\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Session\Settings as SessionSettings;

/**
 * "Get Export Status" AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Michael Birkner-Tröger <office@radix-lab.at>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetExportStatus extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * Constructor
     *
     * @param SessionSettings $ss Session settings
     */
    public function __construct(
        SessionSettings $ss,
    ) {
        $this->sessionSettings = $ss;
    }

    /**
     * Handle request
     *
     * @param Params $params Parameters plugin
     *
     * @return mixed
     */
    public function handleRequest(Params $params)
    {
        // Avoid session write timing bug
        $this->disableSessionWrites();

        // Get query parameters
        $queryParams = $params->fromQuery();

        // Get job ID from parameters
        $jobId = $queryParams['jobId'] ?? '';

        // Define path to status file
        $statusFile = '/opt/exports/export_status_' . $jobId . '.json';

        // If file is missing, assume it's still starting up
        if (!file_exists($statusFile)) {
            // Return 'initializing' status
            $status = ['status' => 'initializing', 'msg' => 'Job wird gestartet...'];
        } else {
            // File exists, read it
            $status = json_decode(file_get_contents($statusFile), true);
        }

        // Return data as JSON response
        return $this->formatResponse([
            'jobId' => $jobId,
            'status_file' => $status
        ]);
    }
    
}