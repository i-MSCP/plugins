<?php
/**
 * i-MSCP DomainAutoApproval plugin
 * Copyright (C) 2013 - 2014 Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

return array(
	# If set to TRUE, any domain alias created by user accounts listed in the 'user_accounts' parameter will be
	# auto-approved
	#
	## If set to FALSE, any domain alias created by user accounts not listed in the 'user_accounts' parameter will be
	# auto approved
	'approval_rule' => false,

	# Here, you can provide the list of user accounts (see above to known how the list is used)
	'user_accounts' => array(
		'test.com' // Sample entry - Replace it by your own domain entries
	),

	# List of disallowed domains
	# Any domain listed here will never be auto-approved, whatever the value of the approval rule above
	'disalowed_domains' => array()
);
