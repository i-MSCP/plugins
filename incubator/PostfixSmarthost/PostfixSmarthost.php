<?php

class iMSCP_Plugin_Settings_Manager
{
	public function addSettingsSection($id, $title, $callback)
	{

	}
}

interface iMSCP_Plugin_Settings
{
	public function registerSettings(iMSCP_Plugin_Settings_Manager $eventsManager);
}

/**
 * Class iMSCP_Plugin_DomainAutoApproval
 */
class  iMSCP_Plugin_PostfixSmarthost extends iMSCP_Plugin_Action implements iMSCP_Plugin_Settings
{
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		// Nothing todo there

	}

	public function registerSettings(iMSCP_Plugin_Settings_Manager $settingsManager)
	{
		$settingsManager->addSettingsSection(
			'postfix_smarthost_settings',
			tr('Configuration settings'),
			array($this, 'renderSettingsText')
		);

		$settingsManager->addSettingsField(
			array(
				'relayHost' => 'relayHost', // ID used to identifiy the field
				tr('Relay Host'), // The label of the element
				array($this, 'getSettingsForm')
			)
		);
	}

	public function renderSettingsText()
	{
		return tr('a text to display');
	}




	public function configure(array $data = array())
	{
		if(empty($data)) {
			$data = $this->getConfig();
		} else {
			$data['relayHost'] = isset($data['relayHost']) ? $data['relayHost'] : $this->getConfigParam('relayHost');
			$data['relayPort'] = isset($data['relayPort']) ? $data['relayPort'] : $this->getConfigParam('relayHost');
			$data['saslAuthUser'] = isset($data['saslAuthUser']) ? $data['saslAuthUser'] : $this->getConfigParam('relayHost');
			$data['saslAuthPasswd'] = isset($data['saslAuthPasswd']) ? $data['saslAuthPasswd'] : $this->getConfigParam('relayHost');
			$data['saslPasswdMapsFile'] = isset($data['saslPasswdMapsFile']) ? $data['saslPasswdMapsFile'] : $this->getConfigParam('relayHost');

			// todo validate
			if(
				!iMSCP_Validate::getInstance()->hostname($data['relayHost']) &&
				!iMSCP_Validate::getInstance()->ip($data['relayHost'])
			) {
				set_page_message(tr('Relay Host should be a valid domain name or Ip address'), 'error');
			}

			if(iMSCP_Validate::getInstance()->assertContains($data['relayPort'], array('25', '587'))) {
				set_page_message(tr('Relay Port should be a 25 or 587'), 'error');
			}
		}

		return $this->getConfigForm($data);
	}

	protected function getConfigForm(array $data)
	{
		return <<<EOF
			<form action="settings_plugin?conifg=PostfixSmartHost">
				<label for="relayPost">Relay Host</label>
				<input type="text" name="relayHost" value="{$data['relayHost']}">
				<label for="relayPort">Relay Port</label>
				<input type="text" name="relayPort" value="{$data['relayPort']}">
				<label for="saslAuthUser">SASL Username</label>
				<input type="text" name="saslAuthUser" value="{$data['saslAuthUser']}">
				<label for="saslAuthPasswd">SASL Password</label>
				<input type="password" name="saslAuthPasswd" value="">
				<label for="saslPasswdMapsFile">Relay password file path</label>
				<input type="text" name="saslPasswdMapsFile" value="{$data['saslAuthPasswdMapsFile']}">
			</form>


EOF;

	}
}
