<?php

/**
 * Tasks plugin for Roundcube webmail
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

class tasklist extends rcube_plugin
{
    const FILTER_MASK_TODAY = 1;
    const FILTER_MASK_TOMORROW = 2;
    const FILTER_MASK_WEEK = 4;
    const FILTER_MASK_LATER = 8;
    const FILTER_MASK_NODATE = 16;
    const FILTER_MASK_OVERDUE = 32;
    const FILTER_MASK_FLAGGED = 64;
    const FILTER_MASK_COMPLETE = 128;

    const SESSION_KEY = 'tasklist_temp';

    public static $filter_masks = array(
        'today'    => self::FILTER_MASK_TODAY,
        'tomorrow' => self::FILTER_MASK_TOMORROW,
        'week'     => self::FILTER_MASK_WEEK,
        'later'    => self::FILTER_MASK_LATER,
        'nodate'   => self::FILTER_MASK_NODATE,
        'overdue'  => self::FILTER_MASK_OVERDUE,
        'flagged'  => self::FILTER_MASK_FLAGGED,
        'complete' => self::FILTER_MASK_COMPLETE,
    );

    public $task = '?(?!login|logout).*';
    public $rc;
    public $lib;
    public $driver;
    public $timezone;
    public $ui;

    private $collapsed_tasks = array();


    /**
     * Plugin initialization.
     */
    function init()
    {
        $this->require_plugin('libcalendaring');

        $this->rc = rcube::get_instance();
        $this->lib = libcalendaring::get_instance();

        $this->register_task('tasks', 'tasklist');

        // load plugin configuration
        $this->load_config();

        // load localizations
        $this->add_texts('localization/', $this->rc->task == 'tasks' && (!$this->rc->action || $this->rc->action == 'print'));
        $this->rc->load_language($_SESSION['language'], array('tasks.tasks' => $this->gettext('navtitle')));  // add label for task title

        $this->timezone = $this->lib->timezone;

        if ($this->rc->task == 'tasks' && $this->rc->action != 'save-pref') {
            $this->load_driver();

            // register calendar actions
            $this->register_action('index', array($this, 'tasklist_view'));
            $this->register_action('task', array($this, 'task_action'));
            $this->register_action('tasklist', array($this, 'tasklist_action'));
            $this->register_action('counts', array($this, 'fetch_counts'));
            $this->register_action('fetch', array($this, 'fetch_tasks'));
            $this->register_action('inlineui', array($this, 'get_inline_ui'));
            $this->register_action('mail2task', array($this, 'mail_message2task'));
            $this->register_action('get-attachment', array($this, 'attachment_get'));
            $this->register_action('upload', array($this, 'attachment_upload'));

            $this->collapsed_tasks = array_filter(explode(',', $this->rc->config->get('tasklist_collapsed_tasks', '')));
        }
        else if ($this->rc->task == 'mail') {
            // TODO: register hooks to catch ical/vtodo email attachments
            if ($this->rc->action == 'show' || $this->rc->action == 'preview') {
                // $this->add_hook('message_load', array($this, 'mail_message_load'));
                // $this->add_hook('template_object_messagebody', array($this, 'mail_messagebody_html'));
            }

            // add 'Create event' item to message menu
            if ($this->api->output->type == 'html') {
                $this->api->add_content(html::tag('li', null, 
                    $this->api->output->button(array(
                        'command'  => 'tasklist-create-from-mail',
                        'label'    => 'tasklist.createfrommail',
                        'type'     => 'link',
                        'classact' => 'icon taskaddlink active',
                        'class'    => 'icon taskaddlink',
                        'innerclass' => 'icon taskadd',
                    ))),
                'messagemenu');
            }
        }

        if (!$this->rc->output->ajax_call && !$this->rc->output->env['framed']) {
            require_once($this->home . '/tasklist_ui.php');
            $this->ui = new tasklist_ui($this);
            $this->ui->init();
        }

        // add hooks for alarms handling
        $this->add_hook('pending_alarms', array($this, 'pending_alarms'));
        $this->add_hook('dismiss_alarms', array($this, 'dismiss_alarms'));
    }


    /**
     * Helper method to load the backend driver according to local config
     */
    private function load_driver()
    {
        if (is_object($this->driver))
            return;

        $driver_name = $this->rc->config->get('tasklist_driver', 'database');
        $driver_class = 'tasklist_' . $driver_name . '_driver';

        require_once($this->home . '/drivers/tasklist_driver.php');
        require_once($this->home . '/drivers/' . $driver_name . '/' . $driver_class . '.php');

        switch ($driver_name) {
        case "kolab":
            $this->require_plugin('libkolab');
        default:
            $this->driver = new $driver_class($this);
            break;
        }

        $this->rc->output->set_env('tasklist_driver', $driver_name);
    }


    /**
     * Dispatcher for task-related actions initiated by the client
     */
    public function task_action()
    {
        $filter = intval(get_input_value('filter', RCUBE_INPUT_GPC));
        $action = get_input_value('action', RCUBE_INPUT_GPC);
        $rec  = get_input_value('t', RCUBE_INPUT_POST, true);
        $oldrec = $rec;
        $success = $refresh = false;

        switch ($action) {
        case 'new':
            $oldrec = null;
            $rec = $this->prepare_task($rec);
            $rec['uid'] = $this->generate_uid();
            $temp_id = $rec['tempid'];
            if ($success = $this->driver->create_task($rec)) {
                $refresh = $this->driver->get_task($rec);
                if ($temp_id) $refresh['tempid'] = $temp_id;
                $this->cleanup_task($rec);
            }
            break;

        case 'edit':
            $rec = $this->prepare_task($rec);
            if ($success = $this->driver->edit_task($rec)) {
                $refresh = $this->driver->get_task($rec);
                $this->cleanup_task($rec);
            }
            break;

          case 'move':
              foreach ((array)$rec['id'] as $id) {
                  $r = $rec;
                  $r['id'] = $id;
                  if ($this->driver->move_task($r)) {
                      $refresh[] = $this->driver->get_task($r);
                      $success = true;

                      // move all childs, too
                      foreach ($this->driver->get_childs(array('id' => $rec['id'], 'list' => $rec['_fromlist'])) as $cid) {
                          $child = $rec;
                          $child['id'] = $cid;
                          if ($this->driver->move_task($child)) {
                              $r = $this->driver->get_task($child);
                              if ((bool)($filter & self::FILTER_MASK_COMPLETE) == ($r['complete'] == 1.0)) {
                                  $refresh[] = $r;
                              }
                          }
                      }
                  }
              }
              break;

        case 'delete':
            $mode  = intval(get_input_value('mode', RCUBE_INPUT_POST));
            $oldrec = $this->driver->get_task($rec);
            if ($success = $this->driver->delete_task($rec, false)) {
                // delete/modify all childs
                foreach ($this->driver->get_childs($rec, $mode) as $cid) {
                    $child = array('id' => $cid, 'list' => $rec['list']);

                    if ($mode == 1) {  // delete all childs
                        if ($this->driver->delete_task($child, false)) {
                            if ($this->driver->undelete)
                                $_SESSION['tasklist_undelete'][$rec['id']][] = $cid;
                        }
                        else
                            $success = false;
                    }
                    else {
                        $child['parent_id'] = strval($oldrec['parent_id']);
                        $this->driver->edit_task($child);
                    }
                }
            }

            if (!$success)
                $this->rc->output->command('plugin.reload_data');
            break;

        case 'undelete':
            if ($success = $this->driver->undelete_task($rec)) {
                $refresh[] = $this->driver->get_task($rec);
                foreach ((array)$_SESSION['tasklist_undelete'][$rec['id']] as $cid) {
                    if ($this->driver->undelete_task($rec)) {
                        $refresh[] = $this->driver->get_task($rec);
                    }
                }
            }
            break;

        case 'collapse':
            if (intval(get_input_value('collapsed', RCUBE_INPUT_GPC))) {
                $this->collapsed_tasks[] = $rec['id'];
            }
            else {
                $i = array_search($rec['id'], $this->collapsed_tasks);
                if ($i !== false)
                    unset($this->collapsed_tasks[$i]);
            }

            $this->rc->user->save_prefs(array('tasklist_collapsed_tasks' => join(',', array_unique($this->collapsed_tasks))));
            return;  // avoid further actions
        }

        if ($success) {
            $this->rc->output->show_message('successfullysaved', 'confirmation');
            $this->update_counts($oldrec, $refresh);
        }
        else
            $this->rc->output->show_message('tasklist.errorsaving', 'error');

        // unlock client
        $this->rc->output->command('plugin.unlock_saving');

        if ($refresh) {
            if ($refresh['id']) {
                $this->encode_task($refresh);
            }
            else if (is_array($refresh)) {
                foreach ($refresh as $i => $r)
                    $this->encode_task($refresh[$i]);
            }
            $this->rc->output->command('plugin.refresh_task', $refresh);
        }
    }

    /**
     * repares new/edited task properties before save
     */
    private function prepare_task($rec)
    {
        // try to be smart and extract date from raw input
        if ($rec['raw']) {
            foreach (array('today','tomorrow','sunday','monday','tuesday','wednesday','thursday','friday','saturday','sun','mon','tue','wed','thu','fri','sat') as $word) {
                $locwords[] = '/^' . preg_quote(mb_strtolower($this->gettext($word))) . '\b/i';
                $normwords[] = $word;
                $datewords[] = $word;
            }
            foreach (array('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','now','dec') as $month) {
                $locwords[] = '/(' . preg_quote(mb_strtolower($this->gettext('long'.$month))) . '|' . preg_quote(mb_strtolower($this->gettext($month))) . ')\b/i';
                $normwords[] = $month;
                $datewords[] = $month;
            }
            foreach (array('on','this','next','at') as $word) {
                $fillwords[] = preg_quote(mb_strtolower($this->gettext($word)));
                $fillwords[] = $word;
            }

            $raw = trim($rec['raw']);
            $date_str = '';

            // translate localized keywords
            $raw = preg_replace('/^(' . join('|', $fillwords) . ')\s*/i', '', $raw);
            $raw = preg_replace($locwords, $normwords, $raw);

            // find date pattern
            $date_pattern = '!^(\d+[./-]\s*)?((?:\d+[./-])|' . join('|', $datewords) . ')\.?(\s+\d{4})?[:;,]?\s+!i';
            if (preg_match($date_pattern, $raw, $m)) {
                $date_str .= $m[1] . $m[2] . $m[3];
                $raw = preg_replace(array($date_pattern, '/^(' . join('|', $fillwords) . ')\s*/i'), '', $raw);
                // add year to date string
                if ($m[1] && !$m[3])
                    $date_str .= date('Y');
            }

            // find time pattern
            $time_pattern = '/^(\d+([:.]\d+)?(\s*[hapm.]+)?),?\s+/i';
            if (preg_match($time_pattern, $raw, $m)) {
                $has_time = true;
                $date_str .= ($date_str ? ' ' : 'today ') . $m[1];
                $raw = preg_replace($time_pattern, '', $raw);
            }

            // yes, raw input matched a (valid) date
            if (strlen($date_str) && strtotime($date_str) && ($date = new DateTime($date_str, $this->timezone))) {
                $rec['date'] = $date->format('Y-m-d');
                if ($has_time)
                    $rec['time'] = $date->format('H:i');
                $rec['title'] = $raw;
            }
            else
                $rec['title'] = $rec['raw'];
        }

        // normalize input from client
        if (isset($rec['complete'])) {
            $rec['complete'] = floatval($rec['complete']);
            if ($rec['complete'] > 1)
                $rec['complete'] /= 100;
        }
        if (isset($rec['flagged']))
            $rec['flagged'] = intval($rec['flagged']);

        // fix for garbage input
        if ($rec['description'] == 'null')
            $rec['description'] = '';

        foreach ($rec as $key => $val) {
            if ($val === 'null')
                $rec[$key] = null;
        }

        if (!empty($rec['date'])) {
            try {
                $date = new DateTime($rec['date'] . ' ' . $rec['time'], $this->timezone);
                $rec['date'] = $date->format('Y-m-d');
                if (!empty($rec['time']))
                    $rec['time'] = $date->format('H:i');
            }
            catch (Exception $e) {
                $rec['date'] = $rec['time'] = null;
            }
        }

        if (!empty($rec['startdate'])) {
            try {
                $date = new DateTime($rec['startdate'] . ' ' . $rec['starttime'], $this->timezone);
                $rec['startdate'] = $date->format('Y-m-d');
                if (!empty($rec['starttime']))
                    $rec['starttime'] = $date->format('H:i');
            }
            catch (Exception $e) {
                $rec['startdate'] = $rec['starttime'] = null;
            }
        }

        // alarms cannot work without a date
        if ($rec['alarms'] && !$rec['date'] && !$rec['startdate'] && strpos($rec['alarms'], '@') === false)
            $rec['alarms'] = '';

        $attachments = array();
        $taskid = $rec['id'];
        if (is_array($_SESSION[self::SESSION_KEY]) && $_SESSION[self::SESSION_KEY]['id'] == $taskid) {
            if (!empty($_SESSION[self::SESSION_KEY]['attachments'])) {
                foreach ($_SESSION[self::SESSION_KEY]['attachments'] as $id => $attachment) {
                    if (is_array($rec['attachments']) && in_array($id, $rec['attachments'])) {
                        $attachments[$id] = $this->rc->plugins->exec_hook('attachment_get', $attachment);
                        unset($attachments[$id]['abort'], $attachments[$id]['group']);
                    }
                }
            }
        }

        $rec['attachments'] = $attachments;

        if (is_numeric($rec['id']) && $rec['id'] < 0)
            unset($rec['id']);

        return $rec;
    }


    /**
     * Releases some resources after successful save
     */
    private function cleanup_task(&$rec)
    {
        // remove temp. attachment files
        if (!empty($_SESSION[self::SESSION_KEY]) && ($taskid = $_SESSION[self::SESSION_KEY]['id'])) {
            $this->rc->plugins->exec_hook('attachments_cleanup', array('group' => $taskid));
            $this->rc->session->remove(self::SESSION_KEY);
        }
    }


    /**
     * Dispatcher for tasklist actions initiated by the client
     */
    public function tasklist_action()
    {
        $action = get_input_value('action', RCUBE_INPUT_GPC);
        $list  = get_input_value('l', RCUBE_INPUT_POST, true);
        $success = false;

        if (isset($list['showalarms']))
          $list['showalarms'] = intval($list['showalarms']);

        switch ($action) {
        case 'new':
            $list += array('showalarms' => true, 'active' => true, 'editable' => true);
            if ($insert_id = $this->driver->create_list($list)) {
                $list['id'] = $insert_id;
                $this->rc->output->command('plugin.insert_tasklist', $list);
                $success = true;
            }
            break;

        case 'edit':
            if ($newid = $this->driver->edit_list($list)) {
                $list['oldid'] = $list['id'];
                $list['id'] = $newid;
                $this->rc->output->command('plugin.update_tasklist', $list);
                $success = true;
            }
            break;

        case 'subscribe':
            $success = $this->driver->subscribe_list($list);
            break;

        case 'remove':
            if (($success = $this->driver->remove_list($list)))
                $this->rc->output->command('plugin.destroy_tasklist', $list);
            break;
        }

        if ($success)
            $this->rc->output->show_message('successfullysaved', 'confirmation');
        else
            $this->rc->output->show_message('tasklist.errorsaving', 'error');

        $this->rc->output->command('plugin.unlock_saving');
    }

    /**
     * Get counts for active tasks divided into different selectors
     */
    public function fetch_counts()
    {
        if (isset($_REQUEST['lists'])) {
            $lists = get_input_value('lists', RCUBE_INPUT_GPC);
        }
        else {
            foreach ($this->driver->get_lists() as $list) {
                if ($list['active'])
                    $lists[] = $list['id'];
            }
        }
        $counts = $this->driver->count_tasks($lists);
        $this->rc->output->command('plugin.update_counts', $counts);
    }

    /**
     * Adjust the cached counts after changing a task
     */
    public function update_counts($oldrec, $newrec)
    {
        // rebuild counts until this function is finally implemented
        $this->fetch_counts();

        // $this->rc->output->command('plugin.update_counts', $counts);
    }

    /**
     *
     */
    public function fetch_tasks()
    {
        $f = intval(get_input_value('filter', RCUBE_INPUT_GPC));
        $search = get_input_value('q', RCUBE_INPUT_GPC);
        $filter = array('mask' => $f, 'search' => $search);
        $lists = get_input_value('lists', RCUBE_INPUT_GPC);;
/*
        // convert magic date filters into a real date range
        switch ($f) {
        case self::FILTER_MASK_TODAY:
            $today = new DateTime('now', $this->timezone);
            $filter['from'] = $filter['to'] = $today->format('Y-m-d');
            break;

        case self::FILTER_MASK_TOMORROW:
            $tomorrow = new DateTime('now + 1 day', $this->timezone);
            $filter['from'] = $filter['to'] = $tomorrow->format('Y-m-d');
            break;

        case self::FILTER_MASK_OVERDUE:
            $yesterday = new DateTime('yesterday', $this->timezone);
            $filter['to'] = $yesterday->format('Y-m-d');
            break;

        case self::FILTER_MASK_WEEK:
            $today = new DateTime('now', $this->timezone);
            $filter['from'] = $today->format('Y-m-d');
            $weekend = new DateTime('now + 7 days', $this->timezone);
            $filter['to'] = $weekend->format('Y-m-d');
            break;

        case self::FILTER_MASK_LATER:
            $date = new DateTime('now + 8 days', $this->timezone);
            $filter['from'] = $date->format('Y-m-d');
            break;

        }
*/
        $data = $tags = $this->task_tree = $this->task_titles = array();
        foreach ($this->driver->list_tasks($filter, $lists) as $rec) {
            if ($rec['parent_id']) {
                $this->task_tree[$rec['id']] = $rec['parent_id'];
            }
            $this->encode_task($rec);
            if (!empty($rec['tags']))
                $tags = array_merge($tags, (array)$rec['tags']);

            // apply filter; don't trust the driver on this :-)
            if ((!$f && $rec['complete'] < 1.0) || ($rec['mask'] & $f))
                $data[] = $rec;
        }

        // sort tasks according to their hierarchy level and due date
        array_walk($data, array($this, 'task_walk_tree'));
        usort($data, array($this, 'task_sort_cmp'));

        $this->rc->output->command('plugin.data_ready', array('filter' => $f, 'lists' => $lists, 'search' => $search, 'data' => $data, 'tags' => array_values(array_unique($tags))));
    }

    /**
     * Prepare the given task record before sending it to the client
     */
    private function encode_task(&$rec)
    {
        $rec['mask'] = $this->filter_mask($rec);
        $rec['flagged'] = intval($rec['flagged']);
        $rec['complete'] = floatval($rec['complete']);
        $rec['changed'] = is_object($rec['changed']) ? $rec['changed']->format('U') : null;

        if ($rec['date']) {
            try {
                $date = new DateTime($rec['date'] . ' ' . $rec['time'], $this->timezone);
                $rec['datetime'] = intval($date->format('U'));
                $rec['date'] = $date->format($this->rc->config->get('date_format', 'Y-m-d'));
                $rec['_hasdate'] = 1;
            }
            catch (Exception $e) {
                $rec['date'] = $rec['datetime'] = null;
            }
        }
        else {
            $rec['date'] = $rec['datetime'] = null;
            $rec['_hasdate'] = 0;
        }

        if ($rec['startdate']) {
            try {
                $date = new DateTime($rec['startdate'] . ' ' . $rec['starttime'], $this->timezone);
                $rec['startdatetime'] = intval($date->format('U'));
                $rec['startdate'] = $date->format($this->rc->config->get('date_format', 'Y-m-d'));
            }
            catch (Exception $e) {
                $rec['startdate'] = $rec['startdatetime'] = null;
            }
        }

        if ($rec['alarms'])
            $rec['alarms_text'] = libcalendaring::alarms_text($rec['alarms']);

        foreach ((array)$rec['attachments'] as $k => $attachment) {
            $rec['attachments'][$k]['classname'] = rcube_utils::file2class($attachment['mimetype'], $attachment['name']);
        }

        if (in_array($rec['id'], $this->collapsed_tasks))
          $rec['collapsed'] = true;

        $this->task_titles[$rec['id']] = $rec['title'];
    }

    /**
     * Callback function for array_walk over all tasks.
     * Sets tree depth and parent titles
     */
    private function task_walk_tree(&$rec)
    {
        $rec['_depth'] = 0;
        $parent_id = $this->task_tree[$rec['id']];
        while ($parent_id) {
            $rec['_depth']++;
            $rec['parent_title'] = $this->task_titles[$parent_id];
            $parent_id = $this->task_tree[$parent_id];
        }
    }

    /**
     * Compare function for task list sorting.
     * Nested tasks need to be sorted to the end.
     */
    private function task_sort_cmp($a, $b)
    {
        $d = $a['_depth'] - $b['_depth'];
        if (!$d) $d = $b['_hasdate'] - $a['_hasdate'];
        if (!$d) $d = $a['datetime'] - $b['datetime'];
        return $d;
    }

    /**
     * Compute the filter mask of the given task
     *
     * @param array Hash array with Task record properties
     * @return int Filter mask
     */
    public function filter_mask($rec)
    {
        static $today, $tomorrow, $weeklimit;

        if (!$today) {
            $today_date = new DateTime('now', $this->timezone);
            $today = $today_date->format('Y-m-d');
            $tomorrow_date = new DateTime('now + 1 day', $this->timezone);
            $tomorrow = $tomorrow_date->format('Y-m-d');
            $week_date = new DateTime('now + 7 days', $this->timezone);
            $weeklimit = $week_date->format('Y-m-d');
        }

        $mask = 0;
        $start = $rec['startdate'] ?: '1900-00-00';
        $duedate = $rec['date'] ?: '3000-00-00';

        if ($rec['flagged'])
            $mask |= self::FILTER_MASK_FLAGGED;
        if ($rec['complete'] == 1.0)
            $mask |= self::FILTER_MASK_COMPLETE;

        if (empty($rec['date']))
            $mask |= self::FILTER_MASK_NODATE;
        else if ($rec['date'] < $today)
            $mask |= self::FILTER_MASK_OVERDUE;

        if ($duedate <= $today || ($rec['startdate'] && $start <= $today))
            $mask |= self::FILTER_MASK_TODAY;
        if ($duedate <= $tomorrow || ($rec['startdate'] && $start <= $tomorrow))
            $mask |= self::FILTER_MASK_TOMORROW;
        if ($start > $tomorrow || ($duedate > $tomorrow && $duedate <= $weeklimit))
            $mask |= self::FILTER_MASK_WEEK;
        if ($start > $weeklimit || ($rec['date'] && $duedate > $weeklimit))
            $mask |= self::FILTER_MASK_LATER;

        return $mask;
    }


    /*******  UI functions  ********/

    /**
     * Render main view of the tasklist task
     */
    public function tasklist_view()
    {
        $this->ui->init();
        $this->ui->init_templates();
        $this->rc->output->set_pagetitle($this->gettext('navtitle'));
        $this->rc->output->send('tasklist.mainview');
    }


    /**
     *
     */
    public function get_inline_ui()
    {
        foreach (array('save','cancel','savingdata') as $label)
            $texts['tasklist.'.$label] = $this->gettext($label);

        $texts['tasklist.newtask'] = $this->gettext('createfrommail');

        $this->ui->init_templates();
        echo $this->api->output->parse('tasklist.taskedit', false, false);
        echo html::tag('script', array('type' => 'text/javascript'),
            "rcmail.set_env('tasklists', " . json_encode($this->api->output->env['tasklists']) . ");\n".
//            "rcmail.set_env('deleteicon', '" . $this->api->output->env['deleteicon'] . "');\n".
//            "rcmail.set_env('cancelicon', '" . $this->api->output->env['cancelicon'] . "');\n".
//            "rcmail.set_env('loadingicon', '" . $this->api->output->env['loadingicon'] . "');\n".
            "rcmail.add_label(" . json_encode($texts) . ");\n"
        );
        exit;
    }


    /**
     * Handler for pending_alarms plugin hook triggered by the calendar module on keep-alive requests.
     * This will check for pending notifications and pass them to the client
     */
    public function pending_alarms($p)
    {
        $this->load_driver();
        if ($alarms = $this->driver->pending_alarms($p['time'] ?: time())) {
            foreach ($alarms as $alarm) {
                // encode alarm object to suit the expectations of the calendaring code
                if ($alarm['date'])
                    $alarm['start'] = new DateTime($alarm['date'].' '.$alarm['time'], $this->timezone);

                $alarm['id'] = 'task:' . $alarm['id'];  // prefix ID with task:
                $alarm['allday'] = empty($alarm['time']) ? 1 : 0;
                $p['alarms'][] = $alarm;
            }
        }

        return $p;
    }

    /**
     * Handler for alarm dismiss hook triggered by the calendar module
     */
    public function dismiss_alarms($p)
    {
        $this->load_driver();
        foreach ((array)$p['ids'] as $id) {
            if (strpos($id, 'task:') === 0)
                $p['success'] |= $this->driver->dismiss_alarm(substr($id, 5), $p['snooze']);
        }

        return $p;
    }


    /******* Attachment handling  *******/

    /**
     * Handler for attachments upload
    */
    public function attachment_upload()
    {
        $this->lib->attachment_upload(self::SESSION_KEY);
    }

    /**
     * Handler for attachments download/displaying
     */
    public function attachment_get()
    {
        // show loading page
        if (!empty($_GET['_preload'])) {
            return $this->lib->attachment_loading_page();
        }

        $task = get_input_value('_t', RCUBE_INPUT_GPC);
        $list = get_input_value('_list', RCUBE_INPUT_GPC);
        $id   = get_input_value('_id', RCUBE_INPUT_GPC);

        $task = array('id' => $task, 'list' => $list);
        $attachment = $this->driver->get_attachment($id, $task);

        // show part page
        if (!empty($_GET['_frame'])) {
            $this->lib->attachment = $attachment;
            $this->register_handler('plugin.attachmentframe', array($this->lib, 'attachment_frame'));
            $this->register_handler('plugin.attachmentcontrols', array($this->lib, 'attachment_header'));
            $this->rc->output->send('tasklist.attachment');
        }
        // deliver attachment content
        else if ($attachment) {
            $attachment['body'] = $this->driver->get_attachment_body($id, $task);
            $this->lib->attachment_get($attachment);
        }

        // if we arrive here, the requested part was not found
        header('HTTP/1.1 404 Not Found');
        exit;
    }


    /*******  Email related function *******/

    public function mail_message2task()
    {
        $uid = get_input_value('_uid', RCUBE_INPUT_POST);
        $mbox = get_input_value('_mbox', RCUBE_INPUT_POST);
        $task = array();

        // establish imap connection
        $imap = $this->rc->get_storage();
        $imap->set_mailbox($mbox);
        $message = new rcube_message($uid);

        if ($message->headers) {
            $task['title'] = trim($message->subject);
            $task['description'] = trim($message->first_text_part());
            $task['id'] = -$uid;

            $this->load_driver();

            // copy mail attachments to task
            if ($message->attachments && $this->driver->attachments) {
                if (!is_array($_SESSION[self::SESSION_KEY]) || $_SESSION[self::SESSION_KEY]['id'] != $task['id']) {
                    $_SESSION[self::SESSION_KEY] = array();
                    $_SESSION[self::SESSION_KEY]['id'] = $task['id'];
                    $_SESSION[self::SESSION_KEY]['attachments'] = array();
                }

                foreach ((array)$message->attachments as $part) {
                    $attachment = array(
                        'data' => $imap->get_message_part($uid, $part->mime_id, $part),
                        'size' => $part->size,
                        'name' => $part->filename,
                        'mimetype' => $part->mimetype,
                        'group' => $task['id'],
                    );

                    $attachment = $this->rc->plugins->exec_hook('attachment_save', $attachment);

                    if ($attachment['status'] && !$attachment['abort']) {
                        $id = $attachment['id'];
                        $attachment['classname'] = rcube_utils::file2class($attachment['mimetype'], $attachment['name']);

                        // store new attachment in session
                        unset($attachment['status'], $attachment['abort'], $attachment['data']);
                        $_SESSION[self::SESSION_KEY]['attachments'][$id] = $attachment;

                        $attachment['id'] = 'rcmfile' . $attachment['id'];  // add prefix to consider it 'new'
                        $task['attachments'][] = $attachment;
                    }
                }
            }

            $this->rc->output->command('plugin.mail2taskdialog', $task);
        }
        else {
            $this->rc->output->command('display_message', $this->gettext('messageopenerror'), 'error');
        }

        $this->rc->output->send();
    }


    /*******  Utility functions  *******/

    /**
     * Generate a unique identifier for an event
     */
    public function generate_uid()
    {
      return strtoupper(md5(time() . uniqid(rand())) . '-' . substr(md5($this->rc->user->get_username()), 0, 16));
    }

}

