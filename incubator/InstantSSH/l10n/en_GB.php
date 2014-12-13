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
	'Invalid default authentication options: %s' => 'Invalid default authentication options: %s',
	'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.' => 'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.',
	'The allowed_ssh_auth_options parameter must be an array.' => 'The allowed_ssh_auth_options parameter must be an array.',
	'The default_ssh_auth_options parameter must be a string.' => 'The default_ssh_auth_options parameter must be a string.',
	'SSH permissions' => 'SSH permissions',
	'SSH users' => 'SSH users',
	'An unexpected error occurred: %s' => 'An unexpected error occurred: %s',
	'Bad request.' => 'Bad request.',
	'All fields are required.' => 'All fields are required.',
	'SSH permissions were added.' => 'SSH permissions were added.',
	'SSH permissions were updated.' => 'SSH permissions were updated.',
	'Nothing has been changed.' => 'Nothing has been changed.',
	"One or many SSH users which belongs to the reseller's customers are currently processed. Please retry in few minutes." => "One or many SSH users which belongs to the reseller's customers are currently processed. Please retry in few minutes.",
	'SSH permissions were deleted.' => 'SSH permissions were deleted.',
	'Edit permissions' => 'Edit permissions',
	'Revoke permissions' => 'Revoke permissions',
	'Yes' => 'Yes',
	'No' => 'No',
	'n/a' => 'n/a',
	'Admin / Settings / SSH Permissions' => 'Admin / Settings / SSH Permissions',
	'This is the list of resellers which are allowed to give SSH permissions to their customers.' => 'This is the list of resellers which are allowed to give SSH permissions to their customers.',
	'Reseller name' => 'Reseller name',
	'Can edit authentication options' => 'Can edit authentication options',
	'Restricted shell' => 'Restricted shell',
	'Status' => 'Status',
	'Actions' => 'Actions',
	'Processing...' => 'Processing...',
	'Add / Edit SSH Permissions' => 'Add / Edit SSH Permissions',
	'Enter a reseller name' => 'Enter a reseller name',
	'See man authorized_keys for further details about authentication options.' => 'See man authorized_keys for further details about authentication options.',
	'Does the shell access have to be provided in restricted environment (recommended)?' => 'Does the shell access have to be provided in restricted environment (recommended)?',
	'Save' => 'Save',
	'Cancel' => 'Cancel',
	'Unknown reseller. Please enter a valid reseller name.' => 'Unknown reseller. Please enter a valid reseller name.',
	'You must enter a reseller name.' => 'You must enter a reseller name.',
	'Are you sure you want to revoke SSH permissions for this reseller?' => 'Are you sure you want to revoke SSH permissions for this reseller?',
	'Unknown action.' => 'Unknown action.',
	'Request Timeout: The server took too long to send the data.' => 'Request Timeout: The server took too long to send the data.',
	'An unexpected error occurred.' => 'An unexpected error occurred.',
	"Wrong value for the 'Maximum number of SSH users' field. Please, enter a number." => "Wrong value for the 'Maximum number of SSH users' field. Please, enter a number.",
	'One or many SSH users which belongs to the customer are currently processed. Please retry in few minutes.' => 'One or many SSH users which belongs to the customer are currently processed. Please retry in few minutes.',
	'Unlimited' => 'Unlimited',
	'An unexpected error occurred. Please contact your administrator.' => 'An unexpected error occurred. Please contact your administrator.',
	'Reseller / Customers / SSH Permissions' => 'Reseller / Customers / SSH Permissions',
	'This is the list of customers which are allowed to create SSH users to login on the system using SSH.' => 'This is the list of customers which are allowed to create SSH users to login on the system using SSH.',
	'Customer name' => 'Customer name',
	'Max SSH users' => 'Max SSH users',
	'Enter a customer name' => 'Enter a customer name',
	'Maximum number of SSH users' => 'Maximum number of SSH users',
	'0 for unlimited' => '0 for unlimited',
	'Enter a number' => 'Enter a number',
	'Unknown customer. Please enter a valid customer name.' => 'Unknown customer. Please enter a valid customer name.',
	'You must enter a customer name.' => 'You must enter a customer name.',
	'Are you sure you want to revoke SSH permissions for this customer?' => 'Are you sure you want to revoke SSH permissions for this customer?',
	'An unexpected error occurred. Please contact your reseller.' => 'An unexpected error occurred. Please contact your reseller.',
	'The username field is required.' => 'The username field is required.',
	'Un-allowed username. Please use alphanumeric characters only.' => 'Un-allowed username. Please use alphanumeric characters only.',
	'The username is too long (Max 8 characters).' => 'The username is too long (Max 8 characters).',
	'This username is not available.' => 'This username is not available.',
	'You must enter an SSH key.' => 'You must enter an SSH key.',
	'You must enter either a password, an SSH key or both.' => 'You must enter either a password, an SSH key or both.',
	'Un-allowed password. Please use ASCII characters only.' => 'Un-allowed password. Please use ASCII characters only.',
	'Wrong password length (Min 8 characters).' => 'Wrong password length (Min 8 characters).',
	'Wrong password length (Max 32 characters).' => 'Wrong password length (Max 32 characters).',
	'Passwords do not match.' => 'Passwords do not match.',
	'Invalid SSH key.' => 'Invalid SSH key.',
	'SSH user has been scheduled for addition.' => 'SSH user has been scheduled for addition.',
	'Your SSH user limit is reached.' => 'Your SSH user limit is reached.',
	'SSH user has been scheduled for update.' => 'SSH user has been scheduled for update.',
	'An SSH user with the same name or the same SSH key already exists.' => 'An SSH user with the same name or the same SSH key already exists.',
	'SSH user has been scheduled for deletion.' => 'SSH user has been scheduled for deletion.',
	'Edit SSH user' => 'Edit SSH user',
	'Delete this SSH user' => 'Delete this SSH user',
	'Client / Domains / SSH Users' => 'Client / Domains / SSH Users',
	'Allowed authentication options: %s' => 'Allowed authentication options: %s',
	'This is the list of SSH users associated with your account.' => 'This is the list of SSH users associated with your account.',
	'SSH user' => 'SSH user',
	'Key fingerprint' => 'Key fingerprint',
	'Add / Edit SSH user' => 'Add / Edit SSH user',
	'Username' => 'Username',
	'Enter an username' => 'Enter an username',
	'Password' => 'Password',
	'Enter a password' => 'Enter a password',
	'Password confirmation' => 'Password confirmation',
	'Confirm the password' => 'Confirm the password',
	'SSH key' => 'SSH key',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Supported RSA key formats are PKCS#1, openSSH and XML Signature.',
	'Enter your SSH key' => 'Enter your SSH key',
	'Authentication options' => 'Authentication options',
	'Enter authentication option(s)' => 'Enter authentication option(s)',
	"You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication." => "You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication.",
	'You can generate your rsa key pair by running the following command:' => 'You can generate your rsa key pair by running the following command:',
	'Are you sure you want to delete this SSH user?' => 'Are you sure you want to delete this SSH user?',
	'Rebuild of jails has been scheduled. Depending of the number of jails, this could take some time...' => 'Rebuild of jails has been scheduled. Depending of the number of jails, this could take some time...',
	'No jail to rebuild. Operation cancelled.' => 'No jail to rebuild. Operation cancelled.',
	'Rebuild Jails' => 'Rebuild Jails'
);
