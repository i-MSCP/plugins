<?php
/**
 * i-MSCP DomainAutoApproval plugin
 * Copyright (C) 2012-2018 Laurent Declercq <l.declercq@nuxwin.com>
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

return [
    // Approval rules (default: false)
    //
    // When set to FALSE, domain aliases that are owned by a client account not
    // listed in the the 'client_accounts' parameter are automatically
    // approved.
    //
    // When set to TRUE, domain aliases that are owned by a client listed in the
    // 'user_accounts' parameter are automatically approved.
    'approval_rule'          => false,

    // List of client accounts for which domain aliases need to be (or NOT)
    // automatically approved, according value of the 'approval_rule'
    // parameter.
    //
    // Client account have to be listed in their punycode representation.
    'client_accounts'        => [],

    // List of ignored domain aliases
    //
    // List of domain aliases that need to be ignored by this plugin,
    // regardless value of both the 'approval_rule' and 'client_accounts'
    // parameters.
    //
    // Domain aliases have to be listed in their punycode representation.
    'ignored_domain_aliases' => []
];
