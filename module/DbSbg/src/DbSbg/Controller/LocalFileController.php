<?php
/**
 * LocalFile controller
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
 * @package  Controller
 * @author   Michael Birkner-Tröger <office@radix-lab.at>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace DbSbg\Controller;

/**
 * LocalFile controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Michael Birkner-Tröger <office@radix-lab.at>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class LocalFileController extends \VuFind\Controller\AbstractBase
{

    /**
     * Open a file or show an error message
     *
     * @return void|\Laminas\View\Model\ViewModel
     */
    public function openAction()
    {
        $config = $this->getConfig()->toArray();
        $webaccessPath = rtrim($config['LocalFile']['webaccess_path'], '/');

        $filename = $this->params()->fromQuery('filename');
        
        // Security Check: Prevent directory traversal
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
            throw new \Exception('Invalid filename');
        }

        $fullFilePath = $webaccessPath . '/' . $filename;

        if (file_exists($fullFilePath)) {
            // Use Laminas Stream Response
            $response = new \Laminas\Http\Response\Stream();
            $response->setStream(fopen($fullFilePath, 'r'));
            $response->setStatusCode(200);
            $response->setStreamName(basename($fullFilePath));
            
            $headers = $response->getHeaders();
            
            $ext = strtolower(pathinfo($fullFilePath, PATHINFO_EXTENSION));
            
            if ($ext == 'csv') {
                // Note: UTF-16 is rare for web CSVs. UTF-8 + BOM is usually better for Excel.
                // If you wrote the file as UTF-8 in the export script, declare it as UTF-8 here.
                //$headers->addHeaderLine('Content-Type', 'text/csv; charset=utf-8');
                $headers->addHeaderLine('Content-Type: text/csv; charset=UTF-16');
                $headers->addHeaderLine('Content-Disposition', 'attachment; filename="' . basename($fullFilePath) . '"');
            } else if ($ext == 'pdf') {
                $headers->addHeaderLine('Content-Type', 'application/pdf');
            } else {
                $headers->addHeaderLine('Content-Type', 'application/octet-stream');
                $headers->addHeaderLine('Content-Disposition', 'attachment; filename="' . basename($fullFilePath) . '"');
            }

            $headers->addHeaderLine('Content-Length', filesize($fullFilePath));
            $headers->addHeaderLine('Content-Description', 'File Transfer');
            $headers->addHeaderLine('Pragma', 'public');
            $headers->addHeaderLine('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
            $headers->addHeaderLine('Expires', '0');

            return $response;
        } else {
            return $this->createViewModel(['filename' => $filename]);
        }
    }

}
