<?php
/**
 * i-MSCP CronJobs plugin
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
 * @translator Ninos Ego (Ninos) <me@ninosego.de>
 */

return array(
	'Cron job permissions' => 'Cron-Job-Berechtigungen',
	'Cron jobs' => 'Cron-Jobs',
	'Cron job has been scheduled for addition.' => 'Cron-Job wurde zum Hinzufügen angesetzt.',
	'Your cron jobs limit is reached.' => 'Ihr cron-Job-Limit ist erreicht.',
	'Cron job has been scheduled for update.' => 'Cron-Job wurde zum Aktualisieren angesetzt.',
	'An unexpected error occurred: %s' => 'Es ist ein unerwarteter Fehler aufgetreten: %s',
	'Bad request.' => 'Bad request.',
	'Cron job has been scheduled for deletion.' => 'Cron-Job wurde zum Löschen angesetzt.',
	'Edit cron job' => 'Cron-Job bearbeiten',
	'Delete cron job' => 'Cron-Job löschen',
	'Url' => 'Url',
	'Shell' => 'Shell',
	'n/a' => 'n/a',
	'Admin / System tools / Cron Jobs' => 'Administrator / Systemwerkzeuge / Cron-Jobs',
	'Interface from which you can add your cron jobs. This interface is for administrators only. Customers have their own interface which is more restricted.' => 'Interface, aus welchem Sie Ihre cron-Jobs hinzufügen können. Dieses Interface ist nur für Administratoren. Kunden haben Ihr eigenes Interface, welches etwas eingeschränkter ist.',
	'Configuring cron jobs requires distinct knowledge of the crontab syntax on Unix based systems. More information about this topic can be obtained on the following webpage:' => 'Das Konfigurieren der cron-Jobs benötigt ausgeprägte Kenntnisse über die Crontab-Syntax auf Unix-basierten Systemen. Weitere Informationen zu diesem Thema sind auf der folgenden Webseite erhältlich:',
	'Newbie: Intro to cron' => 'Neuling: Einführung in die Cron',
	'Type' => 'Typ',
	'Time/Date' => 'Zeit/Datum',
	'User' => 'Benutzer',
	'Command' => 'Befehl',
	'Status' => 'Status',
	'Actions' => 'Aktionen',
	'Loading data...' => 'Lade Daten...',
	'Add cron job' => 'Cron-Job hinzufügen',
	'Email' => 'E-Mail',
	'Email to which cron notifications must be sent if any. Leave blank to disable notifications.' => 'E-Mail zu welcher die Cron-Benachrichtigungen verschickt werden sollen, sofern vorhanden. Leer lassen, um Benachrichtigungen abzuschalten.',
	'Minute' => 'Minute',
	'Minute at which the cron job must be executed.' => 'Minute, an welcher der cron-Job ausgeführt werden soll.',
	'Hour' => 'Stunde',
	'Hour at which the cron job must be executed.' => 'Stunde, an welcher der cron-Job ausgeführt werden soll.',
	'Day of month' => 'Tag des Monats',
	'Day of the month at which the cron job must be executed.' => 'Tag des Monats, an welchem der cron-Job ausgeführt werden soll.',
	'Month' => 'Monat',
	'Month at which the cron job must be executed.' => 'Monat, an welchem der cron-Job ausgeführt werden soll.',
	'Day of week' => 'Wochentag',
	'Day of the week at which the cron job must be executed.' => 'Wochentag, an welchem der cron-Job ausgeführt werden soll.',
	'User under which the cron job must be executed.' => 'Benutzer, unter welchem der cron-Job ausgeführt werden soll.',
	'Command to execute...' => 'Befehl zum ausführen...',
	'Command type' => 'Typ des Befehls',
	'Url commands are run via GNU Wget while shell commands are run via shell command interpreter (eg. Dash, Bash...).' => 'Url-Befehle werden über GNU-Wget ausgeführt, während Shell-Befehle über Shell-Befehlsinterpreter ausgeführt werden (z.B. Dash, Bash...).',
	'You can learn more about the syntax by reading:' => 'Durch Lesen von Folgendem können Sie mehr über die Syntax lernen:',
	'When using a shortcut in the minute time field, all other time/date fields are ignored.' => 'Beim Verwenden eines Kürzels im minütigen Zeitfeld, werden alle anderen Zeit/Datum-Felder igoniert.',
	'The available shortcuts are: @reboot, @yearly, @annually, @monthly, @weekly, @daily, @midnight and @hourly' => 'Die verfügbaren Kürzel sind: @reboot, @yearly, @annually, @monthly, @weekly, @daily, @midnight and @hourly',
	'Minimum time interval between each cron job execution: %s' => 'Minimaler Zeitinterval zwischen der Ausführung jeden cron-Jobs: %s',
	'Add / Edit Cron job' => 'Cron-Job hinzufügen / bearbeiten',
	'Save' => 'Speichern',
	'Cancel' => 'Abbrechen',
	'Are you sure you want to delete this cron job?' => 'Sind Sie sich sicher, diesen cron-Job löschen zu wollen?',
	'Unknown action.' => 'Unbekannte Aktion.',
	'Request Timeout: The server took too long to send the data.' => 'Request Timeout: Der Server brauchte zu lange, um die Daten zu senden..',
	'An unexpected error occurred.' => 'Es ist ein unerwarteter Fehler aufgetreten.',
	'An unexpected error occurred. Please contact your reseller.' => 'Ein unerwarteter Fehler ist aufgetreten. Bitte kontaktieren Sie Ihren Reseller.',
	'Client / Web Tools / Cron Jobs' => 'Kunde / Webwerkzeuge / Cron-Jobs',
	'This is the interface from which you can add your cron jobs.' => 'Das ist das Interface, von welchem aus Sie Ihre cron-Jobs hinzufügen können.',
	"Wrong value for the 'Max. cron jobs' field. Please, enter a number." => "Falscher Wert für das Feld 'Max. cron-Jobs'. Tragen Sie bitte eine Nummer ein.",
	"Wrong value for the 'Cron jobs frequency' field. Please, enter a number." => "Falscher Wert für das Feld 'Cron-Jobs-Frequenz'. Tragen Sie bitte eine Nummer ein.",
	'The cron jobs frequency is lower than your own limit which is currently set to %s minute.' => array(
		"Die cron-Jobs-Frequenz ist niedriger als Ihr eigener Limit, welcher derzeit auf %s Minute gesetzt ist.",
		"Die cron-Jobs-Frequenz ist niedriger als Ihr eigener Limit, welcher derzeit auf %s Minuten gesetzt ist." // Plural form
	),
	'Cron job permissions were added.' => 'Cron-Job-Berechtigungen wurden hinzugefügt.',
	'Cron job permissions were updated.' => 'Cron-Job-Berechtigungen wurden aktualisiert.',
	'Nothing has been changed.' => 'Es wurde nichts geändert.',
	"One or many cron jobs which belongs to the reseller's customers are currently processed. Please retry in few minutes." => "Einer oder mehrere cron-Jobs, welche dem Reseller seinen Kunden zugeordnet sind, werden derzeit verarbeitet. Versuchen Sie es in ein paar Minuten nochmal.",
	'Cron job permissions were revoked.' => 'Cron-Job-Berechtigungen wurden zurückgezogen.',
	'Edit permissions' => 'Berechtigungen bearbeiten',
	'Revoke permissions' => 'Berechtigungen zurückziehen',
	'%d minute' => array(
		'%d Minute',
		'%d Minuten' // Plural form
	),
	'Unlimited' => 'Unbegrenzt',
	'Admin / Settings / Cron Job Permissions' => 'Administrator / Einstellungen / Cron-Job-Berechtigungen',
	'List of resellers which are allowed to give cron job permissions to their customers.' => 'Liste der Reseller, welche befugt sind, Ihren Kunden cron-Job-Berechtigungen zu geben.',
	'Reseller name' => 'Resellername',
	'Cron jobs type' => 'Cron-Jobs-Typen',
	'Cron jobs frequency' => 'Cron-Jobs-Frequenz',
	'Add / Edit cron job permissions' => 'Cron-Job-Berechtigungen hinzufügen / bearbeiten',
	'Enter a reseller name' => 'Tragen Sie einen Resellernamen ein',
	'Type of allowed cron jobs. Note that the Url cron jobs are always available, whatever the selected type.' => 'Typen der erlaubten cron-Jobs. Beachten Sie, dass die Url-Cron-Jobs immer verfügbar sind, unabhängig vom ausgewählten Typ.',
	'Jailed' => 'Eingesperrt',
	'Full' => 'Voll',
	'Minimum time interval between each cron job execution.' => 'Minimaler Intervall zwischen jeder cron-Job Ausführung.',
	'In minutes' => 'In Minuten',
	'Unknown reseller. Please enter a valid reseller name.' => 'Unbekannter Reseller. Tragen Sie bitte einen gültigen Resellernamen ein.',
	'Please enter a reseller name.' => 'Tragen Sie bitte einen Resellernamen ein.',
	'Are you sure you want to revoke the cron job permissions for this reseller?' => 'Sind Sie sich sicher, die cron-Job-Berechtigungen für diesen Reseller zurückziehen zu wollen?',
	'List of customers which are allowed to add cron jobs.' => 'Liste der Kunden, welchen erlaubt ist, cron-Jobs anzulegen.',
	'Max. cron jobs' => 'Max. cron-Jobs',
	'Customer name' => 'Kundenname',
	'Enter a customer name' => 'Tragen Sie einen Kundennamen ein',
	'0 for unlimited' => '0 für unbegrenzt',
	'Unknown customer. Please enter a valid customer name.' => 'Unbekannter Kunde. Tragen Sie bitte einen gültigen Kundennamen ein.',
	'Please enter a customer name.' => 'Tragen Sie bitte einen Kundennamen ein.',
	'Are you sure you want to revoke the cron job permissions for this customer?' => 'Sind Sie sich sicher, die cron-Job-Berechtigungen für diesen Kunden zurückziehen zu wollen?',
	'Invalid cron job type: %s' => 'Ungültiger cron-Job Typ: %s.',
	'Invalid notification email.' => 'Ungültige Benachrichtigungsmail.',
	"Value for the '%s' field cannot be empty." => "Wert für das Feld '%s' kann nicht leer sein.",
	"Invalid value for the '%s' field." => "Ungültiger Wert für das Feld '%s'.",
	'Unable to parse time entry.' => 'Der Eintrag für die Zeit kann nicht geparst werden.',
	"You're exceeding the allowed limit of %s minutes, which is the minimum interval time between each cron job execution." => "Sie überschreiten den erlaubten Limit von %s Minuten, welcher der minimale Zeitinterval zwischen der Ausführung jeden cron-Jobs ist.",
	'User must be a valid UNIX user.' => 'Benutzer muss ein gültiger UNIX-Benutzer sein.',
	'Url must not contain any username/password for security reasons.' => 'Url darf aus Sicherheitsgründen keinen Benutzer/Passwort enthalten.',
	'Command must be a valid HTTP URL.' => 'Befehl muss eine gültige HTTP URL sein.',
);
