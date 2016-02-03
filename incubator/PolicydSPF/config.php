<?php
/**
 * i-MSCP PolicydSPF plugin
 * @copyright 2016 Ninos Ego <me@ninosego.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

return array(
	// policyd-spf service ( default: unix:private/policy-spf )
	'policyd_spf_service' => 'unix:private/policy-spf',

	// policyd-spf time limit ( default: 3600s )
	'policyd_spf_time_limit' => '3600s'
);
