<?php

/**
 * @package JED
 *
 * @copyright (C) 2006-2026 Open Source Matters, Inc.  <https://www.joomla.org>
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Jed\Component\Jed\Administrator\Model;

// No direct access.
// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Exception;
use InvalidArgumentException;
use Jed\Component\Jed\Administrator\Helper\JedHelper;
use Jed\Component\Jed\Administrator\MediaHandling\ImageSize;
use Jed\Component\Jed\Administrator\Table\ExtensionHistoryTable;
use Jed\Component\Jed\Administrator\Table\ExtensionTable;
use Jed\Component\Jed\Administrator\Traits\ExtensionUtilities;
use Jed\Component\Jed\Site\Helper\JedscoreHelper;
use Jed\Component\Jed\Site\Model\ExtensionvarieddatumModel;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Users\Administrator\Table\NoteTable;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Michelf\Markdown;
use RuntimeException;
use stdClass;

use function defined;

/**
 * Extension model.
 *
 * @since 4.0.0
 */
class ExtensionModel extends AdminModel
{
    use ExtensionUtilities;

    /**
     * @var string  Alias to manage history control
     *
     * @since 4.0.0
     */
    public $typeAlias = 'com_jed.extension';

    /**
     * @var string  The prefix to use with controller messages.
     *
     * @since 4.0.0
     */
    protected $text_prefix = 'COM_JED';

    /**
     * @var stdClass  Item data
     *
     * @since 4.0.0
     */
    protected mixed $item;

    public function __construct(
        $config = [],
        ?MVCFactoryInterface $factory = null,
        ?FormFactoryInterface $formFactory = null
    ) {
        parent::__construct($config, $factory, $formFactory);
        $this->setUseExceptions(true);
    }

    protected function populateState()
    {
        parent::populateState();

        // Get the version ID of the record from the request.
        $version = Factory::getApplication()->getInput()->getInt('version');
        $this->setState($this->getName() . '.version', $version);
    }

    public function getItem($pk = null, $version = null)
    {
        $pk      = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');
        $version = (!empty($version)) ? $version : (int) $this->getState($this->getName() . '.version');
        $table   = $this->getTable('ExtensionHistory');

        if ($pk > 0) {
            // Attempt to load the row.
            if ($version) {
                $return = $table->load(['extension_id' => $pk, 'id' => $version]);
            } else {
                $return = $table->load(['extension_id' => $pk, 'active' => 1]);
            }

            // Check for a table object error.
            if ($return === false) {
                // If there was no underlying error, then the false means there simply was not a row in the db for this $pk.
                throw new Exception(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));
            }
        }

        // Convert to \stdClass before adding other data
        $properties = get_object_vars($table);
        $item       = ArrayHelper::toObject($properties);

        if (property_exists($item, 'params')) {
            $registry     = new Registry($item->params);
            $item->params = $registry->toArray();
        }

        $db           = $this->getDatabase();
        $mapId        = $item->extension_id ?: (int) $item->id;
        $catQuery     = $db->getQuery(true)
            ->select($db->quoteName('catid'))
            ->from($db->quoteName('#__jed_extension_category_map'))
            ->where($db->quoteName('extension_id') . ' = :eid')
            ->bind(':eid', $mapId, ParameterType::INTEGER);
        $item->catids = $db->setQuery($catQuery)->loadColumn() ?: [];

