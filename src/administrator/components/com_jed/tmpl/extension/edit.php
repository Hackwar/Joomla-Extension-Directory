<?php

/** @var \Jed\Component\Jed\Administrator\View\Extension\HtmlView $this */
/**
 * @package JED
 *
 * @copyright (C) 2006-2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Jed\Component\Jed\Administrator\Model\ReviewModel;
use Jed\Component\Jed\Administrator\View\Extension\HtmlView;
use Jed\Component\Jed\Administrator\Helper\JedHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;

$headerlabeloptions = ['hiddenLabel' => true, 'readonly' => true];
$fieldhiddenoptions = ['hidden' => true];
/**
 * $model->setUseExceptions(true)
*/

HTMLHelper::_('script', 'com_jed/jed.js', ['version' => 'auto', 'relative' => true]);

try {
    Factory::getApplication()->getDocument()->getWebAssetManager()
        ->useScript('form.validate')
        ->useScript('keepalive')
        ->usePreset('choicesjs')
        ->useScript('webcomponent.field-fancy-select')
        ->useStyle('com_jed.Tickets')
        ->useStyle('com_jed.jquery_dataTables');
} catch (Exception) {
}

Text::script('COM_JED_EXTENSION_ERROR_DURING_SEND_EMAIL_LABEL', true);
Text::script('COM_JED_EXTENSION_MISSING_MESSAGE_ID_LABEL', true);
Text::script('COM_JED_EXTENSION_MISSING_DEVELOPER_ID', true);
Text::script('COM_JED_EXTENSION_MISSING_EXTENSION_ID_LABEL', true);
Text::script('COM_JED_EXTENSION_ERROR_SAVING_APPROVE_LABEL', true);
Text::script('COM_JED_EXTENSION_EXTENSION_APPROVED_REASON_REQUIRED_LABEL', true);
Text::script('COM_JED_EXTENSION_ERROR_SAVING_PUBLISH_LABEL', true);
Text::script('COM_JED_EXTENSION_EXTENSION_PUBLISHED_REASON_REQUIRED_LABEL', true);

$extensionUrl = Uri::root() . 'extension/' . $this->item->alias;
$downloadUrl  = 'index.php?option=com_jed&task=extension.download&id=' . $this->item->id;

$this->getDocument()
    ->addScriptOptions('joomla.userId', $this->getCurrentUser()->id, false);

?>

