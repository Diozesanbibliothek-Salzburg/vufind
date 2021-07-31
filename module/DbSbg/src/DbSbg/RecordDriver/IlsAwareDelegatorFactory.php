<?php
/**
 * Customized ILS aware delegator factory
 *
 * Copyright (C) Michael Birkner 2021.
 *
 * PHP version 7
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
 * @package  RecordDrivers
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:session_handlers Wiki
 */
namespace DbSbg\RecordDriver;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;

/**
 * Customized ILS aware delegator factory
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:session_handlers Wiki
 */
class IlsAwareDelegatorFactory extends \VuFind\RecordDriver\IlsAwareDelegatorFactory
{
    /**
     * Invokes this factory.
     * DbSbg: Use custom hold logic
     *
     * @param ContainerInterface $container Service container
     * @param string             $name      Service name
     * @param callable           $callback  Service callback
     * @param array|null         $options   Service options
     *
     * @return AbstractBase
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        $driver = call_user_func($callback);

        // Attach the ILS if at least one backend supports it:
        $ilsBackends = $this->getIlsBackends($container);
        if (!empty($ilsBackends) && $container->has(\VuFind\ILS\Connection::class)) {
            $driver->attachILS(
                $container->get(\VuFind\ILS\Connection::class),
                // DbSbg: Use custom holds logic
                $container->get(\DbSbg\ILS\Logic\Holds::class),
                $container->get(\VuFind\ILS\Logic\TitleHolds::class)
            );
            $driver->setIlsBackends($ilsBackends);
        }

        return $driver;
    }

}
