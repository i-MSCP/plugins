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
 * @translator W.Wichelmann (Cool) <undisclosed>
 */

return array(
	'Plugin allowing to provide full or restricted shell access to your customers.' => 'Plugin som gör det möjligt att ge fullständig eller begränsad skal åtkomst till dina kunder.',
	'Unable to install: %s' => 'Kan inte installera: %s',
	'Unable to update: %s' => 'Kunde inte uppdatera: %s',
	'Unable to enable: %s' => 'Kunde inte aktivera: %s',
	'Unable to uninstall: %s' => 'Kan inte avinstallera: %s',
	'Your i-MSCP version is not compatible with this plugin. Try with a newer version.'=> 'Din i-MSCP versionen är inte kompatibel med denna plugin. Pröva med en nyare version.',
	'Invalid default authentication options: %s' => 'Ogiltiga alternativ standard autentisering: %s',
	'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.' => 'Alla autentiseringsalternativ som förekommer i default_ssh_auth_options parameter måste också anges i allowed_ssh_auth_options parametern.',
	'allowed_ssh_auth_options parameter must be an array.' => 'Allowed_ssh_auth_options parametern måste vara en array.',
	'default_ssh_auth_options parameter must be a string.' => 'Default_ssh_auth_options parametern måste vara en sträng.',
	'SSH permissions' => 'SSH behörigheter',
	'SSH keys' => 'SSH-nycklar',
	'This is the list of customers which are allowed to add their SSH keys to login on the system using SSH.' => 'Detta är en lista över kunder som får lägga sina SSH-nycklar för att logga in på systemet med SSH.',
	'Customer name' => 'Kundens namn',
	'Max Keys ' => 'Max Keys',
	'Authentication options' => 'Autentiserings alternativ',
	'Restricted shell' => 'Begränsad shell',
	'Status' => 'Status',
	'Actions' => 'Åtgärder',
	'Processing...' => 'Bearbetning...',
	'Add / Edit SSH Permissions' => 'Lägg till / Redigera SSH Behörigheter',
	'Maximum number of SSH keys' => 'Maximalt antal SSH-nycklar',
	'0 for unlimited' => '0 för obegränsad',
	'Enter a number' => 'Ange ett tal',
	'Enter a customer name' => 'Ange ett kundnamn',
	'Can edit authentication options' => 'Kan ändra autentiseringsalternativ',
	'See man authorized_keys for further details about authentication options.' => 'Se man authorized_keys för mer information om autentiseringsalternativ.',
	'Does the shell access must be provided in restricted environment (recommended)?' => 'Är skalet tillträde skall tillhandahållas i begränsad miljö (rekommenderas)?',
	'Unknown customer. Please enter a valid customer name.'=> 'Okänd kund. Ange ett giltigt kundnamn.',
	'Are you sure you want to revoke SSH permissions for this customer?'=> 'Är du säker på att du vill återkalla SSH behörigheter för den här kunden?',
	'Unknown action.' => 'Okänd åtgärd.',
	'Request Timeout: The server took too long to send the data.' => 'Timeout Request: Servern tog för lång tid att skicka data.',
	'An unexpected error occurred.' => 'Ett oväntat fel inträffade.',
	'Save' => 'Spara',
	'Cancel' => 'Avbryt',
	'Admin / Settings / SSH Permissions' => 'Admin / Inställningar / SSH Behörigheter',
	'An unexpected error occurred: %s' => 'Ett oväntat fel inträffade: %s',
	'SSH permissions not found.' => 'SSH behörigheter hittades inte.',
	'Bad request' => 'Ogiltig begäran',
	'All fields are required.' => 'Alla fält är obligatoriska.',
	"Wrong value for the 'Maximum number of SSH keys' field. Please, enter a number." => "Fel värde för 'Maximalt antal SSH-nycklar' fält. Vänligen, ange en siffra.",
	'SSH permissions were scheduled for addition.' => 'SSH behörigheter var planerade för till tillägg.',
	'SSH permissions were scheduled for update.' => 'SSH behörigheter var planerade för uppdatering.',
	'SSH permissions were scheduled for deletion.' => 'SSH behörigheter var planerade för radering.',
	'Edit permissions' => 'Redigera behörigheter',
	'Revoke permissions' => 'Återkalla behörigheter',
	'This is the list of SSH public keys associated with your account. Remove any keys that you do not recognize.'=> 'Detta är en lista över publika SSH nycklar kopplade till ditt konto. Ta bort alla nycklar som du inte känner igen.',
	'You can generate your rsa key pair by running the following command: %s' => 'Du kan skapa din rsa nyckelpar genom att köra följande kommando: %s',
	'Name' => 'Namn',
	'Fingerprint ' => 'Fingerprint',
	'User' => 'Användar',
	'Arbitrary name which allow you to retrieve your SSH key.' => 'Godtyckliga namn som gör att du kan hämta din SSH-nyckel.',
	'SSH Key name' => 'SSH Key namn',
	'SSH Key ' => 'SSH Key',
	'Enter a key name' => 'Ange ett nyckelnamn',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Stöds RSA viktiga format är PKCS#1, OpenSSH och XML Signature.',
	'Enter a key' => 'Ange en nyckel',
	'Are you sure you want to delete this SSH key? Be aware that this will destroy all your SSH sessions.'=> 'Är du säker på att du vill ta bort denna SSH-nyckel? Var medveten om att detta kommer att förstöra alla dina SSH sessioner.',
	'Client / Profile / SSH Keys' => 'Klient / Profile / SSH-nycklar',
	'SSH Key not found.' => 'SSH Key hittades inte.',
	'Un-allowed SSH key name. Please use alphanumeric and space characters only.'=> 'Un-tillåtna SSH nyckelnamn. Använd alfanumeriska och mellanslag bara.',
	'SSH key name is too long (Max 255 characters).'=> 'SSH-nyckel namnet är för långt (max 255 tecken).',
	'Invalid SSH key.' => 'Ogiltig SSH-nyckel.',
	'SSH key scheduled for addition.' => 'SSH-nyckel planerad till tillägg.',
	'Your SSH key limit is reached.' => 'Din SSH-nyckel gränsen är nådd.',
	'SSH key scheduled for update.' => 'SSH-nyckel planerad till uppdateringen.',
	'SSH key with same name or same fingerprint already exists.' => 'SSH-nyckel med samma namn eller samma fingeravtryck finns redan.',
	'SSH key scheduled for deletion.' => 'SSH-nyckel planerad till radering.',
	'An unexpected error occurred. Please contact your reseller.'=> 'Ett oväntat fel inträffade. Vänligen kontakta din återförsäljare.',
	'Show SSH key' => 'Visa SSH-nyckel',
	'Edit SSH key' => 'Redigera SSH-nyckel',
	'Delete this SSH key' => 'Ta bort den här SSH-nyckel',
	'Add / Edit SSH keys' => 'Lägg till / Redigera SSH-nycklar',
	'Add / Show SSH keys' => 'Lägg till / Show SSH-nycklar',
	'Allowed authentication options: %s' => 'tillåtna autentiseringsalternativ: %s',
	'Unlimited' => 'Obegränsad',
	'Yes' => 'Ja',
	'No' => 'Nej',
);
