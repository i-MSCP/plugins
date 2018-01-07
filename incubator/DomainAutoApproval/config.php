<?php
/**
 * i-MSCP DomainAutoApproval plugin
 * Copyright (C) 2012-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
    // When set to TRUE, any domain alias created by a client listed in the 'user_accounts' parameter will be auto-approved
    // When set to FALSE, any domain alias created by a client not listed in the 'user_accounts' parameter will be auto approved
    'approval_rule'   => false,

    // List of client accounts (read above to know how this list is used)
    'user_accounts'   => array(),

    // List of ignored domains
    // Any domain listed in that list will be ignored by this plugin, whatever the value of the approval rule above
    'ignored_domains' => array()
);
