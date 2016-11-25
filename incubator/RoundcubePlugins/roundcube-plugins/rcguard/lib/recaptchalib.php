<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          https://developers.google.com/recaptcha/docs/php
 *    - Get a reCAPTCHA API Key
 *          https://www.google.com/recaptcha/admin/create
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 * @copyright Copyright (c) 2014, Google Inc.
 * @link      http://www.google.com/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * reCAPTCHA client
 */
class ReCaptcha
{
    private static $version = "php_1.1.1";
    private static $signupUrl = "https://www.google.com/recaptcha/admin";
    private static $siteVerifyUrl = "https://www.google.com/recaptcha/api/siteverify";
    private $_secret;

    /**
     * Constructor.
     *
     * @param string $secret shared secret between site and ReCAPTCHA server.
     */
    function __construct($secret)
    {
        if (empty($secret)) {
            die('To use reCAPTCHA you must get an API key from <a href="' .
                self::$signupUrl . '">' . self::$signupUrl . '</a>');
        }

        $this->_secret = $secret;
    }


    /**
     * Submit the POST request with the specified parameters.
     *
     * @param array $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    private function _submit($params)
    {
        // PHP 5.6.0 changed the way you specify the peer name for SSL context options.
        // Using "CN_name" will still work, but it will raise deprecated errors.
        $peer_key = version_compare(PHP_VERSION, '5.6.0', '<') ? 'CN_name' : 'peer_name';
        $options  = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($params, '', '&'),
                // Force the peer to validate (not needed in 5.6.0+, but still works)
                'verify_peer' => true,
                $peer_key => 'www.google.com',
            )
        );

        $context = stream_context_create($options);
        return file_get_contents(self::$siteVerifyUrl, false, $context);
    }


    /**
     * Calls the reCAPTCHA siteverify API to verify whether the user passes
     * CAPTCHA test. (reCAPTCHA version php_1.1.1)
     *
     * @param string $response The value of 'g-recaptcha-response' in the submitted form.
     * @param string $remoteIp The end user's IP address.
     * @return ReCaptchaResponse Response from the service.
     */
    public function verify($response, $remoteIp = null)
    {
        if (empty($response)) { // Discard empty solution submissions
            return new ReCaptchaResponse(false, array('missing-input'));
        }
        
        $params = array('version'  => self::$version,
                        'secret'   => $this->_secret,
                        'remoteip' => $remoteIp,
                        'response' => $response
                        );

        $rawResponse = $this->_submit($params);

        return ReCaptchaResponse::fromJson($rawResponse);
    }
}


/**
 * The response returned from the service.
 */
class ReCaptchaResponse
{
    public $success;
    public $errorCodes;

    /**
     * Constructor.
     *
     * @param boolean $success
     * @param array $errorCodes
     */
    function __construct($success, $errorCodes=array())
    {
        $this->success = $success;
        $this->errorCodes = $errorCodes;
    }

    /**
     * Build the response from the expected JSON returned by the service.
     *
     * @param string $json
     * @return ReCaptchaResponse
     */
    public static function fromJson($json)
    {
        $responseData = json_decode($json, true);

        if (!$responseData) {
            $reCaptchaResponse = new ReCaptchaResponse(false, array('invalid-json'));
        }
        else if (isset($responseData['success']) && $responseData['success'] == true) {
            $reCaptchaResponse = new ReCaptchaResponse(true);
        }
        else if (isset($responseData['error-codes']) && is_array($responseData['error-codes'])) {
            $reCaptchaResponse = new ReCaptchaResponse(false, $responseData['error-codes']);
        }
        else {
            $reCaptchaResponse = new ReCaptchaResponse(false);
        }

        return $reCaptchaResponse;
    }
}

?>