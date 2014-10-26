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
	'Unknown Action' => 'Action inconnue',
	'Request Timeout: The server took too long to send the data.' => 'Temp de réponse dépassé: Le serveur prend trop de temps pour envoyer les données.',
	'An unexpected error occurred.' => "Une erreur innatendue s'est produite.",
	'Save' => 'Sauvegarder',
	'Cancel' => 'Annuler',
	'Admin / Settings / SSH Permissions' => 'Admin / Paramètres / Permissions SSH',
	'An unexpected error occurred: %s' => "Une erreur innatendue s'est produite: %s",
	'SSH permissions not found.' => 'Permissions SSH non trouvées.',
	'Bad request' => 'Mauvaise requête',
	'All fields are required.' => 'Tous les champs sont requis.',
	"Wrong value for the 'Maximum number of SSH keys' field. Please, enter a number." => "Valeur errornée pour le champs 'Nombre de clés SSH'. Veullez entrer un nombre.",
	'SSH permissions scheduled for addition.' => 'Permissions SSH programmée pour ajout.',
	'SSH permissions were scheduled for update.' => 'Permissions SSH programmée pour mise à jour.',
	'SSH permissions were scheduled for deletion.' => 'Permissions SSH programmée pour suppression.',
	'Edit permissions' => 'Éditer les permissions',
	'Revoke permissions' => 'Révoquer les permissions',
	'This is the list of SSH public keys associated with your account. Remove any keys that you do not recognize.' => 'Liste des clés SSH associées à votre compte. Supprimez toute les clés que vous ne reconnaissez pas.',
	'You can generate your rsa key pair by running the following command: %s' => 'Vous pouvez générer votre pair de clés RSA en exécutant la commande suivante: %s',
	'Name' => 'Nom',
	'Fingerprint' => 'Empreinte',
	'User' => 'Utilisateur',
	'Arbitrary name which allow you to retrieve your SSH key' => 'Nom arbitraire vous permettant de retrouver votre clé',
	'Enter a key name' => 'Entrez un nom pour votre clé',
	'Supported RSA key formats are PKCS#1, openSSH and XML Signature' => 'Les formats de clés supportés sont: %PKCS#1, openSSH et XML Signature',
	'Enter a key' => 'Entrez une clé',
	'Are you sure you want to delete this SSH key? Be aware that this will destroy all your SSH sessions.' => 'Êtes-vous certain de vouloir supprimer cette clé? Soyez concient que cela va détruire toute vos sessions SSH.',
	'Client / Profile / SSH Keys' => 'Client / Profil / Clés SSH'
);
