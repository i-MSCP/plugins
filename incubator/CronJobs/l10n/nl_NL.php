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
 * @translator Djawi de Boer (Novy) <djawi@djawi.nl>
 * @translator Thom Heemstra (theemstra) <thom@heemstra.us>
 */

return array(
	'Cron permissions' => 'Cron-rechten',
	'Cron jobs' => 'Cron-jobs',
	'Cron job has been scheduled for addition.' => 'Toevoeging van cron-job is ingepland.',
	'Cron job has been scheduled for update.' => 'Bijwerking van cron-job is ingepland.',
	'An unexpected error occurred: %s' => 'Er is een onverwachte fout opgetreden: %s',
	'Bad request.' => 'Fout verzoek.',
	'Cron job has been scheduled for deletion.' => 'Verwijdering van cron-job is ingepland.',
	'Edit cron job' => 'Cron-job wijzigen',
	'Delete cron job' => 'Cron-job verwijderen',
	'Url' => 'URL',
	'Shell' => 'Shell',
	'n/a' => 'N/B',
	'Admin / System tools / Cron Jobs' => 'Beheer / Systeem / Cron-jobs',
	'Interface from which you can add your cron jobs. This interface is for administrators only. Customers have their own interface which is more restricted.' => 'De interface waar u uw cron-jobs kunt toevoegen. Deze interface is alleen voor beheerders. Klanten hebben hun eigen interface welke beperkter is.',
	'Configuring cron jobs requires distinct knowledge of the crontab syntax on Unix based systems. More information about this topic can be obtained on the following webpage:' => 'Het configureren van cron-jobs vereist een goede kennis van de crontab-syntax op op Unix-gebaseerde systemen. Meer informatie over dit onderwerp kan op de volgende webpagina worden gevonden:',
	'Newbie: Intro to cron' => 'Voor de nieuweling: Introductie over cron',
	'Id' => 'ID',
	'Type' => 'Type',
	'Time/Date' => 'Tijd/Datum',
	'User' => 'Gebruiker',
	'Command' => 'Commando',
	'Status' => 'Status',
	'Actions' => 'Actie',
	'Loading data...' => 'Data aan het laden...',
	'Add cron job' => 'Cron-job toevoegen',
	'Email' => 'E-mail',
	'Email to which cron notifications must be sent if any. Leave blank to disable notifications.' => 'E-mailadres waar - indien aanwezig - cron-notificaties naar verzonden dienen te worden. Leeg laten om notificaties uit te schakelen.',
	'Minute' => 'Minuut',
	'Minute at which the cron job must be executed.' => 'Minuut waarop de cron-job uitgevoerd dient te worden.',
	'Hour' => 'Uur',
	'Hour at which the cron job must be executed.' => 'Uur waarop de cron-job uitgevoerd dient te worden.',
	'Day of month' => 'Dag van de maand',
	'Day of the month at which the cron job must be executed.' => 'Dag van de maand waarop de cron-job uitgevoerd dient te worden.',
	'Month' => 'Maand',
	'Month at which the cron job must be executed.' => 'Maand waarop de cron-job uitgevoerd dient te worden.',
	'Day of week' => 'Dag van de week',
	'Day of the week at which the cron job must be executed.' => 'Dag van de maand waarop de cron-job uitgevoerd dient te worden.',
	'User under which the cron job must be executed.' => 'Gebruiker voor wie de cron-job dient te worden uitgevoerd.',
	'Command to execute...' => 'Uit te voeren commando...',
	'Command type' => 'Commandotype',
	'Url commands are run via GNU Wget while shell commands are run via shell command interpreter (eg. Dash, Bash...).' => 'URL-commando\'s worden uitgevoerd via Wget, terwijl commando\'s in de shell worden uitgevoerd via de opdrachtinterpreter (zoals Dash, Bash...).',
	'You can learn more about the syntax by reading:' => 'U kunt meer over de syntax leren door het lezen van:',
	'When using a shortcut in the minute time field, all other time/date fields are ignored.' => 'Wanneer u een snelkoppeling in de minuuttijdveld gebruikt worden alle andere tijd-/datumvelden genegeerd.',
	'The available shortcuts are: @reboot, @yearly, @annually, @monthly, @weekly, @daily, @midnight and @hourly' => 'De beschikbare snelkoppelingen zijn: @reboot, @yearly, @annually, @monthly, @weekly, @daily, @midnight en @hourly',
	'Minimum time interval between each cron job execution: %s' => 'Minimale tijdsinterval tussen elke uitvoering van een cron-job: %s',
	'Add / Edit Cron job' => 'Cron-job toevoegen/wijzigen',
	'Save' => 'Bewaren',
	'Cancel' => 'Annuleren',
	'Are you sure you want to delete this cron job?' => 'Weet u zeker dat u deze cron-job wilt verwijderen?',
	'Unknown action.' => 'Onbekende handeling.',
	'Request Timeout: The server took too long to send the data.' => 'Geen antwoord: de server deed er te lang over om data te versturen.',
	'An unexpected error occurred.' => 'Er heeft zich een onverwachte fout voorgedaan.',
	'An unexpected error occurred. Please contact your reseller.' => 'Er heeft zich een onverwachte fout voorgedaan. Gelieve contact op te nemen met uw wederverkoper.',
	'Client / Web Tools / Cron Jobs' => 'Klant / Webtools / Cron-jobs',
	'This is the interface from which you can add your cron jobs.' => 'Dit is de interface waar u cron-jobs kunt toevoegen.',
	"Wrong value for the 'Cron jobs frequency' field. Please, enter a number." => "Verkeerde waarde bij het veld 'Frequentie cron-jobs'. Gelieve een nummer in te voeren.",
	'The cron jobs frequency is lower than your own limit which is currently set to %s minute.' => array(
		"The cron jobs frequency is lower than your own limit which is currently set to %s minute.",
		"The cron jobs frequency is lower than your own limit which is currently set to %s minutes." // Plural form
	),
	'Cron permissions were added.' => 'Cron-rechten zijn toegevoegd.',
	'Cron permissions were updated.' => 'Cron-rechten zijn bijgewerkt.',
	'Nothing has been changed.' => 'Er is niets veranderd.',
	"One or many cron jobs which belongs to the reseller's customers are currently processed. Please retry in few minutes." => "Eén of meerdere cron-jobs welke tot de klanten van de wederverkoper behoren worden momenteel bewerkt. Gelieve het over enkele minuten opnieuw te proberen.",
	'CronJobs: Unable to update cron permissions for %s: %s' => 'CronJobs: Kan de cron-rechten niet bijwerken voor: %s: %s',
	'Cron permissions were revoked.' => 'Cron-rechten zijn ingetrokken.',
	'Edit permissions' => 'Rechten bewerken',
	'Revoke permissions' => 'Rechten intrekken',
	'%d minute' => array(
		'%d minuut',
		'%d minuten' // Plural form
	),
	'Admin / Settings / Cron Permissions' => 'Beheerder / Instellingen / Cron-rechten',
	'List of resellers which are allowed to give cron permissions to their customers.' => 'Lijst van wederverkopers wie toestemming hebben om cron-rechten aan hun klanten te geven.', // Correction for English translation: which should be whom, which is only used for items, not persons.
	'Reseller name' => 'Naam van wederverkoper',
	'Cron jobs type' => 'Soorten cron-jobs',
	'Cron jobs frequency' => 'Frequentie van cron-jobs',
	'Add / Edit cron permissions' => 'Cron-rechten toevoegen / bewerken',
	'Enter a reseller name' => 'Naam van wederverkoper invoeren',
	'Type of allowed cron jobs. Note that the Url cron jobs are always available, whatever the selected type.' => 'Soorten toegestane cron-jobs. Merk op dat de URL-cron-jobs altijd beschikbaar zijn, welke soort dan ook geselecteerd is.',
	'Jailed' => 'Ingesloten',
	'Full' => 'Niet ingesloten',
	'Minimum time interval between each cron job execution.' => 'Minimale tijdsinterval tussen elke uitvoering van een cron-job.',
	'In minutes' => 'In minuten',
	'Unknown reseller. Please enter a valid reseller name.' => 'Onbekende wederverkoper. Gelieve een geldige naam in te voeren.',
	'Please enter a reseller name.' => 'Gelieve een naam van een wederverkoper in te voeren.',
	'Are you sure you want to revoke the cron permissions for this reseller?' => 'Weet u zeker dat u de cron-rechten voor deze wederverkoper wilt intrekken?',
	'List of customers which are allowed to add cron jobs.' => 'Lijst van klanten wie bevoegd zijn om cron-jobs toe te voegen.',
	'Max. cron jobs' => 'Max. cron-jobs',
	'Customer name' => 'Naam van klant',
	'Enter a customer name' => 'Naam van klant invoeren',
	'0 for unlimited' => '0 voor ongelimiteerd',
	'Unknown customer. Please enter a valid customer name.' => 'Onbekende klant. Gelieve een geldige naam in te voeren',
	'Please enter a customer name.' => 'Gelieve een naam van een klant in te voeren',
	'Are you sure you want to revoke the cron permissions for this customer?' => 'Weet u zeker dat u de cron-rechten voor deze klant wilt intrekken?',
	'Invalid cron job type: %s' => 'Ongeldig type van cron-job: %s.',
	'Invalid notification email.' => 'Ongeldig notificatie-e-mailadres.',
	"Value for the '%s' field cannot be empty." => "Waarde voor het veld '%s' mag niet leeg zijn.",
	"Invalid value for the '%s' field." => "Ongeldige waarde voor het veld '%s'.",
	'Unable to parse time entry.' => 'Kan tijdinvoer niet verwerken.',
	"You're exceeding the allowed limit of %s minutes, which is the minimum interval time between each cron job execution." => "U overschrijdt de toegestane %s minuten, wat de minimale tijdsinterval tussen elke uitvoering van een cron-job is.",
	'User must be a valid UNIX user.' => 'Gebruiker dient een geldige UNIX-gebruiker te zijn.',
	'Url must not contain any username/password for security reasons.' => 'URL mag wegens veiligheidsoverwegingen geen gebruikersnamen en/of wachtwoorden bevatten.',
	'Command must be a valid HTTP URL.' => 'Commando dient een geldige HTTP-URL te zijn.',
);
