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

return array(
	'Plugin allowing to provide full or restricted shell access to your customers.' => 'Ein Plugin, welches den Kunden vollen oder eingeschränkten Shellzugang ermöglicht.',
	'Unable to install: %s' => '%s kann nicht installiert werden',
	'Unable to update: %s' => '% kann nicht aktualisiert werdens',
	'Unable to enable: %s' => '%s kann nicht aktiviert werden',
	'Unable to uninstall: %s' => '%s kann nicht deinstalliert werden',
	'Your i-MSCP version is not compatible with this plugin. Try with a newer version.' => 'Deine i-MSCP Version ist mit diesem Plugin nicht kompatibel. Benutze eine neuere Version.',
	'Invalid default authentication options: %s' => 'Nicht gültige Standardauthentifizierungsoptionen: %s',
	'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.' => 'Alle Authentifizierungsmethoden im default_ssh_auth_options Parameter müssen auch im allowed_ssh_auth_options Parameter eingetragen sein.',
	'allowed_ssh_auth_options parameter must be an array.' => 'Der allowed_ssh_auth_options Parameter muss ein Array sein.',
	'default_ssh_auth_options parameter must be a string.' => 'Der default_ssh_auth_options Parameter muss ein String sein.',
	'SSH permissions' => 'SSH Berechtigungen',
	'SSH keys' => 'SSH Keys',
	'This is the list of customers which are allowed to add their SSH keys to login on the system using SSH.' => 'Das ist die Liste der Kunden, welche SSH Keys hinzufügen können um sich mit SSH anzumelden.',
	'Customer name' => 'Kundenname',
	'Max Keys' => 'Max Keys',
	'Authentication options' => 'Authentifizierungsoptionen',
	'Restricted shell' => 'Eingeschränkte Shell',
	'Status' => 'Status',
	'Actions' => 'Aktionen',
	'Processing...' => 'Verarbeitung...',
	'Add / Edit SSH Permissions' => 'SSH Berechtigungen hinzufügen / bearbeiten',
	'Maximum number of SSH keys' => 'Maximale Anzahl an SSH Keys',
	'0 for unlimited' => '0 für unbegrenzt',
	'Enter a number' => 'Gebe eine Zahl ein',
	'Enter a customer name' => 'Gebe einen Kundennamen ein',
	'Can edit authentication options' => 'Kann Authentifizierungsoptionen bearbeiten',
	'See man authorized_keys for further details about authentication options.' => 'Siehe man authorized_keys für weitere Details zu den Authentifizierungsoptionen.',
	'Does the shell access must be provided in restricted environment (recommended)?' => 'Soll der Shellzugriff in einer geschützten Umgebung bereitgestellt werden (empfohlen)?',
	'Unknown customer. Please enter a valid customer name.' => 'Unbekannter Kunde. Bitte geben Sie einen gültigen Kundennamen ein.',
	'Are you sure you want to revoke SSH permissions for this customer?' => 'Sind Sie sicher, dass Sie die SSH Berechtigungen für diesen Kunden wiederrufen wollen?',
	'Unknown action.' => 'Unbekannte Aktion.',
	'Request Timeout: The server took too long to send the data.' => 'Request Timeout: Der Server brauchte zu lange, um die Daten zu senden.',
	'An unexpected error occurred.' => 'Es ist ein unerwarteter Fehler aufgetreten.',
	'Save' => 'Speichern',
	'Cancel' => 'Abbrechen',
	'Admin / Settings / SSH Permissions' => 'Admin / Einstellungen / SSH Berechtigungen',
	'An unexpected error occurred: %s' => 'Ein unerwarteter Fehler ist aufgetreten: %s',
	'SSH permissions not found.' => 'SSH Berechtigungen nicht gefunden.',
	'Bad request' => 'Bad request',
	'All fields are required.' => 'Alle Felder müssen ausgefüllt werden.',
	"Wrong value for the 'Maximum number of SSH keys' field. Please, enter a number." => "Ungültiger Wert des 'Maximale Anzahl an SSH Keys' Feldes. Gebe bitte eine Zahl ein.",
	'SSH permissions scheduled for addition.' => 'Die SSH Berechtigungen wurden zum hinzufügen vorgemerkt.',
	'SSH permissions were scheduled for update.' => 'Die SSH Berechtigungen wurden zum Aktualisieren vorgemerkt.',
	'SSH permissions were scheduled for deletion.' => 'Die SSH Berechtigungen wurden zum Löschen vorgemerkt.',
	'Edit permissions' => 'Berechtigungen bearbeiten',
	'Revoke permissions' => 'Berecthigungen wiederrufen',
	'This is the list of SSH public keys associated with your account. Remove any keys that you do not recognize.' => 'Liste der SSH Public Keys welche mit ihrem Account verbunden sind. Entfernen Sie alle Keys die Sie nicht kennen.',
	'You can generate your rsa key pair by running the following command: %s' => 'Sie können Ihr RSA Schlüsselpaar mit folgendem Befehl erzeugen: %s',
	'Name' => 'Name',
	'Fingerprint' => 'Fingerabdruck',
	'User' => 'Benutzer',
	'Arbitrary name which allow you to retrieve your SSH key.' => 'Ein beliebiger Name, welcher es Ihnen erlaubt, ihren SSH Key wieder zu finden.',
	'SSH Key name' => 'SSH Key Name',
	'SSH Key' => 'SSH Key',
	'Enter a key name' => 'Gebe einen Schlüsselnamen ein',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Unterstützte RSA Key Formate sind PKCS#1, openSSH und XML Signatur.',
	'Enter a key' => 'Gebe einen Schlüssel ein',
	'Are you sure you want to delete this SSH key? Be aware that this will destroy all your SSH sessions.' => 'Sind Sie sicher, dass Sie diesen SSH Key löschen wollen? Dies wird alle ihre SSH Sessions löschen.',
	'Client / Profile / SSH Keys' => 'Kunde / Profil / SSH Keys',
	'SSH Key not found.' => 'SSH Key nicht gefunden.',
	'Un-allowed SSH key name. Please use alphanumeric and space characters only.' => 'Unzulässiger SSH Key Name. Bitte benutzen Sie nur Alphanumerische Zeichen und das Leerzeichen.',
	'SSH key name is too long (Max 255 characters).' => 'Der Name des SSH Keys ist zu lange (Max 255 Zeichen).',
	'Invalid SSH key.' => 'Fehlerhafter SSH Key.',
	'SSH key scheduled for addition.' => 'Der SSH Key wurde zum Hinzufügen vorgemerkt.',
	'Your SSH key limit is reached.' => 'Ihr SSH Key Limit ist erreicht.',
	'SSH key scheduled for update.' => 'Der SSH Key wurde zur Aktualisierung vorgemerkt.',
	'SSH key with same name or same fingerprint already exists.' => 'Ein SSH Key mit dem selben Namen oder Fingerabdruck existiert bereits.',
	'SSH key scheduled for deletion.' => 'Der SSH Key wurde für die Löschung vorgemerkt.',
	'An unexpected error occurred. Please contact your reseller.' => 'Ein unerwarteter Fehler ist aufgetreten. Bitte kontaktieren Sie ihren Reseller.',
	'Show SSH key' => 'SSH Key anzeigen',
	'Edit SSH key' => 'SSH Key bearbeiten',
	'Delete this SSH key' => 'Diesen SSH Key löschen',
	'Add / Edit SSH keys' => 'SSH Keys hinzufügen / bearbeiten',
	'Add / Show SSH keys' => 'SSH Keys hinzufügen / anzeigen',
	'Allowed authentication options: %s' => 'Erlaubte Authentifizierungsoptionen: %s',
	'Unlimited' => 'Unbegrenzt',
	'Yes' => 'Ja',
	'No' => 'Nein'
);
