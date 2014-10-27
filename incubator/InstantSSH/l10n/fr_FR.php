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
	'Plugin allowing to provide full or restricted shell access to your customers.' => 'Plugin permettant de fournir des accèss SSH complets ou restreints aux clients.',
	'Unable to install: %s' => "Impossible d'installer: %s",
	'Unable to update: %s' => 'Impossible de mettre à jour: %s',
	'Unable to enable: %s' => "Impossible d'activer: %s",
	'Unable to uninstall: %s' => 'Impossible de désinstaller: %s',
	'Your i-MSCP version is not compatible with this plugin. Try with a newer version.' => "Votre version d'i-MSCP n'est pas compatible avec ce plugin. Veuillez essayer avec une version plus récente.",
	'Invalid default authentication options: %s' => "Options d'authentification par défaut invalides: %s",
	'Any authentication options appearing in the default_ssh_auth_options parameter must be also specified in the allowed_ssh_auth_options parameter.' => "Toute option d'authentification définie dans le paramètre default_ssh_auth_options doit également être définie dans le paramètre allowed_ssh_auth_options.",
	'allowed_ssh_auth_options parameter must be an array.' => 'Le paramètre allowed_ssh_auth_options doit être un tableau.',
	'default_ssh_auth_options parameter must be a string.' => 'Le paramètre default_ssh_auth_options doit être une chaîne.',
	'Permissions SSH' => 'Permissions SSH',
	'SSH keys' => 'Clés SSH',
	'This is the list of customers which are allowed to add their SSH keys to login on the system using SSH.' => 'Liste des clients qui sont autorisés à ajouter leur clés SSH pour se connecter au système en utilisant SSH.',
	'Customer name' => 'Nom du client',
	'Max Keys' => 'Nombre de clés',
	'Authentication options' => "Options d'authentification",
	'Restricted shell' => 'Shell restreint',
	'Status' => 'Statut',
	'Actions' => 'Actions',
	'Processing...' => 'Chargement en cours...',
	'Add / Edit SSH Permissions' => 'Ajout / Édition des permissions SSH',
	'Maximum number of SSH keys' => 'Nombre de clés SSH',
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
	'Bad request' => 'Mauvaise requête',
	'All fields are required.' => 'Tous les champs sont requis.',
	"Wrong value for the 'Maximum number of SSH keys' field. Please, enter a number." => "Valeur erronée pour le champ 'Nombre de clés SSH'. Veullez entrer un nombre.",
	'SSH permissions scheduled for addition.' => 'Permissions SSH programmée pour ajout.',
	'SSH permissions were scheduled for update.' => 'Permissions SSH programmée pour mise à jour.',
	'SSH permissions were scheduled for deletion.' => 'Permissions SSH programmée pour suppression.',
	'Edit permissions' => 'Éditer les permissions',
	'Revoke permissions' => 'Révoquer les permissions',
	'This is the list of SSH public keys associated with your account. Remove any keys that you do not recognize.' => 'Liste des clés SSH associées à votre compte. Supprimez toutes les clés que vous ne reconnaissez pas.',
	'You can generate your rsa key pair by running the following command: %s' => 'Vous pouvez générer votre pair de clés RSA en exécutant la commande suivante: %s',
	'Name' => 'Nom',
	'Fingerprint' => 'Empreinte',
	'User' => 'Utilisateur',
	'SSH Key name' => 'Nom de la clé SSH',
	'Arbitrary name which allow you to retrieve your SSH key.' => 'Nom arbitraire vous permettant de retrouver votre clé.',
	'SSH Key' => 'Clé SSH',
	'Enter a key name' => 'Entrez un nom pour votre clé',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature.' => 'Les formats de clés supportés sont: PKCS#1, openSSH et XML Signature.',
	'Enter a key' => 'Entrez une clé',
	'Are you sure you want to delete this SSH key? Be aware that this will destroy all your SSH sessions.' => 'Êtes-vous certain de vouloir supprimer cette clé? Soyez conscient que cela va détruire toutes vos sessions SSH.',
	'Client / Profile / SSH Keys' => 'Client / Profil / Clés SSH',
	'SSH Key not found.' => 'Clé SSH non trouvée.',
	'Un-allowed SSH key name. Please use alphanumeric and space characters only.' => "Nom de clé SSH non-autorisé. Veuillez n'utiliser que des caractères alphanumériques ainsi que l'espace.",
	'SSH key name is too long (Max 255 characters).' => 'Le nom de la clé SSH est trop long (Max. 255 caractères).',
	'Invalid SSH key.' => 'Clé SSH invalide.',
	'SSH key scheduled for addition.' => 'Clé SSH programmée pour ajout.',
	'Your SSH key limit is reached.' => 'Votre limite de clés SSH est atteinte.',
	'SSH key scheduled for update.' => 'Clé SSH programmée pour mise à jour.',
	'SSH key with same name or same fingerprint already exists.' => 'Une clé SSH avec le même nom ou même empreinte existe déjà.',
	'SSH key scheduled for deletion.' => 'Clé SSH programmée pour suppression.',
	'An unexpected error occurred. Please contact your reseller.' => "Une erreur innatendue s'est produite. Veuillez contacter votre revendeur.",
	'Show SSH key' => 'Montrer la clé SSH',
	'Edit SSH key' => "Éditer la clé SSH",
	'Delete this SSH key' => 'Supprimer cette clé SSH',
	'Add / Edit SSH keys' => 'Ajout / Édition des clés SSH',
	'Add / Show SSH keys' => 'Ajout / Affichage des clés SSH',
	'Allowed authentication options: %s' => "Options d'authentifcation autorisées: %s",
	'Unlimited' => 'Illimité',
	'Yes' => 'Oui',
	'No' => 'Non'
);