<form action="index.php?option=com_jed&view=extension&layout=edit&id=<?php echo (int) ($this->item->extension_id ?: $this->item->id); ?>" method="post" name="adminForm" id="extension-form" class="form-validate">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php
        echo HTMLHelper::_('uitab.startTabSet', 'extensionTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]);

        foreach ($this->form->getFieldsets() as $fieldset) :
            echo HTMLHelper::_('uitab.addTab', 'extensionTab', $fieldset->name, Text::_($fieldset->label));
            ?>
                <div class="row">
                    <div class="col-12 col-lg-6">
                        <?php  echo $this->form->renderFieldset($fieldset->name); ?>
                    </div>
                </div>
            <?php
                echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endforeach; ?>

        <?php
        echo HTMLHelper::_('uitab.addTab', 'extensionTab', 'viewextensionimages', Text::_('Images', true));
        ?>

        <div class="container">
            <div class="row">
                <?php
?>
<div class="col-12">

    <!-- Lightbox Modal -->
    <div class="modal fade" id="jed-image-lightbox" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content" style="background:rgba(0,0,0,.88)">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
                </div>
                <div class="modal-body text-center py-2">
                    <img id="jed-lightbox-img" src="" alt="" class="img-fluid" style="max-height:80vh">
                </div>
            </div>
        </div>
    </div>

    <style>
        .jed-image-grid { display:flex; flex-wrap:wrap; gap:1rem; padding:.25rem; }
        .jed-image-card { flex:0 0 100%; max-width:100%; }
        @media (min-width:576px) { .jed-image-card { flex:0 0 calc(50% - .5rem);       max-width:calc(50% - .5rem);       } }
        @media (min-width:768px) { .jed-image-card { flex:0 0 calc(33.333% - .667rem); max-width:calc(33.333% - .667rem); } }
        @media (min-width:992px) { .jed-image-card { flex:0 0 300px; max-width:300px; } }
        .jed-image-card.jed-dragging  { opacity:.35; }
        .jed-image-card.jed-drag-over { outline:2px dashed var(--bs-primary,#2a69b8); border-radius:.375rem; }
        .jed-drag-handle  { cursor:grab; }
        .jed-drag-handle:active { cursor:grabbing; }
        .jed-image-thumb  { width:100%; height:160px; object-fit:cover; cursor:zoom-in; display:block; }
    </style>

    <?php if (!empty($this->images)) : ?>

        <div class="jed-image-grid" id="jed-image-sortable">
            <?php foreach ($this->images as $image) :
                $imgSrc      = $image->filename;
                $isPublished = (int) ($image->state ?? 0);
            ?>
            <div class="jed-image-card" data-id="<?php echo (int) $image->id; ?>" draggable="true">
                <div class="card h-100 shadow-sm">

                    <div class="card-header d-flex align-items-center gap-1 py-2">
                        <span class="jed-drag-handle text-muted me-1" title="<?php echo Text::_('JORDER'); ?>">
                            <span class="icon-ellipsis-v" aria-hidden="true"></span><span class="icon-ellipsis-v" aria-hidden="true"></span>
                        </span>
                        <small class="text-muted ms-auto"><?php echo Text::_('JORDER'); ?>&thinsp;<?php echo (int) $image->ordering; ?></small>
                    </div>

                    <img src="<?php echo $imgSrc; ?>"
                         alt=""
                         class="jed-image-thumb"
                         loading="lazy"
                         data-lightbox-src="<?php echo $imgSrc; ?>">

                    <div class="card-body py-2">
                        <p class="text-muted small fst-italic mb-0"><?php echo Text::_('COM_JED_EXTENSION_IMAGE_INFO_PLACEHOLDER'); ?></p>
                    </div>

                    <div class="card-footer d-flex align-items-center gap-3 py-2">
                        <?php if ($isPublished) : ?>
                            <a href="#" class="text-success"
                               title="<?php echo Text::_('JUNPUBLISH'); ?>"
                               data-action="unpublish"
                               data-id="<?php echo (int) $image->id; ?>">
                                <span class="icon-publish" aria-hidden="true"></span>
                                <span class="visually-hidden"><?php echo Text::_('JUNPUBLISH'); ?></span>
                            </a>
                        <?php else : ?>
                            <a href="#" class="text-secondary"
                               title="<?php echo Text::_('JPUBLISH'); ?>"
                               data-action="publish"
                               data-id="<?php echo (int) $image->id; ?>">
                                <span class="icon-unpublish" aria-hidden="true"></span>
                                <span class="visually-hidden"><?php echo Text::_('JPUBLISH'); ?></span>
                            </a>
                        <?php endif; ?>

                        <a href="#" class="text-danger ms-auto"
                           title="<?php echo Text::_('JDELETE'); ?>"
                           data-action="delete"
                           data-id="<?php echo (int) $image->id; ?>">
                            <span class="icon-trash" aria-hidden="true"></span>
                            <span class="visually-hidden"><?php echo Text::_('JDELETE'); ?></span>
                        </a>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        </div>

    <?php else : ?>
        <p class="text-muted py-3"><?php echo Text::_('COM_JED_EXTENSION_NO_IMAGES'); ?></p>
    <?php endif; ?>

</div>

<script>
(function () {
    'use strict';

    // ── Lightbox ───────────────────────────────────────────────────────────────
    var lbModal = document.getElementById('jed-image-lightbox');
    var lbImg   = document.getElementById('jed-lightbox-img');

    document.querySelectorAll('.jed-image-thumb').forEach(function (img) {
        img.addEventListener('click', function () {
            lbImg.src = img.dataset.lightboxSrc || img.src;
            bootstrap.Modal.getOrCreateInstance(lbModal).show();
        });
    });

    lbModal.addEventListener('hidden.bs.modal', function () { lbImg.src = ''; });

    // ── Drag-and-drop sort ─────────────────────────────────────────────────────
    var grid    = document.getElementById('jed-image-sortable');
    var dragged = null;

    if (grid) {
        grid.addEventListener('dragstart', function (e) {
            var card = e.target.closest('.jed-image-card');
            if (!card) { return; }
            dragged = card;
            dragged.classList.add('jed-dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        grid.addEventListener('dragend', function () {
            if (!dragged) { return; }
            dragged.classList.remove('jed-dragging');
            grid.querySelectorAll('.jed-image-card').forEach(function (c) { c.classList.remove('jed-drag-over'); });
            dragged = null;
            // TODO: persist new order via AJAX
        });

        grid.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            var target = e.target.closest('.jed-image-card');
            if (target && target !== dragged) {
                grid.querySelectorAll('.jed-image-card').forEach(function (c) { c.classList.remove('jed-drag-over'); });
                target.classList.add('jed-drag-over');
            }
        });

        grid.addEventListener('drop', function (e) {
            e.preventDefault();
            var target = e.target.closest('.jed-image-card');
            if (!target || target === dragged) { return; }
            var cards   = Array.from(grid.querySelectorAll('.jed-image-card'));
            var fromIdx = cards.indexOf(dragged);
            var toIdx   = cards.indexOf(target);
            grid.insertBefore(dragged, fromIdx < toIdx ? target.nextSibling : target);
        });
    }
}());
</script>

            </div>
        </div>

        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php
echo HTMLHelper::_('uitab.addTab', 'extensionTab', 'viewextensionreviews', Text::_('Reviews', true));
?>

            <div class="container">
                <div class="row">
                    <?php

                    $slidesOptions = [//"active" => "slide0" // It is the ID of the active tab.
                    ];


                    $slideid = 0;

                    foreach ($this->reviews as $review) {
                        echo HTMLHelper::_('bootstrap.startAccordion', 'extension_' . $review->id . '_reviews_group', $slidesOptions);

                        if ($review->published === 1) {
                            $ico = '<span class="fas fa-bolt"></span>';
                        } else {
                            $ico = '';
                        }
                        echo HTMLHelper::_(
                            'bootstrap.addSlide',
                            'extension_' . $review->id . '_reviews_group',
                            $review->suptype . ' ' . $review->id . ' - ' . $review->title . '&nbsp;' .
                            JedHelper::prettyDate($review->created_on) . '&nbsp;',
                            'extension_' . $review->suptype . '_reviews_group' . '_slide' . ($slideid++)
                        );
                            $review_model = new ReviewModel();
                            $linked_form  = $review_model->getForm($review, false, 'review');
                            $linked_form->bind($review);
                            ?>
                    <div class="row ticket-header-row">
                        <div class="col-md-4 ticket-header">&nbsp;</div>
                        <div class="col-md-4 ticket-header">&nbsp;</div>
                            <div class="col-md-4 ticket-header">
        <h1>Status - <?php echo $linked_form->renderField('published', null, null, $headerlabeloptions); ?>
                    &nbsp;&nbsp;<button id="btn_save_published" type="button" class="">
                        <span class="icon-save"></span>
                    </button>
                    </h1>
                    <p id="jform_review_status_updated" style="display:none">Status Updated</p>

                </div>
                <div class="row ticket-header-row">

                    <div class="col-md-4  ticket-header">

                        <h1>Version - <?php echo $review->version; ?></h1>

                    </div>
                    <div class="col-md-4  ticket-header">

                        <h1>Type - <?php echo $review->suptype; ?></h1>

                    </div>
                    <div class="col-md-4  ticket-header">

                        <h1>Reviewer - <?php echo $review->created_by_name; ?></h1>

                    </div>

                </div>
                <P>&nbsp;</P>
                <div class="row ticket-header-row">
                    <div class="col-md-6   ticket-header">
                            <?php echo $linked_form->renderField('title', null, null); ?>
                    </div>
                    <div class="col-md-6   ticket-header">

                            <?php echo $linked_form->renderField('alias', null, null); ?>
                    </div>

                </div>
                <div class="row ticket-header-row">
                    <div class="col-md-12   ticket-header">
                            <?php echo $linked_form->renderField('body', null, null); ?>
                    </div>
                    <div class="col-md-12   ticket-header">
                            <?php echo $linked_form->renderField('used_for', null, null); ?>
                    </div>
                </div>
                <div class="row ticket-header-row">
                    <div class="col-md-2   ticket-header">
                        <h1><?php echo Text::_('COM_JED_REVIEWS_FUNCTIONALITY_LABEL') . ' - ' . $review->functionality; ?></h1>
                    </div>
                    <div class="col-md-10   ticket-header">
                            <?php echo $linked_form->renderField('functionality_comment', null, null, $headerlabeloptions); ?>
                    </div>
                </div>
                <div class="row ticket-header-row">
                    <div class="col-md-2   ticket-header">
                        <h1><?php echo Text::_('COM_JED_REVIEWS_EASE_OF_USE_LABEL') . ' - ' . $review->ease_of_use; ?></h1>
                    </div>
                    <div class="col-md-10   ticket-header">
                            <?php echo $linked_form->renderField('ease_of_use_comment', null, null, $headerlabeloptions); ?>
                    </div>
                </div>
                <div class="row ticket-header-row">
                    <div class="col-md-2   ticket-header">
                        <h1><?php echo Text::_('COM_JED_GENERAL_SUPPORT_LABEL') . ' - ' . $review->support; ?></h1>
                    </div>
                    <div class="col-md-10   ticket-header">
                            <?php echo $linked_form->renderField('support_comment', null, null, $headerlabeloptions); ?>
                    </div>
                </div>
                <div class="row ticket-header-row">
                    <div class="col-md-2   ticket-header">
                        <h1><?php echo Text::_('COM_JED_EXTENSION_DOCUMENTATION_LABEL') . ' - ' . $review->documentation; ?></h1>
                    </div>
                    <div class="col-md-10   ticket-header">
                            <?php echo $linked_form->renderField('documentation_comment', null, null, $headerlabeloptions); ?>
                    </div>
                </div>
                <div class="row ticket-header-row">
                    <div class="col-md-2   ticket-header">
                        <h1><?php echo Text::_('COM_JED_REVIEWS_VALUE_FOR_MONEY_LABEL') . ' - ' . $review->value_for_money; ?></h1>
                    </div>
                    <div class="col-md-10   ticket-header">
                            <?php echo $linked_form->renderField('value_for_money_comment', null, null, $headerlabeloptions); ?>
                    </div>
                </div>
                <div class="row ticket-header-row">
                    <div class="col-md-2   ticket-header">
                        <h1><?php echo Text::_('COM_JED_REVIEWS_OVERALL_SCORE_LABEL') . ' - ' . $review->overall_score; ?></h1>
                    </div>
                    <div class="col-md-10   ticket-header">
                        <h1>Created on - <?php echo $review->created_on; ?>&nbsp;&nbsp;IP Address
                            - <?php echo $review->ip_address; ?></h1>
                    </div>
                </div>
                            <?php
                            echo HTMLHelper::_('bootstrap.endSlide');
                        echo HTMLHelper::_('bootstrap.endAccordion');
                    }


                    ?>


                </div>


        <?php
        echo HTMLHelper::_('uitab.endTab');
        /*for ($this->item->varied)
            echo HTMLHelper::_('uitab.endTab');

            foreach ($this->item->varied_data as $vr) {
                $varied_form = $this->itemvarieddatum_form;

                $varied_form->bind($vr);
                echo HTMLHelper::_('uitab.addTab', 'extensionTab', 'viewextensionsupply_tab_' . $vr->supply_type, Text::_($vr->supply_type, true) . '&nbsp;' . Text::_('COM_JED_GENERAL_VERSION_LABEL', true));
                echo $varied_form->renderFieldset('info');

                echo $varied_form->renderField('tags');
                echo $varied_form->renderField('state');
                echo $varied_form->renderField('created_by');

                echo HTMLHelper::_('uitab.endTab');
            }
            //      echo "<pre>";print_r($this->item);echo "</pre>";exit();
            ?>
        <!-- Legacy stuff from here on -->

            <?php
            echo HTMLHelper::_(
                'uitab.addTab',
                'extensionTab',
                'downloads',
                Text::_('COM_JED_EXTENSION_DOWNLOADS_TAB_LABEL')
            ); ?>
            <div class="row-fluid">
                <div class="span12">
                    <div class="form-horizontal">
                        <?php
                                    echo $this->form->renderField('downloadIntegrationType'); ?>
                        <?php
                                    echo $this->form->renderField('requiresRegistration'); ?>
                        <?php
                                    echo $this->form->renderField('downloadIntegrationUrl'); ?>
                        <h3><?php
                                        echo Text::_('COM_JED_EXTENSION_DOWNLOAD_ALTERNATIVE_DOWNLOAD_LABEL'); ?></h3>
                        <?php
                                    echo $this->form->renderField('downloadIntegrationType1'); ?>
                        <?php
                                    echo $this->form->renderField('downloadIntegrationType2'); ?>
                        <?php
                                    echo $this->form->renderField('downloadIntegrationType3'); ?>
                        <?php
                                    echo $this->form->renderField('downloadIntegrationType4'); ?>
                    </div>
                </div>
            </div>
            <?php
                        echo HTMLHelper::_('uitab.endTab'); ?>

            <?php
                        echo HTMLHelper::_(
                            'uitab.addTab',
                            'extensionTab',
                            'reviews',
                            Text::_('COM_JED_TITLE_REVIEWS')
                        ); ?>
            <div class="row-fluid">
                <div class="span12">
                    <div class="form-horizontal">
                        <?php
                                    echo $this->form->renderFieldset('reviews'); ?>
                    </div>
                    <?php
                                echo HTMLHelper::_(
                                    'link',
                                    'index.php?option=com_jed&view=reviews&filter[extension]=' . $this->item->id,
                                    Text::_('COM_JED_EXTENSION_REVIEW_LINK_LABEL') . ' <span class="icon-new-tab"></span>',
                                    'target="_blank"'
                                );
                                ?>
                </div>
            </div>
            <?php
            echo HTMLHelper::_('uitab.endTab'); ?>

            <?php
            echo HTMLHelper::_(
                'uitab.addTab',
                'extensionTab',
                'communication',
                Text::_('COM_JED_EXTENSION_COMMUNICATION_TAB')
            ); ?>
            <div class="row-fluid">
                <div class="span12">
                    <div class="form-horizontal">
                        <?php
                        echo $this->form->renderFieldset('communication'); ?>
                        <div class="control-group">
                            <div class="control-label">
                            </div>
                            <div class="controls">
                                <button class="btn btn-success js-messageType js-sendMessage" onclick="jed.sendMessage(); return false;">
                                    <?php
                                    echo Text::_('COM_JED_SEND_EMAIL'); ?>
                                </button>

                                <button class="btn btn-success js-messageType js-storeNote" style="display: none;" onclick="jed.storeNote(); return false;">
                                    <?php
                                    echo Text::_('COM_JED_STORE_NOTE'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            echo HTMLHelper::_('uitab.endTab'); ?>

            <?php
            echo HTMLHelper::_(
                'uitab.addTab',
                'extensionTab',
                'history',
                Text::_('COM_JED_EXTENSION_HISTORY_TAB')
            ); ?>
            <div class="row-fluid">
                <div class="span12">
                    <table class="table table-striped table-condensed">
                        <thead>
                        <tr>
                            <td><?php
                                echo Text::_('COM_JED_EXTENSION_HISTORY_DATE_LABEL'); ?></td>
                            <td><?php
                                echo Text::_('COM_JED_GENERAL_TYPE_LABEL'); ?></td>
                            <td><?php
                                echo Text::_('COM_JED_EXTENSION_MESSAGE_LABEL'); ?></td>
                            <td><?php
                                echo Text::_('COM_JED_EXTENSION_HISTORY_MEMBER'); ?></td>
                            <td><?php
                                echo Text::_('COM_JED_EXTENSION_HISTORY_USER'); ?></td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (isset($this->item->history)) :
                            foreach ($this->item->history as $history) :
                                ?>
                                <tr><?php
                                ?>
                                <td><?php
                                echo HTMLHelper::_('date', $history->logDate, Text::_('COM_JED_GENERAL_DATETIME_FORMAT')); ?></td><?php
        ?>
                                <td><?php
                                                                echo Text::_('COM_JED_EXTENSION_HISTORY_LOG_' . $history->type); ?></td><?php

        if ($history->type === 'mail') {
        ?>
                                    <td>
        <?php
        echo $history->subject; ?>
                                    <?php
                                    echo $history->body; ?>
                                    </td><?php
                                    ?>
                                    <td><?php
                                    echo $history->memberName; ?></td><?php
        ?>
                                    <td><?php
                                        echo HTMLHelper::_('link', 'index.php?option=com_users&task=user.edit&id=' . $history->developerId, $history->developerName); ?> &lt;<?php
        echo $history->developerEmail; ?>&gt;</td><?php
        }
        if ($history->type === 'note') {
        ?>
                                    <td>
        <?php
        echo $history->body; ?>
                                    </td><?php
                                    ?>
                                    <td><?php
                                    echo $history->memberName; ?></td><?php
        ?>
                                    <td><?php
                                        echo HTMLHelper::_('link', 'index.php?option=com_users&task=user.edit&id=' . $history->developerId, $history->developerName); ?></td><?php
        } elseif ($history->type === 'actionLog') {
        ?>
                                    <td><?php
                                    echo ActionlogsHelper::getHumanReadableLogMessage($history); ?></td><?php
        ?>
                                    <td><?php
                                        echo $history->name; ?></td><?php
        ?>
                                    <td></td><?php
        }
        ?></tr><?php
                            endforeach;
                        endif;
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
            echo HTMLHelper::_('uitab.endTab'); */?>


        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="option" value="com_jed"/>
    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="boxchecked" value="0"/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
