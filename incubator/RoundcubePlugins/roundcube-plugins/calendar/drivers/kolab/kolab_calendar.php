<?php

/**
 * Kolab calendar storage class
 *
 * @version @package_version@
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 * @author Aleksander Machniak <machniak@kolabsys.com>
 *
 * Copyright (C) 2012-2014, Kolab Systems AG <contact@kolabsys.com>
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


class kolab_calendar extends kolab_storage_folder_api
{
  public $ready = false;
  public $readonly = true;
  public $attachments = true;
  public $alarms = false;
  public $history = false;
  public $subscriptions = true;
  public $categories = array();
  public $storage;

  public $type = 'event';

  protected $cal;
  protected $events = array();
  protected $search_fields = array('title', 'description', 'location', 'attendees');

  /**
   * Factory method to instantiate a kolab_calendar object
   *
   * @param string  Calendar ID (encoded IMAP folder name)
   * @param object  calendar plugin object
   * @return object kolab_calendar instance
   */
  public static function factory($id, $calendar)
  {
    $imap = $calendar->rc->get_storage();
    $imap_folder = kolab_storage::id_decode($id);
    $info = $imap->folder_info($imap_folder, true);
    if (empty($info) || $info['noselect'] || strpos(kolab_storage::folder_type($imap_folder), 'event') !== 0) {
      return new kolab_user_calendar($imap_folder, $calendar);
    }
    else {
      return new kolab_calendar($imap_folder, $calendar);
    }
  }

  /**
   * Default constructor
   */
  public function __construct($imap_folder, $calendar)
  {
    $this->cal = $calendar;
    $this->imap = $calendar->rc->get_storage();
    $this->name = $imap_folder;

    // ID is derrived from folder name
    $this->id = kolab_storage::folder_id($this->name, true);
    $old_id   = kolab_storage::folder_id($this->name, false);

    // fetch objects from the given IMAP folder
    $this->storage = kolab_storage::get_folder($this->name);
    $this->ready = $this->storage && $this->storage->valid;

    // Set readonly and alarms flags according to folder permissions
    if ($this->ready) {
      if ($this->storage->get_namespace() == 'personal') {
        $this->readonly = false;
        $this->alarms = true;
      }
      else {
        $rights = $this->storage->get_myrights();
        if ($rights && !PEAR::isError($rights)) {
          if (strpos($rights, 'i') !== false)
            $this->readonly = false;
        }
      }
      
      // user-specific alarms settings win
      $prefs = $this->cal->rc->config->get('kolab_calendars', array());
      if (isset($prefs[$this->id]['showalarms']))
        $this->alarms = $prefs[$this->id]['showalarms'];
      else if (isset($prefs[$old_id]['showalarms']))
        $this->alarms = $prefs[$old_id]['showalarms'];
    }

    $this->default = $this->storage->default;
    $this->subtype = $this->storage->subtype;
  }


  /**
   * Getter for the IMAP folder name
   *
   * @return string Name of the IMAP folder
   */
  public function get_realname()
  {
    return $this->name;
  }

  /**
   *
   */
  public function get_title()
  {
    return null;
  }


  /**
   * Return color to display this calendar
   */
  public function get_color()
  {
    // color is defined in folder METADATA
    if ($color = $this->storage->get_color()) {
      return $color;
    }

    // calendar color is stored in user prefs (temporary solution)
    $prefs = $this->cal->rc->config->get('kolab_calendars', array());

    if (!empty($prefs[$this->id]) && !empty($prefs[$this->id]['color']))
      return $prefs[$this->id]['color'];

    return 'cc0000';
  }

  /**
   * Compose an URL for CalDAV access to this calendar (if configured)
   */
  public function get_caldav_url()
  {
    if ($template = $this->cal->rc->config->get('calendar_caldav_url', null)) {
      return strtr($template, array(
        '%h' => $_SERVER['HTTP_HOST'],
        '%u' => urlencode($this->cal->rc->get_user_name()),
        '%i' => urlencode($this->storage->get_uid()),
        '%n' => urlencode($this->name),
      ));
    }

    return false;
  }


  /**
   * Update properties of this calendar folder
   *
   * @see calendar_driver::edit_calendar()
   */
  public function update(&$prop)
  {
    $prop['oldname'] = $this->get_realname();
    $newfolder = kolab_storage::folder_update($prop);

    if ($newfolder === false) {
      $this->cal->last_error = $this->cal->gettext(kolab_storage::$last_error);
      return false;
    }

    // create ID
    return kolab_storage::folder_id($newfolder);
  }

  /**
   * Getter for a single event object
   */
  public function get_event($id)
  {
    // directly access storage object
    if (!$this->events[$id] && ($record = $this->storage->get_object($id)))
        $this->events[$id] = $this->_to_rcube_event($record);

    // event not found, maybe a recurring instance is requested
    if (!$this->events[$id]) {
      $master_id = preg_replace('/-\d+$/', '', $id);
      if ($master_id != $id && ($record = $this->storage->get_object($master_id)))
        $this->events[$master_id] = $this->_to_rcube_event($record);

      if (($master = $this->events[$master_id]) && $master['recurrence']) {
        $this->get_recurring_events($record, $master['start'], null, $id);
      }
    }

    return $this->events[$id];
  }


  /**
   * @param  integer Event's new start (unix timestamp)
   * @param  integer Event's new end (unix timestamp)
   * @param  string  Search query (optional)
   * @param  boolean Include virtual events (optional)
   * @param  array   Additional parameters to query storage
   * @param  array   Additional query to filter events
   * @return array A list of event records
   */
  public function list_events($start, $end, $search = null, $virtual = 1, $query = array(), $filter_query = null)
  {
    // convert to DateTime for comparisons
    try {
      $start = new DateTime('@'.$start);
    }
    catch (Exception $e) {
      $start = new DateTime('@0');
    }
    try {
      $end = new DateTime('@'.$end);
    }
    catch (Exception $e) {
      $end = new DateTime('today +10 years');
    }

    // get email addresses of the current user
    $user_emails = $this->cal->get_user_emails();

    // query Kolab storage
    $query[] = array('dtstart', '<=', $end);
    $query[] = array('dtend',   '>=', $start);

    // add query to exclude pending/declined invitations
    if (empty($filter_query) && $this->get_namespace() != 'other') {
      foreach ($user_emails as $email) {
        $query[] = array('tags', '!=', 'x-partstat:' . $email . ':needs-action');
        $query[] = array('tags', '!=', 'x-partstat:' . $email . ':declined');
      }
    }
    else if (is_array($filter_query)) {
      $query = array_merge($query, $filter_query);
    }

    if (!empty($search)) {
        $search = mb_strtolower($search);
        foreach (rcube_utils::normalize_string($search, true) as $word) {
            $query[] = array('words', 'LIKE', $word);
        }
    }

    $events = array();
    foreach ($this->storage->select($query) as $record) {
      // post-filter events to skip pending and declined invitations
      if (empty($filter_query) && is_array($record['attendees']) && $this->get_namespace() != 'other') {
        foreach ($record['attendees'] as $attendee) {
          if (in_array($attendee['email'], $user_emails) && in_array($attendee['status'], array('NEEDS-ACTION','DECLINED'))) {
            continue 2;
          }
        }
      }

      $event = $this->_to_rcube_event($record);
      $this->events[$event['id']] = $event;

      // remember seen categories
      if ($event['categories'])
        $this->categories[$event['categories']]++;
      
      // filter events by search query
      if (!empty($search)) {
        $hit = false;
        foreach ($this->search_fields as $col) {
          $sval = is_array($event[$col]) ? self::_complex2string($event[$col]) : $event[$col];
          if (empty($sval))
            continue;
          
          // do a simple substring matching (to be improved)
          $val = mb_strtolower($sval);
          if (strpos($val, $search) !== false) {
            $hit = true;
            break;
          }
        }
        
        if (!$hit)  // skip this event if not match with search term
          continue;
      }
      
      // list events in requested time window
      if ($event['start'] <= $end && $event['end'] >= $start) {
        unset($event['_attendees']);
        $add = true;

        // skip the first instance of a recurring event if listed in exdate
        if ($virtual && (!empty($event['recurrence']['EXDATE']) || !empty($event['recurrence']['EXCEPTIONS']))) {
          $event_date = $event['start']->format('Ymd');
          $exdates = (array)$event['recurrence']['EXDATE'];

          // add dates from exceptions to list
          if (is_array($event['recurrence']['EXCEPTIONS'])) {
              foreach ($event['recurrence']['EXCEPTIONS'] as $exception) {
                  $exdates[] = clone $exception['start'];
              }
          }

          foreach ($exdates as $exdate) {
            if ($exdate->format('Ymd') == $event_date) {
              $add = false;
              break;
            }
          }
        }

        if ($add)
          $events[] = $event;
      }
      
      // resolve recurring events
      if ($record['recurrence'] && $virtual == 1) {
        $events = array_merge($events, $this->get_recurring_events($record, $start, $end));
      }
    }

    // avoid session race conditions that will loose temporary subscriptions
    $this->cal->rc->session->nowrite = true;

    return $events;
  }

  /**
   *
   * @param  integer Date range start (unix timestamp)
   * @param  integer Date range end (unix timestamp)
   * @param  array   Additional query to filter events
   * @return integer Count
   */
  public function count_events($start, $end = null, $filter_query = null)
  {
    // convert to DateTime for comparisons
    try {
      $start = new DateTime('@'.$start);
    }
    catch (Exception $e) {
      $start = new DateTime('@0');
    }
    if ($end) {
      try {
        $end = new DateTime('@'.$end);
      }
      catch (Exception $e) {
        $end = null;
      }
    }

    // query Kolab storage
    $query[] = array('dtend',   '>=', $start);
    
    if ($end)
      $query[] = array('dtstart', '<=', $end);

    // add query to exclude pending/declined invitations
    if (empty($filter_query)) {
      foreach ($this->cal->get_user_emails() as $email) {
        $query[] = array('tags', '!=', 'x-partstat:' . $email . ':needs-action');
        $query[] = array('tags', '!=', 'x-partstat:' . $email . ':declined');
      }
    }
    else if (is_array($filter_query)) {
      $query = array_merge($query, $filter_query);
    }

    // we rely the Kolab storage query (no post-filtering)
    return $this->storage->count($query);
  }

  /**
   * Create a new event record
   *
   * @see calendar_driver::new_event()
   * 
   * @return mixed The created record ID on success, False on error
   */
  public function insert_event($event)
  {
    if (!is_array($event))
      return false;

    // email links are stored separately
    $links = $event['links'];
    unset($event['links']);

    //generate new event from RC input
    $object = $this->_from_rcube_event($event);
    $saved = $this->storage->save($object, 'event');
    
    if (!$saved) {
      rcube::raise_error(array(
        'code' => 600, 'type' => 'php',
        'file' => __FILE__, 'line' => __LINE__,
        'message' => "Error saving event object to Kolab server"),
        true, false);
      $saved = false;
    }
    else {
      // save links in configuration.relation object
      $this->save_links($event['uid'], $links);

      $event['id'] = $event['uid'];
      $this->events = array($event['uid'] => $this->_to_rcube_event($object));
    }
    
    return $saved;
  }

  /**
   * Update a specific event record
   *
   * @see calendar_driver::new_event()
   * @return boolean True on success, False on error
   */

  public function update_event($event, $exception_id = null)
  {
    $updated = false;
    $old = $this->storage->get_object($event['id']);
    if (!$old || PEAR::isError($old))
      return false;

    // email links are stored separately
    $links = $event['links'];
    unset($event['links']);

    $object = $this->_from_rcube_event($event, $old);
    $saved = $this->storage->save($object, 'event', $event['id']);

    if (!$saved) {
      rcube::raise_error(array(
        'code' => 600, 'type' => 'php',
        'file' => __FILE__, 'line' => __LINE__,
        'message' => "Error saving event object to Kolab server"),
        true, false);
    }
    else {
      // save links in configuration.relation object
      $this->save_links($event['uid'], $links);

      $updated = true;
      $this->events[$event['id']] = $this->_to_rcube_event($object);

      // refresh local cache with recurring instances
      if ($exception_id) {
        $this->get_recurring_events($object, $event['start'], $event['end'], $exception_id);
      }
    }

    return $updated;
  }

  /**
   * Delete an event record
   *
   * @see calendar_driver::remove_event()
   * @return boolean True on success, False on error
   */
  public function delete_event($event, $force = true)
  {
    $deleted = $this->storage->delete($event['id'], $force);

    if (!$deleted) {
      rcube::raise_error(array(
        'code' => 600, 'type' => 'php',
        'file' => __FILE__, 'line' => __LINE__,
        'message' => "Error deleting event object from Kolab server"),
        true, false);
    }

    return $deleted;
  }

  /**
   * Restore deleted event record
   *
   * @see calendar_driver::undelete_event()
   * @return boolean True on success, False on error
   */
  public function restore_event($event)
  {
    if ($this->storage->undelete($event['id'])) {
        return true;
    }
    else {
        rcube::raise_error(array(
          'code' => 600, 'type' => 'php',
          'file' => __FILE__, 'line' => __LINE__,
          'message' => "Error undeleting the event object $event[id] from the Kolab server"),
        true, false);
    }

    return false;
  }

  /**
   * Find messages linked with an event
   */
  protected function get_links($uid)
  {
    $storage = kolab_storage_config::get_instance();
    return $storage->get_object_links($uid);
  }

  /**
   *
   */
  protected function save_links($uid, $links)
  {
    // make sure we have a valid array
    if (empty($links)) {
      $links = array();
    }

    $storage = kolab_storage_config::get_instance();
    $remove = array_diff($storage->get_object_links($uid), $links);
    return $storage->save_object_links($uid, $links, $remove);
  }

  /**
   * Create instances of a recurring event
   *
   * @param array  Hash array with event properties
   * @param object DateTime Start date of the recurrence window
   * @param object DateTime End date of the recurrence window
   * @param string ID of a specific recurring event instance
   * @return array List of recurring event instances
   */
  public function get_recurring_events($event, $start, $end = null, $event_id = null)
  {
    $object = $event['_formatobj'];
    if (!$object) {
      $rec = $this->storage->get_object($event['id']);
      $object = $rec['_formatobj'];
    }
    if (!is_object($object))
      return array();

    // determine a reasonable end date if none given
    if (!$end) {
      switch ($event['recurrence']['FREQ']) {
        case 'YEARLY':  $intvl = 'P100Y'; break;
        case 'MONTHLY': $intvl = 'P20Y';  break;
        default:        $intvl = 'P10Y';  break;
      }

      $end = clone $event['start'];
      $end->add(new DateInterval($intvl));
    }

    // add recurrence exceptions to output
    $i = 0;
    $events = array();
    $exdates = array();
    $futuredata = array();
    if (is_array($event['recurrence']['EXCEPTIONS'])) {
      // copy the recurrence rule from the master event (to be used in the UI)
      $recurrence_rule = $event['recurrence'];
      unset($recurrence_rule['EXCEPTIONS'], $recurrence_rule['EXDATE']);

      foreach ($event['recurrence']['EXCEPTIONS'] as $exception) {
        $rec_event = $this->_to_rcube_event($exception);
        $rec_event['id'] = $event['uid'] . '-' . ++$i;
        $rec_event['recurrence_id'] = $event['uid'];
        $rec_event['recurrence'] = $recurrence_rule;
        $rec_event['_instance'] = $i;
        $rec_event['isexception'] = 1;
        $events[] = $rec_event;

        // found the specifically requested instance, exiting...
        if ($rec_event['id'] == $event_id) {
          $this->events[$rec_event['id']] = $rec_event;
          return $events;
        }

        // remember this exception's date
        $exdate = $rec_event['start']->format('Y-m-d');
        $exdates[$exdate] = $rec_event['id'];
        if ($rec_event['thisandfuture']) {
          $futuredata[$exdate] = $rec_event;
        }
      }
    }

    // use libkolab to compute recurring events
    if (class_exists('kolabcalendaring')) {
        $recurrence = new kolab_date_recurrence($object);
    }
    else {
      // fallback to local recurrence implementation
      require_once($this->cal->home . '/lib/calendar_recurrence.php');
      $recurrence = new calendar_recurrence($this->cal, $event);
    }

    while ($next_event = $recurrence->next_instance()) {
      // skip if there's an exception at this date
      $datestr = $next_event['start']->format('Y-m-d');
      if ($exdates[$datestr]) {
        // use this event data for future recurring instances
        if ($futuredata[$datestr])
          $overlay_data = $futuredata[$datestr];
        continue;
      }

      // add to output if in range
      $rec_id = $event['uid'] . '-' . ++$i;
      if (($next_event['start'] <= $end && $next_event['end'] >= $start) || ($event_id && $rec_id == $event_id)) {
        $rec_event = $this->_to_rcube_event($next_event);

        if ($overlay_data)  // copy data from a 'this-and-future' exception
          $this->_merge_event_data($rec_event, $overlay_data);

        $rec_event['id'] = $rec_id;
        $rec_event['recurrence_id'] = $event['uid'];
        $rec_event['_instance'] = $i;
        unset($rec_event['_attendees']);
        $events[] = $rec_event;

        if ($rec_id == $event_id) {
          $this->events[$rec_id] = $rec_event;
          break;
        }
      }
      else if ($next_event['start'] > $end)  // stop loop if out of range
        break;

      // avoid endless recursion loops
      if ($i > 1000)
          break;
    }
    
    return $events;
  }

  /**
   * Merge certain properties from the overlay event to the base event object
   *
   * @param array The event object to be altered
   * @param array The overlay event object to be merged over $event
   */
  private function _merge_event_data(&$event, $overlay)
  {
    static $forbidden = array('id','uid','created','changed','recurrence','organizer','attendees','sequence');

    foreach ($overlay as $prop => $value) {
      // adjust time of the recurring event instance
      if ($prop == 'start' || $prop == 'end') {
        if (is_object($event[$prop]) && is_a($event[$prop], 'DateTime'))
          $event[$prop]->setTime($value->format('G'), intval($value->format('i')), intval($value->format('s')));
      }
      else if ($prop[0] != '_' && !in_array($prop, $forbidden))
        $event[$prop] = $value;
    }
  }

  /**
   * Convert from Kolab_Format to internal representation
   */
  private function _to_rcube_event($record)
  {
    $record['id'] = $record['uid'];
    $record['calendar'] = $this->id;
    $record['links'] = $this->get_links($record['uid']);

    if ($this->get_namespace() == 'other') {
      $record['className'] = 'fc-event-ns-other';
      $record = kolab_driver::add_partstat_class($record, array('NEEDS-ACTION','DECLINED'), $this->get_owner());
    }

    return kolab_driver::to_rcube_event($record);
  }

   /**
   * Convert the given event record into a data structure that can be passed to Kolab_Storage backend for saving
   * (opposite of self::_to_rcube_event())
   */
  private function _from_rcube_event($event, $old = array())
  {
    // in kolab_storage attachments are indexed by content-id
    $event['_attachments'] = array();
    if (is_array($event['attachments'])) {
      foreach ($event['attachments'] as $attachment) {
        $key = null;
        // Roundcube ID has nothing to do with the storage ID, remove it
        if ($attachment['content'] || $attachment['path']) {
          unset($attachment['id']);
        }
        else {
          foreach ((array)$old['_attachments'] as $cid => $oldatt) {
            if ($attachment['id'] == $oldatt['id'])
              $key = $cid;
          }
        }

        // flagged for deletion => set to false
        if ($attachment['_deleted']) {
          $event['_attachments'][$key] = false;
        }
        // replace existing entry
        else if ($key) {
          $event['_attachments'][$key] = $attachment;
        }
        // append as new attachment
        else {
          $event['_attachments'][] = $attachment;
        }
      }

      unset($event['attachments']);
    }

    // set current user as ORGANIZER
    $identity = $this->cal->rc->user->list_emails(true);
    if (empty($event['attendees']) && $identity['email'])
      $event['attendees'] = array(array('role' => 'ORGANIZER', 'name' => $identity['name'], 'email' => $identity['email']));

    $event['_owner'] = $identity['email'];

    // remove EXDATE values if RDATE is given
    if (!empty($event['recurrence']['RDATE'])) {
      $event['recurrence']['EXDATE'] = array();
    }

    // remove recurrence information (e.g. EXDATES and EXCEPTIONS) entirely
    if ($event['recurrence'] && empty($event['recurrence']['FREQ']) && empty($event['recurrence']['RDATE'])) {
      $event['recurrence'] = array();
    }

    // keep 'comment' from initial itip invitation
    if (!empty($old['comment'])) {
      $event['comment'] = $old['comment'];
    }

    // remove some internal properties which should not be saved
    unset($event['_savemode'], $event['_fromcalendar'], $event['_identity'], $event['_folder_id'], $event['className']);

    // copy meta data (starting with _) from old object
    foreach ((array)$old as $key => $val) {
      if (!isset($event[$key]) && $key[0] == '_')
        $event[$key] = $val;
    }

    return $event;
  }

  /**
   * Convert a complex event attribute to a string value
   */
  private static function _complex2string($prop)
  {
      static $ignorekeys = array('role','status','rsvp');

      $out = '';
      if (is_array($prop)) {
          foreach ($prop as $key => $val) {
              if (is_numeric($key)) {
                  $out .= self::_complex2string($val);
              }
              else if (!in_array($key, $ignorekeys)) {
                $out .= $val . ' ';
            }
          }
      }
      else if (is_string($prop) || is_numeric($prop)) {
          $out .= $prop . ' ';
      }

      return rtrim($out);
  }

}
