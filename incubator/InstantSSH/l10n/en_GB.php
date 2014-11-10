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
 *
 * @translator Laurent Declercq (nuxwin) <l.declercq@nuxwin.com>
 */

return array(
	'Plugin allowing to provide full or restricted shell access to your customers.' => 'Plugin allowing to provide full or restricted shell access to your customers.',
	'Unable to install: %s' => 'Unable to install: %s',
	'Unable to update: %s' => 'Unable to update: %s',
	'Unable to enable: %s' => 'Unable to enable: %s',
	'Unable to uninstall: %s' => 'Unable to uninstall: %s',
	'Your i-MSCP version is not compatible with this plugin. Try with a newer version.' => 'Your i-MSCP version is not compatible with this plugin. Try with a newer version.',
	'Invalid default authentication options: %s' => 'Invalid default authentication options: %s',
	'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.' => 'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.',
	'allowed_ssh_auth_options parameter must be an array.' => 'allowed_ssh_auth_options parameter must be an array.',
	'default_ssh_auth_options parameter must be a string.' => 'default_ssh_auth_options parameter must be a string.',
	'SSH permissions' => 'SSH permissions',
	'SSH users' => 'SSH users',
	'This is the list of customers which are allowed to create SSH users to login on the system using SSH.' => 'This is the list of customers which are allowed to create SSH users to login on the system using SSH.',
	'Customer name' => 'Customer name',
	'Max SSH users' => 'Max SSH users',
	'Authentication options' => 'Authentication options',
	'Restricted shell' => 'Restricted Shell',
	'Status' => 'Status',
	'Actions' => 'Actions',
	'Processing...' => 'Processing...',
	'Add / Edit SSH Permissions' => 'Add / Edit SSH Permissions',
	'Maximum number of SSH users' => 'Maximum number of SSH users',
	'0 for unlimited' => '0 for unlimited',
	'Enter a number' => 'Enter a number',
	'Enter a customer name' => 'Enter a customer name',
	'Can edit authentication options' => 'Can edit authentication options',
	'See man authorized_keys for further details about authentication options.' => 'See man authorized_keys for further details about authentication options.',
	'Does the shell access must be provided in restricted environment (recommended)?' => 'Does the shell access must be provided in restricted environment (recommended)?',
	'Unknown customer. Please enter a valid customer name.' => 'Unknown customer. Please enter a valid customer name.',
	'Are you sure you want to revoke SSH permissions for this customer?' => 'Are you sure you want to revoke SSH permissions for this customer?',
	'Unknown action.' => 'Unknown action.',
	'Request Timeout: The server took too long to send the data.' => 'Request Timeout: The server took too long to send the data.',
	'An unexpected error occurred.' => 'An unexpected error occurred.',
	'Save' => 'Save',
	'Cancel' => 'Cancel',
	'Admin / Settings / SSH Permissions' => 'Admin / Settings / SSH Permissions',
	'An unexpected error occurred: %s' => 'An unexpected error occurred: %s',
	'SSH permissions not found.' => 'SSH permissions not found.',
	'Bad request.' => 'Bad request.',
	'All fields are required.' => 'All fields are required.',
	"Wrong value for the 'Maximum number of SSH users' field. Please, enter a number." => "Wrong value for the 'Maximum number of SSH users' field. Please, enter a number.",
	'SSH permissions were added.' => 'SSH permissions were added.',
	'SSH permissions were scheduled for update.' => 'SSH permissions were scheduled for update.',
	'SSH permissions were scheduled for deletion.' => 'SSH permissions were scheduled for deletion.',
	'Edit permissions' => 'Edit permissions',
	'Revoke permissions' => 'Revoke permissions',
	'This is the list of SSH users associated with your account.' => 'This is the list of SSH users associated with your account.',
	"You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication." => "You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication.",
	'You can generate your rsa key pair by running the following command:' => 'You can generate your rsa key pair by running the following command:',
	'Key fingerprint' => 'Key fingerprint',
	'SSH user' => 'SSH user',
	'Username' => 'Username',
	'Enter an username' => 'Enter an username',
	'Password' => 'Password',
	'Enter a password' => 'Enter a password',
	'Password confirmation' => 'Password confirmation',
	'Confirm the password' => 'Confirm the password',
	'SSH key' => 'SSH key',
	'Enter your SSH key' => 'Enter your SSH key',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Supported RSA key formats are PKCS#1, openSSH and XML Signature.',
	'Are you sure you want to delete this SSH user?' => 'Are you sure you want to delete this SSH user?',
	'Client / Profile / SSH Users' => 'Client / Profile / SSH Users',
	'SSH user not found.' => 'SSH Key not found.',
	'Un-allowed username. Please use alphanumeric characters only.' => 'Un-allowed username. Please use alphanumeric characters only.',
	'The username is too long (Max 8 characters).' => 'The username is too long (Max 8 characters).',
	'This username is not available.' => 'This username is not available.',
	'You must enter an SSH key.' => 'You must enter an SSH key.',
	'You must enter either a password, an SSH key or both.' => 'You must enter either a password, an SSH key or both.',
	'Un-allowed password. Please use alphanumeric characters only.' => 'Un-allowed password. Please use alphanumeric characters only.',
	'The password is too long (Max 32 characters).' => 'The password is too long (Max 32 characters).',
	'Passwords do not match.' => 'Passwords do not match.',
	'Invalid SSH key.' => 'Invalid SSH key.',
	'SSH user has been scheduled for addition.' => 'SSH user has been scheduled for addition.',
	'Your SSH user limit is reached.' => 'Your SSH user limit is reached.',
	'SSH user has been scheduled for update.' => 'SSH user has been scheduled for update.',
	'SSH user has been scheduled for deletion.' => 'SSH user has been scheduled for deletion.',
	'Nothing has been changed.' => 'Nothing has been changed.',
	'An unexpected error occurred. Please contact your reseller.' => 'An unexpected error occurred. Please contact your reseller.',
	'Add / Edit SSH user' => 'Add / Edit SSH user',
	'Delete this SSH user' => 'Delete this SSH user',
	'Allowed authentication options: %s' => 'Allowed authentication options: %s',
	'Unlimited' => 'Unlimited',
	'Yes' => 'Yes',
	'No' => 'No'
);
