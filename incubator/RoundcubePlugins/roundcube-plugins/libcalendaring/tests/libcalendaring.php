<?php

/**
 * libcalendaring plugin's utility functions tests
 *
 * @author Thomas Bruederli <bruederli@kolabsys.com>
 *
 * Copyright (C) 2015, Kolab Systems AG <contact@kolabsys.com>
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

class libcalendaring_test extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        require_once __DIR__ . '/../libcalendaring.php';
    }

    /**
     * libcalendaring::parse_alarm_value()
     */
    function test_parse_alarm_value()
    {
        $alarm = libcalendaring::parse_alarm_value('-15M');
        $this->assertEquals('15', $alarm[0]);
        $this->assertEquals('-M', $alarm[1]);
        $this->assertEquals('-PT15M', $alarm[3]);

        $alarm = libcalendaring::parse_alarm_value('-PT5H');
        $this->assertEquals('5',  $alarm[0]);
        $this->assertEquals('-H', $alarm[1]);

        $alarm = libcalendaring::parse_alarm_value('P0DT1H0M0S');
        $this->assertEquals('1',  $alarm[0]);
        $this->assertEquals('+H', $alarm[1]);

        // FIXME: this should return something like (1140 + 120 + 30)M
        $alarm = libcalendaring::parse_alarm_value('-P1DT2H30M');
        // $this->assertEquals('1590', $alarm[0]);
        // $this->assertEquals('-M',   $alarm[1]);

        $alarm = libcalendaring::parse_alarm_value('@1420722000');
        $this->assertInstanceOf('DateTime', $alarm[0]);
    }

    /**
     * libcalendaring::get_next_alarm()
     */
    function test_get_next_alarm()
    {
        // alarm 10 minutes before event
        $date = date('Ymd', strtotime('today + 2 days'));
        $event = array(
            'start' => new DateTime($date . 'T160000Z'),
            'end'   => new DateTime($date . 'T200000Z'),
            'valarms' => array(
                array(
                    'trigger' => '-PT10M',
                    'action'  => 'DISPLAY',
                ),
            ),
        );
        $alarm = libcalendaring::get_next_alarm($event);
        $this->assertEquals($event['valarms'][0]['action'], $alarm['action']);
        $this->assertEquals(strtotime($date . 'T155000Z'), $alarm['time']);

        // alarm 1 hour after event start
        $event['valarms'] = array(
            array(
                'trigger' => '+PT1H',
            ),
        );
        $alarm = libcalendaring::get_next_alarm($event);
        $this->assertEquals('DISPLAY', $alarm['action']);
        $this->assertEquals(strtotime($date . 'T170000Z'), $alarm['time']);

        // alarm 1 hour before event end
        $event['valarms'] = array(
            array(
                'trigger' => '-PT1H',
                'related' => 'END',
            ),
        );
        $alarm = libcalendaring::get_next_alarm($event);
        $this->assertEquals('DISPLAY', $alarm['action']);
        $this->assertEquals(strtotime($date . 'T190000Z'), $alarm['time']);

        // alarm 1 hour after event end
        $event['valarms'] = array(
            array(
                'trigger' => 'PT1H',
                'related' => 'END',
            ),
        );
        $alarm = libcalendaring::get_next_alarm($event);
        $this->assertEquals('DISPLAY', $alarm['action']);
        $this->assertEquals(strtotime($date . 'T210000Z'), $alarm['time']);

        // ignore past alarms
        $event['start'] = new DateTime('today 22:00:00');
        $event['end']   = new DateTime('today 23:00:00');
        $event['valarms'] = array(
            array(
                'trigger' => '-P2D',
                'action'  => 'EMAIL',
            ),
            array(
                'trigger' => '-PT30M',
                'action'  => 'DISPLAY',
            ),
        );
        $alarm = libcalendaring::get_next_alarm($event);
        $this->assertEquals('DISPLAY', $alarm['action']);
        $this->assertEquals(strtotime('today 21:30:00'), $alarm['time']);

        // absolute alarm date/time
        $event['valarms'] = array(
            array('trigger' => new DateTime('today 20:00:00'))
        );
        $alarm = libcalendaring::get_next_alarm($event);
        $this->assertEquals($event['valarms'][0]['trigger']->format('U'), $alarm['time']);

        // no alarms for cancelled events
        $event['status'] = 'CANCELLED';
        $alarm = libcalendaring::get_next_alarm($event);
        $this->assertEquals(null, $alarm);
    }

    /**
     * libcalendaring::part_is_vcalendar()
     */
    function test_part_is_vcalendar()
    {
        $part = new StdClass;
        $part->mimetype = 'text/plain';
        $part->filename = 'event.ics';

        $this->assertFalse(libcalendaring::part_is_vcalendar($part));

        $part->mimetype = 'text/calendar';
        $this->assertTrue(libcalendaring::part_is_vcalendar($part));

        $part->mimetype = 'text/x-vcalendar';
        $this->assertTrue(libcalendaring::part_is_vcalendar($part));

        $part->mimetype = 'application/ics';
        $this->assertTrue(libcalendaring::part_is_vcalendar($part));

        $part->mimetype = 'application/x-any';
        $this->assertTrue(libcalendaring::part_is_vcalendar($part));
    }

    /**
     * libcalendaring::to_rrule()
     */
    function test_to_rrule()
    {
        $rrule = array(
            'FREQ' => 'MONTHLY',
            'BYDAY' => '2WE',
            'INTERVAL' => 2,
            'UNTIL' => new DateTime('2025-05-01 18:00:00 CEST'),
        );

        $s = libcalendaring::to_rrule($rrule);

        $this->assertRegExp('/FREQ='.$rrule['FREQ'].'/',          $s, "Recurrence Frequence");
        $this->assertRegExp('/INTERVAL='.$rrule['INTERVAL'].'/',  $s, "Recurrence Interval");
        $this->assertRegExp('/BYDAY='.$rrule['BYDAY'].'/',        $s, "Recurrence BYDAY");
        $this->assertRegExp('/UNTIL=20250501T160000Z/',           $s, "Recurrence End date (in UTC)");
    }
}
