<?php

/**
 * @package    JED
 *
 * @copyright  (C) 2022 Open Source Matters, Inc.  <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Jed\Component\Jed\Administrator\View\Extensionscores;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Jed\Component\Jed\Administrator\Helper\JedHelper;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

/**
 * View class for a list of Extensionscores.
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected $items;

    protected $pagination;

    protected $state;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return void
     *
     * @throws Exception
     */
    public function display($tpl = null)
    {
        $this->state         = $this->get('State');
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
        }

        $this->addToolbar();

        $this->sidebar = Sidebar::render();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   4.0.0
     * @throws Exception
     * @throws Exception
     */
    protected function addToolbar()
    {
        $state = $this->get('State');
        $canDo = JedHelper::getActions();

        ToolbarHelper::title(Text::_('COM_JED_TITLE_EXTENSIONSCORES'), "generic");

        $toolbar = Toolbar::getInstance('toolbar');

        // Check if the form exists before showing the add/edit buttons
        $formPath = JPATH_COMPONENT_ADMINISTRATOR . '/src/View/Extensionscores';

        if (file_exists($formPath)) {
            if ($canDo->get('core.create')) {
                $toolbar->addNew('extensionscore.add');
            }
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('fas fa-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            if (isset($this->items[0]->state)) {
                $childBar->publish('extensionscores.publish')->listCheck(true);
                $childBar->unpublish('extensionscores.unpublish')->listCheck(true);
                $childBar->archive('extensionscores.archive')->listCheck(true);
            } elseif (isset($this->items[0])) {
                // If this component does not use state then show a direct delete button as we can not trash
                $toolbar->delete('extensionscores.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
            }

            $childBar->standardButton('duplicate')
                ->text('JTOOLBAR_DUPLICATE')
                ->icon('fas fa-copy')
                ->task('extensionscores.duplicate')
                ->listCheck(true);

            if (isset($this->items[0]->checked_out)) {
                $childBar->checkin('extensionscores.checkin')->listCheck(true);
            }

            if (isset($this->items[0]->state)) {
                $childBar->trash('extensionscores.trash')->listCheck(true);
            }
        }



        // Show trash and delete for components that uses the state field
        if (isset($this->items[0]->state)) {
            if ($this->state->get('filter.state') == ContentComponent::CONDITION_TRASHED && $canDo->get('core.delete')) {
                $toolbar->delete('extensionscores.delete')
                    ->text('JTOOLBAR_EMPTY_TRASH')
                    ->message('JGLOBAL_CONFIRM_DELETE')
                    ->listCheck(true);
            }
        }

        if ($canDo->get('core.admin')) {
            $toolbar->preferences('com_jed');
        }

        // Set sidebar action
        Sidebar::setAction('index.php?option=com_jed&view=extensionscores');
    }

    /**
     * Method to order fields
     *
     * @return void
     */
    protected function getSortFields()
    {
        return [
            'a.`id`'                    => Text::_('JGRID_HEADING_ID'),
            'a.`state`'                 => Text::_('JSTATUS'),
            'a.`ordering`'              => Text::_('JGRID_HEADING_ORDERING'),
            'a.`extension_id`'          => Text::_('COM_JED_EXTENSIONSCORES_EXTENSION_ID'),
            'a.`supply_option_id`'      => Text::_('COM_JED_EXTENSIONSCORES_SUPPLY_OPTION_ID'),
            'a.`functionality_score`'   => Text::_('COM_JED_EXTENSIONSCORES_FUNCTIONALITY_SCORE'),
            'a.`ease_of_use_score`'     => Text::_('COM_JED_EXTENSIONSCORES_EASE_OF_USE_SCORE'),
            'a.`support_score`'         => Text::_('COM_JED_EXTENSIONSCORES_SUPPORT_SCORE'),
            'a.`value_for_money_score`' => Text::_('COM_JED_EXTENSIONSCORES_VALUE_FOR_MONEY_SCORE'),
            'a.`documentation_score`'   => Text::_('COM_JED_EXTENSIONSCORES_DOCUMENTATION_SCORE'),
            'a.`number_of_reviews`'     => Text::_('COM_JED_EXTENSIONSCORES_NUMBER_OF_REVIEWS'),
        ];
    }

    /**
     * Check if state is set
     *
     * @param   mixed  $state  State
     *
     * @return bool
     */
    public function getState($state)
    {
        return $this->state->{$state} ?? false;
    }
}
