<?php

/**
 * @package       JED
 *
 * @subpackage    TICKETS
 *
 * @copyright     (C) 2022 Open Source Matters, Inc.  <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Jed\Component\Jed\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Jedtickets class.
 *
 * @since  4.0.0
 */
class JedticketsController extends FormController
{
    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional
     * @param   array   $config  Configuration array for model. Optional
     *
     * @return object    The model
     *
     * @since    4.0.0
     */
    public function getModel($name = 'Jedtickets', $prefix = 'Site', $config = []): object
    {
        return parent::getModel($name, $prefix, ['ignore_request' => true]);
    }
}
