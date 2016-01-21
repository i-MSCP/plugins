<?php

/**
 * Driver interface for the Tasklist plugin
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

 /**
  * Struct of an internal task object how it is passed from/to the driver classes:
  *
  *  $task = array(
  *            'id' => 'Task ID used for editing',  // must be unique for the current user
  *     'parent_id' => 'ID of parent task',  // null if top-level task
  *           'uid' => 'Unique identifier of this task',
  *          'list' => 'Task list identifier to add the task to or where the task is stored',
  *       'changed' => <DateTime>,  // Last modification date/time of the record
  *         'title' => 'Event title/summary',
  *   'description' => 'Event description',
  *          'tags' => array(),      // List of tags for this task
  *          'date' => 'Due date',   // as string of format YYYY-MM-DD or null if no date is set
  *          'time' => 'Due time',   // as string of format hh::ii or null if no due time is set
  *     'startdate' => 'Start date'  // Delay start of the task until that date
  *     'starttime' => 'Start time'  // ...and time
  *    'categories' => 'Task category',
  *       'flagged' => 'Boolean value whether this record is flagged',
  *      'complete' => 'Float value representing the completeness state (range 0..1)',
  *      'status'   => 'Task status string according to (NEEDS-ACTION, IN-PROCESS, COMPLETED, CANCELLED) RFC 2445',
  *       'valarms' => array(           // List of reminders (new format), each represented as a hash array:
  *                array(
  *                   'trigger' => '-PT90M',     // ISO 8601 period string prefixed with '+' or '-', or DateTime object
  *                    'action' => 'DISPLAY|EMAIL|AUDIO',
  *                  'duration' => 'PT15M',      // ISO 8601 period string
  *                    'repeat' => 0,            // number of repetitions
  *               'description' => '',           // text to display for DISPLAY actions
  *                   'summary' => '',           // message text for EMAIL actions
  *                 'attendees' => array(),      // list of email addresses to receive alarm messages
  *                ),
  *    ),
  *    'recurrence' => array(   // Recurrence definition according to iCalendar (RFC 2445) specification as list of key-value pairs
  *              'FREQ' => 'DAILY|WEEKLY|MONTHLY|YEARLY',
  *          'INTERVAL' => 1...n,
  *             'UNTIL' => DateTime,
  *             'COUNT' => 1..n,     // number of times
  *             'RDATE' => array(),  // complete list of DateTime objects denoting individual repeat dates
  *     ),
  *     '_fromlist' => 'List identifier where the task was stored before',
  *  );
  */

/**
 * Driver interface for the Tasklist plugin
 */
abstract class tasklist_driver
{
    // features supported by the backend
    public $alarms = false;
    public $attachments = false;
    public $attendees = false;
    public $undelete = false; // task undelete action
    public $sortable = false;
    public $alarm_types = array('DISPLAY');
    public $alarm_absolute = true;
    public $last_error;

    /**
     * Get a list of available task lists from this source
     */
    abstract function get_lists();

    /**
     * Create a new list assigned to the current user
     *
     * @param array Hash array with list properties
     *        name: List name
     *       color: The color of the list
     *  showalarms: True if alarms are enabled
     * @return mixed ID of the new list on success, False on error
     */
    abstract function create_list(&$prop);

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
    abstract function edit_list(&$prop);

    /**
     * Set active/subscribed state of a list
     *
     * @param array Hash array with list properties
     *          id: List Identifier
     *      active: True if list is active, false if not
     * @return boolean True on success, Fales on failure
     */
    abstract function subscribe_list($prop);

    /**
     * Delete the given list with all its contents
     *
     * @param array Hash array with list properties
     *      id: list Identifier
     * @return boolean True on success, Fales on failure
     */
    abstract function delete_list($prop);

    /**
     * Search for shared or otherwise not listed tasklists the user has access
     *
     * @param string Search string
     * @param string Section/source to search
     * @return array List of tasklists
     */
    abstract function search_lists($query, $source);

    /**
     * Get number of tasks matching the given filter
     *
     * @param array List of lists to count tasks of
     * @return array Hash array with counts grouped by status (all|flagged|completed|today|tomorrow|nodate)
     */
    abstract function count_tasks($lists = null);

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
    abstract function list_tasks($filter, $lists = null);

    /**
     * Get a list of tags to assign tasks to
     *
     * @return array List of tags
     */
    abstract function get_tags();

    /**
     * Get a list of pending alarms to be displayed to the user
     *
     * @param  integer Current time (unix timestamp)
     * @param  mixed   List of list IDs to show alarms for (either as array or comma-separated string)
     * @return array   A list of alarms, each encoded as hash array with task properties
     *         id: Task identifier
     *        uid: Unique identifier of this task
     *       date: Task due date
     *       time: Task due time
     *      title: Task title/summary
     */
    abstract function pending_alarms($time, $lists = null);

    /**
     * (User) feedback after showing an alarm notification
     * This should mark the alarm as 'shown' or snooze it for the given amount of time
     *
     * @param  string  Task identifier
     * @param  integer Suspend the alarm for this number of seconds
     */
    abstract function dismiss_alarm($id, $snooze = 0);

    /**
     * Remove alarm dismissal or snooze state
     *
     * @param  string  Task identifier
     */
    abstract public function clear_alarms($id);

    /**
     * Return data of a specific task
     *
     * @param mixed  Hash array with task properties or task UID
     * @return array Hash array with task properties or false if not found
     */
    abstract public function get_task($prop);

