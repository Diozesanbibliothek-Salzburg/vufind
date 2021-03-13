<?php
/**
 * Customized class to determine which account capabilities are available, based on
 * configuration and other factors.
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
 * @package  Config
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace DbSbg\Config;



/**
 * Customized class to determine which account capabilities are available, based on
 * configuration and other factors.
 *
 * @category VuFind
 * @package  Config
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class AccountCapabilities extends \VuFind\Config\AccountCapabilities
{

    /**
     * Get "E-Mail this record" setting ('enabled' or 'disabled').
     *
     * @return string
     */
    public function getEmailThisRecordSetting()
    {
        return isset($this->config->Social->emailThisRecord)
            && $this->config->Social->emailThisRecord === 'disabled'
            ? 'disabled' : 'enabled';
    }

}
