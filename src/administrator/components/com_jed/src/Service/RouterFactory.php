<?php

/**
 * @package JED
 *
 * @copyright (C) 2006-2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Jed\Component\Jed\Administrator\Service;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Component\Router\RouterInterface;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

/**
 * JED router factory.
 *
 * @since 4.0.0
 */
class RouterFactory implements RouterFactoryInterface
{
    /**
     * Class constructor.
     *
     * @param string                   $namespace
     * @param DatabaseInterface        $db
     * @param MVCFactoryInterface      $factory
     * @param CategoryFactoryInterface $categoryFactory
     * @since 4.0.0
     */
    public function __construct(
        /**
         * The extension's namespace
         *
         * @since 4.0.0
         */
        private readonly string $namespace,
        /**
         * The database factory object
         *
         * @since 4.0.0
         */
        private readonly DatabaseInterface $db,
        /**
         * THe MVC factory object
         *
         * @since 4.0.0
         */
        private readonly MVCFactoryInterface $factory,
        /**
         * The category factory object for ATS
         *
         * @since 4.0.0
         */
        private readonly CategoryFactoryInterface $categoryFactory
    ) {
    }


    /**
     * Creates a router.
     *
     * @param CMSApplicationInterface $application The application
     * @param AbstractMenu            $menu        The menu object to work with
     *
     * @return RouterInterface
     *
     * @since 4.0.0
     */
    public function createRouter(CMSApplicationInterface $application, AbstractMenu $menu): RouterInterface
    {
        $className = trim($this->namespace, '\\') . '\\' . ucfirst($application->getName()) . '\\Service\\Router';

        if (!class_exists($className)) {
            throw new RuntimeException('No router available for this application.');
        }

        return new $className($application, $menu, $this->db, $this->factory, $this->categoryFactory);
    }
}
