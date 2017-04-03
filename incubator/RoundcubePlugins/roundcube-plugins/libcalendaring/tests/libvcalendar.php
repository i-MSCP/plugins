<?php

/**
 * libcalendaring plugin's iCalendar functions tests
 *
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 *
 * Copyright (C) 2014, Kolab Systems AG <contact@kolabsys.com>
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

class libvcalendar_test extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        require_once __DIR__ . '/../libvcalendar.php';
        require_once __DIR__ . '/../libcalendaring.php';
    }

    /**
     * Simple iCal parsing test
     */
    function test_import()
    {
        $ical = new libvcalendar();
        $ics = file_get_contents(__DIR__ . '/resources/snd.ics');
        $events = $ical->import($ics, 'UTF-8');

        $this->assertEquals(1, count($events));
        $event = $events[0];

        $this->assertInstanceOf('DateTime', $event['created'], "'created' property is DateTime object");
        $this->assertInstanceOf('DateTime', $event['changed'], "'changed' property is DateTime object");
        $this->assertEquals('UTC', $event['created']->getTimezone()->getName(), "'created' date is in UTC");

        $this->assertInstanceOf('DateTime', $event['start'], "'start' property is DateTime object");
        $this->assertInstanceOf('DateTime', $event['end'], "'end' property is DateTime object");
        $this->assertEquals('08-01', $event['start']->format('m-d'), "Start date is August 1st");
        $this->assertTrue($event['allday'], "All-day event flag");

        $this->assertEquals('B968B885-08FB-40E5-B89E-6DA05F26AA79', $event['uid'], "Event UID");
        $this->assertEquals('Swiss National Day', $event['title'], "Event title");
        $this->assertEquals('http://en.wikipedia.org/wiki/Swiss_National_Day', $event['url'], "URL property");
        $this->assertEquals(2, $event['sequence'], "Sequence number");

        $desclines = explode("\n", $event['description']);
        $this->assertEquals(4, count($desclines), "Multiline description");
        $this->assertEquals("French: Fête nationale Suisse", rtrim($desclines[1]), "UTF-8 encoding");
    }

    /**
     * Test parsing from files
     */
    function test_import_from_file()
    {
        $ical = new libvcalendar();

        $events = $ical->import_from_file(__DIR__ . '/resources/multiple.ics', 'UTF-8');
        $this->assertEquals(2, count($events));

        $events = $ical->import_from_file(__DIR__ . '/resources/invalid.txt', 'UTF-8');
        $this->assertEmpty($events);
    }

    /**
     * Test parsing from files with multiple VCALENDAR blocks (#2884)
     */
    function test_import_from_file_multiple()
    {
        $ical = new libvcalendar();
        $ical->fopen(__DIR__ . '/resources/multiple-rdate.ics', 'UTF-8');
        $events = array();
        foreach ($ical as $event) {
            $events[] = $event;
        }

        $this->assertEquals(2, count($events));
        $this->assertEquals("AAAA6A8C3CCE4EE2C1257B5C00FFFFFF-Lotus_Notes_Generated", $events[0]['uid']);
        $this->assertEquals("AAAA1C572093EC3FC125799C004AFFFF-Lotus_Notes_Generated", $events[1]['uid']);
    }

    function test_invalid_dates()
    {
        $ical = new libvcalendar();
        $events = $ical->import_from_file(__DIR__ . '/resources/invalid-dates.ics', 'UTF-8');
        $event = $events[0];

        $this->assertEquals(1, count($events), "Import event data");
        $this->assertInstanceOf('DateTime', $event['created'], "Created date field");
        $this->assertFalse(array_key_exists('changed', $event), "No changed date field");
    }

    function test_invalid_vevent()
    {
        $this->setExpectedException('\Sabre\VObject\ParseException');

        $ical = new libvcalendar();
        $events = $ical->import_from_file(__DIR__ . '/resources/invalid-event.ics', 'UTF-8', true);
    }

    /**
     * Test some extended ical properties such as attendees, recurrence rules, alarms and attachments
     */
    function test_extended()
    {
        $ical = new libvcalendar();

        $events = $ical->import_from_file(__DIR__ . '/resources/itip.ics', 'UTF-8');
        $event = $events[0];
        $this->assertEquals('REQUEST', $ical->method, "iTip method");

        // attendees
        $this->assertEquals(3, count($event['attendees']), "Attendees list (including organizer)");
        $organizer = $event['attendees'][0];
        $this->assertEquals('ORGANIZER', $organizer['role'], 'Organizer ROLE');
        $this->assertEquals('Rolf Test', $organizer['name'], 'Organizer name');

        $attendee = $event['attendees'][1];
        $this->assertEquals('REQ-PARTICIPANT', $attendee['role'], 'Attendee ROLE');
        $this->assertEquals('NEEDS-ACTION', $attendee['status'], 'Attendee STATUS');
        $this->assertEquals('rolf2@mykolab.com', $attendee['email'], 'Attendee mailto:');
        $this->assertEquals('carl@mykolab.com', $attendee['delegated-from'], 'Attendee delegated-from');
        $this->assertTrue($attendee['rsvp'], 'Attendee RSVP');

        $delegator = $event['attendees'][2];
        $this->assertEquals('NON-PARTICIPANT',   $delegator['role'], 'Delegator ROLE');
        $this->assertEquals('DELEGATED',         $delegator['status'], 'Delegator STATUS');
        $this->assertEquals('INDIVIDUAL',        $delegator['cutype'], 'Delegator CUTYPE');
        $this->assertEquals('carl@mykolab.com',  $delegator['email'], 'Delegator mailto:');
        $this->assertEquals('rolf2@mykolab.com', $delegator['delegated-to'], 'Delegator delegated-to');
        $this->assertFalse($delegator['rsvp'],   'Delegator RSVP');

        // attachments
        $this->assertEquals(1, count($event['attachments']), "Embedded attachments");
        $attachment = $event['attachments'][0];
        $this->assertEquals('text/html',                 $attachment['mimetype'], "Attachment mimetype attribute");
        $this->assertEquals('calendar.html',             $attachment['name'],     "Attachment filename (X-LABEL) attribute");
        $this->assertContains('<title>Kalender</title>', $attachment['data'],     "Attachment content (decoded)");

        // recurrence rules
        $events = $ical->import_from_file(__DIR__ . '/resources/recurring.ics', 'UTF-8');
        $event = $events[0];

        $this->assertTrue(is_array($event['recurrence']), 'Recurrences rule as hash array');
        $rrule = $event['recurrence'];
        $this->assertEquals('MONTHLY',      $rrule['FREQ'],     "Recurrence frequency");
        $this->assertEquals('1',            $rrule['INTERVAL'], "Recurrence interval");
        $this->assertEquals('3WE',          $rrule['BYDAY'],    "Recurrence frequency");
        $this->assertInstanceOf('DateTime', $rrule['UNTIL'],    "Recurrence end date");

        $this->assertEquals(2, count($rrule['EXDATE']),          "Recurrence EXDATEs");
        $this->assertInstanceOf('DateTime', $rrule['EXDATE'][0], "Recurrence EXDATE as DateTime");

        $this->assertTrue(is_array($rrule['EXCEPTIONS']));
        $this->assertEquals(1, count($rrule['EXCEPTIONS']), "Recurrence Exceptions");

        $exception = $rrule['EXCEPTIONS'][0];
        $this->assertEquals($event['uid'],  $event['uid'], "Exception UID");
        $this->assertEquals('Recurring Test (Exception)',  $exception['title'], "Exception title");
        $this->assertInstanceOf('DateTime', $exception['start'], "Exception start");

        // categories, class
        $this->assertEquals('libcalendaring tests', join(',', (array)$event['categories']), "Event categories");
        $this->assertEquals('confidential', $event['sensitivity'], "Class/sensitivity = confidential");

        // parse a recurrence chain instance
        $events = $ical->import_from_file(__DIR__ . '/resources/recurrence-id.ics', 'UTF-8');
        $this->assertEquals(1, count($events), "Fall back to Component::getComponents() when getBaseComponents() is empty");
        $this->assertInstanceOf('DateTime', $events[0]['recurrence_date'], "Recurrence-ID as date");
        $this->assertTrue($events[0]['thisandfuture'], "Range=THISANDFUTURE");

        $this->assertEquals(count($events[0]['exceptions']), 1, "Second VEVENT as exception");
        $this->assertEquals($events[0]['exceptions'][0]['uid'], $events[0]['uid'], "Exception UID match");
        $this->assertEquals($events[0]['exceptions'][0]['sequence'], '2', "Exception sequence");
    }

    /**
     * 
     */
    function test_alarms()
    {
        $ical = new libvcalendar();

        $events = $ical->import_from_file(__DIR__ . '/resources/recurring.ics', 'UTF-8');
        $event = $events[0];

        $this->assertEquals('-12H:DISPLAY', $event['alarms'], "Serialized alarms string");
        $alarm = libcalendaring::parse_alarm_value($event['alarms']);
        $this->assertEquals('12', $alarm[0], "Alarm value");
        $this->assertEquals('-H', $alarm[1], "Alarm unit");

        $this->assertEquals('DISPLAY', $event['valarms'][0]['action'],  "Full alarm item (action)");
        $this->assertEquals('-PT12H',  $event['valarms'][0]['trigger'], "Full alarm item (trigger)");
        $this->assertEquals('END',  $event['valarms'][0]['related'], "Full alarm item (related)");

        // alarm trigger with 0 values
        $events = $ical->import_from_file(__DIR__ . '/resources/alarms.ics', 'UTF-8');
        $event = $events[0];

        $this->assertEquals('-30M:DISPLAY', $event['alarms'], "Stripped alarm string");
        $alarm = libcalendaring::parse_alarm_value($event['alarms']);
        $this->assertEquals('30', $alarm[0], "Alarm value");
        $this->assertEquals('-M', $alarm[1], "Alarm unit");
        $this->assertEquals('-30M', $alarm[2], "Alarm string");
        $this->assertEquals('-PT30M', $alarm[3], "Unified alarm string (stripped zero-values)");

        $this->assertEquals('DISPLAY', $event['valarms'][0]['action'],  "First alarm action");
        $this->assertEquals('', $event['valarms'][0]['related'],  "First alarm related property");
        $this->assertEquals('This is the first event reminder', $event['valarms'][0]['description'],  "First alarm text");

        $this->assertEquals(3, count($event['valarms']), "List all VALARM blocks");

        $valarm = $event['valarms'][1];
        $this->assertEquals(1, count($valarm['attendees']), "Email alarm attendees");
        $this->assertEquals('EMAIL', $valarm['action'],  "Second alarm item (action)");
        $this->assertEquals('-P1D',  $valarm['trigger'], "Second alarm item (trigger)");
        $this->assertEquals('This is the reminder message',  $valarm['summary'], "Email alarm text");
        $this->assertInstanceOf('DateTime', $event['valarms'][2]['trigger'], "Absolute trigger date/time");

        // test alarms export
        $ics = $ical->export(array($event));
        $this->assertContains('ACTION:DISPLAY',   $ics, "Display alarm block");
        $this->assertContains('ACTION:EMAIL',     $ics, "Email alarm block");
        $this->assertContains('DESCRIPTION:This is the first event reminder',    $ics, "Alarm description");
        $this->assertContains('SUMMARY:This is the reminder message',            $ics, "Email alarm summary");
        $this->assertContains('ATTENDEE:mailto:reminder-recipient@example.org',  $ics, "Email alarm recipient");
        $this->assertContains('TRIGGER;VALUE=DATE-TIME:20130812',  $ics, "Date-Time trigger");
    }

    /**
     * @depends test_import_from_file
     */
    function test_attachment()
    {
        $ical = new libvcalendar();

        $events = $ical->import_from_file(__DIR__ . '/resources/attachment.ics', 'UTF-8');
        $event = $events[0];

        $this->assertEquals(2, count($events));
        $this->assertEquals(1, count($event['attachments']));
        $this->assertEquals('image/png', $event['attachments'][0]['mimetype']);
        $this->assertEquals('500px-Opensource.svg.png', $event['attachments'][0]['name']);
    }

    /**
     * @depends test_import
     */
    function test_apple_alarms()
    {
        $ical = new libvcalendar();
        $events = $ical->import_from_file(__DIR__ . '/resources/apple-alarms.ics', 'UTF-8');
        $event = $events[0];

        // alarms
        $this->assertEquals('-45M:AUDIO', $event['alarms'], "Relative alarm string");
        $alarm = libcalendaring::parse_alarm_value($event['alarms']);
        $this->assertEquals('45', $alarm[0], "Alarm value");
        $this->assertEquals('-M', $alarm[1], "Alarm unit");

        $this->assertEquals(1, count($event['valarms']), "Ignore invalid alarm blocks");
        $this->assertEquals('AUDIO', $event['valarms'][0]['action'],   "Full alarm item (action)");
        $this->assertEquals('-PT45M', $event['valarms'][0]['trigger'], "Full alarm item (trigger)");
        $this->assertEquals('Basso',  $event['valarms'][0]['uri'],     "Full alarm item (attachment)");
    }

    /**
     * 
     */
    function test_escaped_values()
    {
        $ical = new libvcalendar();
        $events = $ical->import_from_file(__DIR__ . '/resources/escaped.ics', 'UTF-8');
        $event = $events[0];

        $this->assertEquals("House, Street, Zip Place", $event['location'], "Decode escaped commas in location value");
        $this->assertEquals("Me, meets Them\nThem, meet Me", $event['description'], "Decode description value");
        $this->assertEquals("Kolab, Thomas", $event['attendees'][3]['name'], "Unescaped");

        $ics = $ical->export($events);
        $this->assertContains('ATTENDEE;CN="Kolab, Thomas";PARTSTAT=', $ics, "Quoted attendee parameters");
    }

    /**
     * Parse RDATE properties (#2885)
     */
    function test_rdate()
    {
        $ical = new libvcalendar();
        $events = $ical->import_from_file(__DIR__ . '/resources/multiple-rdate.ics', 'UTF-8');
        $event = $events[0];

        $this->assertEquals(9, count($event['recurrence']['RDATE']));
        $this->assertInstanceOf('DateTime', $event['recurrence']['RDATE'][0]);
        $this->assertInstanceOf('DateTime', $event['recurrence']['RDATE'][1]);
    }

    /**
     * @depends test_import
     */
    function test_freebusy()
    {
        $ical = new libvcalendar();
        $ical->import_from_file(__DIR__ . '/resources/freebusy.ifb', 'UTF-8');
        $freebusy = $ical->freebusy;

        $this->assertInstanceOf('DateTime', $freebusy['start'], "'start' property is DateTime object");
        $this->assertInstanceOf('DateTime', $freebusy['end'], "'end' property is DateTime object");
        $this->assertEquals(11, count($freebusy['periods']), "Number of freebusy periods defined");
        $periods = $ical->get_busy_periods();
        $this->assertEquals(9, count($periods), "Number of busy periods found");
        $this->assertEquals('BUSY-TENTATIVE', $periods[8][2], "FBTYPE=BUSY-TENTATIVE");
    }

    /**
     * @depends test_import
     */
    function test_freebusy_dummy()
    {
        $ical = new libvcalendar();
        $ical->import_from_file(__DIR__ . '/resources/dummy.ifb', 'UTF-8');
        $freebusy = $ical->freebusy;

        $this->assertEquals(0, count($freebusy['periods']), "Ignore 0-length freebudy periods");
        $this->assertContains('dummy', $freebusy['comment'], "Parse comment");
    }

    function test_vtodo()
    {
        $ical = new libvcalendar();
        $tasks = $ical->import_from_file(__DIR__ . '/resources/vtodo.ics', 'UTF-8', true);
        $task = $tasks[0];

        $this->assertInstanceOf('DateTime', $task['start'],   "'start' property is DateTime object");
        $this->assertInstanceOf('DateTime', $task['due'],     "'due' property is DateTime object");
        $this->assertEquals('-1D:DISPLAY',  $task['alarms'],  "Taks alarm value");
        $this->assertEquals('IN-PROCESS',   $task['status'],  "Task status property");
        $this->assertEquals(1, count($task['x-custom']),      "Custom properties");
        $this->assertEquals(4, count($task['categories']));
        $this->assertEquals('1234567890-12345678-PARENT', $task['parent_id'], "Parent Relation");

        $completed = $tasks[1];
        $this->assertEquals('COMPLETED', $completed['status'], "Task status=completed when COMPLETED property is present");
        $this->assertEquals(100, $completed['complete'], "Task percent complete value");

        $ics = $ical->export(array($completed));
        $this->assertRegExp('/COMPLETED(;VALUE=DATE-TIME)?:[0-9TZ]+/', $ics, "Export COMPLETED property");
    }

    /**
     * Test for iCal export from internal hash array representation
     *
     * 
     */
    function test_export()
    {
        $ical = new libvcalendar();

        $events = $ical->import_from_file(__DIR__ . '/resources/itip.ics', 'UTF-8');
        $event = $events[0];
        $events = $ical->import_from_file(__DIR__ . '/resources/recurring.ics', 'UTF-8');
        $event += $events[0];

        $this->attachment_data = $event['attachments'][0]['data'];
        unset($event['attachments'][0]['data']);
        $event['attachments'][0]['id'] = '1';
        $event['description'] = '*Exported by libvcalendar*';

        $event['start']->setTimezone(new DateTimezone('America/Montreal'));
        $event['end']->setTimezone(new DateTimezone('Europe/Berlin'));

        $ics = $ical->export(array($event), 'REQUEST', false, array($this, 'get_attachment_data'), true);

        $this->assertContains('BEGIN:VCALENDAR',    $ics, "VCALENDAR encapsulation BEGIN");

        $this->assertContains('BEGIN:VTIMEZONE', $ics, "VTIMEZONE encapsulation BEGIN");
        $this->assertContains('TZID:Europe/Berlin', $ics, "Timezone ID");
        $this->assertContains('TZOFFSETFROM:+0100', $ics, "Timzone transition FROM");
        $this->assertContains('TZOFFSETTO:+0200', $ics, "Timzone transition TO");
        $this->assertContains('TZOFFSETFROM:-0400', $ics, "TZOFFSETFROM with negative offset (Bug T428)");
        $this->assertContains('TZOFFSETTO:-0500', $ics, "TZOFFSETTO with negative offset (Bug T428)");
        $this->assertContains('END:VTIMEZONE', $ics, "VTIMEZONE encapsulation END");

        $this->assertContains('BEGIN:VEVENT',       $ics, "VEVENT encapsulation BEGIN");
        $this->assertSame(2, substr_count($ics, 'DTSTAMP'), "Duplicate DTSTAMP (T1148)");
        $this->assertContains('UID:ac6b0aee-2519-4e5c-9a25-48c57064c9f0', $ics, "Event UID");
        $this->assertContains('SEQUENCE:' . $event['sequence'],           $ics, "Export Sequence number");
        $this->assertContains('CLASS:CONFIDENTIAL',                       $ics, "Sensitivity => Class");
        $this->assertContains('DESCRIPTION:*Exported by',                 $ics, "Export Description");
        $this->assertContains('ORGANIZER;CN=Rolf Test:mailto:rolf@',      $ics, "Export organizer");
        $this->assertRegExp('/ATTENDEE.*;ROLE=REQ-PARTICIPANT/',          $ics, "Export Attendee ROLE");
        $this->assertRegExp('/ATTENDEE.*;PARTSTAT=NEEDS-ACTION/',         $ics, "Export Attendee Status");
        $this->assertRegExp('/ATTENDEE.*;RSVP=TRUE/',                     $ics, "Export Attendee RSVP");
        $this->assertRegExp('/:mailto:rolf2@/',                           $ics, "Export Attendee mailto:");

        $rrule = $event['recurrence'];
        $this->assertRegExp('/RRULE:.*FREQ='.$rrule['FREQ'].'/',          $ics, "Export Recurrence Frequence");
        $this->assertRegExp('/RRULE:.*INTERVAL='.$rrule['INTERVAL'].'/',  $ics, "Export Recurrence Interval");
        $this->assertRegExp('/RRULE:.*UNTIL=20140718T215959Z/',           $ics, "Export Recurrence End date");
        $this->assertRegExp('/RRULE:.*BYDAY='.$rrule['BYDAY'].'/',        $ics, "Export Recurrence BYDAY");
        $this->assertRegExp('/EXDATE.*:20131218/',     $ics, "Export Recurrence EXDATE");

        $this->assertContains('BEGIN:VALARM',   $ics, "Export VALARM");
        $this->assertContains('TRIGGER;RELATED=END:-PT12H', $ics, "Export Alarm trigger");

        $this->assertRegExp('/ATTACH.*;VALUE=BINARY/',                    $ics, "Embed attachment");
        $this->assertRegExp('/ATTACH.*;ENCODING=BASE64/',                 $ics, "Attachment B64 encoding");
        $this->assertRegExp('!ATTACH.*;FMTTYPE=text/html!',               $ics, "Attachment mimetype");
        $this->assertRegExp('!ATTACH.*;X-LABEL=calendar.html!',           $ics, "Attachment filename with X-LABEL");

        $this->assertContains('END:VEVENT',     $ics, "VEVENT encapsulation END");
        $this->assertContains('END:VCALENDAR',  $ics, "VCALENDAR encapsulation END");
    }

    /**
     * @depends test_extended
     * @depends test_export
     */
    function test_export_multiple()
    {
        $ical = new libvcalendar();
        $events = array_merge(
            $ical->import_from_file(__DIR__ . '/resources/snd.ics', 'UTF-8'),
            $ical->import_from_file(__DIR__ . '/resources/multiple.ics', 'UTF-8')
        );

        $num = count($events);
        $ics = $ical->export($events, null, false);

        $this->assertContains('BEGIN:VCALENDAR', $ics, "VCALENDAR encapsulation BEGIN");
        $this->assertContains('END:VCALENDAR',   $ics, "VCALENDAR encapsulation END");
        $this->assertEquals($num, substr_count($ics, 'BEGIN:VEVENT'), "VEVENT encapsulation BEGIN");
        $this->assertEquals($num, substr_count($ics, 'END:VEVENT'),   "VEVENT encapsulation END");
    }

    /**
     * @depends test_export
     */
    function test_export_recurrence_exceptions()
    {
        $ical = new libvcalendar();
        $events = $ical->import_from_file(__DIR__ . '/resources/recurring.ics', 'UTF-8');

        // add exceptions
        $event = $events[0];
        unset($event['recurrence']['EXCEPTIONS']);

        $exception1 = $event;
        $exception1['start'] = clone $event['start'];
        $exception1['start']->setDate(2013, 8, 14);
        $exception1['end'] = clone $event['end'];
        $exception1['end']->setDate(2013, 8, 14);

        $exception2 = $event;
        $exception2['start'] = clone $event['start'];
        $exception2['start']->setDate(2013, 11, 13);
        $exception2['end'] = clone $event['end'];
        $exception2['end']->setDate(2013, 11, 13);
        $exception2['title'] = 'Recurring Exception';

        $events[0]['recurrence']['EXCEPTIONS'] = array($exception1, $exception2);

        $ics = $ical->export($events, null, false);

        $num = count($events[0]['recurrence']['EXCEPTIONS']) + 1;
        $this->assertEquals($num, substr_count($ics, 'BEGIN:VEVENT'),       "VEVENT encapsulation BEGIN");
        $this->assertEquals($num, substr_count($ics, 'UID:'.$event['uid']), "Recurrence Exceptions with same UID");
        $this->assertEquals($num, substr_count($ics, 'END:VEVENT'),         "VEVENT encapsulation END");

        $this->assertContains('RECURRENCE-ID;TZID=Europe/Zurich:20130814', $ics, "Recurrence-ID (1) being the exception date");
        $this->assertContains('RECURRENCE-ID;TZID=Europe/Zurich:20131113', $ics, "Recurrence-ID (2) being the exception date");
        $this->assertContains('SUMMARY:'.$exception2['title'], $ics, "Exception title");
    }

    function test_export_valid_rrules()
    {
        $event = array(
            'uid' => '1234567890',
            'start' => new DateTime('now'),
            'end' => new DateTime('now + 30min'),
            'title' => 'test_export_valid_rrules',
            'recurrence' => array(
                'FREQ' => 'DAILY',
                'COUNT' => 5,
                'EXDATE' => array(),
                'RDATE' => array(),
            ),
        );
        $ical = new libvcalendar();
        $ics = $ical->export(array($event), null, false, null, false);

        $this->assertNotContains('EXDATE=', $ics);
        $this->assertNotContains('RDATE=', $ics);
    }

    /**
     *
     */
    function test_export_rdate()
    {
        $ical = new libvcalendar();
        $events = $ical->import_from_file(__DIR__ . '/resources/multiple-rdate.ics', 'UTF-8');
        $ics = $ical->export($events, null, false);

        $this->assertContains('RDATE:20140520T020000Z', $ics, "VALUE=PERIOD is translated into single DATE-TIME values");
    }

    /**
     * @depends test_export
     */
    function test_export_direct()
    {
        $ical = new libvcalendar();
        $events = $ical->import_from_file(__DIR__ . '/resources/multiple.ics', 'UTF-8');
        $num = count($events);

        ob_start();
        $return = $ical->export($events, null, true);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($return, "Return true on successful writing");
        $this->assertContains('BEGIN:VCALENDAR', $output, "VCALENDAR encapsulation BEGIN");
        $this->assertContains('END:VCALENDAR',   $output, "VCALENDAR encapsulation END");
        $this->assertEquals($num, substr_count($output, 'BEGIN:VEVENT'), "VEVENT encapsulation BEGIN");
        $this->assertEquals($num, substr_count($output, 'END:VEVENT'),   "VEVENT encapsulation END");
    }

    function test_datetime()
    {
        $ical = new libvcalendar();
        $cal  = new \Sabre\VObject\Component\VCalendar();
        $localtime = $ical->datetime_prop($cal, 'DTSTART', new DateTime('2013-09-01 12:00:00', new DateTimeZone('Europe/Berlin')));
        $localdate = $ical->datetime_prop($cal, 'DTSTART', new DateTime('2013-09-01', new DateTimeZone('Europe/Berlin')), false, true);
        $utctime   = $ical->datetime_prop($cal, 'DTSTART', new DateTime('2013-09-01 12:00:00', new DateTimeZone('UTC')));
        $asutctime = $ical->datetime_prop($cal, 'DTSTART', new DateTime('2013-09-01 12:00:00', new DateTimeZone('Europe/Berlin')), true);

        $this->assertContains('TZID=Europe/Berlin', $localtime->serialize());
        $this->assertContains('VALUE=DATE', $localdate->serialize());
        $this->assertContains('20130901T120000Z', $utctime->serialize());
        $this->assertContains('20130901T100000Z', $asutctime->serialize());
    }

    function test_get_vtimezone()
    {
        $vtz = libvcalendar::get_vtimezone('Europe/Berlin', strtotime('2014-08-22T15:00:00+02:00'));
        $this->assertInstanceOf('\Sabre\VObject\Component', $vtz, "VTIMEZONE is a Component object");
        $this->assertEquals('Europe/Berlin', $vtz->TZID);
        $this->assertEquals('4', $vtz->{'X-MICROSOFT-CDO-TZID'});

        // check for transition to daylight saving time which is BEFORE the given date
        $dst = reset($vtz->select('DAYLIGHT'));
        $this->assertEquals('DAYLIGHT', $dst->name);
        $this->assertEquals('20140330T010000', $dst->DTSTART);
        $this->assertEquals('+0100', $dst->TZOFFSETFROM);
        $this->assertEquals('+0200', $dst->TZOFFSETTO);
        $this->assertEquals('CEST', $dst->TZNAME);

        // check (last) transition to standard time which is AFTER the given date
        $std = end($vtz->select('STANDARD'));
        $this->assertEquals('STANDARD', $std->name);
        $this->assertEquals('20141026T010000', $std->DTSTART);
        $this->assertEquals('+0200', $std->TZOFFSETFROM);
        $this->assertEquals('+0100', $std->TZOFFSETTO);
        $this->assertEquals('CET', $std->TZNAME);

        // unknown timezone
        $vtz = libvcalendar::get_vtimezone('America/Foo Bar');
        $this->assertEquals(false, $vtz);

        // invalid input data
        $vtz = libvcalendar::get_vtimezone(new DateTime());
        $this->assertEquals(false, $vtz);

        // DateTimezone as input data
        $vtz = libvcalendar::get_vtimezone(new DateTimezone('Pacific/Chatham'));
        $this->assertInstanceOf('\Sabre\VObject\Component', $vtz);
        $this->assertContains('TZOFFSETFROM:+1245', $vtz->serialize());
        $this->assertContains('TZOFFSETTO:+1345', $vtz->serialize());
    }

    function get_attachment_data($id, $event)
    {
        return $this->attachment_data;
    }
}

