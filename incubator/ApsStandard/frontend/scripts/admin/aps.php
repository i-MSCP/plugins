<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  ApsStandard
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

$tpl = new iMSCP_pTemplate();

$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page_message' => 'layout'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / APS Installer / {TR_TITLE}'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()
	)
);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->assign('LAYOUT_CONTENT', 'APS page content goes here'); // TODO Replace by partial content from APS controller

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
