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
 * @translator Igor Scheller (MyIgel) <undisclosed>
 * @translator Ninos Ego (Ninos) <me@ninosego.de>
 */

return array(
	'Invalid default authentication options: %s' => 'Nicht gültige Standardauthentifizierungsoptionen: %s',
	'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.' => 'Alle Authentifizierungsmethoden im default_ssh_auth_options Parameter müssen auch im allowed_ssh_auth_options Parameter eingetragen sein.',
	'The allowed_ssh_auth_options parameter must be an array.' => 'Der allowed_ssh_auth_options Parameter muss ein Array sein.',
	'The default_ssh_auth_options parameter must be a string.' => 'Der default_ssh_auth_options Parameter muss ein String sein.',
	'SSH permissions' => 'SSH-Berechtigungen',
	'SSH users' => 'SSH-Benutzer',
	'An unexpected error occurred: %s' => 'Es ist ein unerwarteter Fehler aufgetreten: %s',
	'Bad request.' => 'Bad request.',
	'All fields are required.' => 'Alle Felder müssen ausgefüllt werden.',
	'SSH permissions were added.' => 'Die SSH-Berechtigungen wurden hinzugefügt.',
	'SSH permissions were updated.' => 'SSH Berechtigungen wurden aktualisiert.',
	'Nothing has been changed.' => 'Es hat sich nicht geändert.',
	"One or many SSH users which belongs to the reseller's customers are currently processed. Please retry in few minutes." => "Einer oder mehrere SSH-Benutzer, welche dem Reseller seinen Kunden gehören, werden derzeit verarbeitet. Versuchen Sie es in wenigen Minuten nochmal.",
	'SSH permissions were deleted.' => 'Die SSH-Berechtigungen wurden gelöscht.',
	'Edit permissions' => 'Berechtigungen bearbeiten',
	'Revoke permissions' => 'Berechtigungen widerrufen',
	'Yes' => 'Ja',
	'No' => 'Nein',
	'n/a' => 'n/a',
	'Admin / Settings / SSH Permissions' => 'Admin / Einstellungen / SSH-Berechtigungen',
	'This is the list of resellers which are allowed to give SSH permissions to their customers.' => 'Dies ist die Liste der Reseller, welche berechtigt sind, Ihren Kunden SSH-Berechtigungen zu erteilen.',
	'Reseller name' => 'Resellername',
	'Can edit authentication options' => 'Kann Authentifizierungsoptionen bearbeiten',
	'Restricted shell' => 'Eingeschränkte Shell',
	'Status' => 'Status',
	'Actions' => 'Aktionen',
	'Processing...' => 'Verarbeitung...',
	'Add / Edit SSH Permissions' => 'SSH-Berechtigungen hinzufügen / bearbeiten',
	'Enter a reseller name' => 'Tragen Sie einen Resellernamen ein',
	'See man authorized_keys for further details about authentication options.' => 'Siehe man authorized_keys für weitere Details zu den Authentifizierungsoptionen.',
	'Does the shell access have to be provided in restricted environment (recommended)?' => 'Soll der Shellzugriff in einer geschützten Umgebung bereitgestellt werden (empfohlen)?',
	'Save' => 'Speichern',
	'Cancel' => 'Abbrechen',
	'Unknown reseller. Please enter a valid reseller name.' => 'Unbekannter Reseller. Bitte geben Sie einen gültigen Resellernamen ein.',
	'You must enter a reseller name.' => 'Sie müssen einen Resellernamen eingeben.',
	'Are you sure you want to revoke SSH permissions for this reseller?' => 'Sind Sie sich sicher, dass Sie die SSH-Berechtigungen für diesen Reseller widerrufen möchten?',
	'Unknown action.' => 'Unbekannte Aktion.',
	'Request Timeout: The server took too long to send the data.' => 'Request Timeout: Der Server brauchte zu lange, um die Daten zu senden.',
	'An unexpected error occurred.' => 'Es ist ein unerwarteter Fehler aufgetreten.',
	"Wrong value for the 'Maximum number of SSH users' field. Please, enter a number." => "Ungültiger Wert des 'Maximale Anzahl an SSH-Benutzer' Feldes. Geben Sie bitte eine Zahl ein.",
	'One or many SSH users which belongs to the customer are currently processed. Please retry in few minutes.' => 'Einer oder mehrere SSH-Benutzer, welche dem Kunden gehören, werden derzeit verarbeitet. Versuchen Sie es in wenigen Minuten nochmal.',
	'Unlimited' => 'Unbegrenzt',
	'An unexpected error occurred. Please contact your administrator.' => 'Es ist ein unerwarteter Fehler aufgetreten. Bitte wenden Sie sich an Ihren Administrator.',
	'Reseller / Customers / SSH Permissions' => 'Reseller / Kunden / SSH-Berechtigungen',
	'This is the list of customers which are allowed to create SSH users to login on the system using SSH.' => 'Dies ist die Liste der Kunden, die berechtigt sind, SSH-Benutzer zu erstellen, um das System mit SSH anzumelden.',
	'Customer name' => 'Kundenname',
	'Max SSH users' => 'Max SSH-Benutzer',
	'Enter a customer name' => 'Geben Sie einen Kundennamen ein',
	'Maximum number of SSH users' => 'Maximum number of SSH users',
	'0 for unlimited' => '0 für unbegrenzt',
	'Enter a number' => 'Geben Sie eine Zahl ein',
	'Unknown customer. Please enter a valid customer name.' => 'Unbekannter Kunde. Bitte geben Sie einen gültigen Kundennamen ein.',
	'You must enter a customer name.' => 'You must enter a customer name.',
	'Are you sure you want to revoke SSH permissions for this customer?' => 'Sind Sie sich sicher, dass Sie die SSH-Berechtigungen für diesen Kunden widerrufen möchten?',
	'An unexpected error occurred. Please contact your reseller.' => 'Ein unerwarteter Fehler ist aufgetreten. Bitte kontaktieren Sie Ihren Reseller.',
	'The username field is required.' => 'Das Feld Benutzername ist erforderlich.',
	'Un-allowed username. Please use alphanumeric characters only.' => 'Unerlaubter Benutzername. Bitte verwenden Sie nur alphanumerische Buchstaben.',
	'The username is too long (Max 8 characters).' => 'Der Name des SSH-Benutzers ist zu lange (Max 8 Zeichen).',
	'This username is not available.' => 'Benutzername ist nicht verfügbar.',
	'You must enter an SSH key.' => 'Sie müssen einen SSH-Schlüssel eingeben.',
	'You must enter either a password, an SSH key or both.' => 'Sie müssen entweder ein Passwort, einen SSH-Schlüssel oder beides eingeben.',
	'Un-allowed password. Please use ASCII characters only.' => 'Unerlaubtes Passwort. Bitte verwenden Sie nur ASCII Buchstaben.',
	'Wrong password length (Min 8 characters).' => 'Falsche Passwortlänge (Min 8 Zeichen).',
	'Wrong password length (Max 32 characters).' => 'Falsche Passwortlänge (Max 32 Zeichen).',
	'Passwords do not match.' => 'Die Passwörter stimmen nicht überein.',
	'Invalid SSH key.' => 'Üngültiger SSH-Schlüssel.',
	'SSH user has been scheduled for addition.' => 'SSH Benutzer hat für die Zugabe geplant.',
	'Your SSH user limit is reached.' => 'Ihr SSH-Benutzerlimit ist erreicht.',
	'SSH user has been scheduled for update.' => 'Der SSH-Benutzer wurde zur Aktualisierung vorgemerkt.',
	'An SSH user with the same name or the same SSH key already exists.' => 'Ein SSH_Benutzer mit dem gleichen Namen oder dem gleichen SSH-Schlüssel ist bereits vorhanden.',
	'SSH user has been scheduled for deletion.' => 'Der SSH-Benutzer wurde für die Löschung vorgemerkt.',
	'Edit SSH user' => 'SSH-Benutzer bearbeiten',
	'Delete this SSH user' => 'Diesen SSH-Benutzer löschen',
	'Client / Domains / SSH Users' => 'Kunde / Domains / SSH-Benutzer',
	'Allowed authentication options: %s' => 'Erlaubte Authentifizierungsoptionen: %s',
	'This is the list of SSH users associated with your account.' => 'Liste der SSH-Benutzer, welche mit Ihrem Account verbunden sind.',
	'SSH user' => 'SSH-Benutzer',
	'Key fingerprint' => 'Schlüssel-Fingerabdruck',
	'Add / Edit SSH user' => 'SSH-Benutzer hinzufügen / bearbeiten',
	'Username' => 'Benutzername',
	'Enter an username' => 'Geben Sie einen Benutzernamen ein',
	'Password' => 'Passwort',
	'Enter a password' => 'Geben Sie ein Passwort ein',
	'Password confirmation' => 'Passwortbestätigung',
	'Confirm the password' => 'Bestätigen Sie das Passwort',
	'SSH key' => 'SSH-Schlüssel',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Unterstützte RSA-Schlüsselformate sind PKCS#1, openSSH und XML Signatur.',
	'Enter your SSH key' => 'Geben Sie Ihren SSH-Schlüssel ein',
	'Authentication options' => 'Authentifizierungsoptionen',
	'Enter authentication option(s)' => 'Authentifizierungsoption(en) eingeben',
	"You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication." => "Sie können entweder ein Kennwort, einen SSH-Schlüssel oder beides bieten. Allerdings ist es empfehlenswert, schlüsselbasierte Authentifizierungen zu bevorzugen.",
	'You can generate your rsa key pair by running the following command:' => 'Sie können Ihr RSA-Schlüsselpaar mit folgendem Befehl erzeugen:',
	'Are you sure you want to delete this SSH user?' => 'Sind Sie sicher, dass Sie diesen SSH-Benutzer löschen möchten?',
);
