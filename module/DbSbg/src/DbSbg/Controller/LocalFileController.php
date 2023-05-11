<?php
/**
 * LocalFile controller
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
 * LocalFile controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Michael Birkner <birkner_michael@yahoo.de>
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
        $fullFilePath = $webaccessPath.'/'.$filename;

        if (file_exists($fullFilePath)) {
            $ext = substr($fullFilePath, -3, 3);
            if ($ext == 'csv') {
                header('Content-Type: text/csv; charset=UTF-16');
                header('Content-Disposition: attachment; filename="' . basename($fullFilePath) . '"');
            } else if ($ext == 'pdf') {
                header('Content-Type: application/pdf');
            } else {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($fullFilePath) . '"');
                header('Content-Length: ' . filesize($fullFilePath));
            }
            
            header('Content-Description: File Transfer');
            header('Pragma: public');
            
            readfile($fullFilePath);
        } else {
            return $this->createViewModel(['filename' => $filename]);
        }
    }
}
