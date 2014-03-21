<?php
/**
 * ServerProvisioning plugin for i-MSCP
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
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  ServerProvisioning
 * @copyright   2014 by Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/lgpl-2.1.txt LGPL v2.1
 */

namespace ServerProvisioning\Controller;

use iMSCP_Authentication as Authentication;

/**
 * Class Login
 *
 * API calls:
 *
 * Login: POST http(s)://<panel.tld>/<base_endpoint>/login
 *
 * @package ServerProvisioning\Api
 */
class Login extends AbstractController
{
	/**
	 * Authenticate user through default username/password i-MSCP authentication handler and redirect him to its UI
	 *
	 * This is primarly provided for softwares which want provide an username/password based authentication form (such
	 * as WHMCS billing software).
	 *
	 * @param array $data
	 */
	public function authenticate(array $data)
	{
		$auth = Authentication::getInstance()
			->setUsername($data['username'])
			->setPassword($data['password']);

		if(!$auth->authenticate()->isValid()) {
			set_page_message('Invalid credentials', 'error');
		}

		redirectTo('/index.php');
	}

	/**
	 * Return array describing payload requirements
	 *
	 * @param string $action Action
	 * @return array
	 */
	protected function getPayloadRequirements($action)
	{
		$req = array('authenticate' => array('username', 'password'));

		return $req[$action];
	}
}
