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
	'allowed_ssh_auth_options parameter must be an array.' => 'Der allowed_ssh_auth_options Parameter muss ein Array sein.',
	'default_ssh_auth_options parameter must be a string.' => 'Der default_ssh_auth_options Parameter muss ein String sein.',
	'SSH permissions' => 'SSH-Berechtigungen',
	'SSH users' => 'SSH-Benutzer',
	'This is the list of customers which are allowed to create SSH users to login on the system using SSH.' => 'Dies ist die Liste der Kunden, die berechtigt sind, SSH-Benutzer zu erstellen, um das System mit SSH anmelden.',
	'Customer name' => 'Kundenname',
	'Max SSH users' => 'Max SSH-Benutzer',
	'Authentication options' => 'Authentifizierungsoptionen',
	'Restricted shell' => 'Eingeschränkte Shell',
	'Status' => 'Status',
	'Actions' => 'Aktionen',
	'Processing...' => 'Verarbeitung...',
	'Add / Edit SSH Permissions' => 'SSH-Berechtigungen hinzufügen / bearbeiten',
	'Maximum number of SSH users' => 'Maximale Anzahl an SSH-Benutzer',
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
	'Bad request.' => 'Bad request.',
	'All fields are required.' => 'Alle Felder müssen ausgefüllt werden.',
	"Wrong value for the 'Maximum number of SSH users' field. Please, enter a number." => "Ungültiger Wert des 'Maximale Anzahl an SSH-Benutzer' Feldes. Gebe bitte eine Zahl ein.",
	'SSH permissions were added.' => 'Die SSH-Berechtigungen wurden hinzugefügt.',
	'SSH permissions were scheduled for update.' => 'Die SSH-Berechtigungen wurden zum Aktualisieren vorgemerkt.',
	'SSH permissions were scheduled for deletion.' => 'Die SSH-Berechtigungen wurden zum Löschen vorgemerkt.',
	'Edit permissions' => 'Berechtigungen bearbeiten',
	'Revoke permissions' => 'Berecthigungen wiederrufen',
	'This is the list of SSH users associated with your account.' => 'Liste der SSH-Benutzer, welche mit deinem Account verbunden sind.',
	"You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication." => "Sie können entweder ein Kennwort, einen SSH-Schlüssel oder beides bieten. Allerdings ist es empfehlenswert, schlüsselbasierte Authentifizierung bevorzugen.",
	'You can generate your rsa key pair by running the following command:' => 'Du kannst dein RSA-Schlüsselpaar mit folgendem Befehl erzeugen:',
	'Key fingerprint' => 'Schlüssel fingerabdruck',
	'SSH user' => 'SSH-benutzer',
	'Username' => 'Benutzername',
	'Enter an username' => 'Geben Sie einen Benutzernamen',
	'Password' => 'Passwort',
	'Enter a password' => 'Geben Sie ein Passwort',
	'Password confirmation' => 'Passwortbestätigung',
	'Confirm the password' => 'Bestätigen Sie das Passwort',
	'SSH key' => 'SSH-Schlüssel',
	'Enter your SSH key' => 'Geben Sie Ihre SSH-Schlüssel',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Unterstützte RSA-Schlüsselformate sind PKCS#1, openSSH und XML Signatur.',
	'Are you sure you want to delete this SSH user?' => 'Sind Sie sicher, Sie wollen diesen SSH-Benutzer löschen?',
	'Client / Profile / SSH Users' => 'Kunde / Profil / SSH-Benutzer',
	'SSH user not found.' => 'SSH-Benutzer nicht gefunden.',
	'Un-allowed username. Please use alphanumeric characters only.' => 'Unzulässiger Benutzernamen. Bitte benutze nur alphanumerische Zeichen.',
	'The username is too long (Max 8 characters).' => 'Der Name des SSH-Benutzer ist zu lange (Max 8 Zeichen).',
	'This username is not available.' => 'Benutzername ist nicht verfügbar.',
	'You must enter an SSH key.' => 'Sie müssen SSH-Schlüssel eingeben.',
	'You must enter either a password, an SSH key or both.' => 'Sie müssen ein Passwort Entweder, einen SSH-Schlüssel oder beides eingeben.',
	'Un-allowed password. Please use alphanumeric characters only.' => 'Unzulässiger passwort. Bitte benutze nur alphanumerische Zeichen.',
	'Wrong password length (Max 32 characters).' => 'Falsches Passwort Länge (Max 32 Zeichen).',
	'Wrong password length (Min 6 characters).' => 'Falsches Passwort Länge (Min 8 Zeichen).',
	'Passwords do not match.' => 'Passwörter stimmen nicht überein.',
	'Invalid SSH key.' => 'Fehlerhafter SSH-Schlüssel.',
	'SSH user has been scheduled for addition.' => 'Der SSH-Benutzer wurde zum Hinzufügen vorgemerkt.',
	'Your SSH user limit is reached.' => 'Dein SSH-Benutzerlimit ist erreicht.',
	'SSH user has been scheduled for update.' => 'Der SSH-Benutzer wurde zur Aktualisierung vorgemerkt.',
	'SSH user has been scheduled for deletion.' => 'Der SSH-Benutzer wurde für die Löschung vorgemerkt.',
	'An unexpected error occurred. Please contact your reseller.' => 'Ein unerwarteter Fehler ist aufgetreten. Bitte kontaktiere deinen Reseller.',
	'Add / Edit SSH user' => 'SSH-Benutzer hinzufügen / bearbeiten',
	'Delete this SSH user' => 'Diesen SSH-Schlüssel löschen',
	'Allowed authentication options: %s' => 'Erlaubte Authentifizierungsoptionen: %s',
	'Unlimited' => 'Unbegrenzt',
	'Yes' => 'Ja',
	'No' => 'Nein'
);
