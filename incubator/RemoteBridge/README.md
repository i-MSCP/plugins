## i-MSCP RemoteBridge plugin v0.0.7

Plugin providing an API which allows to manage i-MSCP accounts.

### LICENSE

Copyright (C) Sascha Bay <info@space2place.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See [GPL v2](http://www.gnu.org/licenses/gpl-2.0.html "GPL v2")

### REQUIREMENTS

Plugin compatible with i-MSCP versions >= 1.1.0

### INSTALLATION

	- Login into the panel as admin and go to the plugin management interface
	- Upload the RemoteBridge plugin archive
	- Activate the plugin
	- Login into the panel as reseller, and create a Bridge key and a server ipaddress which should have access to the remote bridge
	- Add the url http(s)://adminurl.tld/remotebridge.php to your website where you want to manage i-MSCP accounts from

### UPDATE

**1.** Get the plugin from github

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**2.** Create new Plugin archive

	# cd plugins
	# tar cvzf RemoteBridge.tar.gz RemoteBridge

**3.** Plugin upload and update

	- Login into the panel as admin and go to the plugin management interface
	- Upload the RemoteBridge plugin archive
	- Update the plugin list through the plugin interface

### How to send data to the remote bridge (example)

	function dataEncryption($dataToEncrypt, $ResellerUsername) {
		return strtr(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($ResellerUsername), serialize($dataToEncrypt), MCRYPT_MODE_CBC, md5(md5($ResellerUsername)))), '+/=', '-_,');
	}
	$bridgeKey = '';
	$ResellerUsername = '';

	$dataToEncrypt = array(
			'action'                => '',
			'reseller_username'     => $ResellerUsername,
			'reseller_password'     => '',
			'bridge_key'            => $bridgeKey,
			'hosting_plan'			=> '',
			'admin_pass'            => '',
			'email'                 => '',
			'domain'                => ''
	);

	$ch = curl_init('http(s)://admin.myserver.tld/remotebridge.php');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'key='.$bridgeKey.'&data='.dataEncryption($dataToEncrypt, $ResellerUsername));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$httpResponse = curl_exec($ch);
	echo $httpResponse;
	curl_close($ch);

### Post data variables which are available / required

**1.** key (required)

	- This is your own bridge key
	
**2.** data (required)

	- This is a encrypted data array
	
### Encrypted array variables which are available / required

**1.** action (required)

	- This actions are available: create, terminate, suspend, unsuspend, addalias

**1.1.** action create

	- creates a new i-MSCP acount

**1.2.** action update

	- updates existing i-MSCP acount

**1.3.** action terminate

	- deletes an existing i-MSCP acount

**1.4.** action suspend

	- disables an existing i-MSCP acount

**1.5.** action unsuspend

	- enables an existing i-MSCP acount

**1.6.** action addalias

	- Adds a new domain alias to an existing i-MSCP acount

**1.7.** action get_user

	- get all users from a reseller

**2.** reseller_username (required)

	- value: Username of the reseller account

**3.** reseller_password (required)

	- value: Password of the reseller account
	
**4.** domain (required)

	- This will be later the new login of the i-MSCP panel

**5.** admin_pass (required)

	- Password for the new login of the i-MSCP panel

**6.** email (required)

	- Emailadress for the new login of the i-MSCP panel

**7.** hosting_plan (required if you want to use hosting plans to create a user)

	- value: string of the hosting plan name

**7.1.** hp_mail (required if hosting_plan not set)

	- value: -1 (disabled), 0 (unlimited) or a number > 0
	
**7.2.** hp_ftp (required if hosting_plan not set)

	- value: -1 (disabled), 0 (unlimited) or a number > 0

**7.3.** hp_traff (required if hosting_plan not set)

	- value: 0 (unlimited) or a number > 0 in MB

**7.4.** hp_sql_db (required if hosting_plan not set)

	- value: -1 (disabled), 0 (unlimited) or a number > 0

**7.5.** hp_sql_user (required if hosting_plan not set)

	- value: -1 (disabled), 0 (unlimited) or a number > 0

**7.6.** hp_sub (required if hosting_plan not set)

	- value: -1 (disabled), 0 (unlimited) or a number > 0

**7.7.** hp_disk (required if hosting_plan not set)

	- value: 0 (unlimited) or a number > 0 in MB

**7.8.** hp_als (required if hosting_plan not set)

	- value: -1 (disabled), 0 (unlimited) or a number > 0

**7.9.** hp_php (required if hosting_plan not set)

	- value: yes or no

**7.10.** hp_cgi (required if hosting_plan not set)

	- value: yes or no

**7.11.** hp_backup (required if hosting_plan not set)

	- value: no, dmn, sql or full

**7.12.** hp_dns (required if hosting_plan not set)

	- value: yes or no

**7.13.** hp_allowsoftware (required if hosting_plan not set)

	- value: yes or no (php must enabled if you set this value to yes)
	
**7.14.** external_mail (required if hosting_plan not set)

	- value: yes or no (hp_mail does not set to hp_mail -1)

**7.15.** web_folder_protection (required if hosting_plan not set)

	- value: yes or no

**7.16.** phpini_system (required if hosting_plan not set)

	- value: yes or no

**7.17.** phpini_perm_allow_url_fopen (required if hosting_plan not set)

	- value: yes or no

**7.18.** phpini_perm_display_errors (required if hosting_plan not set)

	- value: yes or no

**7.19.** phpini_perm_disable_functions (required if hosting_plan not set)

	- value: yes or no

**7.20.** phpini_post_max_size (required if hosting_plan not set)

	- value: numeric in MB
	
**7.21.** phpini_upload_max_filesize (required if hosting_plan not set)

	- value: numeric in MB

**7.22.** phpini_max_execution_time (required if hosting_plan not set)

	- value: numeric in seconds

**7.23.** phpini_max_input_time (required if hosting_plan not set)

	- value: numeric in seconds

**7.24.** phpini_memory_limit (required if hosting_plan not set)

	- value: numeric in MB

**8.** alias_domains

	- (must be an array), array('alias1.tld', 'alias2.tld')

### Customer data variable which are available

	- fname: first name
	- lname: last name
	- firm: company
	- zip: zipcode
	- city: city
	- state: state
	- country: country
	- phone: phone number
	- fax: fax number
	- street1: street
	- street2: additional street informations
	- gender: value can be "U=unknown F=female, M=male"

### AUTHORS AND CONTRIBUTORS

 * Sascha Bay <info@space2place.de> (Author)
 * Peter Ziergöbel <info@fisa4.de> (Contributor)

**Thank you for using this plugin.**
