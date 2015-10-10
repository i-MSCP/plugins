<?php

/**
 * Kolab Groupware driver for the Tasklist plugin
 *
 * @version @package_version@
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 *
 * Copyright (C) 2012, Kolab Systems AG <contact@kolabsys.com>
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
    public $alarms = false;
    public $attachments = true;
    public $undelete = false; // task undelete action
    public $alarm_types = array('DISPLAY');

    private $rc;
    private $plugin;
    private $lists;
    private $folders = array();
    private $tasks = array();


    /**
     * Default constructor
     */
    public function __construct($plugin)
    {
        $this->rc = $plugin->rc;
        $this->plugin = $plugin;

        $this->_read_lists();

        if (kolab_storage::$version == '2.0') {
            $this->alarm_absolute = false;
        }
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
        $this->folders = kolab_storage::get_folders('task');
        $this->lists = array();

        // convert to UTF8 and sort
        $names = array();
        $default_folder = null;
        foreach ($this->folders as $folder) {
            $names[$folder->name] = rcube_charset::convert($folder->name, 'UTF7-IMAP');
            $this->folders[$folder->name] = $folder;
            if ($folder->default)
                $default_folder = $folder->name;
        }

        asort($names, SORT_LOCALE_STRING);

        // put default folder (aka INBOX) on top of the list
        if ($default_folder) {
            $default_name = $names[$default_folder];
            unset($names[$default_folder]);
            $names = array_merge(array($default_folder => $default_name), $names);
        }

        $delim = $this->rc->get_storage()->get_hierarchy_delimiter();
        $listnames = array();

        $prefs = $this->rc->config->get('kolab_tasklists', array());

        foreach ($names as $utf7name => $name) {
            $folder = $this->folders[$utf7name];

            $path_imap = explode($delim, $name);
            $editname = array_pop($path_imap);  // pop off raw name part
            $path_imap = join($delim, $path_imap);

            $name = kolab_storage::folder_displayname(kolab_storage::object_name($utf7name), $listnames);

            if ($folder->get_namespace() == 'personal') {
                $readonly = false;
                $alarms = true;
            }
            else {
                $alarms = false;
                $readonly = true;
                if (($rights = $folder->get_myrights()) && !PEAR::isError($rights)) {
                    if (strpos($rights, 'i') !== false)
                      $readonly = false;
                }
            }

            $list_id = kolab_storage::folder_id($utf7name);
            $tasklist = array(
                'id' => $list_id,
                'name' => $name,
                'editname' => $editname,
                'color' => $folder->get_color('0000CC'),
                'showalarms' => isset($prefs[$list_id]['showalarms']) ? $prefs[$list_id]['showalarms'] : $alarms,
                'editable' => !$readonly,
                'active' => $folder->is_active(),
                'parentfolder' => $path_imap,
                'default' => $folder->default,
                'children' => true,  // TODO: determine if that folder indeed has child folders
                'class_name' => trim($folder->get_namespace() . ($folder->default ? ' default' : '')),
            );
            $this->lists[$tasklist['id']] = $tasklist;
            $this->folders[$tasklist['id']] = $folder;
        }
    }

    /**
     * Get a list of available task lists from this source
     */
    public function get_lists()
    {
        // attempt to create a default list for this user
        if (empty($this->lists)) {
            if ($this->create_list(array('name' => 'Tasks', 'color' => '0000CC', 'default' => true)))
                $this->_read_lists(true);
        }

        return $this->lists;
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
    public function create_list($prop)
    {
        $prop['type'] = 'task' . ($prop['default'] ? '.default' : '');
        $prop['active'] = true; // activate folder by default
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
    public function edit_list($prop)
    {
        if ($prop['id'] && ($folder = $this->folders[$prop['id']])) {
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
     * @return boolean True on success, Fales on failure
     */
    public function subscribe_list($prop)
    {
        if ($prop['id'] && ($folder = $this->folders[$prop['id']])) {
            return $folder->activate($prop['active']);
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
    public function remove_list($prop)
    {
        if ($prop['id'] && ($folder = $this->folders[$prop['id']])) {
          if (kolab_storage::folder_delete($folder->name))
              return true;
          else
              $this->last_error = kolab_storage::$last_error;
        }

        return false;
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

        $counts = array('all' => 0, 'flagged' => 0, 'today' => 0, 'tomorrow' => 0, 'overdue' => 0, 'nodate' => 0);
        foreach ($lists as $list_id) {
            $folder = $this->folders[$list_id];
            foreach ((array)$folder->select(array(array('tags','!~','x-complete'))) as $record) {
                $rec = $this->_to_rcube_task($record);

                if ($rec['complete'] >= 1.0)  // don't count complete tasks
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
            }
        }

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
        else
            $query[] = array('tags','!~','x-complete');

        // full text search (only works with cache enabled)
        if ($filter['search']) {
            $search = mb_strtolower($filter['search']);
            foreach (rcube_utils::normalize_string($search, true) as $word) {
                $query[] = array('words', '~', $word);
            }
        }

        foreach ($lists as $list_id) {
            $folder = $this->folders[$list_id];
            foreach ((array)$folder->select($query) as $record) {
                $task = $this->_to_rcube_task($record);
                $task['list'] = $list_id;

                // TODO: post-filter tasks returned from storage

                $results[] = $task;
            }
        }

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
        $id = is_array($prop) ? ($prop['uid'] ?: $prop['id']) : $prop;
        $list_id = is_array($prop) ? $prop['list'] : null;
        $folders = $list_id ? array($list_id => $this->folders[$list_id]) : $this->folders;

        // find task in the available folders
        foreach ($folders as $list_id => $folder) {
            if (is_numeric($list_id))
                continue;
            if (!$this->tasks[$id] && ($object = $folder->get_object($id))) {
                $this->tasks[$id] = $this->_to_rcube_task($object);
                $this->tasks[$id]['list'] = $list_id;
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
            $prop = array('id' => $task['id'], 'list' => $task['list']);
        }

        $childs = array();
        $list_id = $prop['list'];
        $task_ids = array($prop['id']);
        $folder = $this->folders[$list_id];

        // query for childs (recursively)
        while ($folder && !empty($task_ids)) {
            $query_ids = array();
            foreach ($task_ids as $task_id) {
                $query = array(array('tags','=','x-parent:' . $task_id));
                foreach ((array)$folder->select($query) as $record) {
                    // don't rely on kolab_storage_folder filtering
                    if ($record['parent_id'] == $task_id) {
                        $childs[] = $record['uid'];
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

        $tasks = array();
        $query = array(array('tags', '=', 'x-has-alarms'), array('tags', '!=', 'x-complete'));
        foreach ($this->lists as $lid => $list) {
            // skip lists with alarms disabled
            if (!$list['showalarms'] || ($lists && !in_array($lid, $lists)))
                continue;

            $folder = $this->folders[$lid];
            foreach ((array)$folder->select($query) as $record) {
                if (!$record['alarms'])  // don't trust query :-)
                    continue;

                $task = $this->_to_rcube_task($record);

                // add to list if alarm is set
                $alarm = libcalendaring::get_next_alarm($task, 'task');
                if ($alarm && $alarm['time'] && $alarm['time'] <= $time && $alarm['action'] == 'DISPLAY') {
                    $id = $task['id'];
                    $tasks[$id] = $task;
                    $tasks[$id]['notifyat'] = $alarm['time'];
                }
            }
        }

        // get alarm information stored in local database
        if (!empty($tasks)) {
            $task_ids = array_map(array($this->rc->db, 'quote'), array_keys($tasks));
            $result = $this->rc->db->query(sprintf(
                "SELECT * FROM kolab_alarms
                 WHERE event_id IN (%s) AND user_id=?",
                 join(',', $task_ids),
                 $this->rc->db->now()
                ),
                $this->rc->user->ID
            );

            while ($result && ($rec = $this->rc->db->fetch_assoc($result))) {
                $dbdata[$rec['event_id']] = $rec;
            }
        }

        $alarms = array();
        foreach ($tasks as $id => $task) {
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
            "DELETE FROM kolab_alarms
             WHERE event_id=? AND user_id=?",
            $id,
            $this->rc->user->ID
        );

        // set new notifyat time or unset if not snoozed
        $notifyat = $snooze > 0 ? date('Y-m-d H:i:s', time() + $snooze) : null;

        $query = $this->rc->db->query(
            "INSERT INTO kolab_alarms
             (event_id, user_id, dismissed, notifyat)
             VALUES(?, ?, ?, ?)",
            $id,
            $this->rc->user->ID,
            $snooze > 0 ? 0 : 1,
            $notifyat
        );

        return $this->rc->db->affected_rows($query);
    }

    /**
     * Convert from Kolab_Format to internal representation
     */
    private function _to_rcube_task($record)
    {
        $task = array(
            'id' => $record['uid'],
            'uid' => $record['uid'],
            'title' => $record['title'],
#            'location' => $record['location'],
            'description' => $record['description'],
            'tags' => (array)$record['categories'],
            'flagged' => $record['priority'] == 1,
            'complete' => $record['status'] == 'COMPLETED' ? 1 : floatval($record['complete'] / 100),
            'parent_id' => $record['parent_id'],
        );

        // convert from DateTime to internal date format
        if (is_a($record['due'], 'DateTime')) {
            $task['date'] = $record['due']->format('Y-m-d');
            if (!$record['due']->_dateonly)
                $task['time'] = $record['due']->format('H:i');
        }
        // convert from DateTime to internal date format
        if (is_a($record['start'], 'DateTime')) {
            $task['startdate'] = $record['start']->format('Y-m-d');
            if (!$record['start']->_dateonly)
                $task['starttime'] = $record['start']->format('H:i');
        }
        if (is_a($record['dtstamp'], 'DateTime')) {
            $task['changed'] = $record['dtstamp'];
        }

        if ($record['alarms']) {
            $task['alarms'] = $record['alarms'];
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
        $object['categories'] = (array)$task['tags'];

        if (!empty($task['date'])) {
            $object['due'] = new DateTime($task['date'].' '.$task['time'], $this->plugin->timezone);
            if (empty($task['time']))
                $object['due']->_dateonly = true;
            unset($object['date']);
        }

        if (!empty($task['startdate'])) {
            $object['start'] = new DateTime($task['startdate'].' '.$task['starttime'], $this->plugin->timezone);
            if (empty($task['starttime']))
                $object['start']->_dateonly = true;
            unset($object['startdate']);
        }

        $object['complete'] = $task['complete'] * 100;
        if ($task['complete'] == 1.0)
            $object['status'] = 'COMPLETED';

        if ($task['flagged'])
            $object['priority'] = 1;
        else
            $object['priority'] = $old['priority'] > 1 ? $old['priority'] : 0;

        // copy meta data (starting with _) from old object
        foreach ((array)$old as $key => $val) {
          if (!isset($object[$key]) && $key[0] == '_')
            $object[$key] = $val;
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
                if ($attachment['content']) {
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

        unset($object['tempid'], $object['raw'], $object['list'], $object['flagged'], $object['tags']);
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
        $list_id = $task['list'];
        if (!$list_id || !($folder = $this->folders[$list_id]))
            return false;

        // moved from another folder
        if ($task['_fromlist'] && ($fromfolder = $this->folders[$task['_fromlist']])) {
            if (!$fromfolder->move($task['id'], $folder->name))
                return false;

            unset($task['_fromlist']);
        }

        // load previous version of this task to merge
        if ($task['id']) {
            $old = $folder->get_object($task['id']);
            if (!$old || PEAR::isError($old))
                return false;

            // merge existing properties if the update isn't complete
            if (!isset($task['title']) || !isset($task['complete']))
                $task += $this->_to_rcube_task($old);
        }

        // generate new task object from RC input
        $object = $this->_from_rcube_task($task, $old);
        $saved = $folder->save($object, 'task', $task['id']);

        if (!$saved) {
            raise_error(array(
                'code' => 600, 'type' => 'php',
                'file' => __FILE__, 'line' => __LINE__,
                'message' => "Error saving task object to Kolab server"),
                true, false);
            $saved = false;
        }
        else {
            $task = $this->_to_rcube_task($object);
            $task['list'] = $list_id;
            $this->tasks[$task['id']] = $task;
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
        $list_id = $task['list'];
        if (!$list_id || !($folder = $this->folders[$list_id]))
            return false;

        // execute move command
        if ($task['_fromlist'] && ($fromfolder = $this->folders[$task['_fromlist']])) {
            return $fromfolder->move($task['id'], $folder->name);
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
        $list_id = $task['list'];
        if (!$list_id || !($folder = $this->folders[$list_id]))
            return false;

        return $folder->delete($task['id']);
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
     *
     * @return array Hash array with attachment properties:
     *         id: Attachment identifier
     *       name: Attachment name
     *   mimetype: MIME content type of the attachment
     *       size: Attachment size
     */
    public function get_attachment($id, $task)
    {
        $task['uid'] = $task['id'];
        $task = $this->get_task($task);

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
     *
     * @return string Attachment body
     */
    public function get_attachment_body($id, $task)
    {
        if ($storage = $this->folders[$task['list']]) {
            return $storage->get_attachment($task['id'], $id);
        }

        return false;
    }

    /**
     * 
     */
    public function tasklist_edit_form($fieldprop)
    {
        $select = kolab_storage::folder_selector('task', array('name' => 'parent', 'id' => 'taskedit-parentfolder'), null);
        $fieldprop['parent'] = array(
            'id' => 'taskedit-parentfolder',
            'label' => $this->plugin->gettext('parentfolder'),
            'value' => $select->show(''),
        );

        $formfields = array();
        foreach (array('name','parent','showalarms') as $f) {
            $formfields[$f] = $fieldprop[$f];
        }

        return parent::tasklist_edit_form($formfields);
    }

}
