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
 * @translator Laurent Declercq (nuxwin) <l.declercq@nuxwin.com>
 */

return array(
	'Invalid default authentication options: %s' => "Options d'authentification par défaut invalides: %s",
	'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.' => "Toute les options d'authentification définies dans le paramètre default_ssh_auth_options doivent également être définies dans le paramètre allowed_ssh_auth_options.",
	'The allowed_ssh_auth_options parameter must be an array.' => 'Le paramètre allowed_ssh_auth_options doit être un tableau.',
	'The default_ssh_auth_options parameter must be a string.' => 'Le paramètre default_ssh_auth_options doit être une chaîne.',
	'SSH permissions' => 'Permissions SSH',
	'SSH users' => 'Comptes SSH',
	'An unexpected error occurred: %s' => "Une erreur inattendue s'est produite: %s",
	'Bad request.' => 'Mauvaise requête.',
	'All fields are required.' => 'Tous les champs sont requis.',
	'SSH permissions were added.' => 'Les permissions SSH ont été ajoutées',
	'SSH permissions were updated.' => 'Les permissions SSH ont été mises à jour.',
	'Nothing has been changed.' => 'Rien a été modifié.',
	"One or many SSH users which belongs to the reseller's customers are currently processed. Please retry in few minutes." => "Un ou plusieurs utilisateurs SSH appartenant aux clients du revendeur sont actuellement en cours de traitement. Veuillez ré-essayer dans quelques minnutes.",
	'SSH permissions were deleted.' => 'Les permissions SSH ont été supprimées.',
	'Edit permissions' => 'Éditer les permissions',
	'Revoke permissions' => 'Révoquer les permissions',
	'Yes' => 'Oui',
	'No' => 'Non',
	'n/a' => 'n/a',
	'Admin / Settings / SSH Permissions' => 'Administrateur / Paramètres / Permissions SSH',
	'This is the list of resellers which are allowed to give SSH permissions to their customers.' => 'Liste des revendeurs qui sont autorisés à donner des permissions SSH à leur clients',
	'Reseller name' => 'Nom du revendeur',
	'Can edit authentication options' => "Peut éditer les options d'authentification",
	'Restricted shell' => 'Shell restreint',
	'Status' => 'Statut',
	'Actions' => 'Actions',
	'Processing...' => 'Chargement en cours...',
	'Add / Edit SSH Permissions' => 'Ajout / Édition des permissions SSH',
	'Enter a reseller name' => "Entrez le nom d'un revendeur",
	'See man authorized_keys for further details about authentication options.' => "Voir man authorized_keys pour plus de détails à propos des options d'authentification.",
	'Does the shell access have to be provided in restricted environment (recommended)?' => "Est-ce que l'accès au shell doit être fournit dans un environnement restreint (recommandé)?",
	'Save' => 'Sauvegarder',
	'Cancel' => 'Annuler',
	'Unknown reseller. Please enter a valid reseller name.' => "Revendeur inconnu. Veuillez entrer le nom d'un revendeur valide.",
	'You must enter a reseller name.' => "Vous devez entrer le nom d'un revendeur.",
	'Are you sure you want to revoke SSH permissions for this reseller?' => 'Êtes-vous certain de vouloir révoquer les permissions SSH de ce revendeur?',
	'Unknown action.' => 'Action inconnue.',
	'Request Timeout: The server took too long to send the data.' => 'Temp de réponse dépassé: Le serveur prend trop de temps pour envoyer les données.',
	'An unexpected error occurred.' => "Une erreur inattendue s'est produite.",
	"Wrong value for the 'Maximum number of SSH users' field. Please, enter a number." => "Valeur incorrecte pour le champ 'Nombre maximum de comptes SSH'. Veuillez entrer un nombre.",
	'One or many SSH users which belongs to the customer are currently processed. Please retry in few minutes.' => 'Un ou plusieurs utilisateurs SSH appartenant au client sont actuellement en cours de traitement. Veuillez ré-essayer dans quelques minutes.',
	'Unlimited' => 'Illimité',
	'An unexpected error occurred. Please contact your administrator.' => "Une erreur inattendue s'est produite. Veuillez contacter votre administrateur.",
	'Reseller / Customers / SSH Permissions' => 'Revendeur / Clients / Permissions SSH',
	'This is the list of customers which are allowed to create SSH users to login on the system using SSH.' => 'Liste des clients qui sont autorisés à créer des comptes SSH pour se connecter au système.',
	'Customer name' => 'Nom du client',
	'Max SSH users' => "Nombre maximum de comptes SSH",
	'Enter a customer name' => "Entrez le nom d'un client",
	'Maximum number of SSH users' => "Nombre maximum de comptes SSH",
	'0 for unlimited' => '0 pour illimité',
	'Enter a number' => 'Entrez un nombre',
	'Unknown customer. Please enter a valid customer name.' => 'Client inconnu. Veuillez entrer un nom de client valide.',
	'You must enter a customer name.' => "Vous devez entrer un nom d'utilisateur.",
	'Are you sure you want to revoke SSH permissions for this customer?' => 'Êtes-vous certain de vouloir révoquer les permissions SSH de ce client?',
	'An unexpected error occurred. Please contact your reseller.' => "Une erreur inattendue s'est produite. Veuillez contacter votre revendeur.",
	'The username field is required.' => "Le nom d'utilisateur est requis.",
	'Un-allowed username. Please use alphanumeric characters only.' => "Nom d'utilisateur non-autorisé. Veuillez n'utiliser que des caractères alphanumériques.",
	'The username is too long (Max 8 characters).' => "Le nom d'utilisateur est trop long (8 charactères maximum).",
	'This username is not available.' => "Ce nom d'utilisateur n'est pas disponible.",
	'You must enter an SSH key.' => 'Vous devez entrer une clé SSH.',
	'You must enter either a password, an SSH key or both.' => 'Vous devez entrer un mot de passe, une clé SSH ou les deux.',
	'Un-allowed password. Please use ASCII characters only.' => "Mot de passe non-autorisé. Veuillez n'utiliser que des caractères ASCII.",
	'Wrong password length (Min 8 characters).' => 'Mauvaise longueur de mot de passe (8 caractères minimum).',
	'Wrong password length (Max 32 characters).' => 'Mauvaise longueur de mot de passe (32 caractères maximum).',
	'Passwords do not match.' => 'Les mots de passe ne correspondent pas.',
	'Invalid SSH key.' => 'Invalid SSH key.',
	'SSH user has been scheduled for addition.' => 'Le compte SSH va être créé.',
	'Your SSH user limit is reached.' => 'Votre limite de comptes SSH est atteinte.',
	'SSH user has been scheduled for update.' => 'Le compte SSH va être mis à jour.',
	'An SSH user with the same name or the same SSH key already exists.' => 'Un utilisateur SSH ayant le même nom ou la même clé SSH existe déjà.',
	'SSH user has been scheduled for deletion.' => 'Le compte SSH va être supprimé.',
	'Edit SSH user' => "Éditer l'utilisateur SSH",
	'Delete this SSH user' => 'Supprimer ce compte SSH',
	'Client / Domains / SSH Users' => 'Client / Domaines / Utilisateurs SSH',
	'Allowed authentication options: %s' => "Options d'authentifcation autorisées: %s",
	'This is the list of SSH users associated with your account.' => 'Liste des comptes SSH associés à votre compte.',
	'SSH user' => 'SSH user',
	'Key fingerprint' => 'Empreinte de clé',
	'Add / Edit SSH user' => "Ajout / Édition d'un compte SSH",
	'Username' => "Nom d'utilisateur",
	'Enter an username' => "Entrez un nom d'utilisateur ",
	'Password' => 'Mot de passe',
	'Enter a password' => 'Entrez un mot de passe',
	'Password confirmation' => 'Confirmation du mot de passe',
	'Confirm the password' => 'Confirmez le mot de passe',
	'SSH key' => 'Clé SSH',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Les formats de clés SSH supportés sont: PKCS#1, openSSH et XML Signature.',
	'Enter your SSH key' => 'Entrez votre clé SSH',
	'Authentication options' => "Options d'authentification",
	'Enter authentication option(s)' => "Entrez les options d'authentification",
	"You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication." => "Vous pouvez fournir un mot de passe, une clé SSH ou les deux. Toutefois, il est recommandé d'utiliser une authentification par clé.",
	'You can generate your rsa key pair by running the following command:' => 'Vous pouvez générer votre pair de clés RSA en exécutant la commande suivante:',
	'Are you sure you want to delete this SSH user?' => 'Êtes-vous certain de vouloir supprimer ce compte SSH?',
	'Rebuild of jails has been scheduled. Depending of the number of jails, this could take some time...' => 'La reconstruction des prisons a été planifiée. Selon le nombre de prisons, cela pourrait prendre du temps...',
	'No jail to rebuild. Operation cancelled.' => "Aucune prison n'a été trouvée. Opération annulée.",
	'Rebuild Jails' => 'Reconstruire les prisons',
	'Unable to schedule rebuild of jails: %s' => 'Impossible de plannifier la reconstruction des prisons: %s',
	'Are you sure you want to schedule rebuild of all jails?' => 'Êtes-vous certain de vouloir plannifier la reconstruction de toutes les prisons?'
);
