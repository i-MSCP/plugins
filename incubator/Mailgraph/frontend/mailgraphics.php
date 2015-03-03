<?php
/**
 * i-MSCP Mailgraph plugin
 * Copyright (C) 2010-2015 by Sascha Bay <info@space2place.de>
 * Copyright (C) 2013-2015 Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/***********************************************************************************************************************
 * Main
 */

check_login('admin');

$whichGraph = (isset($_GET['graph']) && $_GET['graph'] != '') ? clean_input($_GET['graph']) : '';

switch ($whichGraph) {
	case 'mailgraph_day':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_day.png');
		break;
	case 'mailgraph_week':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_week.png');
		break;
	case 'mailgraph_month':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_month.png');
		break;
	case 'mailgraph_year':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_year.png');
		break;
	case 'mailgraph_virus_day':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_virus_day.png');
		break;
	case 'mailgraph_virus_week':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_virus_week.png');
		break;
	case 'mailgraph_virus_month':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_virus_month.png');
		break;
	case 'mailgraph_virus_year':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_virus_year.png');
		break;
	case 'mailgraph_greylist_day':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_greylist_day.png');
		break;
	case 'mailgraph_greylist_week':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_greylist_week.png');
		break;
	case 'mailgraph_greylist_month':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_greylist_month.png');
		break;
	case 'mailgraph_greylist_year':
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_greylist_year.png');
		break;
	default:
		$imgPng = imagecreatefrompng(PLUGINS_PATH . '/Mailgraph/frontend/noimage.png');
}

/* Output image to browser */
header("Content-type: image/png");
imagePng($imgPng);
imagedestroy($imgPng);