        return $item;
    }

    /**
     * Method to get the record form.
     *
     * @param array $data     An optional array of data for the form to interogate.
     * @param bool  $loadData True if the form is to load its own data (default case), false if not.
     *
     * @return Form|bool  A \JForm object on success, false on failure
     *
     * @since  4.0.0
     * @throws Exception
     */
    public function getForm($data = [], $loadData = true, $formname = 'jform'): Form|bool
    {
        // Get the form.
        $form = $this->loadForm('com_jed.extension.' . $formname, 'extension', ['control' => $formname, 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Load all images for an extension from #__jed_extension_images, ordered by ordering.
     *
     * @param int $extensionId The extension id to load images for.
     *
     * @return array
     *
     * @since 4.0.0
     */
    public function getImages(?int $extensionId = null): array
    {
        $extensionId      = (!empty($extensionId)) ? $extensionId : (int) $this->getState($this->getName() . '.id');

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__jed_extension_images'))
            ->where($db->quoteName('extension_id') . ' = :extensionId')
            ->bind(':extensionId', $extensionId, ParameterType::INTEGER)
            ->order($db->quoteName('ordering') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Load all history entries for an extension from #__jed_extensions_history.
     *
     * @param int $extensionId The extension id to load history for.
     *
     * @return array
     *
     * @since 4.0.0
     */
    public function getHistory(?int $extensionId = null): array
    {
        $extensionId = (!empty($extensionId)) ? $extensionId : (int) $this->getState($this->getName() . '.id');

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('h') . '.*')
            ->select($db->quoteName('u.name', 'editor_name'))
            ->from($db->quoteName('#__jed_extensions_history', 'h'))
            ->leftJoin(
                $db->quoteName('#__users', 'u')
                . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('h.modified_by')
            )
            ->where($db->quoteName('h.extension_id') . ' = :extensionId')
            ->bind(':extensionId', $extensionId, ParameterType::INTEGER)
            ->order($db->quoteName('h.id') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Set one history entry as active and deactivate all others for the extension.
     *
     * @param int $extensionId The extension PK in #__jed_extensions.
     * @param int $historyId   The history entry PK to activate.
     *
     * @return void
     *
     * @since 4.0.0
     */
    public function activateVersion(int $extensionId, int $historyId): void
    {
        $db = $this->getDatabase();

        $db->setQuery(
            $db->getQuery(true)
                ->update($db->quoteName('#__jed_extensions_history'))
                ->set($db->quoteName('active') . ' = 0')
                ->where($db->quoteName('extension_id') . ' = :eid')
                ->bind(':eid', $extensionId, ParameterType::INTEGER)
        )->execute();

        $db->setQuery(
            $db->getQuery(true)
                ->update($db->quoteName('#__jed_extensions_history'))
                ->set($db->quoteName('active') . ' = 1')
                ->where($db->quoteName('id') . ' = :id')
                ->where($db->quoteName('extension_id') . ' = :eid')
                ->bind(':id', $historyId, ParameterType::INTEGER)
                ->bind(':eid', $extensionId, ParameterType::INTEGER)
        )->execute();
    }

    /**
     * Load all review entries for an extension from #__jed_reviews.
     *
     * @param int $extensionId The extension id to load reviews for.
     *
     * @return array
     *
     * @since 4.0.0
     */
    public function getReviews(?int $extensionId = null): array
    {
        $extensionId      = (!empty($extensionId)) ? $extensionId : (int) $this->getState($this->getName() . '.id');

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__jed_reviews'))
            ->where($db->quoteName('extension_id') . ' = :extensionId')
            ->bind(':extensionId', $extensionId, ParameterType::INTEGER)
            ->order($db->quoteName('id') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param string $name    The table type to instantiate
     * @param string $prefix  A prefix for the table class name. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Table    A database object
     *
     * @since  4.0.0
     * @throws Exception
     */
    public function getTable($name = 'ExtensionHistory', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return mixed  The data for the form.
     *
     * @since  4.0.0
     * @throws Exception
     */
    protected function loadFormData(): mixed
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_jed.edit.extension.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Method to save the form data.
     *
     * @param array $data The form data.
     *
     * @return bool  True on success, False on error.
     *
     * @since 4.0.0
     *
     * @throws Exception
     */
    public function save($data): bool
    {
        // The URL id= parameter is always the extension PK in #__jed_extensions.
        $extensionId = (int) $this->getState($this->getName() . '.id');

        if (!$extensionId) {
            $this->setError(Text::_('COM_JED_EXTENSION_ID_MISSING'));
            return false;
        }

        // Force a new INSERT rather than an UPDATE of an existing history entry
        $data['id']           = 0;
        $data['extension_id'] = $extensionId;
        $data['active']       = 1;
        unset($data['created_on']); // ExtensionHistoryTable::bind() sets this for new rows

        $table = $this->getTable();

        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->check()) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        // Keep state pointing to the extension ID (not the new history entry's PK)
        $this->setState($this->getName() . '.id', $extensionId);

        return true;
    }

    /**
     * Get array of review scores for extension
     *
     * @param int $extension_id
     *
     * @return array
     *
     * @since 4.0.0
     */
    public function getScores(int $extension_id): array
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true);
        $query->select('*')->from($db->quoteName('#__jed_extension_scores'))->where($db->quoteName('extension_id') . ' = ' . $db->quote($extension_id));

        $db->setQuery($query);
        $result = $db->loadObjectList();
        $retval = [];
        foreach ($result as $r) {
            if ($r->supply_option_id == 1) {
                $supply = 'Free';
            } else {
                $supply = 'Paid';
            }
            $retval[$supply] = $r;
        }

        return $retval;
    }

    /**
     * Method to get Developer Information
     *
     * @return stdClass
     *
     * @since  4.0.0
     * @throws Exception
     */
    public function getDeveloperInfo(): stdClass
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)->select('e.id,d.developer_name,u.username,u.email')->from('#__jed_extensions as e')->join('INNER', '#__users as u ON u.id = e.created_by')->join('INNER', '#__jed_developers as d ON d.user_id=u.id')->where($db->quoteName('e.id') . ' = ' . $this->item->id);
        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Get the filename of the given extension ID.
     *
     * @param int $extensionId The extension ID to get the filename for
     *
     * @return stdClass  The extension file information.
     *
     * @since 4.0.0
     */
    public function getFilename(int $extensionId, $supply_option_id): stdClass
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)->select('*')->from($db->quoteName('#__jed_extensions_files'))->where($db->quoteName('extension_id') . ' = ' . $extensionId)->where($db->quoteName('supply_option_id') . ' = ' . $supply_option_id);
        $db->setQuery($query);

        $fileDetails = $db->loadObject();

        if ($fileDetails === null) {
            $fileDetails       = new stdClass();
            $fileDetails->file = '';
        }

        return $fileDetails;
    }

    /**
     * Method to save the approved state.
     *
     * @param array $data The form data.
     *
     * @return void
     *
     * @since 4.0.0
     *
     * @throws Exception
     */
    public function saveApprove(array $data): void
    {
        if (!$data['id']) {
            throw new InvalidArgumentException(
                Text::_('COM_JED_EXTENSION_ID_MISSING')
            );
        }

        $db          = $this->getDatabase();
        $extensionId = (int) $data['id'];

        /**
         *
         *
         * @var ExtensionTable $table
         */
        $table = $this->getTable('Extension');

        $table->load($extensionId);

        if (!$table->save($data)) {
            throw new RuntimeException('Save Failed');
        }

        $this->removeApprovedReason($extensionId);

        if (empty($data['approvedReason']) || (int) $data['approved'] !== 3) {
            return;
        }

        $query = $db->getQuery(true)->insert($db->quoteName('#__jed_extensions_approved_reasons'))->columns(
            $db->quoteName(
                [
                    'extension_id',
                    'reason',
                ]
            )
        );

        array_walk(
            $data['approvedReason'],
            static function ($reason) use (&$query, $db, $extensionId) {
                $query->values($extensionId . ',' . $db->quote($reason));
            }
        );

        $db->setQuery($query)->execute();
    }

    /**
     * Remove approved reasons.
     *
     * @param int $extensionId The extension ID to remove the approved reasons for
     *
     * @return void
     *
     * @since 4.0.0
     */
    private function removeApprovedReason(int $extensionId): void
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)->delete($db->quoteName('#__jed_extensions_approved_reasons'))->where($db->quoteName('extension_id') . ' = ' . $extensionId);
        $db->setQuery($query)->execute();
    }

    /**
     * Method to save the published state.
     *
     * @param array $data The form data.
     *
     * @return void
     *
     * @since  4.0.0
     * @throws Exception
     */
    public function savePublish(array $data): void
    {
        if (!$data['id']) {
            throw new InvalidArgumentException(
                Text::_('COM_JED_EXTENSION_ID_MISSING')
            );
        }

        $db          = $this->getDatabase();
        $extensionId = (int) $data['id'];

        /**
         * @var ExtensionTable $table
         */
        $table = $this->getTable('Extension');

        $table->load($extensionId);

        if (!$table->save($data)) {
            throw new RuntimeException('Save Failed');
        }

        $this->removePublishedReason($extensionId);

        if (empty($data['publishedReason']) || (int) $data['published'] === 1) {
            return;
        }

        $query = $db->getQuery(true)->insert($db->quoteName('#__jed_extensions_published_reasons'))->columns(
            $db->quoteName(
                [
                    'extension_id',
                    'reason',
                ]
            )
        );

        array_walk(
            $data['publishedReason'],
            static function ($reason) use (&$query, $db, $extensionId) {
                $query->values($extensionId . ',' . $db->quote($reason));
            }
        );

        $db->setQuery($query)->execute();
    }

    /**
     * Remove published reasons.
     *
     * @param int $extensionId The extension ID to remove the published reasons for
     *
     * @return void
     *
     * @since 4.0.0
     */
    private function removePublishedReason(int $extensionId): void
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)->delete($db->quoteName('#__jed_extensions_published_reasons'))->where($db->quoteName('extension_id') . ' = ' . $extensionId);
        $db->setQuery($query)->execute();
    }

    /**
     * Store used extension types for an extension.
     *
     * @param int   $extensionId The extension ID to save the types for
     * @param array $types       The extension types to store
     *
     * @return void
     *
     * @since 4.0.0
     */
    private function storeExtensionTypes(int $extensionId, array $types): void
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)->delete($db->quoteName('#__jed_extensions_types'))->where($db->quoteName('extension_id') . ' = ' . $extensionId);
        $db->setQuery($query)->execute();

        if (empty($types)) {
            return;
        }

        $query->clear()->insert($db->quoteName('#__jed_extensions_types'))->columns(
            $db->quoteName(
                [
                    'extension_id',
                    'type',
                ]
            )
        );

        array_walk(
            $types,
            static function ($type) use (&$query, $db, $extensionId) {
                $query->values($extensionId . ',' . $db->quote($type));
            }
        );

        $db->setQuery($query)->execute();
    }

    /**
     * Store the images for an extension.
     *
     * @param int   $extensionId The extension ID to save the images for
     * @param array $images      The extension types to store
     *
     * @return void
     *
     * @since 4.0.0
     */
    private function storeImages(int $extensionId, array $images): void
    {
        $db = $this->getDatabase();


        $query = $db->getQuery(true)->delete($db->quoteName('#__jed_extensions_images'))->where($db->quoteName('extension_id') . ' = ' . $extensionId);
        $db->setQuery($query)->execute();

        if (empty($images)) {
            return;
        }

        $query->clear()->insert($db->quoteName('#__jed_extensions_images'))->columns(
            $db->quoteName(
                [
                    'extension_id',
                    'filename',
                    'order',
                ]
            )
        );

        array_walk(
            $images,
            static function ($image, $key) use (&$query, $db, $extensionId) {
                $order = (int) str_replace('images', '', $key) + 1;
                $query->values(
                    $extensionId . ',' . $db->quote($image['image']) . ',' . $order
                );
            }
        );

        $db->setQuery($query)->execute();
    }

    /**
     * Store an internal note.
     *
     * @param string $body        The note content
     * @param int    $developerId The developer to store the note for
     * @param int    $userId      The JED member storing the note
     * @param int    $extensionId The extension ID the message is about
     *
     * @return void
     *
     * @since 4.0.0
     */
    public function storeNote(string $body, int $developerId, int $userId, int $extensionId): void
    {

        $developer = new User($developerId);

        if ($developer->id == 0) {
            throw new InvalidArgumentException(
                Text::_('COM_JED_DEVELOPER_NOT_FOUND')
            );
        }

        $noteTable = new NoteTable($this->getDatabase());
        $result    = $noteTable->save(
            [
                'extension_id'    => $extensionId,
                'body'            => $body,
                'developer_id'    => $developer->id,
                'developer_name'  => $developer->name,
                'developer_email' => $developer->email,
                'created'         => (Date::getInstance())->toSql(),
                'created_by'      => $userId,
            ]
        );

        if ($result === false) {
            throw new RuntimeException('Save Failed');
        }
    }

    /**
     * Store related categories for an extension.
     *
     * @param int   $extensionId        The extension ID to save the categories for
     * @param array $relatedCategoryIds The related category IDs to store
     *
     * @return void
     *
     * @since 4.0.0
     */
    private function storeRelatedCategories(
        int $extensionId,
        array $relatedCategoryIds
    ): void {
        $db = $this->getDatabase();


        $query = $db->getQuery(true)->delete($db->quoteName('#__jed_extensions_categories'))->where($db->quoteName('extension_id') . ' = ' . $extensionId);
        $db->setQuery($query)->execute();

        if (empty($relatedCategoryIds)) {
            return;
        }

        $relatedCategoryIds = array_slice($relatedCategoryIds, 0, 5);

        $query->clear()->insert($db->quoteName('#__jed_extensions_categories'))->columns(
            $db->quoteName(
                [
                    'extension_id',
                    'category_id',
                ]
            )
        );

        array_walk(
            $relatedCategoryIds,
            static function ($relatedCategoryId) use (&$query, $extensionId) {
                $query->values($extensionId . ',' . $relatedCategoryId);
            }
        );

        $db->setQuery($query)->execute();
    }

    /**
     * Store supported versions for an extension.
     *
     * @param int    $extensionId The extension ID to save the versions for
     * @param array  $versions    The versions to store
     * @param string $type        THe type of versions to store
     *
     * @return void
     *
     * @since 4.0.0
     */
    private function storeVersions(
        int $extensionId,
        array $versions,
        string $type
    ): void {
        $db = $this->getDatabase();


        $query = $db->getQuery(true)->delete($db->quoteName('#__jed_extensions_' . $type . '_versions'))->where($db->quoteName('extension_id') . ' = ' . $extensionId);
        $db->setQuery($query)->execute();

        if (empty($versions)) {
            return;
        }

        $query->clear()->insert($db->quoteName('#__jed_extensions_' . $type . '_versions'))->columns(
            $db->quoteName(
                [
                    'extension_id',
                    'version',
                ]
            )
        );

        array_walk(
            $versions,
            static function ($version) use (&$query, $db, $extensionId) {
                $query->values($extensionId . ',' . $db->quote($version));
            }
        );

        $db->setQuery($query)->execute();
    }
}
