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
 * @translator Igor Scheller (MyIgel)
 * @translator Ninos Ego (Ninos) <me@ninosego.de>
 */

return array(
	'Plugin allowing to provide full or restricted shell access to your customers.' => 'Ein Plugin, welches deinen Kunden vollen oder eingeschränkten Shellzugang ermöglicht.',
	'Unable to install: %s' => 'Kann nicht installiert werden: %s',
	'Unable to update: %s' => 'Kann nicht aktualisiert werden: %s',
	'Unable to enable: %s' => 'Kann nicht aktiviert werden: %s',
	'Unable to uninstall: %s' => 'Kann nicht deinstalliert werden: %s',
	'Your i-MSCP version is not compatible with this plugin. Try with a newer version.' => 'Deine i-MSCP Version ist mit diesem Plugin nicht kompatibel. Benutze eine neuere Version.',
	'Invalid default authentication options: %s' => 'Nicht gültige Standardauthentifizierungsoptionen: %s',
	'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.' => 'Alle Authentifizierungsmethoden im default_ssh_auth_options Parameter müssen auch im allowed_ssh_auth_options Parameter eingetragen sein.',
	'allowed_ssh_auth_options parameter must be an array.' => 'Der allowed_ssh_auth_options Parameter muss ein Array sein.',
	'default_ssh_auth_options parameter must be a string.' => 'Der default_ssh_auth_options Parameter muss ein String sein.',
	'SSH permissions' => 'SSH-Berechtigungen',
	'SSH keys' => 'SSH-Schlüssel',
	'This is the list of customers which are allowed to add their SSH keys to login on the system using SSH.' => 'Das ist die Liste der Kunden, welche SSH-Schlüssel hinzufügen können, um sich mit SSH anzumelden.',
	'Customer name' => 'Kundenname',
	'Max Keys' => 'Max Schlüssel',
	'Authentication options' => 'Authentifizierungsoptionen',
	'Restricted shell' => 'Eingeschränkte Shell',
	'Status' => 'Status',
	'Actions' => 'Aktionen',
	'Processing...' => 'Verarbeitung...',
	'Add / Edit SSH Permissions' => 'SSH-Berechtigungen hinzufügen / bearbeiten',
	'Maximum number of SSH keys' => 'Maximale Anzahl an SSH-Schlüsseln',
	'0 for unlimited' => '0 für unbegrenzt',
	'Enter a number' => 'Gebe eine Zahl ein',
	'Enter a customer name' => 'Gebe einen Kundennamen ein',
	'Can edit authentication options' => 'Kann Authentifizierungsoptionen bearbeiten',
	'See man authorized_keys for further details about authentication options.' => 'Siehe man authorized_keys für weitere Details zu den Authentifizierungsoptionen.',
	'Does the shell access must be provided in restricted environment (recommended)?' => 'Soll der Shellzugriff in einer geschützten Umgebung bereitgestellt werden (empfohlen)?',
	'Unknown customer. Please enter a valid customer name.' => 'Unbekannter Kunde. Bitte gebe einen gültigen Kundennamen ein.',
	'Are you sure you want to revoke SSH permissions for this customer?' => 'Bist du sicher, dass du die SSH-Berechtigungen für diesen Kunden wiederrufen möchtest?',
	'Unknown action.' => 'Unbekannte Aktion.',
	'Request Timeout: The server took too long to send the data.' => 'Request Timeout: Der Server brauchte zu lange, um die Daten zu senden.',
	'An unexpected error occurred.' => 'Es ist ein unerwarteter Fehler aufgetreten.',
	'Save' => 'Speichern',
	'Cancel' => 'Abbrechen',
	'Admin / Settings / SSH Permissions' => 'Admin / Einstellungen / SSH-Berechtigungen',
	'An unexpected error occurred: %s' => 'Ein unerwarteter Fehler ist aufgetreten: %s',
	'SSH permissions not found.' => 'SSH-Berechtigungen nicht gefunden.',
	'Bad request' => 'Bad request',
	'All fields are required.' => 'Alle Felder müssen ausgefüllt werden.',
	"Wrong value for the 'Maximum number of SSH keys' field. Please, enter a number." => "Ungültiger Wert des 'Maximale Anzahl an SSH-Schlüsseln' Feldes. Gebe bitte eine Zahl ein.",
	'SSH permissions were scheduled for addition.' => 'Die SSH-Berechtigungen wurden zum Hinzufügen vorgemerkt.',
	'SSH permissions were scheduled for update.' => 'Die SSH-Berechtigungen wurden zum Aktualisieren vorgemerkt.',
	'SSH permissions were scheduled for deletion.' => 'Die SSH-Berechtigungen wurden zum Löschen vorgemerkt.',
	'Edit permissions' => 'Berechtigungen bearbeiten',
	'Revoke permissions' => 'Berecthigungen wiederrufen',
	'This is the list of SSH public keys associated with your account. Remove any keys that you do not recognize.' => 'Liste der öffentlichen SSH-Schlüssel, welche mit deinem Account verbunden sind. Entferne alle Schlüssel, die du nicht kennst.',
	'You can generate your rsa key pair by running the following command: %s' => 'Du kannst dein RSA-Schlüsselpaar mit folgendem Befehl erzeugen: %s',
	'Name' => 'Name',
	'Fingerprint' => 'Fingerabdruck',
	'User' => 'Benutzer',
	'Arbitrary name which allow you to retrieve your SSH key.' => 'Ein beliebiger Name, welcher es dir erlaubt, deinen SSH-Schlüssel wieder zu finden.',
	'SSH Key name' => 'SSH-Schlüsselname',
	'SSH Key' => 'SSH-Schlüssel',
	'Enter a key name' => 'Gebe einen Schlüsselnamen ein',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Unterstützte RSA-Schlüsselformate sind PKCS#1, openSSH und XML Signatur.',
	'Enter a key' => 'Gebe einen Schlüssel ein',
	'Are you sure you want to delete this SSH key? Be aware that this will destroy all your SSH sessions.' => 'Bist du sicher, dass du diesen SSH-Schlüssel löschen willst? Dies wird alle deine SSH-Sessions beenden.',
	'Client / Profile / SSH Keys' => 'Kunde / Profil / SSH-Schlüssel',
	'SSH Key not found.' => 'SSH-Schlüssel nicht gefunden.',
	'Un-allowed SSH key name. Please use alphanumeric and space characters only.' => 'Unzulässiger SSH-Schlüsselname. Bitte benutze nur alphanumerische Zeichen und das Leerzeichen.',
	'SSH key name is too long (Max 255 characters).' => 'Der Name des SSH-Schlüssels ist zu lange (Max 255 Zeichen).',
	'Invalid SSH key.' => 'Fehlerhafter SSH-Schlüssel.',
	'SSH key scheduled for addition.' => 'Der SSH-Schlüssel wurde zum Hinzufügen vorgemerkt.',
	'Your SSH key limit is reached.' => 'Dein SSH-Schlüsselimit ist erreicht.',
	'SSH key scheduled for update.' => 'Der SSH-Schlüssel wurde zur Aktualisierung vorgemerkt.',
	'SSH key with same name or same fingerprint already exists.' => 'Ein SSH-Schlüssel mit dem selben Namen oder Fingerabdruck existiert bereits.',
	'SSH key scheduled for deletion.' => 'Der SSH-Schlüssel wurde für die Löschung vorgemerkt.',
	'An unexpected error occurred. Please contact your reseller.' => 'Ein unerwarteter Fehler ist aufgetreten. Bitte kontaktiere deinen Reseller.',
	'Show SSH key' => 'SSH-Schlüssel anzeigen',
	'Edit SSH key' => 'SSH-Schlüssel bearbeiten',
	'Delete this SSH key' => 'Diesen SSH-Schlüssel löschen',
	'Add / Edit SSH keys' => 'SSH-Schlüssel hinzufügen / bearbeiten',
	'Add / Show SSH keys' => 'SSH-Schlüssel hinzufügen / anzeigen',
	'Allowed authentication options: %s' => 'Erlaubte Authentifizierungsoptionen: %s',
	'Unlimited' => 'Unbegrenzt',
	'Yes' => 'Ja',
	'No' => 'Nein'
);
