<?php
/**
 * i-MSCP RecaptchaPMA Plugin
 * Copyright (C) 2010-2016 by Sascha Bay
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
 * Class iMSCP_Plugin_RecaptchaPMA
 */
class iMSCP_Plugin_RecaptchaPMA extends iMSCP_Plugin_Action
{
    /**
     * Plugin activation
     *
     * @param iMSCP_Plugin_Manager $pluginManager
     * @throws iMSCP_Plugin_Exception
     */
    public function enable(iMSCP_Plugin_Manager $pluginManager)
    {
        $config = $this->getConfig();
        if (!isset($config['reCaptchaLoginPublicKey']) || $config['reCaptchaLoginPublicKey'] == '' ||
            !isset($config['reCaptchaLoginPrivateKey']) || $config['reCaptchaLoginPrivateKey'] == ''
        ) {
            throw  new iMSCP_Plugin_Exception('You must first set public and private keys in plugin configuration file');
        }
    }
}
