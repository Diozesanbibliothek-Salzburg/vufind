<?php
/**
 * Customized VuFind Bootstrapper
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
 * @package  Bootstrap
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace DbSbg;

/**
 * Customized VuFind Bootstrapper
 *
 * @category VuFind
 * @package  Bootstrap
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Bootstrapper extends \VuFind\Bootstrapper
{

  /**
   * Set up configuration manager.
   * 
   * DbSbg: This function must be used here, even though it is already used in
   * extended \VuFind\Bootstrapper. If not, we won't get the config.
   *
   * @return void
   */
  protected function initConfig()
  {
      // Create the configuration manager:
      $app = $this->event->getApplication();
      $sm = $app->getServiceManager();
      $this->config = $sm->get(\VuFind\Config\PluginManager::class)->get('config');
  }

  /**
   * Set up theme handling.
   * 
   * DbSbg: This loads the custom \DbSbgTheme\Initializer in which we avoid setting
   * the 'ui' cookie due to privacy reasons.
   *
   * @return void
   */
  protected function initTheme()
  {
      // Attach remaining theme configuration to the dispatch event at high
      // priority (TODO: use priority constant once defined by framework):
      $config = $this->config->Site;
      $callback = function ($event) use ($config) {
          // DbSbg: Load custom \DbSbgTheme\Initializer. This removes the "ui"
          // cookie.
          $theme = new \DbSbgTheme\Initializer($config, $event);
          $theme->init();
      };
      $this->events->attach('dispatch.error', $callback, 9000);
      $this->events->attach('dispatch', $callback, 9000);
  }

}
