<?php

class GeneralUtility
{
    /**
     * Returns the global $_GET array (or value from) normalized to contain un-escaped values.
     * ALWAYS use this API function to acquire the GET variables!
     * This function was previously used to normalize between magic quotes logic, which was removed from PHP 5.5
     *
     * @param string $var Optional pointer to value in GET array (basically name of GET var)
     * @return mixed If $var is set it returns the value of $_GET[$var]. If $var is NULL (default), returns $_GET itself. In any case *slashes are stipped from the output!*
     * @see _POST(), _GP(), _GETset()
     */
    public static function _GET($var = null)
    {
        $value = $var === null ? $_GET : (empty($var) ? null : ($_GET[$var] ?? null));
        // This is there for backwards-compatibility, in order to avoid NULL
        if (isset($value) && !is_array($value)) {
            $value = (string)$value;
        }
        return $value;
    }

    /**
     * Validate a given IP address.
     *
     * Possible format are IPv4 and IPv6.
     *
     * @param string $ip IP address to be tested
     * @return bool TRUE if $ip is either of IPv4 or IPv6 format.
     */
    public static function validIP($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate a given IP address to the IPv4 address format.
     *
     * Example for possible format: 10.0.45.99
     *
     * @param string $ip IP address to be tested
     * @return bool TRUE if $ip is of IPv4 format.
     */
    public static function validIPv4($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate a given IP address to the IPv6 address format.
     *
     * Example for possible format: 43FB::BB3F:A0A0:0 | ::1
     *
     * @param string $ip IP address to be tested
     * @return bool TRUE if $ip is of IPv6 format.
     */
    public static function validIPv6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Log message to frontend and die
     *
     * @param string $message
     * @return void
     */
    public static function logAndDie($message)
    {
        if (Request::isDebugEnabled()) {
            die($message);
        }
    }
}

class Request
{
    /**
     * Check, if debug is enabled
     *
     * @return bool
     */
    public static function isDebugEnabled()
    {
        return (bool)GeneralUtility::_GET('debug');
    }

    /**
     * Get user from Request
     *
     * @return string
     */
    public static function getUsername()
    {
        $username = (string)GeneralUtility::_GET('username');
        if (empty($username)) {
            GeneralUtility::logAndDie('A username has not been found in request or is empty. Please check your router configuration.');
        }

        return $username;
    }

    /**
     * Get password from request
     *
     * @return string Will return password as hashed value
     */
    public static function getPassword()
    {
        $password = (string)GeneralUtility::_GET('password');
        if (empty($password)) {
            GeneralUtility::logAndDie('A password has not been found in request or is empty. Please check your router configuration.');
        }

        return md5($password);
    }

    /**
     * Get Sub-Domain from request
     *
     * @return string
     */
    public static function getSubDomain()
    {
        $subDomain = GeneralUtility::_GET('subdomain');
        if (empty($subDomain)) {
            GeneralUtility::logAndDie('A Sub-Domain has not been found in request or is empty. Please check your router configuration.');
        }
        if (strpos($subDomain, '.') !== false) {
            GeneralUtility::logAndDie('Please set only Sub-Domain name (the first part of hostname) and not full host');
        }

        return $subDomain;
    }

    /**
     * Get IPv4 from request
     *
     * @return string
     */
    public static function getIPv4Address()
    {
        $ip = GeneralUtility::_GET('ip');
        if (empty($ip)) {
            GeneralUtility::logAndDie('An IP address has not been found in request or is empty. Please check your router configuration.');
        }
        if (!GeneralUtility::validIPv4($ip)) {
            GeneralUtility::logAndDie('Given IP is not a valid IPv4 address');
        }

        return $ip;
    }
}

class Authentication
{
    CONST USERS_JSON_PATH = '/var/bind/users.json';

    /**
     * Configured users from JSON file
     *
     * @var array
     */
    protected $users = [];

    /**
     * Authentication constructor.
     * Pre-Load users as array
     */
    public function __construct()
    {
        $content = file_get_contents(self::USERS_JSON_PATH);
        if ($content === false) {
            GeneralUtility::logAndDie('users.json can not be read.');
        }

        $users = json_decode($content, true);
        if ($users === null) {
            GeneralUtility::logAndDie('users.json is invalid. Please re-check syntax');
        }

        $this->users = $users;
    }

    /**
     * Check, if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        if (!$this->isUsernameConfigured(Request::getUsername())) {
            GeneralUtility::logAndDie('Username does not exist.');
        }
        $user = $this->getUserRecord(Request::getUsername());
        if (!isset($user['password'])) {
            GeneralUtility::logAndDie('There is no password configured for this username in users.json');
        }

        return Request::getPassword() === $user['password'];
    }

    /**
     * Check, if user is authenticated to update domain
     *
     * @param string $subDomain
     * @return bool
     */
    public function isUserAuthenticatedToUpdateSubDomain($subDomain)
    {
        if (!$this->isAuthenticated()) {
            GeneralUtility::logAndDie('Authentication failed. Please check username and password in your router/device');
        }
        $subDomains = $this->getSubDomainsOfUsername(Request::getUsername());
        return in_array($subDomain, $subDomains);
    }

    /**
     * Get configured Sub-Domains for username
     *
     * @param string $username
     * @return array
     */
    protected function getSubDomainsOfUsername($username)
    {
        $user = $this->getUserRecord($username);
        if (!isset($user['subdomains'])) {
            GeneralUtility::logAndDie('There are no Sub-Domains for this username configured in users.json');
        }

        return $user['subdomains'];
    }

    /**
     * Get user array
     *
     * @param string $username
     * @return array
     */
    protected function getUserRecord($username)
    {
        $user = [];
        if ($this->isUsernameConfigured($username)) {
            $user = (array)$this->users[$username];
        }
        if (empty($user)) {
            GeneralUtility::logAndDie('Configuration for username in users.json is empty');
        }

        return $user;
    }

    /**
     * Is username configured in JSON
     *
     * @param string $username
     * @return bool
     */
    protected function isUsernameConfigured($username)
    {
        if (!array_key_exists($username, $this->users)) {
            GeneralUtility::logAndDie('There is no user record in users.json configured');
        }

        return true;
    }
}

if (Request::isDebugEnabled()) {
	ini_set("display_errors", 1);
	error_reporting(E_ALL);
}

$authentication = new Authentication();
if (!$authentication->isUserAuthenticatedToUpdateSubDomain(Request::getSubDomain())) {
    GeneralUtility::logAndDie('You are not allowed to update this Sub-Domain');
}

$commandFormat = '%s %s %s %s';
if (Request::isDebugEnabled()) {
    $commandFormat .= ' 2>&1';
}

$clientUpdatePath = '/var/bind/nsclient_update.sh';

if (!is_file($clientUpdatePath)) {
    GeneralUtility::logAndDie('Executable to update NS server does not exists. Please check path');
}

// build command
$command = sprintf(
    '%s %s %s %s',
    '/bin/sh',
    $clientUpdatePath,
    escapeshellarg(Request::getSubDomain()),
    escapeshellarg(Request::getIPv4Address())
);

exec($command, $output);

if (Request::isDebugEnabled()) {
    var_dump($output);
}
