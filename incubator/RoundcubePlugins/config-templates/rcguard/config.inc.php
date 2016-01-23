<?php

/*
 * rcguard configuration file
 */

// Number of failed logins before reCAPTCHA is shown
$config['failed_attempts'] = {failed_attempts};

// Release IP after how many minutes (after last failed attempt)
$config['expire_time'] = {expire_time};

// reCAPTCHA API
$config['recaptcha_api']        = 'http://www.google.com/recaptcha/api.js';
$config['recaptcha_api_secure'] = 'https://www.google.com/recaptcha/api.js';

// Use HTTPS for reCAPTCHA
$config['recaptcha_https'] = {recaptcha_https};

// Keys can be obtained from http://www.google.com/recaptcha/

// Public key for reCAPTCHA
$config['recaptcha_publickey'] = '{recaptcha_publickey}';

// Private key for reCAPTCHA
$config['recaptcha_privatekey'] = '{recaptcha_privatekey}';

// Log events
$config['recaptcha_log'] = false;

// Event is not logged when set to NULL
// Parameter expansion:
// %r - Remote IP
// %u - Username
$config['recaptcha_log_success'] = 'Verification succeeded for %u. [%r]';
$config['recaptcha_log_failure'] = 'Error: Verification failed for %u. [%r]';
$config['recaptcha_log_unknown'] = 'Error: Unknown log type.';

// Options for persistent login plugin
// -----------------------------------

// Set to true if persistent login plugin is in use
$config['pl_plugin'] = false;

// Name of persistent login cookie
$config['pl_cookie_name'] = '_pt';

// XXX: Not supported yet!
// Set to true if persistent login uses tokens
$config['pl_auth_tokens'] = false;

// Name of the database table for tokens
$config['pl_auth_tokens_db_table'] = 'auth_tokens';
