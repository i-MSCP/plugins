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
 * @translator undisclosed (theemstra) <undisclosed>
 */
return array(
	'Invalid default authentication options: %s' => 'Foute standaardauthenticatie-opties: %s',
	'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.' => 'Alle authenticatieopties, ingesteld in de default_ssh_auth_options-parameter moeten ook toegestaan zijn in de allowed_ssh_auth_options-parameter.',
	'allowed_ssh_auth_options parameter must be an array.' => 'De allowed_ssh_auth_options-parameter moet een array zijn.',
	'default_ssh_auth_options parameter must be a string.' => 'De default_ssh_auth_options-parameter moet een tekenreeks (string) zijn.',
	'SSH permissions' => 'SSH-rechten',
	'SSH users' => 'SSH-gebruikers',
	'This is the list of customers which are allowed to create SSH users to login on the system using SSH.' => 'Dit is de lijst van klanten, welke toestemming hebben om SSH-gebruikers te maken die met SSH kunnen inloggen op het systeem.',
	'Customer name' => 'Klantnaam',
	'Max SSH users' => 'Maximaal aantal SSH-gebruikers',
	'Authentication options' => 'Authenticatie-options',
	'Restricted shell' => 'Beperkte Shell',
	'Status' => 'Status',
	'Actions' => 'Acties',
	'Processing...' => 'Verwerkende...',
	'Add / Edit SSH Permissions' => 'Voeg SSH-rechten toe / Pas deze aan',
	'Maximum number of SSH users' => 'Maximaal aantal SSH-gebruikers',
	'0 for unlimited' => '0 voor onbeperkt',
	'Enter a number' => 'Voer een getal in',
	'Enter a customer name' => 'Voer een klantnaam in',
	'Can edit authentication options' => 'Kan authenticatie-opties aanpassen',
	'See man authorized_keys for further details about authentication options.' => 'Bekijk de handleiding (man) voor authorized_keys voor meer informatie over authenticatie-opties.',
	'Does the shell access have to be provided in restricted environment (recommended)?' => 'Moet de shelltoegang aangeboden worden in een beperkte omgeving? (aanbevolen)',
	'Unknown customer. Please enter a valid customer name.' => 'Onbekende klant. Vul een geldige klantnaam in.',
	'Are you sure you want to revoke SSH permissions for this customer?' => 'Weet u zeker dat u de SSH-rechten voor deze klant wilt intrekken?',
	'Unknown action.' => 'Onbekende actie.',
	'Request Timeout: The server took too long to send the data.' => 'Verzoek verlopen: De server deed er te lang over om de data te versturen.',
	'An unexpected error occurred.' => 'Er is een onbekende fout opgetreden.',
	'Save' => 'Opslaan',
	'Cancel' => 'Annuleren',
	'Admin / Settings / SSH Permissions' => 'Beheerder / Instellingen / SSH-instellingen',
	'An unexpected error occurred: %s' => 'Er is een onbekende fout opgetreden: %s',
	'SSH permissions not found.' => 'SSH-rechten niet gevonden.',
	'Bad request.' => 'Fout verzoek.',
	'All fields are required.' => 'Alle velden dienen te worden ingevuld.',
	"Wrong value for the 'Maximum number of SSH users' field. Please, enter a number." => "Incorrecte waarde voor het 'Maximaal aantal SSH-gebruikers'-veld. Vul een correct getal in.",
	'SSH permissions were added.' => 'SSH-rechten zijn toegevoegd.',
	'SSH permissions were scheduled for update.' => 'SSH-rechten zijn ingepland om bijgewerkt te worden.',
	'SSH permissions were scheduled for deletion.' => 'SSH-rechten zijn ingepland om verwijderd te worden.',
	'Edit permissions' => 'Wijzig rechten',
	'Revoke permissions' => 'Trek rechten in',
	'This is the list of SSH users associated with your account.' => 'Dit is de lijst met SSH-gebruikers gekoppeld aan uw account.',
	"You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication." => "U kunt een wachtwoord, een SSH-key of beide aanleveren, maar het is aanbevolen om aan te melden met SSH-keys.",
	'You can generate your rsa key pair by running the following command:' => 'U kunt een RSA-sleutel genereren door het volgende commando te geven:',
	'Key fingerprint' => 'Vingerafdruk sleutel',
	'SSH user' => 'SSH-gebruiker',
	'Username' => 'Gebruikersnaam',
	'Enter an username' => 'Vul een gebruikersnaam in',
	'Password' => 'Wachtwoord',
	'Enter a password' => 'Vul een wachtwoord in',
	'Password confirmation' => 'Wachtwoordbevestiging',
	'Confirm the password' => 'Bevestig het wachtwoord',
	'SSH key' => 'SSH-key',
	'Enter your SSH key' => 'Vul uw SSH-key in',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Ondersteunde RSA-keyformaten zijn PKCS#1, openSSH en een XML-handtekening.',
	'Are you sure you want to delete this SSH user?' => 'Weet u zeker dat u deze SSH-gebruiker wilt aanpassen?',
	'Client / Profile / SSH Users' => 'Klant / Profiel / SSH-gebruikers',
	'SSH user not found.' => 'SSH-key niet gevonden.',
	'Un-allowed username. Please use alphanumeric characters only.' => 'Gebruikersnaam niet toegestaan. Gebruik enkel alfanumerieke tekens.',
	'The username is too long (Max 8 characters).' => 'De gebruikersnaam is te lang (Maximaal 8 tekens).',
	'This username is not available.' => 'Deze gebruikernaam is niet (meer) beschikbaar.',
	'An SSH user with the same name or the same SSH key already exists.' => 'Een SSH gebruiker met dezelfde naam of dezelfde dezelfde SSH-sleutel al bestaat.',
	'You must enter an SSH key.' => 'U moet een SSH-key ingeven.',
	'You must enter either a password, an SSH key or both.' => 'U dient een wachtwoord, een SSH-key of beide in te geven..',
	'Un-allowed password. Please use alphanumeric characters only.' => 'Wachtwoord niet toegestaan. Kies uit alfanumerieke tekens.',
	'Wrong password length (Max 32 characters).' => 'Verkeerde wachtwoordlengte (Maximaal 32 tekens).',
	'Wrong password length (Min 6 characters).' => 'Verkeerde wachtwoordlengte (Minimaal 6 tekens).',
	'Passwords do not match.' => 'Wachtwoorden komen niet overeen.',
	'Invalid SSH key.' => 'Incorrecte SSH-key.',
	'SSH user has been scheduled for addition.' => 'SSH-gebruiker ingepland om toegevoegd te worden.',
	'Your SSH user limit is reached.' => 'Uw SSH-gebruikerslimiet is bereikt.',
	'SSH user has been scheduled for update.' => 'SSH-gebruiker ingepland om bijgewerkt te worden.',
	'SSH user has been scheduled for deletion.' => 'SSH-gebruiker ingepland om verwijderd te worden.',
	'An unexpected error occurred. Please contact your reseller.' => 'Er is een onverwachte fout opgetreden. Neem contact op met uw wederverkoper.',
	'Add / Edit SSH user' => 'Voeg SSH-gebruiker toe / Pas deze aan',
	'Delete this SSH user' => 'Verwijder deze SSH-gebruiker',
	'Allowed authentication options: %s' => 'Authenticatie-opties toegestaan: %s',
	'Unlimited' => 'Ongelimiteerd',
	'Yes' => 'Ja',
	'No' => 'Nee'
);
