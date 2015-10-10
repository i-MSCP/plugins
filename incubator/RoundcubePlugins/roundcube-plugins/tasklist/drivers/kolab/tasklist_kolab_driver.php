<?php

/**
 * Kolab Groupware driver for the Tasklist plugin
 *
 * @version @package_version@
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 *
 * Copyright (C) 2012-2015, Kolab Systems AG <contact@kolabsys.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class tasklist_kolab_driver extends tasklist_driver
{
    // features supported by the backend
    public $alarms      = false;
    public $attachments = true;
    public $attendees   = true;
    public $undelete    = false; // task undelete action
    public $alarm_types = array('DISPLAY','AUDIO');
    public $search_more_results;

    private $rc;
    private $plugin;
    private $lists;
    private $folders = array();
    private $tasks   = array();
    private $tags    = array();
    private $bonnie_api = false;


    /**
     * Default constructor
     */
    public function __construct($plugin)
    {
        $this->rc = $plugin->rc;
        $this->plugin = $plugin;

        if (kolab_storage::$version == '2.0') {
            $this->alarm_absolute = false;
        }

        // tasklist use fully encoded identifiers
        kolab_storage::$encode_ids = true;

        // get configuration for the Bonnie API
        $this->bonnie_api = libkolab::get_bonnie_api();

        $this->_read_lists();

        $this->plugin->register_action('folder-acl', array($this, 'folder_acl'));
    }

    /**
     * Read available calendars for the current user and store them internally
     */
    private function _read_lists($force = false)
    {
        // already read sources
        if (isset($this->lists) && !$force)
            return $this->lists;

        // get all folders that have type "task"
        $folders = kolab_storage::sort_folders(kolab_storage::get_folders('task'));
        $this->lists = $this->folders = array();

        $delim = $this->rc->get_storage()->get_hierarchy_delimiter();

        // find default folder
        $default_index = 0;
        foreach ($folders as $i => $folder) {
            if ($folder->default && strpos($folder->name, $delim) === false)
                $default_index = $i;
        }

        // put default folder (aka INBOX) on top of the list
        if ($default_index > 0) {
            $default_folder = $folders[$default_index];
            unset($folders[$default_index]);
            array_unshift($folders, $default_folder);
        }

        $prefs = $this->rc->config->get('kolab_tasklists', array());

        foreach ($folders as $folder) {
            $tasklist = $this->folder_props($folder, $prefs);

            $this->lists[$tasklist['id']] = $tasklist;
            $this->folders[$tasklist['id']] = $folder;
            $this->folders[$folder->name] = $folder;
        }
    }

    /**
     * Derive list properties from the given kolab_storage_folder object
     */
    protected function folder_props($folder, $prefs)
    {
        if ($folder->get_namespace() == 'personal') {
            $norename = false;
            $editable = true;
            $rights = 'lrswikxtea';
            $alarms = true;
        }
        else {
            $alarms = false;
            $rights = 'lr';
            $editable = false;
            if (($myrights = $folder->get_myrights()) && !PEAR::isError($myrights)) {
                $rights = $myrights;
                if (strpos($rights, 't') !== false || strpos($rights, 'd') !== false)
                    $editable = strpos($rights, 'i');
            }
            $info = $folder->get_folder_info();
            $norename = $readonly || $info['norename'] || $info['protected'];
        }

        $list_id = $folder->id; #kolab_storage::folder_id($folder->name);
        $old_id = kolab_storage::folder_id($folder->name, false);

        if (!isset($prefs[$list_id]['showalarms']) && isset($prefs[$old_id]['showalarms'])) {
            $prefs[$list_id]['showalarms'] = $prefs[$old_id]['showalarms'];
        }

        return array(
            'id' => $list_id,
            'name' => $folder->get_name(),
            'listname' => $folder->get_foldername(),
            'editname' => $folder->get_foldername(),
            'color' => $folder->get_color('0000CC'),
            'showalarms' => isset($prefs[$list_id]['showalarms']) ? $prefs[$list_id]['showalarms'] : $alarms,
            'editable' => $editable,
            'rights'    => $rights,
            'norename' => $norename,
            'active' => $folder->is_active(),
            'parentfolder' => $folder->get_parent(),
            'default' => $folder->default,
            'virtual' => $folder->virtual,
            'children' => true,  // TODO: determine if that folder indeed has child folders
            'subscribed' => (bool)$folder->is_subscribed(),
            'removable' => !$folder->default,
            'subtype'  => $folder->subtype,
            'group' => $folder->default ? 'default' : $folder->get_namespace(),
            'class' => trim($folder->get_namespace() . ($folder->default ? ' default' : '')),
            'caldavuid' => $folder->get_uid(),
            'history' => !empty($this->bonnie_api),
        );
    }

    /**
     * Get a list of available task lists from this source
     */
    public function get_lists(&$tree = null)
    {
        // attempt to create a default list for this user
        if (empty($this->lists) && !isset($this->search_more_results)) {
            $prop = array('name' => 'Tasks', 'color' => '0000CC', 'default' => true);
            if ($this->create_list($prop))
                $this->_read_lists(true);
        }

        $folders = array();
        foreach ($this->lists as $id => $list) {
            if (!empty($this->folders[$id])) {
                $folders[] = $this->folders[$id];
            }
        }

        // include virtual folders for a full folder tree
        if (!is_null($tree)) {
            $folders = kolab_storage::folder_hierarchy($folders, $tree);
        }

        $delim = $this->rc->get_storage()->get_hierarchy_delimiter();
        $prefs = $this->rc->config->get('kolab_tasklists', array());

        $lists = array();
        foreach ($folders as $folder) {
            $list_id   = $folder->id; // kolab_storage::folder_id($folder->name);
            $imap_path = explode($delim, $folder->name);

            // find parent
            do {
              array_pop($imap_path);
              $parent_id = kolab_storage::folder_id(join($delim, $imap_path));
            }
            while (count($imap_path) > 1 && !$this->folders[$parent_id]);

            // restore "real" parent ID
            if ($parent_id && !$this->folders[$parent_id]) {
                $parent_id = kolab_storage::folder_id($folder->get_parent());
            }

            $fullname = $folder->get_name();
            $listname = $folder->get_foldername();

            // special handling for virtual folders
            if ($folder instanceof kolab_storage_folder_user) {
                $lists[$list_id] = array(
                    'id'       => $list_id,
                    'name'     => $folder->get_name(),
                    'listname' => $listname,
                    'title'    => $folder->get_title(),
                    'virtual'  => true,
                    'editable' => false,
                    'rights'   => 'l',
                    'group'    => 'other virtual',
                    'class'    => 'user',
                    'parent'   => $parent_id,
                );
            }
            else if ($folder->virtual) {
                $lists[$list_id] = array(
                    'id'       => $list_id,
                    'name'     => kolab_storage::object_name($fullname),
                    'listname' => $listname,
                    'virtual'  => true,
                    'editable' => false,
                    'rights'   => 'l',
                    'group'    => $folder->get_namespace(),
                    'class'    => 'folder',
                    'parent'   => $parent_id,
                );
            }
            else {
                if (!$this->lists[$list_id]) {
                    $this->lists[$list_id] = $this->folder_props($folder, $prefs);
                    $this->folders[$list_id] = $folder;
                }
                $this->lists[$list_id]['parent'] = $parent_id;
                $lists[$list_id] = $this->lists[$list_id];
            }
        }

        return $lists;
    }

    /**
     * Get the kolab_calendar instance for the given calendar ID
     *
     * @param string List identifier (encoded imap folder name)
     * @return object kolab_storage_folder Object nor null if list doesn't exist
     */
    protected function get_folder($id)
    {
        // create list and folder instance if necesary
        if (!$this->lists[$id]) {
            $folder = kolab_storage::get_folder(kolab_storage::id_decode($id));
            if ($folder->type) {
                $this->folders[$id] = $folder;
                $this->lists[$id] = $this->folder_props($folder, $this->rc->config->get('kolab_tasklists', array()));
            }
        }

        return $this->folders[$id];
    }


    /**
     * Create a new list assigned to the current user
     *
     * @param array Hash array with list properties
     *        name: List name
     *       color: The color of the list
     *  showalarms: True if alarms are enabled
     * @return mixed ID of the new list on success, False on error
     */
    public function create_list(&$prop)
    {
        $prop['type'] = 'task' . ($prop['default'] ? '.default' : '');
        $prop['active'] = true; // activate folder by default
        $prop['subscribed'] = true;
        $folder = kolab_storage::folder_update($prop);

        if ($folder === false) {
            $this->last_error = kolab_storage::$last_error;
            return false;
        }

        // create ID
        $id = kolab_storage::folder_id($folder);

        $prefs['kolab_tasklists'] = $this->rc->config->get('kolab_tasklists', array());

        if (isset($prop['showalarms']))
            $prefs['kolab_tasklists'][$id]['showalarms'] = $prop['showalarms'] ? true : false;

        if ($prefs['kolab_tasklists'][$id])
            $this->rc->user->save_prefs($prefs);

        // force page reload to properly render folder hierarchy
        if (!empty($prop['parent'])) {
            $prop['_reload'] = true;
        }
        else {
            $folder = kolab_storage::get_folder($folder);
            $prop += $this->folder_props($folder, array());
        }

        return $id;
    }

    /**
     * Update properties of an existing tasklist
     *
     * @param array Hash array with list properties
     *          id: List Identifier
     *        name: List name
     *       color: The color of the list
     *  showalarms: True if alarms are enabled (if supported)
     * @return boolean True on success, Fales on failure
     */
    public function edit_list(&$prop)
    {
        if ($prop['id'] && ($folder = $this->get_folder($prop['id']))) {
            $prop['oldname'] = $folder->name;
            $prop['type'] = 'task';
            $newfolder = kolab_storage::folder_update($prop);

            if ($newfolder === false) {
                $this->last_error = kolab_storage::$last_error;
                return false;
            }

            // create ID
            $id = kolab_storage::folder_id($newfolder);

            // fallback to local prefs
            $prefs['kolab_tasklists'] = $this->rc->config->get('kolab_tasklists', array());
            unset($prefs['kolab_tasklists'][$prop['id']]);

            if (isset($prop['showalarms']))
                $prefs['kolab_tasklists'][$id]['showalarms'] = $prop['showalarms'] ? true : false;

            if ($prefs['kolab_tasklists'][$id])
                $this->rc->user->save_prefs($prefs);

            // force page reload if folder name/hierarchy changed
            if ($newfolder != $prop['oldname'])
                $prop['_reload'] = true;

            return $id;
        }

        return false;
    }

    /**
     * Set active/subscribed state of a list
     *
     * @param array Hash array with list properties
     *          id: List Identifier
     *      active: True if list is active, false if not
     *   permanent: True if list is to be subscribed permanently
     * @return boolean True on success, Fales on failure
     */
    public function subscribe_list($prop)
    {
        if ($prop['id'] && ($folder = $this->get_folder($prop['id']))) {
            $ret = false;
            if (isset($prop['permanent']))
                $ret |= $folder->subscribe(intval($prop['permanent']));
            if (isset($prop['active']))
                $ret |= $folder->activate(intval($prop['active']));

            // apply to child folders, too
            if ($prop['recursive']) {
                foreach ((array)kolab_storage::list_folders($folder->name, '*', 'task') as $subfolder) {
                    if (isset($prop['permanent']))
                        ($prop['permanent'] ? kolab_storage::folder_subscribe($subfolder) : kolab_storage::folder_unsubscribe($subfolder));
                    if (isset($prop['active']))
                        ($prop['active'] ? kolab_storage::folder_activate($subfolder) : kolab_storage::folder_deactivate($subfolder));
                }
            }
            return $ret;
        }
        return false;
    }

    /**
     * Delete the given list with all its contents
     *
     * @param array Hash array with list properties
     *      id: list Identifier
     * @return boolean True on success, Fales on failure
     */
    public function delete_list($prop)
    {
        if ($prop['id'] && ($folder = $this->get_folder($prop['id']))) {
          if (kolab_storage::folder_delete($folder->name))
              return true;
          else
              $this->last_error = kolab_storage::$last_error;
        }

        return false;
    }

    /**
     * Search for shared or otherwise not listed tasklists the user has access
     *
     * @param string Search string
     * @param string Section/source to search
     * @return array List of tasklists
     */
    public function search_lists($query, $source)
    {
        if (!kolab_storage::setup()) {
            return array();
        }

        $this->search_more_results = false;
        $this->lists = $this->folders = array();

        // find unsubscribed IMAP folders that have "event" type
        if ($source == 'folders') {
            foreach ((array)kolab_storage::search_folders('task', $query, array('other')) as $folder) {
                $this->folders[$folder->id] = $folder;
                $this->lists[$folder->id] = $this->folder_props($folder, array());
            }
        }
        // search other user's namespace via LDAP
        else if ($source == 'users') {
            $limit = $this->rc->config->get('autocomplete_max', 15) * 2;  // we have slightly more space, so display twice the number
            foreach (kolab_storage::search_users($query, 0, array(), $limit * 10) as $user) {
                $folders = array();
                // search for tasks folders shared by this user
                foreach (kolab_storage::list_user_folders($user, 'task', false) as $foldername) {
                    $folders[] = new kolab_storage_folder($foldername, 'task');
                }

                if (count($folders)) {
                    $userfolder = new kolab_storage_folder_user($user['kolabtargetfolder'], '', $user);
                    $this->folders[$userfolder->id] = $userfolder;
                    $this->lists[$userfolder->id] = $this->folder_props($userfolder, array());

                    foreach ($folders as $folder) {
                        $this->folders[$folder->id] = $folder;
                        $this->lists[$folder->id] = $this->folder_props($folder, array());
                        $count++;
                    }
                }

                if ($count >= $limit) {
                    $this->search_more_results = true;
                    break;
                }
            }
        }

        return $this->get_lists();
    }

    /**
     * Get a list of tags to assign tasks to
     *
     * @return array List of tags
     */
    public function get_tags()
    {
        $config = kolab_storage_config::get_instance();
        $tags   = $config->get_tags();
        $backend_tags = array_map(function($v) { return $v['name']; }, $tags);

        return array_values(array_unique(array_merge($this->tags, $backend_tags)));
    }

    /**
     * Get number of tasks matching the given filter
     *
     * @param array List of lists to count tasks of
     * @return array Hash array with counts grouped by status (all|flagged|completed|today|tomorrow|nodate)
     */
    public function count_tasks($lists = null)
    {
        if (empty($lists))
            $lists = array_keys($this->lists);
        else if (is_string($lists))
            $lists = explode(',', $lists);

        $today_date = new DateTime('now', $this->plugin->timezone);
        $today = $today_date->format('Y-m-d');
        $tomorrow_date = new DateTime('now + 1 day', $this->plugin->timezone);
        $tomorrow = $tomorrow_date->format('Y-m-d');

        $counts = array('all' => 0, 'flagged' => 0, 'today' => 0, 'tomorrow' => 0, 'overdue' => 0, 'nodate' => 0, 'mytasks' => 0);
        foreach ($lists as $list_id) {
            if (!$folder = $this->get_folder($list_id)) {
                continue;
            }
            foreach ($folder->select(array(array('tags','!~','x-complete'))) as $record) {
                $rec = $this->_to_rcube_task($record, $list_id, false);

                if ($this->is_complete($rec))  // don't count complete tasks
                    continue;

                $counts['all']++;
                if ($rec['flagged'])
                    $counts['flagged']++;
                if (empty($rec['date']))
                    $counts['nodate']++;
                else if ($rec['date'] == $today)
                    $counts['today']++;
                else if ($rec['date'] == $tomorrow)
                    $counts['tomorrow']++;
                else if ($rec['date'] < $today)
                    $counts['overdue']++;
                if ($this->plugin->is_attendee($rec) !== false)
                    $counts['mytasks']++;
            }
        }

        // avoid session race conditions that will loose temporary subscriptions
        $this->plugin->rc->session->nowrite = true;

        return $counts;
    }

    /**
     * Get all taks records matching the given filter
     *
     * @param array Hash array with filter criterias:
     *  - mask:  Bitmask representing the filter selection (check against tasklist::FILTER_MASK_* constants)
     *  - from:  Date range start as string (Y-m-d)
     *  - to:    Date range end as string (Y-m-d)
     *  - search: Search query string
     * @param array List of lists to get tasks from
     * @return array List of tasks records matchin the criteria
     */
    public function list_tasks($filter, $lists = null)
    {
        if (empty($lists))
            $lists = array_keys($this->lists);
        else if (is_string($lists))
            $lists = explode(',', $lists);

        $results = array();

        // query Kolab storage
        $query = array();
        if ($filter['mask'] & tasklist::FILTER_MASK_COMPLETE)
            $query[] = array('tags','~','x-complete');
        else if (empty($filter['since']))
            $query[] = array('tags','!~','x-complete');

        // full text search (only works with cache enabled)
        if ($filter['search']) {
            $search = mb_strtolower($filter['search']);
            foreach (rcube_utils::normalize_string($search, true) as $word) {
                $query[] = array('words', '~', $word);
            }
        }

        if ($filter['since']) {
            $query[] = array('changed', '>=', $filter['since']);
        }

        // load all tags into memory first
        kolab_storage_config::get_instance()->get_tags();

        foreach ($lists as $list_id) {
            if (!$folder = $this->get_folder($list_id)) {
                continue;
            }
            foreach ($folder->select($query) as $record) {
                $this->load_tags($record);
                $task = $this->_to_rcube_task($record, $list_id);

                // TODO: post-filter tasks returned from storage

                $results[] = $task;
            }
        }

        // avoid session race conditions that will loose temporary subscriptions
        $this->plugin->rc->session->nowrite = true;

        return $results;
    }

    /**
     * Return data of a specific task
     *
     * @param mixed  Hash array with task properties or task UID
     * @return array Hash array with task properties or false if not found
     */
    public function get_task($prop)
    {
        $this->_parse_id($prop);
        $id      = $prop['uid'];
        $list_id = $prop['list'];
        $folders = $list_id ? array($list_id => $this->get_folder($list_id)) : $this->folders;

        // find task in the available folders
        foreach ($folders as $list_id => $folder) {
            if (is_numeric($list_id) || !$folder)
                continue;
            if (!$this->tasks[$id] && ($object = $folder->get_object($id))) {
                $this->load_tags($object);
                $this->tasks[$id] = $this->_to_rcube_task($object, $list_id);
                break;
            }
        }

        return $this->tasks[$id];
    }

    /**
     * Get all decendents of the given task record
     *
     * @param mixed  Hash array with task properties or task UID
     * @param boolean True if all childrens children should be fetched
     * @return array List of all child task IDs
     */
    public function get_childs($prop, $recursive = false)
    {
        if (is_string($prop)) {
            $task = $this->get_task($prop);
            $prop = array('uid' => $task['uid'], 'list' => $task['list']);
        }
        else {
            $this->_parse_id($prop);
        }

        $childs = array();
        $list_id = $prop['list'];
        $task_ids = array($prop['uid']);
        $folder = $this->get_folder($list_id);

        // query for childs (recursively)
        while ($folder && !empty($task_ids)) {
            $query_ids = array();
            foreach ($task_ids as $task_id) {
                $query = array(array('tags','=','x-parent:' . $task_id));
                foreach ($folder->select($query) as $record) {
                    // don't rely on kolab_storage_folder filtering
                    if ($record['parent_id'] == $task_id) {
                        $childs[] = $list_id . ':' . $record['uid'];
                        $query_ids[] = $record['uid'];
                    }
                }
            }

            if (!$recursive)
                break;

            $task_ids = $query_ids;
        }

        return $childs;
    }

    /**
     * Provide a list of revisions for the given task
     *
     * @param array  $task Hash array with task properties
     * @return array List of changes, each as a hash array
     * @see tasklist_driver::get_task_changelog()
     */
    public function get_task_changelog($prop)
    {
        if (empty($this->bonnie_api)) {
            return false;
        }

        list($uid, $mailbox, $msguid) = $this->_resolve_task_identity($prop);

        $result = $uid && $mailbox ? $this->bonnie_api->changelog('task', $uid, $mailbox, $msguid) : null;
        if (is_array($result) && $result['uid'] == $uid) {
            return $result['changes'];
        }

        return false;
    }

    /**
     * Return full data of a specific revision of an event
     *
     * @param mixed  $task UID string or hash array with task properties
     * @param mixed  $rev Revision number
     *
     * @return array Task object as hash array
     * @see tasklist_driver::get_task_revision()
     */
    public function get_task_revison($prop, $rev)
    {
        if (empty($this->bonnie_api)) {
            return false;
        }

        $this->_parse_id($prop);
        $uid     = $prop['uid'];
        $list_id = $prop['list'];
        list($uid, $mailbox, $msguid) = $this->_resolve_task_identity($prop);

        // call Bonnie API
        $result = $this->bonnie_api->get('task', $uid, $rev, $mailbox, $msguid);
        if (is_array($result) && $result['uid'] == $uid && !empty($result['xml'])) {
            $format = kolab_format::factory('task');
            $format->load($result['xml']);
            $rec = $format->to_array();
            $format->get_attachments($rec, true);

            if ($format->is_valid()) {
                $rec = self::_to_rcube_task($rec, $list_id, false);
                $rec['rev'] = $result['rev'];
                return $rec;
            }
        }

        return false;
    }

    /**
     * Command the backend to restore a certain revision of a task.
     * This shall replace the current object with an older version.
     *
     * @param mixed  $task UID string or hash array with task properties
     * @param mixed  $rev Revision number
     *
     * @return boolean True on success, False on failure
     * @see tasklist_driver::restore_task_revision()
     */
    public function restore_task_revision($prop, $rev)
    {
        if (empty($this->bonnie_api)) {
            return false;
        }

        $this->_parse_id($prop);
        $uid     = $prop['uid'];
        $list_id = $prop['list'];
        list($uid, $mailbox, $msguid) = $this->_resolve_task_identity($prop);

        $folder = $this->get_folder($list_id);
        $success = false;

        if ($folder && ($raw_msg = $this->bonnie_api->rawdata('task', $uid, $rev, $mailbox))) {
            $imap = $this->rc->get_storage();

            // insert $raw_msg as new message
            if ($imap->save_message($folder->name, $raw_msg, null, false)) {
                $success = true;

                // delete old revision from imap and cache
                $imap->delete_message($msguid, $folder->name);
                $folder->cache->set($msguid, false);
            }
        }

        return $success;
    }

    /**
     * Get a list of property changes beteen two revisions of a task object
     *
     * @param array  $task Hash array with task properties
     * @param mixed  $rev   Revisions: "from:to"
     *
     * @return array List of property changes, each as a hash array
     * @see tasklist_driver::get_task_diff()
     */
    public function get_task_diff($prop, $rev1, $rev2)
    {
        $this->_parse_id($prop);
        $uid     = $prop['uid'];
        $list_id = $prop['list'];
        list($uid, $mailbox, $msguid) = $this->_resolve_task_identity($prop);

        // call Bonnie API
        $result = $this->bonnie_api->diff('task', $uid, $rev1, $rev2, $mailbox, $msguid, $instance_id);
        if (is_array($result) && $result['uid'] == $uid) {
            $result['rev1'] = $rev1;
            $result['rev2'] = $rev2;

            $keymap = array(
                'start'    => 'start',
                'due'      => 'date',
                'dstamp'   => 'changed',
                'summary'  => 'title',
                'alarm'    => 'alarms',
                'attendee' => 'attendees',
                'attach'   => 'attachments',
                'rrule'    => 'recurrence',
                'related-to' => 'parent_id',
                'percent-complete' => 'complete',
                'lastmodified-date' => 'changed',
            );
            $prop_keymaps = array(
                'attachments' => array('fmttype' => 'mimetype', 'label' => 'name'),
                'attendees'   => array('partstat' => 'status'),
            );
            $special_changes = array();

            // map kolab event properties to keys the client expects
            array_walk($result['changes'], function(&$change, $i) use ($keymap, $prop_keymaps, $special_changes) {
                if (array_key_exists($change['property'], $keymap)) {
                    $change['property'] = $keymap[$change['property']];
                }
                if ($change['property'] == 'priority') {
                    $change['property'] = 'flagged';
                    $change['old'] = $change['old'] == 1 ? $this->plugin->gettext('yes') : null;
                    $change['new'] = $change['new'] == 1 ? $this->plugin->gettext('yes') : null;
                }
                // map alarms trigger value
                if ($change['property'] == 'alarms') {
                    if (is_array($change['old']) && is_array($change['old']['trigger']))
                        $change['old']['trigger'] = $change['old']['trigger']['value'];
                    if (is_array($change['new']) && is_array($change['new']['trigger']))
                        $change['new']['trigger'] = $change['new']['trigger']['value'];
                }
                // make all property keys uppercase
                if ($change['property'] == 'recurrence') {
                    $special_changes['recurrence'] = $i;
                    foreach (array('old','new') as $m) {
                        if (is_array($change[$m])) {
                            $props = array();
                            foreach ($change[$m] as $k => $v) {
                                $props[strtoupper($k)] = $v;
                            }
                            $change[$m] = $props;
                        }
                    }
                }
                // map property keys names
                if (is_array($prop_keymaps[$change['property']])) {
                  foreach ($prop_keymaps[$change['property']] as $k => $dest) {
                    if (is_array($change['old']) && array_key_exists($k, $change['old'])) {
                        $change['old'][$dest] = $change['old'][$k];
                        unset($change['old'][$k]);
                    }
                    if (is_array($change['new']) && array_key_exists($k, $change['new'])) {
                        $change['new'][$dest] = $change['new'][$k];
                        unset($change['new'][$k]);
                    }
                  }
                }

                if ($change['property'] == 'exdate') {
                    $special_changes['exdate'] = $i;
                }
                else if ($change['property'] == 'rdate') {
                    $special_changes['rdate'] = $i;
                }
            });

            // merge some recurrence changes
            foreach (array('exdate','rdate') as $prop) {
                if (array_key_exists($prop, $special_changes)) {
                    $exdate = $result['changes'][$special_changes[$prop]];
                    if (array_key_exists('recurrence', $special_changes)) {
                        $recurrence = &$result['changes'][$special_changes['recurrence']];
                    }
                    else {
                        $i = count($result['changes']);
                        $result['changes'][$i] = array('property' => 'recurrence', 'old' => array(), 'new' => array());
                        $recurrence = &$result['changes'][$i]['recurrence'];
                    }
                    $key = strtoupper($prop);
                    $recurrence['old'][$key] = $exdate['old'];
                    $recurrence['new'][$key] = $exdate['new'];
                    unset($result['changes'][$special_changes[$prop]]);
                }
            }

            return $result;
        }

        return false;
    }

    /**
     * Helper method to resolved the given task identifier into uid and folder
     *
     * @return array (uid,folder,msguid) tuple
     */
    private function _resolve_task_identity($prop)
    {
        $mailbox = $msguid = null;

        $this->_parse_id($prop);
        $uid     = $prop['uid'];
        $list_id = $prop['list'];

        if ($folder = $this->get_folder($list_id)) {
            $mailbox = $folder->get_mailbox_id();

            // get task object from storage in order to get the real object uid an msguid
            if ($rec = $folder->get_object($uid)) {
                $msguid = $rec['_msguid'];
                $uid = $rec['uid'];
            }
        }

        return array($uid, $mailbox, $msguid);
    }


    /**
     * Get a list of pending alarms to be displayed to the user
     *
     * @param  integer Current time (unix timestamp)
     * @param  mixed   List of list IDs to show alarms for (either as array or comma-separated string)
     * @return array   A list of alarms, each encoded as hash array with task properties
     * @see tasklist_driver::pending_alarms()
     */
    public function pending_alarms($time, $lists = null)
    {
        $interval = 300;
        $time -= $time % 60;

        $slot = $time;
        $slot -= $slot % $interval;

        $last = $time - max(60, $this->rc->config->get('refresh_interval', 0));
        $last -= $last % $interval;

        // only check for alerts once in 5 minutes
        if ($last == $slot)
            return array();

        if ($lists && is_string($lists))
            $lists = explode(',', $lists);

        $time = $slot + $interval;

        $candidates = array();
        $query = array(array('tags', '=', 'x-has-alarms'), array('tags', '!=', 'x-complete'));
        foreach ($this->lists as $lid => $list) {
            // skip lists with alarms disabled
            if (!$list['showalarms'] || ($lists && !in_array($lid, $lists)))
                continue;

            $folder = $this->get_folder($lid);
            foreach ($folder->select($query) as $record) {
                if (!($record['valarms'] || $record['alarms']) || $record['status'] == 'COMPLETED' || $record['complete'] == 100)  // don't trust query :-)
                    continue;

                $task = $this->_to_rcube_task($record, $lid, false);

                // add to list if alarm is set
                $alarm = libcalendaring::get_next_alarm($task, 'task');
                if ($alarm && $alarm['time'] && $alarm['time'] <= $time && in_array($alarm['action'], $this->alarm_types)) {
                    $id = $alarm['id'];  // use alarm-id as primary identifier
                    $candidates[$id] = array(
                        'id'       => $id,
                        'title'    => $task['title'],
                        'date'     => $task['date'],
                        'time'     => $task['time'],
                        'notifyat' => $alarm['time'],
                        'action'   => $alarm['action'],
                    );
                }
            }
        }

        // get alarm information stored in local database
        if (!empty($candidates)) {
            $alarm_ids = array_map(array($this->rc->db, 'quote'), array_keys($candidates));
            $result = $this->rc->db->query("SELECT *"
                . " FROM " . $this->rc->db->table_name('kolab_alarms', true)
                . " WHERE `alarm_id` IN (" . join(',', $alarm_ids) . ")"
                    . " AND `user_id` = ?",
                $this->rc->user->ID
            );

            while ($result && ($rec = $this->rc->db->fetch_assoc($result))) {
                $dbdata[$rec['alarm_id']] = $rec;
            }
        }

        $alarms = array();
        foreach ($candidates as $id => $task) {
          // skip dismissed
          if ($dbdata[$id]['dismissed'])
              continue;

          // snooze function may have shifted alarm time
          $notifyat = $dbdata[$id]['notifyat'] ? strtotime($dbdata[$id]['notifyat']) : $task['notifyat'];
          if ($notifyat <= $time)
              $alarms[] = $task;
        }

        return $alarms;
    }

    /**
     * (User) feedback after showing an alarm notification
     * This should mark the alarm as 'shown' or snooze it for the given amount of time
     *
     * @param  string  Task identifier
     * @param  integer Suspend the alarm for this number of seconds
     */
    public function dismiss_alarm($id, $snooze = 0)
    {
        // delete old alarm entry
        $this->rc->db->query(
            "DELETE FROM " . $this->rc->db->table_name('kolab_alarms', true) . "
             WHERE `alarm_id` = ? AND `user_id` = ?",
            $id,
            $this->rc->user->ID
        );

        // set new notifyat time or unset if not snoozed
        $notifyat = $snooze > 0 ? date('Y-m-d H:i:s', time() + $snooze) : null;

        $query = $this->rc->db->query(
            "INSERT INTO " . $this->rc->db->table_name('kolab_alarms', true) . "
             (`alarm_id`, `user_id`, `dismissed`, `notifyat`)
             VALUES (?, ?, ?, ?)",
            $id,
            $this->rc->user->ID,
            $snooze > 0 ? 0 : 1,
            $notifyat
        );

        return $this->rc->db->affected_rows($query);
    }

    /**
     * Remove alarm dismissal or snooze state
     *
     * @param  string  Task identifier
     */
    public function clear_alarms($id)
    {
        // delete alarm entry
        $this->rc->db->query(
            "DELETE FROM " . $this->rc->db->table_name('kolab_alarms', true) . "
             WHERE `alarm_id` = ? AND `user_id` = ?",
            $id,
            $this->rc->user->ID
        );

        return true;
    }

    /**
     * Get task tags
     */
    private function load_tags(&$object)
    {
        // this task hasn't been migrated yet
        if (!empty($object['categories'])) {
            // OPTIONAL: call kolab_storage_config::apply_tags() to migrate the object
            $object['tags'] = (array)$object['categories'];
            if (!empty($object['tags'])) {
                $this->tags = array_merge($this->tags, $object['tags']);
            }
        }
        else {
            $config = kolab_storage_config::get_instance();
            $tags   = $config->get_tags($object['uid']);
            $object['tags'] = array_map(function($v) { return $v['name']; }, $tags);
        }
    }

    /**
     * Update task tags
     */
    private function save_tags($uid, $tags)
    {
        $config = kolab_storage_config::get_instance();
        $config->save_tags($uid, $tags);
    }

    /**
     * Find messages linked with a task record
     */
    private function get_links($uid)
    {
        $config = kolab_storage_config::get_instance();
        return $config->get_object_links($uid);
    }

    /**
     *
     */
    private function save_links($uid, $links)
    {
        // make sure we have a valid array
        if (empty($links)) {
            $links = array();
        }

        $config = kolab_storage_config::get_instance();
        $remove = array_diff($config->get_object_links($uid), $links);
        return $config->save_object_links($uid, $links, $remove);
    }

    /**
     * Extract uid + list identifiers from the given input
     *
     * @param mixed array or string with task identifier(s)
     */
    private function _parse_id(&$prop)
    {
        $id_ = null;
        if (is_array($prop)) {
            // 'uid' + 'list' available, nothing to be done
            if (!empty($prop['uid']) && !empty($prop['list'])) {
                return;
            }

            // 'id' is given
            if (!empty($prop['id'])) {
                if (!empty($prop['list'])) {
                    $list_id = $prop['_fromlist'] ?: $prop['list'];
                    if (strpos($prop['id'], $list_id.':') === 0) {
                        $prop['uid'] = substr($prop['id'], strlen($list_id)+1);
                    }
                    else {
                        $prop['uid'] = $prop['id'];
                    }
                }
                else {
                    $id_ = $prop['id'];
                }
            }
        }
        else {
            $id_ = strval($prop);
            $prop = array();
        }

        // split 'id' into list + uid
        if (!empty($id_)) {
            list($list, $uid) = explode(':', $id_, 2);
            if (!empty($uid)) {
                $prop['uid'] = $uid;
                $prop['list'] = $list;
            }
            else {
                $prop['uid'] = $id_;
            }
        }
    }

    /**
     * Convert from Kolab_Format to internal representation
     */
    private function _to_rcube_task($record, $list_id, $all = true)
    {
        $id_prefix = $list_id . ':';
        $task = array(
            'id' => $id_prefix . $record['uid'],
            'uid' => $record['uid'],
            'title' => $record['title'],
//            'location' => $record['location'],
            'description' => $record['description'],
            'flagged' => $record['priority'] == 1,
            'complete' => floatval($record['complete'] / 100),
            'status' => $record['status'],
            'parent_id' => $record['parent_id'] ? $id_prefix . $record['parent_id'] : null,
            'recurrence' => $record['recurrence'],
            'attendees' => $record['attendees'],
            'organizer' => $record['organizer'],
            'sequence' => $record['sequence'],
            'tags' => $record['tags'],
            'list' => $list_id,
        );

        // we can sometimes skip this expensive operation
        if ($all) {
            $task['links'] = $this->get_links($task['uid']);
        }

        // convert from DateTime to internal date format
        if (is_a($record['due'], 'DateTime')) {
            $due = $this->plugin->lib->adjust_timezone($record['due']);
            $task['date'] = $due->format('Y-m-d');
            if (!$record['due']->_dateonly)
                $task['time'] = $due->format('H:i');
        }
        // convert from DateTime to internal date format
        if (is_a($record['start'], 'DateTime')) {
            $start = $this->plugin->lib->adjust_timezone($record['start']);
            $task['startdate'] = $start->format('Y-m-d');
            if (!$record['start']->_dateonly)
                $task['starttime'] = $start->format('H:i');
        }
        if (is_a($record['changed'], 'DateTime')) {
            $task['changed'] = $record['changed'];
        }
        if (is_a($record['created'], 'DateTime')) {
            $task['created'] = $record['created'];
        }

        if ($record['valarms']) {
            $task['valarms'] = $record['valarms'];
        }
        else if ($record['alarms']) {
            $task['alarms'] = $record['alarms'];
        }

        if (!empty($task['attendees'])) {
            foreach ((array)$task['attendees'] as $i => $attendee) {
                if (is_array($attendee['delegated-from'])) {
                    $task['attendees'][$i]['delegated-from'] = join(', ', $attendee['delegated-from']);
                }
                if (is_array($attendee['delegated-to'])) {
                    $task['attendees'][$i]['delegated-to'] = join(', ', $attendee['delegated-to']);
                }
            }
        }

        if (!empty($record['_attachments'])) {
            foreach ($record['_attachments'] as $key => $attachment) {
                if ($attachment !== false) {
                    if (!$attachment['name'])
                        $attachment['name'] = $key;
                    $attachments[] = $attachment;
                }
            }

            $task['attachments'] = $attachments;
        }

        return $task;
    }

    /**
     * Convert the given task record into a data structure that can be passed to kolab_storage backend for saving
     * (opposite of self::_to_rcube_event())
     */
    private function _from_rcube_task($task, $old = array())
    {
        $object = $task;
        $id_prefix = $task['list'] . ':';

        if (!empty($task['date'])) {
            $object['due'] = rcube_utils::anytodatetime($task['date'].' '.$task['time'], $this->plugin->timezone);
            if (empty($task['time']))
                $object['due']->_dateonly = true;
            unset($object['date']);
        }

        if (!empty($task['startdate'])) {
            $object['start'] = rcube_utils::anytodatetime($task['startdate'].' '.$task['starttime'], $this->plugin->timezone);
            if (empty($task['starttime']))
                $object['start']->_dateonly = true;
            unset($object['startdate']);
        }

        // as per RFC (and the Kolab schema validation), start and due dates need to be of the same type (#3614)
        // this should be catched in the client already but just make sure we don't write invalid objects
        if (!empty($object['start']) && !empty($object['due']) && $object['due']->_dateonly != $object['start']->_dateonly) {
            $object['start']->_dateonly = true;
            $object['due']->_dateonly = true;
        }

        $object['complete'] = $task['complete'] * 100;
        if ($task['complete'] == 1.0 && empty($task['complete']))
            $object['status'] = 'COMPLETED';

        if ($task['flagged'])
            $object['priority'] = 1;
        else
            $object['priority'] = $old['priority'] > 1 ? $old['priority'] : 0;

        // remove list: prefix from parent_id
        if (!empty($task['parent_id']) && strpos($task['parent_id'], $id_prefix) === 0) {
            $object['parent_id'] = substr($task['parent_id'], strlen($id_prefix));
        }

        // copy meta data (starting with _) from old object
        foreach ((array)$old as $key => $val) {
            if (!isset($object[$key]) && $key[0] == '_')
                $object[$key] = $val;
        }

        // copy recurrence rules if the client didn't submit it (#2713)
        if (!array_key_exists('recurrence', $object) && $old['recurrence']) {
            $object['recurrence'] = $old['recurrence'];
        }

        // delete existing attachment(s)
        if (!empty($task['deleted_attachments'])) {
            foreach ($task['deleted_attachments'] as $attachment) {
                if (is_array($object['_attachments'])) {
                    foreach ($object['_attachments'] as $idx => $att) {
                        if ($att['id'] == $attachment)
                            $object['_attachments'][$idx] = false;
                    }
                }
            }
            unset($task['deleted_attachments']);
        }

        // in kolab_storage attachments are indexed by content-id
        if (is_array($task['attachments'])) {
            foreach ($task['attachments'] as $idx => $attachment) {
                $key = null;
                // Roundcube ID has nothing to do with the storage ID, remove it
                if ($attachment['content'] || $attachment['path']) {
                    unset($attachment['id']);
                }
                else {
                    foreach ((array)$old['_attachments'] as $cid => $oldatt) {
                        if ($oldatt && $attachment['id'] == $oldatt['id'])
                            $key = $cid;
                    }
                }

                // replace existing entry
                if ($key) {
                    $object['_attachments'][$key] = $attachment;
                }
                // append as new attachment
                else {
                    $object['_attachments'][] = $attachment;
                }
            }

            unset($object['attachments']);
        }

        // allow sequence increments if I'm the organizer
        if ($this->plugin->is_organizer($object) && empty($object['_method'])) {
            unset($object['sequence']);
        }
        else if (isset($old['sequence']) && empty($object['_method'])) {
            $object['sequence'] = $old['sequence'];
        }

        unset($object['tempid'], $object['raw'], $object['list'], $object['flagged'], $object['tags'], $object['created']);
        return $object;
    }

    /**
     * Add a single task to the database
     *
     * @param array Hash array with task properties (see header of tasklist_driver.php)
     * @return mixed New task ID on success, False on error
     */
    public function create_task($task)
    {
        return $this->edit_task($task);
    }

    /**
     * Update an task entry with the given data
     *
     * @param array Hash array with task properties (see header of tasklist_driver.php)
     * @return boolean True on success, False on error
     */
    public function edit_task($task)
    {
        $this->_parse_id($task);
        $list_id = $task['list'];
        if (!$list_id || !($folder = $this->get_folder($list_id))) {
            rcube::raise_error(array(
                'code' => 600, 'type' => 'php',
                'file' => __FILE__, 'line' => __LINE__,
                'message' => "Invalid list identifer to save taks: " . var_dump($list_id, true)),
                true, false);
            return false;
        }

        // email links and tags are stored separately
        $links = $task['links'];
        $tags = $task['tags'];
        unset($task['tags'], $task['links']);

        // moved from another folder
        if ($task['_fromlist'] && ($fromfolder = $this->get_folder($task['_fromlist']))) {
            if (!$fromfolder->move($task['uid'], $folder))
                return false;

            unset($task['_fromlist']);
        }

        // load previous version of this task to merge
        if ($task['id']) {
            $old = $folder->get_object($task['uid']);
            if (!$old || PEAR::isError($old))
                return false;

            // merge existing properties if the update isn't complete
            if (!isset($task['title']) || !isset($task['complete']))
                $task += $this->_to_rcube_task($old, $list_id);
        }

        // generate new task object from RC input
        $object = $this->_from_rcube_task($task, $old);
        $saved  = $folder->save($object, 'task', $task['uid']);

        if (!$saved) {
            rcube::raise_error(array(
                'code' => 600, 'type' => 'php',
                'file' => __FILE__, 'line' => __LINE__,
                'message' => "Error saving task object to Kolab server"),
                true, false);
            $saved = false;
        }
        else {
            // save links in configuration.relation object
            $this->save_links($object['uid'], $links);
            // save tags in configuration.relation object
            $this->save_tags($object['uid'], $tags);

            $task = $this->_to_rcube_task($object, $list_id);
            $task['tags'] = (array) $tags;
            $this->tasks[$task['uid']] = $task;
        }

        return $saved;
    }

    /**
     * Move a single task to another list
     *
     * @param array   Hash array with task properties:
     * @return boolean True on success, False on error
     * @see tasklist_driver::move_task()
     */
    public function move_task($task)
    {
        $this->_parse_id($task);
        $list_id = $task['list'];
        if (!$list_id || !($folder = $this->get_folder($list_id)))
            return false;

        // execute move command
        if ($task['_fromlist'] && ($fromfolder = $this->get_folder($task['_fromlist']))) {
            return $fromfolder->move($task['uid'], $folder);
        }

        return false;
    }

    /**
     * Remove a single task from the database
     *
     * @param array   Hash array with task properties:
     *      id: Task identifier
     * @param boolean Remove record irreversible (mark as deleted otherwise, if supported by the backend)
     * @return boolean True on success, False on error
     */
    public function delete_task($task, $force = true)
    {
        $this->_parse_id($task);
        $list_id = $task['list'];
        if (!$list_id || !($folder = $this->get_folder($list_id)))
            return false;

        $status = $folder->delete($task['uid']);

        if ($status) {
            // remove tag assignments
            // @TODO: don't do this when undelete feature will be implemented
            $this->save_tags($task['uid'], null);
        }

        return $status;
    }

    /**
     * Restores a single deleted task (if supported)
     *
     * @param array Hash array with task properties:
     *      id: Task identifier
     * @return boolean True on success, False on error
     */
    public function undelete_task($prop)
    {
        // TODO: implement this
        return false;
    }


    /**
     * Get attachment properties
     *
     * @param string $id    Attachment identifier
     * @param array  $task  Hash array with event properties:
     *         id: Task identifier
     *       list: List identifier
     *        rev: Revision (optional)
     *
     * @return array Hash array with attachment properties:
     *         id: Attachment identifier
     *       name: Attachment name
     *   mimetype: MIME content type of the attachment
     *       size: Attachment size
     */
    public function get_attachment($id, $task)
    {
        // get old revision of the object
        if ($task['rev']) {
            $task = $this->get_task_revison($task, $task['rev']);
        }
        else {
            $task = $this->get_task($task);
        }

        if ($task && !empty($task['attachments'])) {
            foreach ($task['attachments'] as $att) {
                if ($att['id'] == $id)
                    return $att;
            }
        }

        return null;
    }

    /**
     * Get attachment body
     *
     * @param string $id    Attachment identifier
     * @param array  $task  Hash array with event properties:
     *         id: Task identifier
     *       list: List identifier
     *        rev: Revision (optional)
     *
     * @return string Attachment body
     */
    public function get_attachment_body($id, $task)
    {
        $this->_parse_id($task);

        // get old revision of event
        if ($task['rev']) {
            if (empty($this->bonnie_api)) {
                return false;
            }

            $cid = substr($id, 4);

            // call Bonnie API and get the raw mime message
            list($uid, $mailbox, $msguid) = $this->_resolve_task_identity($task);
            if ($msg_raw = $this->bonnie_api->rawdata('task', $uid, $task['rev'], $mailbox, $msguid)) {
                // parse the message and find the part with the matching content-id
                $message = rcube_mime::parse_message($msg_raw);
                foreach ((array)$message->parts as $part) {
                    if ($part->headers['content-id'] && trim($part->headers['content-id'], '<>') == $cid) {
                        return $part->body;
                    }
                }
            }

            return false;
        }


        if ($storage = $this->get_folder($task['list'])) {
            return $storage->get_attachment($task['uid'], $id);
        }

        return false;
    }

    /**
     * Build a struct representing the given message reference
     *
     * @see tasklist_driver::get_message_reference()
     */
    public function get_message_reference($uri_or_headers, $folder = null)
    {
        if (is_object($uri_or_headers)) {
            $uri_or_headers = kolab_storage_config::get_message_uri($uri_or_headers, $folder);
        }

        if (is_string($uri_or_headers)) {
            return kolab_storage_config::get_message_reference($uri_or_headers, 'task');
        }

        return false;
    }

    /**
     * Find tasks assigned to a specified message
     *
     * @see tasklist_driver::get_message_related_tasks()
     */
    public function get_message_related_tasks($headers, $folder)
    {
        $config = kolab_storage_config::get_instance();
        $result = $config->get_message_relations($headers, $folder, 'task');

        foreach ($result as $idx => $rec) {
            $result[$idx] = $this->_to_rcube_task($rec, kolab_storage::folder_id($rec['_mailbox']));
        }

        return $result;
    }

    /**
     * 
     */
    public function tasklist_edit_form($action, $list, $fieldprop)
    {
        if ($list['id'] && ($list = $this->lists[$list['id']])) {
            $folder_name = $this->get_folder($list['id'])->name; // UTF7
        }
        else {
            $folder_name = '';
        }

        $storage = $this->rc->get_storage();
        $delim   = $storage->get_hierarchy_delimiter();
        $form    = array();

        if (strlen($folder_name)) {
            $path_imap = explode($delim, $folder_name);
            array_pop($path_imap);  // pop off name part
            $path_imap = implode($path_imap, $delim);

            $options = $storage->folder_info($folder_name);
        }
        else {
            $path_imap = '';
        }

        $hidden_fields[] = array('name' => 'oldname', 'value' => $folder_name);

        // folder name (default field)
        $input_name = new html_inputfield(array('name' => 'name', 'id' => 'taskedit-tasklistame', 'size' => 20));
        $fieldprop['name']['value'] = $input_name->show($list['editname'], array('disabled' => ($options['norename'] || $options['protected'])));

        // prevent user from moving folder
        if (!empty($options) && ($options['norename'] || $options['protected'])) {
            $hidden_fields[] = array('name' => 'parent', 'value' => $path_imap);
        }
        else {
            $select = kolab_storage::folder_selector('task', array('name' => 'parent', 'id' => 'taskedit-parentfolder'), $folder_name);
            $fieldprop['parent'] = array(
                'id'    => 'taskedit-parentfolder',
                'label' => $this->plugin->gettext('parentfolder'),
                'value' => $select->show($path_imap),
            );
        }

        // General tab
        $form['properties'] = array(
            'name' => $this->rc->gettext('properties'),
            'fields' => array(),
        );

        foreach (array('name','parent','showalarms') as $f) {
            $form['properties']['fields'][$f] = $fieldprop[$f];
        }

        // add folder ACL tab
        if ($action != 'form-new') {
            $form['sharing'] = array(
                'name'    => rcube::Q($this->plugin->gettext('tabsharing')),
                'content' => html::tag('iframe', array(
                    'src' => $this->rc->url(array('_action' => 'folder-acl', '_folder' => $folder_name, 'framed' => 1)),
                    'width' => '100%',
                    'height' => 280,
                    'border' => 0,
                    'style' => 'border:0'),
                '')
            );
        }

        $form_html = '';
        if (is_array($hidden_fields)) {
            foreach ($hidden_fields as $field) {
                $hiddenfield = new html_hiddenfield($field);
                $form_html .= $hiddenfield->show() . "\n";
            }
        }

        // create form output
        foreach ($form as $tab) {
            if (is_array($tab['fields']) && empty($tab['content'])) {
                $table = new html_table(array('cols' => 2));
                foreach ($tab['fields'] as $col => $colprop) {
                    $label = !empty($colprop['label']) ? $colprop['label'] : $this->plugin->gettext($col);

                    $table->add('title', html::label($colprop['id'], rcube::Q($label)));
                    $table->add(null, $colprop['value']);
                }
                $content = $table->show();
            }
            else {
                $content = $tab['content'];
            }

            if (!empty($content)) {
                $form_html .= html::tag('fieldset', null, html::tag('legend', null, rcube::Q($tab['name'])) . $content) . "\n";
            }
        }

        return $form_html;
    }

    /**
     * Handler to render ACL form for a notes folder
     */
    public function folder_acl()
    {
        $this->plugin->require_plugin('acl');
        $this->rc->output->add_handler('folderacl', array($this, 'folder_acl_form'));
        $this->rc->output->send('tasklist.kolabacl');
    }

    /**
     * Handler for ACL form template object
     */
    public function folder_acl_form()
    {
        $folder = rcube_utils::get_input_value('_folder', rcube_utils::INPUT_GPC);

        if (strlen($folder)) {
            $storage = $this->rc->get_storage();
            $options = $storage->folder_info($folder);

            // get sharing UI from acl plugin
            $acl = $this->rc->plugins->exec_hook('folder_form',
                array('form' => array(), 'options' => $options, 'name' => $folder));
        }

        return $acl['form']['sharing']['content'] ?: html::div('hint', $this->plugin->gettext('aclnorights'));
    }
}
