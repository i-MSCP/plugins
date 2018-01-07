<?php
/**
 * i-MSCP DebugBar Plugin
 * Copyright (C) 2010-2017 by Laurent Declercq
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

return [
    // List of enabled DebugBar components
    'components' => [
        '\\iMSCP\\Plugin\\DebugBar\\Component\\ComponentVersion',
        '\\iMSCP\\Plugin\\DebugBar\\Component\\ComponentVariables',
        '\\iMSCP\\Plugin\\DebugBar\\Component\\ComponentTimer',
        '\\iMSCP\\Plugin\\DebugBar\\Component\\ComponentMemory',
        '\\iMSCP\\Plugin\\DebugBar\\Component\\ComponentFiles',
        '\\iMSCP\\Plugin\\DebugBar\\Component\\ComponentDatabase',
        // Tab will be show only if the APCu userland cache is enabled
        '\\iMSCP\\Plugin\\DebugBar\\Component\\ComponentAPCu',
        // Tab will be show only if the OPcache cache is enabled
        '\\iMSCP\\Plugin\\DebugBar\\Component\\ComponentOPcache'
    ]
];
