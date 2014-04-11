<?php
/**
 * i-MSCP InstantSSH plugin
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

require_once __DIR__ . '/library/InstantSSH/Validate/SshAuthOptions.php';

return array(
	// Default SSH authentication options added on any new customer key.
	// See man authorized_keys for list of allowed authentication options.
	// eg. command="dump /home",no-pty,no-port-forwarding
	//
	// Note that any option defined here must be specified in the allowed_ssh_auth_options parameter (see below)
	'default_ssh_auth_options' => 'no-agent-forwarding,no-port-forwarding,no-X11-forwarding',

	/**
	 * SSH authentication options that customers are allowed to define when they are allowed to override default.
	 *
	 * Valid options are:
	 *
	 * \InstantSSH\Validate\SshAuthOptions::ALL (for all options)
	 * \InstantSSH\Validate\SshAuthOptions::CERT_AUTHORITY (for the cert-authority option)
	 * \InstantSSH\Validate\SshAuthOptions::COMMAND (for the 'command' option)
	 * \InstantSSH\Validate\SshAuthOptions::ENVIRONMENT (for the 'environment' option)
	 * \InstantSSH\Validate\SshAuthOptions::FROM (for the 'from' option)
	 * \InstantSSH\Validate\SshAuthOptions::NO_AGENT_FORWARDING (for the 'no-agent-forwarding' option)
	 * \InstantSSH\Validate\SshAuthOptions::NO_PORT_FORWARDING (for the 'no-port-forwarding' option)
	 * \InstantSSH\Validate\SshAuthOptions::NO_PTY (for the 'no-pty' option)
	 * \InstantSSH\Validate\SshAuthOptions::NO_USER_RC (for the 'no-user-rc' option)
	 * \InstantSSH\Validate\SshAuthOptions::NO_X11_FORWARDING (for the 'no-x11-forwarding' option)
	 * \InstantSSH\Validate\SshAuthOptions::PERMITOPEN (for the 'permitopen' option)
	 * \InstantSSH\Validate\SshAuthOptions::PRINCIPALS (for the 'principals' option)
	 * \InstantSSH\Validate\SshAuthOptions::TUNNEL (for the 'tunnel' option)
	 */
	'allowed_ssh_auth_options' => array(
		\InstantSSH\Validate\SshAuthOptions::NO_AGENT_FORWARDING,
		\InstantSSH\Validate\SshAuthOptions::NO_PORT_FORWARDING,
		\InstantSSH\Validate\SshAuthOptions::NO_X11_FORWARDING
	)
);
