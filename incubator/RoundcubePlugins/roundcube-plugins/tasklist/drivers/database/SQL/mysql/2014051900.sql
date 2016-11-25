ALTER TABLE `tasks` ADD `status` ENUM('','NEEDS-ACTION','IN-PROCESS','COMPLETED','CANCELLED') NOT NULL DEFAULT '' AFTER `complete`;

UPDATE `tasks` SET status='COMPLETED' WHERE complete=1.0 AND status='';
