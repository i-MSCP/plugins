<?php
/**
 * i-MSCP InstantSSH plugin
 * Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
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
 * @translator W.Wichelmann (Cool) <undisclosed>
 */

return array(
	'Invalid default authentication options: %s' => 'Ogiltiga alternativ standard autentisering: %s',
	'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.' => 'Alla autentiseringsalternativ som förekommer i default_ssh_auth_options parameter måste också anges i allowed_ssh_auth_options parametern.',
	'allowed_ssh_auth_options parameter must be an array.' => 'Den allowed_ssh_auth_options parametern måste vara en array.',
	'default_ssh_auth_options parameter must be a string.' => 'Den default_ssh_auth_options parametern måste vara en sträng.',
	'SSH permissions' => 'SSH behörigheter',
	'SSH users' => 'SSH-användares',
	'An unexpected error occurred: %s' => 'Ett oväntat fel inträffade: %s',
	'Bad request.' => 'Ogiltig begäran.',
	'All fields are required.' => 'Alla fält är obligatoriska.',
	'SSH permissions were added.' => 'SSH behörigheter sattes.',
	'SSH permissions were updated.' => 'SSH behörigheter uppdaterades.',
	'Nothing has been changed.' => 'Ingenting har förändrats.',
	"One or many SSH users which belongs to the reseller's customers are currently processed. Please retry in few minutes." => "One or many SSH users which belongs to the reseller's customers are currently processed. Please retry in few minutes.",
	'SSH permissions were deleted.' => 'SSH behörigheter ströks.',
	'Edit permissions' => 'Redigera behörigheter',
	'Revoke permissions' => 'Återkalla behörigheter',
	'Yes' => 'Ja',
	'No' => 'Nej',
	'n/a' => 'n/a',
	'Admin / Settings / SSH Permissions' => 'Admin / Inställningar / SSH Behörigheter',
	'This is the list of resellers which are allowed to give SSH permissions to their customers.' => 'This is the list of resellers which are allowed to give SSH permissions to their customers.',
	'Reseller name' => 'Reseller name',
	'Can edit authentication options' => 'Kan ändra autentiseringsalternativ',
	'Restricted shell' => 'Restricted shell',
	'Status' => 'Status',
	'Actions' => 'Åtgärder',
	'Processing...' => 'Bearbetning...',
	'Add / Edit SSH Permissions' => 'Lägg till / Redigera SSH Behörigheter',
	'Enter a reseller name' => 'Enter a reseller name',
	'See man authorized_keys for further details about authentication options.' => 'Se man authorized_keys för mer information om autentiseringsalternativ.',
	'Does the shell access have to be provided in restricted environment (recommended)?' => 'Är skalet tillträde skall tillhandahållas i begränsad miljö (rekommenderas)?',
	'Save' => 'Spara',
	'Cancel' => 'Avbryt',
	'Unknown reseller. Please enter a valid reseller name.' => 'Unknown reseller. Please enter a valid reseller name.',
	'You must enter a reseller name.' => 'You must enter a reseller name.',
	'Are you sure you want to revoke SSH permissions for this reseller?' => 'Are you sure you want to revoke SSH permissions for this reseller?',
	'Unknown action.' => 'Okänd åtgärd.',
	'Request Timeout: The server took too long to send the data.' => 'Timeout Request: Servern tog för lång tid att skicka data.',
	'An unexpected error occurred.' => 'Ett oväntat fel inträffade.',
	"Wrong value for the 'Maximum number of SSH users' field. Please, enter a number." => "Fel värde för 'Maximalt antal SSH-nycklar' fält. Vänligen, ange en siffra.",
	'One or many SSH users which belongs to the customer are currently processed. Please retry in few minutes.' => 'One or many SSH users which belongs to the customer are currently processed. Please retry in few minutes.',
	'Unlimited' => 'Obegränsad',
	'An unexpected error occurred. Please contact your administrator.' => 'An unexpected error occurred. Please contact your administrator.',
	'Reseller / Customers / SSH Permissions' => 'Reseller / Customers / SSH Permissions',
	'This is the list of customers which are allowed to create SSH users to login on the system using SSH.' => 'Detta är en lista över kunder som får skapa SSH-användare att logga in på systemet med SSH.',
	'Customer name' => 'Kundens namn',
	'Max SSH users' => 'Max SSH-användares',
	'Enter a customer name' => 'Ange ett kundnamn',
	'Maximum number of SSH users' => 'Maximalt antal SSH-användares',
	'0 for unlimited' => '0 för obegränsad',
	'Enter a number' => 'Ange ett tal',
	'Unknown customer. Please enter a valid customer name.'=> 'Okänd kund. Ange ett giltigt kundnamn.',
	'You must enter a customer name.' => 'Du måste ange ett kundnamn.',
	'Are you sure you want to revoke SSH permissions for this customer?'=> 'Är du säker på att du vill återkalla SSH behörigheter för den här kunden?',
	'An unexpected error occurred. Please contact your reseller.'=> 'Ett oväntat fel inträffade. Vänligen kontakta din återförsäljare.',
	'The username field is required.' => 'Användarnamnet fältet är obligatoriskt.',
	'Un-allowed username. Please use alphanumeric characters only.'=> 'Un-tillåtet användarnamn. Använd bara alfanumeriska tecken.',
	'The username is too long (Max 8 characters).' => 'Användarnamnet är för lång (max 8 tecken).',
	'This username is not available.' => 'Användarnamnet är inte tillgänglig.',
	'You must enter an SSH key.' => 'Du måste ange en SSH-nyckel.',
	'You must enter either a password, an SSH key or both.' => 'Du måste ange antingen ett lösenord, en SSH-nyckel eller båda.',
	'Un-allowed password. Please use ASCII characters only.' => 'Un-tillåtet lösenord. Använd bara ASCII tecken.',
	'Wrong password length (Min 8 characters).' => 'Fel lösenord längd (Min 8 tecken).',
	'Wrong password length (Max 32 characters).' => 'Fel lösenord längd (Max 32 tecken).',
	'Passwords do not match.' => 'Lösenorden matchar inte.',
	'Invalid SSH key.' => 'Ogiltig SSH-nyckel.',
	'SSH user has been scheduled for addition.' => 'SSH user has been scheduled for addition.',
	'Your SSH user limit is reached.' => 'Din SSH-användare gränsen är nådd.',
	'SSH user has been scheduled for update.' => 'SSH-användare planerad till uppdateringen.',
	'An SSH user with the same name or the same SSH key already exists.' => 'En SSH användare med samma namn eller samma SSH-nyckel finns redan.',
	'SSH user has been scheduled for deletion.' => 'SSH-användare planerad till radering.',
	'Edit SSH user' => 'Edit SSH user',
	'Delete this SSH user' => 'Ta bort den här SSH-användare',
	'Client / Domains / SSH Users' => 'Client / Domains / SSH Users',
	'Allowed authentication options: %s' => 'tillåtna autentiseringsalternativ: %s',
	'This is the list of SSH users associated with your account.'=> 'Detta är en lista över SSH-användare är kopplade till ditt konto.',
	'SSH user' => 'SSH-användar',
	'Key fingerprint' => 'Nyckelns fingeravtryck',
	'Add / Edit SSH user' => 'Lägg till / redigera SSH-användare',
	'Username' => 'Username',
	'Enter an username' => 'Ange ett användarnamn',
	'Password' => 'lösenord',
	'Enter a password' => 'Ange ett lösenord',
	'Password confirmation' => 'Bekräftelse lösenord',
	'Confirm the password' => 'Bekräfta lösenord',
	'SSH key' => 'SSH-nyckel',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Stöds RSA viktiga format är PKCS#1, OpenSSH och XML Signature.',
	'Enter your SSH key' => 'Ange ditt SSH-nyckel',
	'Authentication options' => 'Autentiserings alternativ',
	'Enter authentication option(s)' => 'Enter authentication option(s)',
	"You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication." => "Du kan ge antingen ett lösenord, en SSH-nyckel eller båda. Men det är rekommenderat att föredra nyckelbaserad autentisering.",
	'You can generate your rsa key pair by running the following command:' => 'Du kan skapa din RSA-nyckelpar genom att köra följande kommando:',
	'Are you sure you want to delete this SSH user?'=> 'Är du säker på att du vill ta bort denna SSH användare?',
	'Rebuild of jails has been scheduled. Depending of the number of jails, this could take some time...' => 'Rebuild of jails has been scheduled. Depending of the number of jails, this could take some time...',
	'No jail to rebuild. Operation cancelled.' => 'No jail to rebuild. Operation cancelled.',
	'Rebuild Jails' => 'Rebuild Jails',
	'Unable to schedule rebuild of jails: %s' => 'Unable to schedule rebuild of jails: %s',
	'Are you sure you want to schedule rebuild of all jails?' => 'Are you sure you want to schedule rebuild of all jails?'
);
