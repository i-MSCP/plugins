<?php

/**
 * Database driver for the Tasklist plugin
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

class tasklist_database_driver extends tasklist_driver
{
    const IS_COMPLETE_SQL = "(status='COMPLETED' OR (complete=1 AND status=''))";

    public $undelete = true; // yes, we can
    public $sortable = false;
    public $alarm_types = array('DISPLAY');

    private $rc;
    private $plugin;
    private $lists = array();
    private $list_ids = '';
    private $tags = array();

    private $db_tasks = 'tasks';
    private $db_lists = 'tasklists';


    /**
     * Default constructor
     */
    public function __construct($plugin)
    {
        $this->rc = $plugin->rc;
        $this->plugin = $plugin;

        // read database config
        $db = $this->rc->get_dbh();
        $this->db_lists = $this->rc->config->get('db_table_lists', $db->table_name($this->db_lists));
        $this->db_tasks = $this->rc->config->get('db_table_tasks', $db->table_name($this->db_tasks));

        $this->_read_lists();
    }

    /**
     * Read available calendars for the current user and store them internally
     */
    private function _read_lists()
    {
      $hidden = array_filter(explode(',', $this->rc->config->get('hidden_tasklists', '')));

      if (!empty($this->rc->user->ID)) {
        $list_ids = array();
        $result = $this->rc->db->query(
          "SELECT *, tasklist_id AS id FROM " . $this->db_lists . "
           WHERE user_id=?
           ORDER BY CASE WHEN name='INBOX' THEN 0 ELSE 1 END, name",
           $this->rc->user->ID
        );

        while ($result && ($arr = $this->rc->db->fetch_assoc($result))) {
          $arr['showalarms'] = intval($arr['showalarms']);
          $arr['active'] = !in_array($arr['id'], $hidden);
          $arr['name'] = html::quote($arr['name']);
          $arr['listname'] = html::quote($arr['name']);
          $arr['editable'] = true;
          $arr['rights'] = 'lrswikxtea';
          $this->lists[$arr['id']] = $arr;
          $list_ids[] = $this->rc->db->quote($arr['id']);
        }
        $this->list_ids = join(',', $list_ids);
      }
    }

    /**
     * Get a list of available tasks lists from this source
     */
    public function get_lists()
    {
      // attempt to create a default list for this user
      if (empty($this->lists)) {
        $prop = array('name' => 'Default', 'color' => '000000');
        if ($this->create_list($prop))
          $this->_read_lists();
      }

      return $this->lists;
    }

    /**
     * Create a new list assigned to the current user
     *
     * @param array Hash array with list properties
     * @return mixed ID of the new list on success, False on error
     * @see tasklist_driver::create_list()
     */
    public function create_list(&$prop)
    {
        $result = $this->rc->db->query(
            "INSERT INTO " . $this->db_lists . "
             (user_id, name, color, showalarms)
             VALUES (?, ?, ?, ?)",
            $this->rc->user->ID,
            strval($prop['name']),
            strval($prop['color']),
            $prop['showalarms']?1:0
        );

        if ($result)
            return $this->rc->db->insert_id($this->db_lists);

        return false;
    }

    /**
     * Update properties of an existing tasklist
     *
     * @param array Hash array with list properties
     * @return boolean True on success, Fales on failure
     * @see tasklist_driver::edit_list()
     */
    public function edit_list(&$prop)
    {
        $query = $this->rc->db->query(
            "UPDATE " . $this->db_lists . "
             SET   name=?, color=?, showalarms=?
             WHERE tasklist_id=?
             AND   user_id=?",
            $prop['name'],
            $prop['color'],
            $prop['showalarms']?1:0,
            $prop['id'],
            $this->rc->user->ID
        );

        return $this->rc->db->affected_rows($query);
    }

    /**
     * Set active/subscribed state of a list
     *
     * @param array Hash array with list properties
     * @return boolean True on success, Fales on failure
     * @see tasklist_driver::subscribe_list()
     */
    public function subscribe_list($prop)
    {
        $hidden = array_flip(explode(',', $this->rc->config->get('hidden_tasklists', '')));

        if ($prop['active'])
            unset($hidden[$prop['id']]);
        else
            $hidden[$prop['id']] = 1;

        return $this->rc->user->save_prefs(array('hidden_tasklists' => join(',', array_keys($hidden))));
    }

    /**
     * Delete the given list with all its contents
     *
     * @param array Hash array with list properties
     * @return boolean True on success, Fales on failure
     * @see tasklist_driver::delete_list()
     */
    public function delete_list($prop)
    {
        $list_id = $prop['id'];

        if ($this->lists[$list_id]) {
            // delete all tasks linked with this list
            $this->rc->db->query(
                "DELETE FROM " . $this->db_tasks . "
                 WHERE tasklist_id=?",
                $list_id
            );

            // delete list record
            $query = $this->rc->db->query(
                "DELETE FROM " . $this->db_lists . "
                 WHERE tasklist_id=?
                 AND user_id=?",
                $list_id,
                $this->rc->user->ID
            );

            return $this->rc->db->affected_rows($query);
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
        return array();
    }

    /**
     * Get a list of tags to assign tasks to
     *
     * @return array List of tags
     */
    public function get_tags()
    {
        return array_values(array_unique($this->tags, SORT_STRING));
    }

    /**
     * Get number of tasks matching the given filter
     *
     * @param array List of lists to count tasks of
     * @return array Hash array with counts grouped by status (all|flagged|today|tomorrow|overdue|nodate)
     * @see tasklist_driver::count_tasks()
     */
    function count_tasks($lists = null)
    {
        if (empty($lists))
            $lists = array_keys($this->lists);
        else if (is_string($lists))
            $lists = explode(',', $lists);

        // only allow to select from lists of this user
        $list_ids = array_map(array($this->rc->db, 'quote'), array_intersect($lists, array_keys($this->lists)));

        $today_date = new DateTime('now', $this->plugin->timezone);
        $today = $today_date->format('Y-m-d');
        $tomorrow_date = new DateTime('now + 1 day', $this->plugin->timezone);
        $tomorrow = $tomorrow_date->format('Y-m-d');

        $result = $this->rc->db->query(sprintf(
            "SELECT task_id, flagged, date FROM " . $this->db_tasks . "
             WHERE tasklist_id IN (%s)
             AND del=0 AND NOT " . self::IS_COMPLETE_SQL,
            join(',', $list_ids)
        ));

        $counts = array('all' => 0, 'flagged' => 0, 'today' => 0, 'tomorrow' => 0, 'overdue' => 0, 'nodate' => 0);
        while ($result && ($rec = $this->rc->db->fetch_assoc($result))) {
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

        return $counts;
    }

    /**
     * Get all taks records matching the given filter
     *
     * @param array Hash array wiht filter criterias
     * @param array List of lists to get tasks from
     * @return array List of tasks records matchin the criteria
     * @see tasklist_driver::list_tasks()
     */
    function list_tasks($filter, $lists = null)
    {
        if (empty($lists))
            $lists = array_keys($this->lists);
        else if (is_string($lists))
            $lists = explode(',', $lists);

        // only allow to select from lists of this user
        $list_ids = array_map(array($this->rc->db, 'quote'), array_intersect($lists, array_keys($this->lists)));
        $sql_add = '';

        // add filter criteria
        if ($filter['from'] || ($filter['mask'] & tasklist::FILTER_MASK_TODAY)) {
            $sql_add .= ' AND (date IS NULL OR date >= ?)';
            $datefrom = $filter['from'];
        }
        if ($filter['to']) {
            if ($filter['mask'] & tasklist::FILTER_MASK_OVERDUE)
                $sql_add .= ' AND (date IS NOT NULL AND date <= ' . $this->rc->db->quote($filter['to']) . ')';
            else
                $sql_add .= ' AND (date IS NULL OR date <= ' . $this->rc->db->quote($filter['to']) . ')';
        }

        // special case 'today': also show all events with date before today
        if ($filter['mask'] & tasklist::FILTER_MASK_TODAY) {
            $datefrom = date('Y-m-d', 0);
        }

        if ($filter['mask'] & tasklist::FILTER_MASK_NODATE)
            $sql_add = ' AND date IS NULL';

        if ($filter['mask'] & tasklist::FILTER_MASK_COMPLETE)
            $sql_add .= ' AND ' . self::IS_COMPLETE_SQL;
        else if (empty($filter['since']))  // don't show complete tasks by default
            $sql_add .= ' AND NOT ' . self::IS_COMPLETE_SQL;

        if ($filter['mask'] & tasklist::FILTER_MASK_FLAGGED)
            $sql_add .= ' AND flagged=1';

        // compose (slow) SQL query for searching
        // FIXME: improve searching using a dedicated col and normalized values
        if ($filter['search']) {
            $sql_query = array();
            foreach (array('title','description','organizer','attendees') as $col)
                $sql_query[] = $this->rc->db->ilike($col, '%'.$filter['search'].'%');
            $sql_add = 'AND (' . join(' OR ', $sql_query) . ')';
        }

        if ($filter['since'] && is_numeric($filter['since'])) {
            $sql_add .= ' AND changed >= ' . $this->rc->db->quote(date('Y-m-d H:i:s', $filter['since']));
        }

        $tasks = array();
        if (!empty($list_ids)) {
            $result = $this->rc->db->query(sprintf(
                "SELECT * FROM " . $this->db_tasks . "
                 WHERE tasklist_id IN (%s)
                 AND del=0
                 %s
                 ORDER BY parent_id, task_id ASC",
                 join(',', $list_ids),
                 $sql_add
                ),
                $datefrom
           );

            while ($result && ($rec = $this->rc->db->fetch_assoc($result))) {
                $tasks[] = $this->_read_postprocess($rec);
            }
        }

        return $tasks;
    }

    /**
     * Return data of a specific task
     *
     * @param mixed  Hash array with task properties or task UID
     * @return array Hash array with task properties or false if not found
     */
    public function get_task($prop)
    {
        if (is_string($prop))
            $prop['uid'] = $prop;

        $query_col = $prop['id'] ? 'task_id' : 'uid';

        $result = $this->rc->db->query(sprintf(
             "SELECT * FROM " . $this->db_tasks . "
              WHERE tasklist_id IN (%s)
              AND %s=?
              AND del=0",
              $this->list_ids,
              $query_col
             ),
             $prop['id'] ? $prop['id'] : $prop['uid']
        );

        if ($result && ($rec = $this->rc->db->fetch_assoc($result))) {
             return $this->_read_postprocess($rec);
        }

        return false;
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
        // resolve UID first
        if (is_string($prop)) {
            $result = $this->rc->db->query(sprintf(
                "SELECT task_id AS id, tasklist_id AS list FROM " . $this->db_tasks . "
                 WHERE tasklist_id IN (%s)
                 AND uid=?",
                 $this->list_ids
                ),
                $prop);
            $prop = $this->rc->db->fetch_assoc($result);
        }

        $childs = array();
        $task_ids = array($prop['id']);

        // query for childs (recursively)
        while (!empty($task_ids)) {
            $result = $this->rc->db->query(sprintf(
                "SELECT task_id AS id FROM " . $this->db_tasks . "
                 WHERE tasklist_id IN (%s)
                 AND parent_id IN (%s)
                 AND del=0",
                $this->list_ids,
                join(',', array_map(array($this->rc->db, 'quote'), $task_ids))
            ));

            $task_ids = array();
            while ($result && ($rec = $this->rc->db->fetch_assoc($result))) {
                $childs[] = $rec['id'];
                $task_ids[] = $rec['id'];
            }

            if (!$recursive)
                break;
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
        if (empty($lists))
            $lists = array_keys($this->lists);
        else if (is_string($lists))
            $lists = explode(',', $lists);

        // only allow to select from calendars with activated alarms
        $list_ids = array();
        foreach ($lists as $lid) {
            if ($this->lists[$lid] && $this->lists[$lid]['showalarms'])
                $list_ids[] = $lid;
        }
        $list_ids = array_map(array($this->rc->db, 'quote'), $list_ids);

        $alarms = array();
        if (!empty($list_ids)) {
            $result = $this->rc->db->query(sprintf(
                "SELECT * FROM " . $this->db_tasks . "
                 WHERE tasklist_id IN (%s)
                 AND notify <= %s AND NOT " . self::IS_COMPLETE_SQL,
                join(',', $list_ids),
                $this->rc->db->fromunixtime($time)
            ));

            while ($result && ($rec = $this->rc->db->fetch_assoc($result)))
                $alarms[] = $this->_read_postprocess($rec);
        }

        return $alarms;
    }

    /**
     * Feedback after showing/sending an alarm notification
     *
     * @see tasklist_driver::dismiss_alarm()
     */
    public function dismiss_alarm($task_id, $snooze = 0)
    {
        // set new notifyat time or unset if not snoozed
        $notify_at = $snooze > 0 ? date('Y-m-d H:i:s', time() + $snooze) : null;

        $query = $this->rc->db->query(sprintf(
            "UPDATE " . $this->db_tasks . "
             SET   changed=%s, notify=?
             WHERE task_id=?
             AND tasklist_id IN (" . $this->list_ids . ")",
            $this->rc->db->now()),
            $notify_at,
            $task_id
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
        // Nothing to do here. Alarms are reset in edit_task()
    }

    /**
     * Map some internal database values to match the generic "API"
     */
    private function _read_postprocess($rec)
    {
        $rec['id'] = $rec['task_id'];
        $rec['list'] = $rec['tasklist_id'];
        $rec['changed'] = new DateTime($rec['changed']);
        $rec['tags'] = array_filter(explode(',', $rec['tags']));

        if (!$rec['parent_id'])
            unset($rec['parent_id']);

        // decode serialized alarms
        if ($rec['alarms']) {
            $rec['valarms'] = $this->unserialize_alarms($rec['alarms']);
            unset($rec['alarms']);
        }

        // decode serialze recurrence rules
        if ($rec['recurrence']) {
            $rec['recurrence'] = $this->unserialize_recurrence($rec['recurrence']);
        }

        if (!empty($rec['tags'])) {
            $this->tags = array_merge($this->tags, (array)$rec['tags']);
        }

        unset($rec['task_id'], $rec['tasklist_id'], $rec['created']);
        return $rec;
    }

    /**
     * Add a single task to the database
     *
     * @param array Hash array with task properties (see header of this file)
     * @return mixed New event ID on success, False on error
     * @see tasklist_driver::create_task()
     */
    public function create_task($prop)
    {
        // check list permissions
        $list_id = $prop['list'] ? $prop['list'] : reset(array_keys($this->lists));
        if (!$this->lists[$list_id] || $this->lists[$list_id]['readonly'])
            return false;

        if (is_array($prop['valarms'])) {
            $prop['alarms'] = $this->serialize_alarms($prop['valarms']);
        }
        if (is_array($prop['recurrence'])) {
            $prop['recurrence'] = $this->serialize_recurrence($prop['recurrence']);
        }

        foreach (array('parent_id', 'date', 'time', 'startdate', 'starttime', 'alarms', 'recurrence', 'status') as $col) {
            if (empty($prop[$col]))
                $prop[$col] = null;
        }

        $notify_at = $this->_get_notification($prop);
        $result = $this->rc->db->query(sprintf(
            "INSERT INTO " . $this->db_tasks . "
             (tasklist_id, uid, parent_id, created, changed, title, date, time, startdate, starttime, description, tags, flagged, complete, status, alarms, recurrence, notify)
             VALUES (?, ?, ?, %s, %s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
             $this->rc->db->now(),
             $this->rc->db->now()
            ),
            $list_id,
            $prop['uid'],
            $prop['parent_id'],
            $prop['title'],
            $prop['date'],
            $prop['time'],
            $prop['startdate'],
            $prop['starttime'],
            strval($prop['description']),
            join(',', (array)$prop['tags']),
            $prop['flagged'] ? 1 : 0,
            intval($prop['complete']),
            $prop['status'],
            $prop['alarms'],
            $prop['recurrence'],
            $notify_at
        );

        if ($result)
            return $this->rc->db->insert_id($this->db_tasks);

        return false;
    }

    /**
     * Update an task entry with the given data
     *
     * @param array Hash array with task properties
     * @return boolean True on success, False on error
     * @see tasklist_driver::edit_task()
     */
    public function edit_task($prop)
    {
        if (is_array($prop['valarms'])) {
            $prop['alarms'] = $this->serialize_alarms($prop['valarms']);
        }
        if (is_array($prop['recurrence'])) {
            $prop['recurrence'] = $this->serialize_recurrence($prop['recurrence']);
        }

        $sql_set = array();
        foreach (array('title', 'description', 'flagged', 'complete') as $col) {
            if (isset($prop[$col]))
                $sql_set[] = $this->rc->db->quote_identifier($col) . '=' . $this->rc->db->quote($prop[$col]);
        }
        foreach (array('parent_id', 'date', 'time', 'startdate', 'starttime', 'alarms', 'recurrence', 'status') as $col) {
            if (isset($prop[$col]))
                $sql_set[] = $this->rc->db->quote_identifier($col) . '=' . (empty($prop[$col]) ? 'NULL' : $this->rc->db->quote($prop[$col]));
        }
        if (isset($prop['tags']))
            $sql_set[] = $this->rc->db->quote_identifier('tags') . '=' . $this->rc->db->quote(join(',', (array)$prop['tags']));

        if (isset($prop['date']) || isset($prop['time']) || isset($prop['alarms'])) {
            $notify_at = $this->_get_notification($prop);
            $sql_set[] = $this->rc->db->quote_identifier('notify') . '=' . (empty($notify_at) ? 'NULL' : $this->rc->db->quote($notify_at));
        }

        // moved from another list
        if ($prop['_fromlist'] && ($newlist = $prop['list'])) {
            $sql_set[] = 'tasklist_id=' . $this->rc->db->quote($newlist);
        }

        $query = $this->rc->db->query(sprintf(
            "UPDATE " . $this->db_tasks . "
             SET   changed=%s %s
             WHERE task_id=?
             AND   tasklist_id IN (%s)",
            $this->rc->db->now(),
            ($sql_set ? ', ' . join(', ', $sql_set) : ''),
            $this->list_ids
          ),
          $prop['id']
        );

        return $this->rc->db->affected_rows($query);
    }

    /**
     * Move a single task to another list
     *
     * @param array   Hash array with task properties:
     * @return boolean True on success, False on error
     * @see tasklist_driver::move_task()
     */
    public function move_task($prop)
    {
        return $this->edit_task($prop);
    }

    /**
     * Remove a single task from the database
     *
     * @param array   Hash array with task properties
     * @param boolean Remove record irreversible
     * @return boolean True on success, False on error
     * @see tasklist_driver::delete_task()
     */
    public function delete_task($prop, $force = true)
    {
        $task_id = $prop['id'];

        if ($task_id && $force) {
            $query = $this->rc->db->query(
                "DELETE FROM " . $this->db_tasks . "
                 WHERE task_id=?
                 AND tasklist_id IN (" . $this->list_ids . ")",
                $task_id
            );
        }
        else if ($task_id) {
            $query = $this->rc->db->query(sprintf(
                "UPDATE " . $this->db_tasks . "
                 SET   changed=%s, del=1
                 WHERE task_id=?
                 AND   tasklist_id IN (%s)",
                $this->rc->db->now(),
                $this->list_ids
              ),
              $task_id
            );
        }

        return $this->rc->db->affected_rows($query);
    }

    /**
     * Restores a single deleted task (if supported)
     *
     * @param array Hash array with task properties
     * @return boolean True on success, False on error
     * @see tasklist_driver::undelete_task()
     */
    public function undelete_task($prop)
    {
        $query = $this->rc->db->query(sprintf(
            "UPDATE " . $this->db_tasks . "
             SET   changed=%s, del=0
             WHERE task_id=?
             AND   tasklist_id IN (%s)",
            $this->rc->db->now(),
            $this->list_ids
          ),
          $prop['id']
        );

        return $this->rc->db->affected_rows($query);
    }

    /**
     * Compute absolute time to notify the user
     */
    private function _get_notification($task)
    {
        if ($task['valarms'] && !$this->is_complete($task)) {
            $alarm = libcalendaring::get_next_alarm($task, 'task');

        if ($alarm['time'] && in_array($alarm['action'], $this->alarm_types))
          return date('Y-m-d H:i:s', $alarm['time']);
      }

      return null;
    }

    /**
     * Helper method to serialize the list of alarms into a string
     */
    private function serialize_alarms($valarms)
    {
        foreach ((array)$valarms as $i => $alarm) {
            if ($alarm['trigger'] instanceof DateTime) {
                $valarms[$i]['trigger'] = '@' . $alarm['trigger']->format('c');
            }
        }

        return $valarms ? json_encode($valarms) : null;
    }

    /**
     * Helper method to decode a serialized list of alarms
     */
    private function unserialize_alarms($alarms)
    {
        // decode json serialized alarms
        if ($alarms && $alarms[0] == '[') {
            $valarms = json_decode($alarms, true);
            foreach ($valarms as $i => $alarm) {
                if ($alarm['trigger'][0] == '@') {
                    try {
                        $valarms[$i]['trigger'] = new DateTime(substr($alarm['trigger'], 1));
                    }
                    catch (Exception $e) {
                        unset($valarms[$i]);
                    }
                }
            }
        }
        // convert legacy alarms data
        else if (strlen($alarms)) {
            list($trigger, $action) = explode(':', $alarms, 2);
            if ($trigger = libcalendaring::parse_alarm_value($trigger)) {
                $valarms = array(array('action' => $action, 'trigger' => $trigger[3] ?: $trigger[0]));
            }
        }

        return $valarms;
    }

    /**
     * Helper method to serialize task recurrence properties
     */
    private function serialize_recurrence($recurrence)
    {
        foreach ((array)$recurrence as $k => $val) {
            if ($val instanceof DateTime) {
                $recurrence[$k] = '@' . $val->format('c');
            }
        }

        return $recurrence ? json_encode($recurrence) : null;
    }

    /**
     * Helper method to decode a serialized task recurrence struct
     */
    private function unserialize_recurrence($ser)
    {
        if (strlen($ser)) {
            $recurrence = json_decode($ser, true);
            foreach ((array)$recurrence as $k => $val) {
                if ($val[0] == '@') {
                    try {
                        $recurrence[$k] = new DateTime(substr($val, 1));
                    }
                    catch (Exception $e) {
                        unset($recurrence[$k]);
                    }
                }
            }
        }
        else {
            $recurrence = '';
        }

        return $recurrence;
    }

    /**
     * Handler for user_delete plugin hook
     */
    public function user_delete($args)
    {
        $db = $this->rc->db;
        $list_ids = array();
        $lists = $db->query("SELECT tasklist_id FROM " . $this->db_lists . " WHERE user_id=?", $args['user']->ID);
        while ($row = $db->fetch_assoc($lists)) {
            $list_ids[] = $row['tasklist_id'];
        }

        if (!empty($list_ids)) {
            foreach (array($this->db_tasks, $this->db_lists) as $table) {
                $db->query(sprintf("DELETE FROM $table WHERE tasklist_id IN (%s)", join(',', $list_ids)));
            }
        }
    }
}
