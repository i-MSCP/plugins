<?php

/*
 * rcguard configuration file
 */

// Number of failed logins before reCAPTCHA is shown
$rcmail_config['failed_attempts'] = 5;

// Release IP after how many minutes (after last failed attempt)
$rcmail_config['expire_time'] = 30;

// Reset failure count after successfull login (see bratkartoffel/rcguard@670395e)
$rcmail_config['rcguard_reset_after_success'] = true;

// reCAPTCHA API
$rcmail_config['recaptcha_api']        = 'http://www.google.com/recaptcha/api.js';
$rcmail_config['recaptcha_api_secure'] = 'https://www.google.com/recaptcha/api.js';

// Use HTTPS for reCAPTCHA
$rcmail_config['recaptcha_https'] = false;

// Keys can be obtained from http://www.google.com/recaptcha/

// Public key for reCAPTCHA
$rcmail_config['recaptcha_publickey'] = '';

// Private key for reCAPTCHA
$rcmail_config['recaptcha_privatekey'] = '';

// Log events
$rcmail_config['recaptcha_log'] = false;

// Event is not logged when set to NULL
// Parameter expansion:
// %r - Remote IP
// %u - Username
$rcmail_config['recaptcha_log_success'] = 'Verification succeeded for %u. [%r]';
$rcmail_config['recaptcha_log_failure'] = 'Error: Verification failed for %u. [%r]';
$rcmail_config['recaptcha_log_unknown'] = 'Error: Unknown log type.';

// Block IPv6 clients based on prefix length
// Use an integer between 16 and 128, 0 to disable
$rcmail_config['rcguard_ipv6_prefix'] = 0;
