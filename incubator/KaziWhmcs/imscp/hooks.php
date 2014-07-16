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

/**
 * Update auto-generated username with domain name
 *
 * @param array $vars Variables
 */
function hook_imscp_update_username($vars)
{
    if (!empty($vars['products'])) {
        register_shutdown_function(
            function ($products) {
                foreach ($products as $product) {
                    if (isset($product['domain'])) {
                        update_query(
                            'tblhosting',
                            array(
                                'username' => $product['domain'],
                                'lastupdate' => 'now()'
                            ),
                            array(
                                'domain' => $product['domain']
                            )
                        );
                    }
                }
            },
            $vars['products']
        );
    }
}

add_hook('OverrideOrderNumberGeneration', 1, 'hook_imscp_update_username');
