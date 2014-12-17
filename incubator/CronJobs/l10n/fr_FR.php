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
 */

return array(
	'Cron permissions' => 'Permissions cron',
	'Cron jobs' => 'Tâches cron',
	'Cron job has been scheduled for addition.' => 'La tâche cron va être ajoutée.',
	'Cron job has been scheduled for update.' => 'La tâche cron va être mise à jour.',
	'An unexpected error occurred: %s' => "Une erreur innatendue s'est produite: %s",
	'Bad request.' => 'Mauvaise requête.',
	'Cron job has been scheduled for deletion.' => 'La tâche cron va être supprimée.',
	'Edit cron job' => 'Éditer la tâche cron',
	'Delete cron job' => 'Supprimer la tâche cron',
	'Url' => 'Url',
	'Shell' => 'Shell',
	'n/a' => 'n/a',
	'Admin / System tools / Cron Jobs' => 'Admin / Outils système / Tâches cron',
	'Interface from which you can add your cron jobs. This interface is for administrators only. Customers have their own interface which is more restricted.' => "Interface à partir de laquelle vous pouvez ajouter vos tâches cron. Cette interface est réservée aux administrateurs. Les clients ont leur propre interface, laquelle est plus restreinte.",
	'Configuring cron jobs requires distinct knowledge of the crontab syntax on Unix based systems. More information about this topic can be obtained on the following webpage:' => "La configuration des tâches cron requière des connaissances distinctes de la syntaxe des fichiers crontab sur les systèmes UNIX. Vous pouvez obtenir plus d'informations à ce sujet en lisant:",
	'Newbie: Intro to cron' => 'Débutant: Introduction à cron',
	'Id' => 'Id',
	'Type' => 'Type',
	'Time/Date' => 'Heure/Date',
	'User' => 'Utilisateur',
	'Command' => 'Commande',
	'Status' => 'Statut',
	'Actions' => 'Actions',
	'Loading data...' => 'Chargement des données...',
	'Add cron job' => 'Ajouter une tâche cron',
	'Email' => 'Email',
	'Email to which cron notifications must be sent if any. Leave blank to disable notifications.' => 'Email à laquelle les notifications provenant de cron doivent êtres envoyées si besoin. Laissez blanc pour désactiver les notifications.',
	'Minute' => 'Minute',
	'Minute at which the cron job must be executed.' => 'Minute à laquelle la tâche cron doit être exécutée.',
	'Hour' => 'Heure',
	'Hour at which the cron job must be executed.' => 'Heure à laquelle la tâche cron doit être exécutée.',
	'Day of month' => 'Jour du mois',
	'Day of the month at which the cron job must be executed.' => 'Jour au cours duquel la tâche cron doit être exécutée.',
	'Month' => 'Mois',
	'Month at which the cron job must be executed.' => 'Mois au cours duquel la tâche cron doit être exécutée.',
	'Day of week' => 'Jour de la semaine',
	'Day of the week at which the cron job must be executed.' => 'Jour de la semaine au cours duquel la tâche cron doit être exécutée.',
	'User under which the cron job must be executed.' => 'Utilisateur sous lequel la tâche cron doit être exécutée.',
	'Command to execute...' => 'Commande à exécuter...',
	'Command type' => 'Type de commande',
	'Url commands are run via GNU Wget while shell commands are run via shell command interpreter (eg. Dash, Bash...).' => "Les commandes de type Url sont exécutées via GNU Wget, alors que les commandes de type Shell sont exécutées via l'interpréteur de commande ( Dash, Bash... ).",
	'You can learn more about the syntax by reading:' => "Vous pouvez en apprendre d'avantage sur la syntaxe employée en lisant:",
	'When using a shortcut in the minute time field, all other time/date fields are ignored.' => 'Quand vous utilisez un raccourci dans le champ minute, tous les autres champs relatives au temps et date sont ignorés.',
	'The available shortcuts are: @reboot, @yearly, @annually, @monthly, @weekly, @daily, @midnight and @hourly' => 'Les raccourcis disponibles sont: @reboot, @yearly, @annually, @monthly, @weekly, @daily, @midnight et @hourly',
	'Minimum time interval between each cron job execution: %s' => "Intervale de temps minimum entre l'exécution de chaque tâche cron: %s",
	'Add / Edit Cron job' => "Ajout / Édition d'une tâche cron",
	'Save' => 'Sauvegarder',
	'Cancel' => 'Annuler',
	'Are you sure you want to delete this cron job?' => 'Êtes-vous certain de vouloir supprimer cette tâche cron?',
	'Unknown action.' => 'Action inconnue.',
	'Request Timeout: The server took too long to send the data.' => 'Temp de réponse dépassé: Le serveur prend trop de temps pour envoyer les données.',
	'An unexpected error occurred.' => "Une erreur innatendue s'est produite.",
	'An unexpected error occurred. Please contact your reseller.' => "Une erreur innatendue s'est produite. Veuillez contacter votre revendeur.",
	'Client / Web Tools / Cron Jobs' => 'Client / Outils Web / Tâches cron',
	'This is the interface from which you can add your cron jobs.' => 'Interface à partir de laquelle vous pouvez ajouter vos tâches cron.',
	"Wrong value for the 'Cron jobs frequency' field. Please, enter a number." => "Mauvaise valeur pour le champ 'Fréquence des tâches cron'. Veuillez entrer un nombre.",
	'Cron permissions were added.' => 'Les permissions cron ont été ajoutées.',
	'Cron permissions were updated.' => 'Les permissions cron ont été mises à jour.',
	'Nothing has been changed.' => "Rien n'a été modifé.",
	"One or many cron jobs which belongs to the reseller's customers are currently processed. Please retry in few minutes." => "Une ou plusieur tâches cron appartenant aux clients du revendeur sont actuellement en cours de traitement. Veuillez ré-essayer dans quelques minutes.",
	'CronJobs: Unable to update cron permissions for %s: %s' => 'CronJobs: Unable to update cron permissions for %s: %s',
	'Cron permissions were revoked.' => 'Les permissions cron ont été révoquées.',
	'Edit permissions' => 'Éditer les permissions',
	'Revoke permissions' => 'Révoquer les permissions',
	'%d minute' => array(
		'%d minute',
		'%d minutes', // Plural form
	),
	'Admin / Settings / Cron Permissions' => 'Admin / Paramètres / Permission cron',
	'List of resellers which are allowed to give cron permissions to their customers.' => 'Liste des revendeurs qui sont autorisés à donner des permissions cron à leur clients.',
	'Reseller name' => 'Nom du revendeur',
	'Cron jobs type' => 'Type de tâches cron',
	'Cron jobs frequency' => 'Fréquence des tâches cron',
	'Add / Edit cron permissions' => 'Ajout / Édition des permissions cron',
	'Enter a reseller name' => "Entrez le nom d'un revendeur",
	'Type of allowed cron jobs. Note that the Url cron jobs are always available, whatever the selected type.' => 'Type de tâches cron autorisé. Notez que les tâches cron de type Url sont toujour disponibles, quelque soit le type sélectionné.',
	'Jailed' => 'Emprisonnée',
	'Full' => 'Complet',
	'Minimum time interval between each cron job execution.' => "Intervale de temps minimum entre l'éxécution de chaque tâche cron.",
	'In minutes' => 'En minutes',
	'Unknown reseller. Please enter a valid reseller name.' => "Revendeur inconnu. Veuillez entrer le nom d'un revendeur valide.",
	'Please enter a reseller name.' => "Veuillez entrer le nom d'un revendeur.",
	'Are you sure you want to revoke the cron permissions for this reseller?' => 'Êtes-vous certain de vouloir révoquer les permissions cron pour ce revendeur?',
	'List of customers which are allowed to add cron jobs.' => 'Liste des clients qui sont autorisés à ajouter des tâches cron.',
	'Max. cron jobs' => 'Nombre max. de tâches cron',
	'Customer name' => 'Nom du client',
	'Enter a customer name' => "Entrez le nom d'un client",
	'0 for unlimited' => '0 pour illimité',
	'Unknown customer. Please enter a valid customer name.' => 'Client inconnu. Veuillez entrer un nom de client valide.',
	'Please enter a customer name.' => "Veuillez entrer le nom d'un client.",
	'Are you sure you want to revoke the cron permissions for this customer?' => 'Êtes-vous certain de vouloir révoquer les permissions cron pour ce client?',

	'Invalid cron job type: %s' => 'Type de tâche cron invalide: %s.',
	'Invalid notification email.' => 'Email invalide.',
	'Value for the %s field cannot be empty.' => 'La valeur pour le champ %s ne peut être vide.',
	'Invalid value for the %s field.' => 'Valeur invalide pour le champ %s.',
	'Unable to parse time entry.' => "Erreur d'analyse.",
	"You're exceeding the allowed limit of %s minutes, which is the minimum interval time between each cron job execution." => "vous excédez la limite autorisée de %s minutes qui est l'intervale de temp minimum entre l'éxécution de chaque tâche cron.",
	'User must be a valid UNIX user.' => "L'utilisateur doit être un utilisateur UNIX valide.",
	'Url must not contain any username/password for security reasons.' => "L'Url ne doit pas contenir d'information d'authentification pour des raisons de sécurité.",
	'Command must be a valid HTTP URL.' => 'La commande doit être une Url valide.'
);
