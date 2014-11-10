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
 * @translator Laurent Declercq (nuxwin) <l.declercq@nuxwin.com>
 */

return array(
	'Plugin allowing to provide full or restricted shell access to your customers.' => 'Plugin permettant de fournir des accèss SSH complets ou restreints aux clients.',
	'Unable to install: %s' => "Impossible d'installer: %s",
	'Unable to update: %s' => 'Impossible de mettre à jour: %s',
	'Unable to enable: %s' => "Impossible d'activer: %s",
	'Unable to uninstall: %s' => 'Impossible de désinstaller: %s',
	'Your i-MSCP version is not compatible with this plugin. Try with a newer version.' => "Votre version d'i-MSCP n'est pas compatible avec ce plugin. Veuillez essayer avec une version plus récente.",
	'Invalid default authentication options: %s' => "Options d'authentification par défaut invalides: %s",
	'Any authentication options defined in the default_ssh_auth_options parameter must be also defined in the allowed_ssh_auth_options parameter.' => "Toute option d'authentification définie dans le paramètre default_ssh_auth_options doit également être définie dans le paramètre allowed_ssh_auth_options.",
	'allowed_ssh_auth_options parameter must be an array.' => 'Le paramètre allowed_ssh_auth_options doit être un tableau.',
	'default_ssh_auth_options parameter must be a string.' => 'Le paramètre default_ssh_auth_options doit être une chaîne.',
	'SSH permissions' => 'Permissions SSH',
	'SSH users' => 'Comptes SSH',
	'This is the list of customers which are allowed to create SSH users to login on the system using SSH.' => 'Liste des clients qui sont autorisés à créer des comptes SSH pour se connecter au système.',
	'Customer name' => 'Nom du client',
	'Max SSH users' => "Nombre de comptes SSH",
	'Authentication options' => "Options d'authentification",
	'Restricted shell' => 'Shell restreint',
	'Status' => 'Statut',
	'Actions' => 'Actions',
	'Processing...' => 'Chargement en cours...',
	'Add / Edit SSH Permissions' => 'Ajout / Édition des permissions SSH',
	'Maximum number of SSH users' => "Nombre comptes SSH",
	'0 for unlimited' => '0 pour illimité',
	'Enter a number' => 'Entrez un nombre',
	'Enter a customer name' => "Entrez le nom d'un client",
	'Can edit authentication options' => "Peut éditer les options d'authentification",
	'See man authorized_keys for further details about authentication options.' => "Voir man authorized_keys pour plus de détails à propos des options d'authentification.",
	'Does the shell access must be provided in restricted environment (recommended)?' => "Est-ce que l'accès au shell doit être fournit dans un environnement restreint (recommandé)?",
	'Unknown customer. Please enter a valid customer name.' => 'Client inconnu. Veuillez entrez un nom de client valide.',
	'Are you sure you want to revoke SSH permissions for this customer?' => 'Êtes-vous certain de vouloir révoquer les permissions SSH pour ce client?',
	'Unknown action.' => 'Action inconnue.',
	'Request Timeout: The server took too long to send the data.' => 'Temp de réponse dépassé: Le serveur prend trop de temps pour envoyer les données.',
	'An unexpected error occurred.' => "Une erreur innatendue s'est produite.",
	'Save' => 'Sauvegarder',
	'Cancel' => 'Annuler',
	'Admin / Settings / SSH Permissions' => 'Admin / Paramètres / Permissions SSH',
	'An unexpected error occurred: %s' => "Une erreur innatendue s'est produite: %s",
	'SSH permissions not found.' => 'Permissions SSH non trouvées.',
	'Bad request.' => 'Mauvaise requête.',
	'All fields are required.' => 'Tous les champs sont requis.',
	"Wrong value for the 'Maximum number of SSH users' field. Please, enter a number." => "Valeur erronée pour le champ 'Nombre de comptes SSH'. Veullez entrer un nombre.",
	'SSH permissions were added.' => 'Les permissions SSH ont été ajoutées',
	'SSH permissions were scheduled for update.' => 'Les permissions SSH vont êtres mises à jour.',
	'SSH permissions were scheduled for deletion.' => 'Les permissions SSH vont êtres supprimées.',
	'Edit permissions' => 'Éditer les permissions',
	'Revoke permissions' => 'Révoquer les permissions',
	'This is the list of SSH users associated with your account.' => 'Liste des comptes SSH associées à votre compte.',
	"You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication." =>  "Vous pouvez fournir un mot de passe, une clé SSH ou les deux. Toutefois, il est recommendé d'utiliser une authentification par clé.",
	'You can generate your rsa key pair by running the following command:' => 'Vous pouvez générer votre pair de clés RSA en exécutant la commande suivante:',
	'Key fingerprint' => 'Empreinte de clé',
	'SSH user' => 'Compte SSH',
	'Username' => "Nom d'utilisateur",
	'Enter an username' => "Entrez un nom d'utilisateur ",
	'Password' => 'Mot de passe',
	'Enter a password' => 'Entrez un mot de passe',
	'Password confirmation' => 'Confirmation du mot de passe',
	'Confirm the password' => 'Confirmez le mot de passe',
	'SSH key' => 'Clé SSH',
	'Enter your SSH key' => 'Entrez votre clé SSH',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Les formats de clés supportés sont: PKCS#1, openSSH et XML Signature.',
	'Enter a key' => 'Entrez une clé',
	'Are you sure you want to delete this SSH user?' => 'Êtes-vous certain de vouloir supprimer ce compte SSH?',
	'Client / Profile / SSH Users' => 'Client / Profil / Comptes SSH',
	'SSH user not found.' => 'Utilisateur SSH non trouvé.',
	'Un-allowed username. Please use alphanumeric characters only.' => "Nom d'utilisateur non-autorisé. Veuillez n'utiliser que des caractères alphanumériques.",
	'The username is too long (Max 255 characters).' => "Le nom d'utilisateur est trop long (Max. 8 caractères).",
	'This username is not available.' => "Ce nom d'utilisateur n'est pas disponible.",
	'You must enter an SSH key.' => 'Vous devez entrer une clé SSH.',
	'You must enter either a password, an SSH key or both.' => 'Vous devez entrer un mot de passe, une clé SSH ou les deux.',
	'Un-allowed password. Please use alphanumeric characters only.' => "Mot de passe non-autorisé. Veuillez n'utiliser que des caractères alphanumériques.",
	'Wrong password length (Max 32 characters).' => 'Mauvaise longueur de mot de passe (Max 32 caractères).',
	'Wrong password length (Min 6 characters).' => 'Mauvaise longueur de mot de passe (Min 8 caractères).',
	'Passwords do not match.' => 'Les mots de passe ne correspondent pas.',
	'Invalid SSH key.' => 'Clé SSH invalide.',
	'SSH user has been scheduled for addition.' => "Le compte SSH va être ajouté.",
	'Your SSH user limit is reached.' => "Votre limite de comptes SSH est atteinte.",
	'SSH user has been scheduled for update.' => "Le compte SSH va être mis à jour.",
	'SSH user has been scheduled for deletion.' => "Le compte SSH va être supprimé.",
	'An unexpected error occurred. Please contact your reseller.' => "Une erreur innatendue s'est produite. Veuillez contacter votre revendeur.",
	'Add / Edit SSH user' => "Ajout / Édition d'un compte SSH",
	'Delete this SSH user' => 'Supprimer ce compte SSH',
	'Allowed authentication options: %s' => "Options d'authentifcation autorisées: %s",
	'Unlimited' => 'Illimité',
	'Yes' => 'Oui',
	'No' => 'Non'
);
