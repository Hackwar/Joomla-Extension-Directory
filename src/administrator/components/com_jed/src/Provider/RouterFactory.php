<?php

/**
 * @package JED
 *
 * @copyright (C) 2006-2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Jed\Component\Jed\Administrator\Provider;

use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * @package Jed\Component\Jed\Administrator\Provider
 *
 * @since 4.0.0
 */
class RouterFactory implements ServiceProviderInterface
{
    /**
     * Router factory constructor.
     *
     * @param string $namespace The namespace
     *
     * @since 4.0.0
     */
    public function __construct(
        /**
         * The component's namespace
         *
         *
         * @since 4.0.0
         */
        private readonly string $namespace
    )
    {
    }

    /**
     * @inheritDoc
     *
     * @since 4.0.0
     */
    public function register(Container $container): void
    {
        $container->set(
            RouterFactoryInterface::class,
            fn(Container $container) => new \Jed\Component\Jed\Administrator\Service\RouterFactory(
                $this->namespace,
                $container->get(DatabaseInterface::class),
                $container->get(MVCFactoryInterface::class),
                $container->get(CategoryFactoryInterface::class)
            )
        );
    }
}