    /**
     * Get decendents of the given task record
     *
     * @param mixed  Hash array with task properties or task UID
     * @param boolean True if all childrens children should be fetched
     * @return array List of all child task IDs
     */
    abstract public function get_childs($prop, $recursive = false);

    /**
     * Add a single task to the database
     *
     * @param array Hash array with task properties (see header of this file)
     * @return mixed New event ID on success, False on error
     */
    abstract function create_task($prop);

    /**
     * Update an task entry with the given data
     *
     * @param array Hash array with task properties (see header of this file)
     * @return boolean True on success, False on error
     */
    abstract function edit_task($prop);

    /**
     * Move a single task to another list
     *
     * @param array   Hash array with task properties:
     *      id: Task identifier
     *      list: New list identifier to move to
     *      _fromlist: Previous list identifier
     * @return boolean True on success, False on error
     */
    abstract function move_task($prop);

    /**
     * Remove a single task from the database
     *
     * @param array   Hash array with task properties:
     *      id: Task identifier
     *    list: Tasklist identifer
     * @param boolean Remove record irreversible (mark as deleted otherwise, if supported by the backend)
     * @return boolean True on success, False on error
     */
    abstract function delete_task($prop, $force = true);

    /**
     * Restores a single deleted task (if supported)
     *
     * @param array Hash array with task properties:
     *      id: Task identifier
     * @return boolean True on success, False on error
     */
    public function undelete_task($prop)
    {
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
    public function get_attachment($id, $task) { }

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
    public function get_attachment_body($id, $task) { }

    /**
     * Build a struct representing the given message reference
     *
     * @param object|string $uri_or_headers rcube_message_header instance holding the message headers
     *                         or an URI from a stored link referencing a mail message.
     * @param string $folder  IMAP folder the message resides in
     *
     * @return array An struct referencing the given IMAP message
     */
    public function get_message_reference($uri_or_headers, $folder = null)
    {
        // to be implemented by the derived classes
        return false;
    }

    /**
     * Find tasks assigned to a specified message
     *
     * @param object $message rcube_message_header instance
     * @param string $folder  IMAP folder the message resides in
     *
     * @param array List of linked task objects
     */
    public function get_message_related_tasks($headers, $folder)
    {
        // to be implemented by the derived classes
        return array();
    }

    /**
     * Helper method to determine whether the given task is considered "complete"
     *
     * @param array  $task  Hash array with event properties
     * @return boolean True if complete, False otherwiese
     */
    public function is_complete($task)
    {
        return ($task['complete'] >= 1.0 && empty($task['status'])) || $task['status'] === 'COMPLETED';
    }

    /**
     * Provide a list of revisions for the given task
     *
     * @param array  $task Hash array with task properties:
     *         id: Task identifier
     *       list: List identifier
     *
     * @return array List of changes, each as a hash array:
     *         rev: Revision number
     *        type: Type of the change (create, update, move, delete)
     *        date: Change date
     *        user: The user who executed the change
     *          ip: Client IP
     *     mailbox: Destination list for 'move' type
     */
    public function get_task_changelog($task)
    {
        return false;
    }

    /**
     * Get a list of property changes beteen two revisions of a task object
     *
     * @param array  $task Hash array with task properties:
     *         id: Task identifier
     *       list: List identifier
     * @param mixed  $rev1   Old Revision
     * @param mixed  $rev2   New Revision
     *
     * @return array List of property changes, each as a hash array:
     *    property: Revision number
     *         old: Old property value
     *         new: Updated property value
     */
    public function get_task_diff($task, $rev1, $rev2)
    {
        return false;
    }

    /**
     * Return full data of a specific revision of an event
     *
     * @param mixed  $task UID string or hash array with task properties:
     *         id: Task identifier
     *       list: List identifier
     * @param mixed  $rev Revision number
     *
     * @return array Task object as hash array
     * @see self::get_task()
     */
    public function get_task_revison($task, $rev)
    {
        return false;
    }

    /**
     * Command the backend to restore a certain revision of a task.
     * This shall replace the current object with an older version.
     *
     * @param mixed  $task UID string or hash array with task properties:
     *         id: Task identifier
     *       list: List identifier
     * @param mixed  $rev Revision number
     *
     * @return boolean True on success, False on failure
     */
    public function restore_task_revision($task, $rev)
    {
        return false;
    }

    /**
     * Build the edit/create form for lists.
     * This gives the drivers the opportunity to add more list properties
     *
     * @param string  The action called this form
     * @param array   Tasklist properties
     * @param array   List with form fields to be rendered
     * @return string HTML content of the form
     */
    public function tasklist_edit_form($action, $list, $formfields)
    {
        $html = '';
        foreach ($formfields as $field) {
            $html .= html::div('form-section',
                html::label($field['id'], $field['label']) .
                $field['value']);
        }

        return $html;
    }

    /**
     * Compose an URL for CalDAV access to the given list (if configured)
     */
    public function tasklist_caldav_url($list)
    {
        $rcmail = rcube::get_instance();
        if (!empty($list['caldavuid']) && ($template = $rcmail->config->get('calendar_caldav_url', null))) {
            return strtr($template, array(
                '%h' => $_SERVER['HTTP_HOST'],
                '%u' => urlencode($rcmail->get_user_name()),
                '%i' => urlencode($list['caldavuid']),
                '%n' => urlencode($list['editname']),
            ));
        }

        return null;
    }

    /**
     * Handler for user_delete plugin hook
     *
     * @param array Hash array with hook arguments
     * @return array Return arguments for plugin hooks
     */
    public function user_delete($args)
    {
        // TO BE OVERRIDDEN
        return $args;
    }
}
