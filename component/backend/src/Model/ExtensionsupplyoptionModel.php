<?php

/**
 * @package    JED
 *
 * @copyright  (C) 2022 Open Source Matters, Inc.  <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Jed\Component\Jed\Administrator\Model;

// No direct access.
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;

/**
 * Extensionsupplyoption model.
 *
 * @since  4.0.0
 */
class ExtensionsupplyoptionModel extends AdminModel
{
    /**
     * @var    string  Alias to manage history control
     *
     * @since  4.0.0
     */
    public $typeAlias = 'com_jed.extensionsupplyoption';
    /**
     * @var    string  The prefix to use with controller messages.
     *
     * @since  4.0.0
     */
    protected $text_prefix = 'COM_JED';
    /**
     * @var    null  Item data
     *
     * @since  4.0.0
     */
    protected mixed $item = null;


    /**
     * Method to get the record form.
     *
     * @param   array    $data      An optional array of data for the form to interogate.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|bool  A Form object on success, false on failure
     *
     * @since   4.0.0
     *
     * @throws Exception
     */
    public function getForm($data = [], $loadData = true, $formname = 'jform'): Form|bool
    {
        // Get the form.
        $form = $this->loadForm(
            'com_jed.extensionsupplyoption',
            'extensionsupplyoption',
            [
                'control'   => $formname,
                'load_data' => $loadData,
            ]
        );


        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get a single record.
     *
     * @param   null  $pk  The id of the primary key.
     *
     * @return CMSObject Object on success
     *
     * @since   4.0.0
     *
     * @throws Exception
     */
    public function getItem($pk = null): CMSObject
    {

        if ($item = parent::getItem($pk)) {
            if (isset($item->params)) {
                $item->params = json_encode($item->params);
            }

            // Do any procesing on fields here if needed


            return $item;
        } else {
            throw new Exception(Text::_("JERROR_ALERTNOAUTHOR"), 401);
        }
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $name     The table type to instantiate
     * @param   string  $prefix   A prefix for the table class name. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table    A database object
     *
     * @since   4.0.0
     *
     * @throws Exception
     */
    public function getTable($name = 'Extensionsupplyoption', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   4.0.0
     *
     * @throws Exception
     */
    protected function loadFormData(): mixed
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_jed.edit.extensionsupplyoption.data', []);

        if (empty($data)) {
            if ($this->item === null) {
                $this->item = $this->getItem();
            }

            $data = $this->item;
        }

        return $data;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  Table Object
     *
     * @return  void
     *
     * @since   4.0.0
     */
    protected function prepareTable($table)
    {
    }
}
