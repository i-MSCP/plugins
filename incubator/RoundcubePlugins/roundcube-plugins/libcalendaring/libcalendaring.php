<?php

/**
 * Library providing common functions for calendaring plugins
 *
 * Provides utility functions for calendar-related modules such as
 * - alarms display and dismissal
 * - attachment handling
 * - recurrence computation and UI elements (TODO)
 * - ical parsing and exporting (TODO)
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

class libcalendaring extends rcube_plugin
{
    public $rc;
    public $timezone;
    public $gmt_offset;
    public $dst_active;
    public $timezone_offset;

    public $defaults = array(
      'calendar_date_format'  => "yyyy-MM-dd",
      'calendar_date_short'   => "M-d",
      'calendar_date_long'    => "MMM d yyyy",
      'calendar_date_agenda'  => "ddd MM-dd",
      'calendar_time_format'  => "HH:mm",
      'calendar_first_day'    => 1,
      'calendar_first_hour'   => 6,
      'calendar_date_format_sets' => array(
        'yyyy-MM-dd' => array('MMM d yyyy',   'M-d',  'ddd MM-dd'),
        'dd-MM-yyyy' => array('d MMM yyyy',   'd-M',  'ddd dd-MM'),
        'yyyy/MM/dd' => array('MMM d yyyy',   'M/d',  'ddd MM/dd'),
        'MM/dd/yyyy' => array('MMM d yyyy',   'M/d',  'ddd MM/dd'),
        'dd/MM/yyyy' => array('d MMM yyyy',   'd/M',  'ddd dd/MM'),
        'dd.MM.yyyy' => array('dd. MMM yyyy', 'd.M',  'ddd dd.MM.'),
        'd.M.yyyy'   => array('d. MMM yyyy',  'd.M',  'ddd d.MM.'),
      ),
    );

    private static $instance;

    /**
     * Singleton getter to allow direct access from other plugins
     */
    public static function get_instance()
    {
        return self::$instance;
    }

    /**
     * Required plugin startup method
     */
    public function init()
    {
        self::$instance = $this;

        $this->rc = rcube::get_instance();

        // set user's timezone
        $this->timezone = new DateTimeZone($this->rc->config->get('timezone', 'GMT'));
        $now = new DateTime('now', $this->timezone);
        $this->gmt_offset = $now->getOffset();
        $this->dst_active = $now->format('I');
        $this->timezone_offset = $this->gmt_offset / 3600 - $this->dst_active;

        $this->add_texts('localization/', false);

        // include client scripts and styles
        if ($this->rc->output) {
            if ($this->rc->output->type == 'html') {
                $this->rc->output->set_env('libcal_settings', $this->load_settings());
                $this->include_script('libcalendaring.js');
                $this->include_stylesheet($this->local_skin_path() . '/libcal.css');
            }

            // add hook to display alarms
            $this->add_hook('refresh', array($this, 'refresh'));
            $this->register_action('plugin.alarms', array($this, 'alarms_action'));
        }
    }


    /**
     * Shift dates into user's current timezone
     *
     * @param mixed Any kind of a date representation (DateTime object, string or unix timestamp)
     * @return object DateTime object in user's timezone
     */
    public function adjust_timezone($dt)
    {
        if (is_numeric($dt))
            $dt = new DateTime('@'.$dt);
        else if (is_string($dt))
            $dt = new DateTime($dt);

        $dt->setTimezone($this->timezone);
        return $dt;
    }


    /**
     *
     */
    public function load_settings()
    {
        $this->date_format_defaults();
        $settings = array();

        // configuration
        $settings['date_format'] = (string)$this->rc->config->get('calendar_date_format', $this->defaults['calendar_date_format']);
        $settings['time_format'] = (string)$this->rc->config->get('calendar_time_format', $this->defaults['calendar_time_format']);
        $settings['date_short']  = (string)$this->rc->config->get('calendar_date_short', $this->defaults['calendar_date_short']);
        $settings['date_long']   = (string)$this->rc->config->get('calendar_date_long', $this->defaults['calendar_date_long']);
        $settings['dates_long']  = str_replace(' yyyy', '[ yyyy]', $settings['date_long']) . "{ '&mdash;' " . $settings['date_long'] . '}';
        $settings['first_day']   = (int)$this->rc->config->get('calendar_first_day', $this->defaults['calendar_first_day']);

        $settings['timezone'] = $this->timezone_offset;
        $settings['dst'] = $this->dst_active;

        // localization
        $settings['days'] = array(
            $this->rc->gettext('sunday'),   $this->rc->gettext('monday'),
            $this->rc->gettext('tuesday'),  $this->rc->gettext('wednesday'),
            $this->rc->gettext('thursday'), $this->rc->gettext('friday'),
            $this->rc->gettext('saturday')
        );
        $settings['days_short'] = array(
            $this->rc->gettext('sun'), $this->rc->gettext('mon'),
            $this->rc->gettext('tue'), $this->rc->gettext('wed'),
            $this->rc->gettext('thu'), $this->rc->gettext('fri'),
            $this->rc->gettext('sat')
        );
        $settings['months'] = array(
            $this->rc->gettext('longjan'), $this->rc->gettext('longfeb'),
            $this->rc->gettext('longmar'), $this->rc->gettext('longapr'),
            $this->rc->gettext('longmay'), $this->rc->gettext('longjun'),
            $this->rc->gettext('longjul'), $this->rc->gettext('longaug'),
            $this->rc->gettext('longsep'), $this->rc->gettext('longoct'),
            $this->rc->gettext('longnov'), $this->rc->gettext('longdec')
        );
        $settings['months_short'] = array(
            $this->rc->gettext('jan'), $this->rc->gettext('feb'),
            $this->rc->gettext('mar'), $this->rc->gettext('apr'),
            $this->rc->gettext('may'), $this->rc->gettext('jun'),
            $this->rc->gettext('jul'), $this->rc->gettext('aug'),
            $this->rc->gettext('sep'), $this->rc->gettext('oct'),
            $this->rc->gettext('nov'), $this->rc->gettext('dec')
        );
        $settings['today'] = $this->rc->gettext('today');

        // define list of file types which can be displayed inline
        // same as in program/steps/mail/show.inc
        $settings['mimetypes'] = (array)$this->rc->config->get('client_mimetypes');

        return $settings;
    }


    /**
     * Helper function to set date/time format according to config and user preferences
     */
    private function date_format_defaults()
    {
        static $defaults = array();

        // nothing to be done
        if (isset($defaults['date_format']))
          return;

        $defaults['date_format'] = $this->rc->config->get('calendar_date_format', self::from_php_date_format($this->rc->config->get('date_format')));
        $defaults['time_format'] = $this->rc->config->get('calendar_time_format', self::from_php_date_format($this->rc->config->get('time_format')));

        // override defaults
        if ($defaults['date_format'])
            $this->defaults['calendar_date_format'] = $defaults['date_format'];
        if ($defaults['time_format'])
            $this->defaults['calendar_time_format'] = $defaults['time_format'];

        // derive format variants from basic date format
        $format_sets = $this->rc->config->get('calendar_date_format_sets', $this->defaults['calendar_date_format_sets']);
        if ($format_set = $format_sets[$this->defaults['calendar_date_format']]) {
            $this->defaults['calendar_date_long'] = $format_set[0];
            $this->defaults['calendar_date_short'] = $format_set[1];
            $this->defaults['calendar_date_agenda'] = $format_set[2];
        }
    }

    /**
     * Compose a date string for the given event
     */
    public function event_date_text($event, $tzinfo = false)
    {
        $fromto = '';

        // abort if no valid event dates are given
        if (!is_object($event['start']) || !is_a($event['start'], 'DateTime') || !is_object($event['end']) || !is_a($event['end'], 'DateTime'))
            return $fromto;

        $duration = $event['start']->diff($event['end'])->format('s');

        $this->date_format_defaults();
        $date_format = self::to_php_date_format($this->rc->config->get('calendar_date_format', $this->defaults['calendar_date_format']));
        $time_format = self::to_php_date_format($this->rc->config->get('calendar_time_format', $this->defaults['calendar_time_format']));

        if ($event['allday']) {
            $fromto = format_date($event['start'], $date_format);
            if (($todate = format_date($event['end'], $date_format)) != $fromto)
                $fromto .= ' - ' . $todate;
        }
        else if ($duration < 86400 && $event['start']->format('d') == $event['end']->format('d')) {
            $fromto = format_date($event['start'], $date_format) . ' ' . format_date($event['start'], $time_format) .
                ' - ' . format_date($event['end'], $time_format);
        }
        else {
            $fromto = format_date($event['start'], $date_format) . ' ' . format_date($event['start'], $time_format) .
                ' - ' . format_date($event['end'], $date_format) . ' ' . format_date($event['end'], $time_format);
        }

        // add timezone information
        if ($tzinfo && ($tzname = $this->timezone->getName())) {
            $fromto .= ' (' . strtr($tzname, '_', ' ') . ')';
        }

        return $fromto;
    }


    /**
     * Render HTML form for alarm configuration
     */
    public function alarm_select($attrib, $alarm_types, $absolute_time = true)
    {
        unset($attrib['name']);
        $select_type = new html_select(array('name' => 'alarmtype[]', 'class' => 'edit-alarm-type'));
        $select_type->add($this->gettext('none'), '');
        foreach ($alarm_types as $type)
            $select_type->add($this->gettext(strtolower("alarm{$type}option")), $type);

        $input_value = new html_inputfield(array('name' => 'alarmvalue[]', 'class' => 'edit-alarm-value', 'size' => 3));
        $input_date = new html_inputfield(array('name' => 'alarmdate[]', 'class' => 'edit-alarm-date', 'size' => 10));
        $input_time = new html_inputfield(array('name' => 'alarmtime[]', 'class' => 'edit-alarm-time', 'size' => 6));

        $select_offset = new html_select(array('name' => 'alarmoffset[]', 'class' => 'edit-alarm-offset'));
        foreach (array('-M','-H','-D','+M','+H','+D') as $trigger)
            $select_offset->add($this->gettext('trigger' . $trigger), $trigger);

        if ($absolute_time)
            $select_offset->add($this->gettext('trigger@'), '@');

        // pre-set with default values from user settings
        $preset = self::parse_alaram_value($this->rc->config->get('calendar_default_alarm_offset', '-15M'));
        $hidden = array('style' => 'display:none');
        $html = html::span('edit-alarm-set',
            $select_type->show($this->rc->config->get('calendar_default_alarm_type', '')) . ' ' .
            html::span(array('class' => 'edit-alarm-values', 'style' => 'display:none'),
                $input_value->show($preset[0]) . ' ' .
                $select_offset->show($preset[1]) . ' ' .
                $input_date->show('', $hidden) . ' ' .
                $input_time->show('', $hidden)
            )
        );

        // TODO: support adding more alarms
        #$html .= html::a(array('href' => '#', 'id' => 'edit-alam-add', 'title' => $this->gettext('addalarm')),
        #  $attrib['addicon'] ? html::img(array('src' => $attrib['addicon'], 'alt' => 'add')) : '(+)');

        return $html;
    }


    /*********  Alarms handling  *********/

    /**
     * Helper function to convert alarm trigger strings
     * into two-field values (e.g. "-45M" => 45, "-M")
     */
    public static function parse_alaram_value($val)
    {
        if ($val[0] == '@')
            return array(substr($val, 1));
        else if (preg_match('/([+-])(\d+)([HMD])/', $val, $m))
            return array($m[2], $m[1].$m[3]);

        return false;
    }

    /**
     * Render localized text for alarm settings
     */
    public static function alarms_text($alarm)
    {
        list($trigger, $action) = explode(':', $alarm);

        $text = '';
        $rcube = rcube::get_instance();

        switch ($action) {
        case 'EMAIL':
            $text = $rcube->gettext('libcalendaring.alarmemail');
            break;
        case 'DISPLAY':
            $text = $rcube->gettext('libcalendaring.alarmdisplay');
            break;
        }

        if (preg_match('/@(\d+)/', $trigger, $m)) {
            $text .= ' ' . $rcube->gettext(array(
                'name' => 'libcalendaring.alarmat',
                'vars' => array('datetime' => $rcube->format_date($m[1]))
            ));
        }
        else if ($val = self::parse_alaram_value($trigger)) {
            $text .= ' ' . intval($val[0]) . ' ' . $rcube->gettext('libcalendaring.trigger' . $val[1]);
        }
        else
            return false;

        return $text;
    }

    /**
     * Get the next alarm (time & action) for the given event
     *
     * @param array Record data
     * @return array Hash array with alarm time/type or null if no alarms are configured
     */
    public static function get_next_alarm($rec, $type = 'event')
    {
        if (!$rec['alarms'])
            return null;

        if ($type == 'task') {
            $timezone = self::get_instance()->timezone;
            if ($rec['date'])
                $rec['start'] = new DateTime($rec['date'] . ' ' . ($rec['time'] ?: '12:00'), $timezone);
            if ($rec['startdate'])
                $rec['end'] = new DateTime($rec['startdate'] . ' ' . ($rec['starttime'] ?: '12:00'), $timezone);
        }

        if (!$rec['end'])
            $rec['end'] = $rec['start'];


        // TODO: handle multiple alarms (currently not supported)
        list($trigger, $action) = explode(':', $rec['alarms'], 2);

        $notify = self::parse_alaram_value($trigger);
        if (!empty($notify[1])){  // offset
            $mult = 1;
            switch ($notify[1]) {
                case '-S': $mult =     -1; break;
                case '+S': $mult =      1; break;
                case '-M': $mult =    -60; break;
                case '+M': $mult =     60; break;
                case '-H': $mult =  -3600; break;
                case '+H': $mult =   3600; break;
                case '-D': $mult = -86400; break;
                case '+D': $mult =  86400; break;
                case '-W': $mult = -604800; break;
                case '+W': $mult =  604800; break;
            }
            $offset = $notify[0] * $mult;
            $refdate = $mult > 0 ? $rec['end'] : $rec['start'];
            $notify_at = $refdate->format('U') + $offset;
        }
        else {  // absolute timestamp
            $notify_at = $notify[0];
        }

        return array('time' => $notify_at, 'action' => $action ? strtoupper($action) : 'DISPLAY');
    }

    /**
     * Handler for keep-alive requests
     * This will check for pending notifications and pass them to the client
     */
    public function refresh($attr)
    {
        // collect pending alarms from all providers (e.g. calendar, tasks)
        $plugin = $this->rc->plugins->exec_hook('pending_alarms', array(
            'time' => time(),
            'alarms' => array(),
        ));

        if (!$plugin['abort'] && !empty($plugin['alarms'])) {
            // make sure texts and env vars are available on client
            $this->add_texts('localization/', true);
            $this->rc->output->set_env('snooze_select', $this->snooze_select());
            $this->rc->output->command('plugin.display_alarms', $this->_alarms_output($plugin['alarms']));
        }
    }

    /**
     * Handler for alarm dismiss/snooze requests
     */
    public function alarms_action()
    {
//        $action = rcube_utils::get_input_value('action', rcube_utils::INPUT_GPC);
        $data  = rcube_utils::get_input_value('data', rcube_utils::INPUT_POST, true);

        $data['ids'] = explode(',', $data['id']);
        $plugin = $this->rc->plugins->exec_hook('dismiss_alarms', $data);

        if ($plugin['success'])
            $this->rc->output->show_message('successfullysaved', 'confirmation');
        else
            $this->rc->output->show_message('calendar.errorsaving', 'error');
    }

    /**
     * Generate reduced and streamlined output for pending alarms
     */
    private function _alarms_output($alarms)
    {
        $out = array();
        foreach ($alarms as $alarm) {
            $out[] = array(
                'id'       => $alarm['id'],
                'start'    => $alarm['start'] ? $this->adjust_timezone($alarm['start'])->format('c') : '',
                'end'      => $alarm['end']   ? $this->adjust_timezone($alarm['end'])->format('c') : '',
                'allDay'   => ($alarm['allday'] == 1)?true:false,
                'title'    => $alarm['title'],
                'location' => $alarm['location'],
                'calendar' => $alarm['calendar'],
            );
        }

        return $out;
    }

    /**
     * Render a dropdown menu to choose snooze time
     */
    private function snooze_select($attrib = array())
    {
        $steps = array(
             5 => 'repeatinmin',
            10 => 'repeatinmin',
            15 => 'repeatinmin',
            20 => 'repeatinmin',
            30 => 'repeatinmin',
            60 => 'repeatinhr',
            120 => 'repeatinhrs',
            1440 => 'repeattomorrow',
            10080 => 'repeatinweek',
        );

        $items = array();
        foreach ($steps as $n => $label) {
            $items[] = html::tag('li', null, html::a(array('href' => "#" . ($n * 60), 'class' => 'active'),
                $this->gettext(array('name' => $label, 'vars' => array('min' => $n % 60, 'hrs' => intval($n / 60))))));
        }

        return html::tag('ul', $attrib + array('class' => 'toolbarmenu'), join("\n", $items), html::$common_attrib);
    }


    /*********  Attachments handling  *********/

    /**
     * Handler for attachment uploads
     */
    public function attachment_upload($session_key, $id_prefix = '')
    {
        // Upload progress update
        if (!empty($_GET['_progress'])) {
            $this->rc->upload_progress();
        }

        $recid = $id_prefix . rcube_utils::get_input_value('_id', rcube_utils::INPUT_GPC);
        $uploadid = rcube_utils::get_input_value('_uploadid', rcube_utils::INPUT_GPC);

        if (!is_array($_SESSION[$session_key]) || $_SESSION[$session_key]['id'] != $recid) {
            $_SESSION[$session_key] = array();
            $_SESSION[$session_key]['id'] = $recid;
            $_SESSION[$session_key]['attachments'] = array();
        }

        // clear all stored output properties (like scripts and env vars)
        $this->rc->output->reset();

        if (is_array($_FILES['_attachments']['tmp_name'])) {
            foreach ($_FILES['_attachments']['tmp_name'] as $i => $filepath) {
              // Process uploaded attachment if there is no error
              $err = $_FILES['_attachments']['error'][$i];

              if (!$err) {
                $attachment = array(
                    'path' => $filepath,
                    'size' => $_FILES['_attachments']['size'][$i],
                    'name' => $_FILES['_attachments']['name'][$i],
                    'mimetype' => rcube_mime::file_content_type($filepath, $_FILES['_attachments']['name'][$i], $_FILES['_attachments']['type'][$i]),
                    'group' => $recid,
                );

                $attachment = $this->rc->plugins->exec_hook('attachment_upload', $attachment);
              }

              if (!$err && $attachment['status'] && !$attachment['abort']) {
                  $id = $attachment['id'];

                  // store new attachment in session
                  unset($attachment['status'], $attachment['abort']);
                  $_SESSION[$session_key]['attachments'][$id] = $attachment;

                  if (($icon = $_SESSION[$session_key . '_deleteicon']) && is_file($icon)) {
                      $button = html::img(array(
                          'src' => $icon,
                          'alt' => $this->rc->gettext('delete')
                      ));
                  }
                  else {
                      $button = Q($this->rc->gettext('delete'));
                  }

                  $content = html::a(array(
                      'href' => "#delete",
                      'class' => 'delete',
                      'onclick' => sprintf("return %s.remove_from_attachment_list('rcmfile%s')", JS_OBJECT_NAME, $id),
                      'title' => $this->rc->gettext('delete'),
                  ), $button);

                  $content .= Q($attachment['name']);

                  $this->rc->output->command('add2attachment_list', "rcmfile$id", array(
                      'html' => $content,
                      'name' => $attachment['name'],
                      'mimetype' => $attachment['mimetype'],
                      'classname' => rcube_utils::file2class($attachment['mimetype'], $attachment['name']),
                      'complete' => true), $uploadid);
              }
              else {  // upload failed
                  if ($err == UPLOAD_ERR_INI_SIZE || $err == UPLOAD_ERR_FORM_SIZE) {
                    $msg = $this->rc->gettext(array('name' => 'filesizeerror', 'vars' => array(
                        'size' => show_bytes(parse_bytes(ini_get('upload_max_filesize'))))));
                  }
                  else if ($attachment['error']) {
                      $msg = $attachment['error'];
                  }
                  else {
                      $msg = $this->rc->gettext('fileuploaderror');
                  }

                  $this->rc->output->command('display_message', $msg, 'error');
                  $this->rc->output->command('remove_from_attachment_list', $uploadid);
                }
            }
        }
        else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // if filesize exceeds post_max_size then $_FILES array is empty,
            // show filesizeerror instead of fileuploaderror
            if ($maxsize = ini_get('post_max_size'))
                $msg = $this->rc->gettext(array('name' => 'filesizeerror', 'vars' => array(
                    'size' => show_bytes(parse_bytes($maxsize)))));
            else
                $msg = $this->rc->gettext('fileuploaderror');

            $this->rc->output->command('display_message', $msg, 'error');
            $this->rc->output->command('remove_from_attachment_list', $uploadid);
        }

        $this->rc->output->send('iframe');
    }


    /**
     * Deliver an event/task attachment to the client
     * (similar as in Roundcube core program/steps/mail/get.inc)
     */
    public function attachment_get($attachment)
    {
        ob_end_clean();

        if ($attachment && $attachment['body']) {
            // allow post-processing of the attachment body
            $part = new rcube_message_part;
            $part->filename  = $attachment['name'];
            $part->size      = $attachment['size'];
            $part->mimetype  = $attachment['mimetype'];

            $plugin = $this->rc->plugins->exec_hook('message_part_get', array(
                'body'     => $attachment['body'],
                'mimetype' => strtolower($attachment['mimetype']),
                'download' => !empty($_GET['_download']),
                'part'     => $part,
            ));

            if ($plugin['abort'])
                exit;

            $mimetype = $plugin['mimetype'];
            list($ctype_primary, $ctype_secondary) = explode('/', $mimetype);

            $browser = $this->rc->output->browser;

            // send download headers
            if ($plugin['download']) {
                header("Content-Type: application/octet-stream");
                if ($browser->ie)
                    header("Content-Type: application/force-download");
            }
            else if ($ctype_primary == 'text') {
                header("Content-Type: text/$ctype_secondary");
            }
            else {
                header("Content-Type: $mimetype");
                header("Content-Transfer-Encoding: binary");
            }

            // display page, @TODO: support text/plain (and maybe some other text formats)
            if ($mimetype == 'text/html' && empty($_GET['_download'])) {
                $OUTPUT = new rcube_html_page();
                // @TODO: use washtml on $body
                $OUTPUT->write($plugin['body']);
            }
            else {
                // don't kill the connection if download takes more than 30 sec.
                @set_time_limit(0);

                $filename = $attachment['name'];
                $filename = preg_replace('[\r\n]', '', $filename);

                if ($browser->ie && $browser->ver < 7)
                    $filename = rawurlencode(abbreviate_string($filename, 55));
                else if ($browser->ie)
                    $filename = rawurlencode($filename);
                else
                    $filename = addcslashes($filename, '"');

                $disposition = !empty($_GET['_download']) ? 'attachment' : 'inline';
                header("Content-Disposition: $disposition; filename=\"$filename\"");

                echo $plugin['body'];
            }

            exit;
        }

        // if we arrive here, the requested part was not found
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    /**
     * Show "loading..." page in attachment iframe
     */
    public function attachment_loading_page()
    {
        $url = str_replace('&_preload=1', '', $_SERVER['REQUEST_URI']);
        $message = $this->rc->gettext('loadingdata');

        header('Content-Type: text/html; charset=' . RCMAIL_CHARSET);
        print "<html>\n<head>\n"
            . '<meta http-equiv="refresh" content="0; url='.Q($url).'">' . "\n"
            . '<meta http-equiv="content-type" content="text/html; charset='.RCMAIL_CHARSET.'">' . "\n"
            . "</head>\n<body>\n$message\n</body>\n</html>";
        exit;
    }

    /**
     * Template object for attachment display frame
     */
    public function attachment_frame($attrib = array())
    {
        $mimetype = strtolower($this->attachment['mimetype']);
        list($ctype_primary, $ctype_secondary) = explode('/', $mimetype);

        $attrib['src'] = './?' . str_replace('_frame=', ($ctype_primary == 'text' ? '_show=' : '_preload='), $_SERVER['QUERY_STRING']);

        return html::iframe($attrib);
    }

    /**
     *
     */
    public function attachment_header($attrib = array())
    {
        $table = new html_table(array('cols' => 3));

        if (!empty($this->attachment['name'])) {
            $table->add('title', Q($this->rc->gettext('filename')));
            $table->add('header', Q($this->attachment['name']));
            $table->add('download-link', html::a('?'.str_replace('_frame=', '_download=', $_SERVER['QUERY_STRING']), Q($this->rc->gettext('download'))));
        }

        if (!empty($this->attachment['size'])) {
            $table->add('title', Q($this->rc->gettext('filesize')));
            $table->add('header', Q(show_bytes($this->attachment['size'])));
        }

        return $table->show($attrib);
    }


    /*********  Static utility functions  *********/

    /**
     * Convert the internal structured data into a vcalendar rrule 2.0 string
     */
    public static function to_rrule($recurrence)
    {
        if (is_string($recurrence))
            return $recurrence;

        $rrule = '';
        foreach ((array)$recurrence as $k => $val) {
            $k = strtoupper($k);
            switch ($k) {
            case 'UNTIL':
                $val = $val->format('Ymd\THis');
                break;
            case 'EXDATE':
                foreach ((array)$val as $i => $ex)
                    $val[$i] = $ex->format('Ymd\THis');
                $val = join(',', (array)$val);
                break;
            }
            $rrule .= $k . '=' . $val . ';';
        }

        return rtrim($rrule, ';');
    }

    /**
     * Convert from fullcalendar date format to PHP date() format string
     */
    public static function to_php_date_format($from)
    {
        // "dd.MM.yyyy HH:mm:ss" => "d.m.Y H:i:s"
        return strtr(strtr($from, array(
            'yyyy' => 'Y',
            'yy'   => 'y',
            'MMMM' => 'F',
            'MMM'  => 'M',
            'MM'   => 'm',
            'M'    => 'n',
            'dddd' => 'l',
            'ddd'  => 'D',
            'dd'   => 'd',
            'd'    => 'j',
            'HH'   => '**',
            'hh'   => '%%',
            'H'    => 'G',
            'h'    => 'g',
            'mm'   => 'i',
            'ss'   => 's',
            'TT'   => 'A',
            'tt'   => 'a',
            'T'    => 'A',
            't'    => 'a',
            'u'    => 'c',
        )), array(
            '**'   => 'H',
            '%%'   => 'h',
        ));
    }

    /**
     * Convert from PHP date() format to fullcalendar format string
     */
    public static function from_php_date_format($from)
    {
        // "d.m.Y H:i:s" => "dd.MM.yyyy HH:mm:ss"
        return strtr($from, array(
            'y' => 'yy',
            'Y' => 'yyyy',
            'M' => 'MMM',
            'F' => 'MMMM',
            'm' => 'MM',
            'n' => 'M',
            'j' => 'd',
            'd' => 'dd',
            'D' => 'ddd',
            'l' => 'dddd',
            'H' => 'HH',
            'h' => 'hh',
            'G' => 'H',
            'g' => 'h',
            'i' => 'mm',
            's' => 'ss',
            'A' => 'TT',
            'a' => 'tt',
            'c' => 'u',
        ));
    }

}
