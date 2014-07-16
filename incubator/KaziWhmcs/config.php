<?php
/**
 * i-MSCP KaziWhmcs plugin
 * Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
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
    /**
     * API endpoint
     *
     * If you change this value, do not forget to edit the KAZIWHMCS_API_ENDPOINT constant from the
     * modules/servers/imscp/imscp.php file.
     */
    'api_endpoint' => '/kaziwhmcs',

    /**
     * Disable/Enable welcome email sent by i-MSCP to customer when a new account is created (default is disabled)
     */
    'imscp_welcome_msg' => false
);
