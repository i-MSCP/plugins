<?php
/**
 * i-MSCP RecaptchaPMA Plugin
 * Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Plugin_Manager as PluginManager;

/**
 * Class iMSCP_Plugin_RecaptchaPMA
 */
class iMSCP_Plugin_RecaptchaPMA extends PluginAction
{
    /**
     * @inheritdoc
     */
    public function enable(PluginManager $pluginManager)
    {
        $config = $this->getConfig();

        if (!isset($config['reCaptchaLoginPublicKey']) || $config['reCaptchaLoginPublicKey'] == '' ||
            !isset($config['reCaptchaLoginPrivateKey']) || $config['reCaptchaLoginPrivateKey'] == ''
        ) {
            throw new PluginException('You must first set public and private keys in plugin configuration file.');
        }
    }
}
