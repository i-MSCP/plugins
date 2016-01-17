<?php
/**
 * i-MSCP PolicydWeight plugin
 * @copyright 2015-2016 Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * Class iMSCP_Plugin_PolicydWeight
 */
class iMSCP_Plugin_PolicydWeight extends iMSCP_Plugin_Action
{
	/**
	 * Plugin activation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function enable(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			# Make sure that postgrey smtp restriction is evaluated first. This is based on plugin_priority field.
			if($pluginManager->pluginIsKnown('Postgrey') && $pluginManager->pluginIsEnabled('Postgrey')) {
				$pluginManager->pluginChange('Postgrey');
			}

			iMSCP_Registry::get('dbConfig')->set(
				'PORT_POLICYD_WEIGHT',
				$this->getConfigParam('policyd_weight_port', 12525) . ';tcp;POLICYD WEIGHT;1;127.0.0.1'
			);
		} catch(iMSCP_Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Plugin deactivation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function disable(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			iMSCP_Registry::get('dbConfig')->del('PORT_POLICYD_WEIGHT');
		} catch(iMSCP_Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
}
