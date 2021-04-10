<?php
/**
 * Customized VuFind Theme Initializer
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
 * @package  Theme
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace DbSbgTheme;

/**
 * Customized VuFind Theme Initializer
 *
 * @category VuFind
 * @package  Theme
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Initializer extends \VuFindTheme\Initializer
{
    /**
     * Initialize the theme.  This needs to be triggered as part of the dispatch
     * event.
     * 
     * DbSbg: Remove "ui" cookie
     *
     * @throws \Exception
     * @return void
     */
    public function init()
    {
        // DbSbg: Remove cookie that was set in \VuFindTheme\Initializer->pickTheme()
        // because it is not possible to override that function and avoid creating
        // the cookie in the first place. This is because it is called via
        // \VuFind\Bootstrapper->initTheme() which in turn is called via
        // \VuFind\Module, and this will always be executed (it can't be overridden).
        $this->cookieManager->clear('ui');
    }
}
