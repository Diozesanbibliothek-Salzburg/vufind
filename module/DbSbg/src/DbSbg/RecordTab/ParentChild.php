<?php
/**
 * Parent-Child tab
 *
 * PHP version 8
 *
 * Copyright (C) Radix Lab - Michael Birkner-Tröger, 2026.
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
 * @package  RecordTabs
 * @author   Radix Lab - Michael Birkner-Tröger <office@radix-lab.at>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */

namespace DbSbg\RecordTab;

/**
 * Parent-Child tab
 *
 * @category VuFind
 * @package  RecordTabs
 * @author   Radix Lab - Michael Birkner-Tröger <office@radix-lab.at>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */
class ParentChild extends \VuFind\RecordTab\AbstractBase
{
    /**
     * Child records
     *
     * @var array
     */
    protected $childs = null;

    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription() {
        return 'VolumeTitles';
    }

    /**
     * Is this tab active?
     *
     * @return bool
     */
    public function isActive() {
        $this->childs = $this->driver->getChilds();
        return !empty($this->childs);
    }

    /**
     * Get the child records that are set in "isActive"
     *
     * @return array
     */
    public function getChilds() {
        return $this->childs;
    }
}
