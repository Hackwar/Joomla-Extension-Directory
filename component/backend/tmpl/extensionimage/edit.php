<?php

/**
 * @package    JED
 *
 * @copyright  (C) 2022 Open Source Matters, Inc.  <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;


HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');
?>

<form
    action="<?php echo Route::_('index.php?option=com_jed&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="extensionimage-form" class="form-validate form-horizontal">

    
    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'extensionimage')); ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'extensionimage', Text::_('COM_JED_TAB_EXTENSIONIMAGE', true)); ?>
    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <fieldset class="adminform">
                <legend><?php echo Text::_('COM_JED_FIELDSET_EXTENSIONIMAGE'); ?></legend>
                <?php echo $this->form->renderField('extension_id'); ?>
                <?php echo $this->form->renderField('filename'); ?>
                <?php if (!empty($this->item->filename)) : ?>
                    <?php $filenameFiles = array(); ?>
                    <?php foreach ((array)$this->item->filename as $fileSingle) : ?>
                        <?php if (!is_array($fileSingle)) : ?>
                            <a href="<?php echo Route::_(Uri::root() . '/tmp' . DIRECTORY_SEPARATOR . $fileSingle, false);?>"><?php echo $fileSingle; ?></a> | 
                            <?php $filenameFiles[] = $fileSingle; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <input type="hidden" name="jform[filename_hidden]" id="jform_filename_hidden" value="<?php echo implode(',', $filenameFiles); ?>" />
                <?php endif; ?>
            </fieldset>
        </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
    <input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
    <input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
    <input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
    <input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
    <?php echo $this->form->renderField('created_by'); ?>
    <?php echo $this->form->renderField('modified_by'); ?>

    
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>

</form>
