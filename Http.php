<?php
namespace zero0x00chan;
$errd = include_once $_SERVER['DOCUMENT_ROOT'] . "/zero0x00chan/site/errdocs.php";

class Http {

    // An array containing all http response cookies
    public static $Cookies = [];

    // Contains the headers for the response
    public static $ReqHeaders = [];

    // Contains data about the current url
    public static $url = [
        'scheme' => '',
        'host'   => '',
        'port'   => '',
        'path'   => '',
        'query'  => []
    ];

    // some url components validation regexes
    public const REGEX = [
        'host' => "/^((www\.|)([a-zA-Z0-9\-]+)(\.([a-zA-Z]+))((:[0-9]+)|))/"
    ];

    public static $codes = [
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Moved Temporarily',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Time-out',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Large',
        '415' => 'Unsupported Media Type',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Time-out',
        '505' => 'HTTP Version not supported'
    ];

    /*
     * Get the method used for the current request.
     * @return string Returns the request method as a string.
     */
    public static function getMethod() {
        return strtolower( $_SERVER['REQUEST_METHOD'] );
    }

    /*
     * Get the current url (https or http)
     */
    public static function GetURL(){
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://' ;
        $url    = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return $scheme . $url;
    }

    /*
     * Encode the given url
     * @param string $url This is the url to be encoded
     * $param bool $raw This indicates if the user wants to use raw encoding
     */
    public static function EncodeURL( string $url, bool $raw ){
        $encoded = '';
        $url     = utf8_encode( $url );
        $chars   = "0123456789ABCDEF";
        if ( $url ) {
            do{
                $c = ord( $url[$i] );
                if ( $url[$i] == ' ' ) {
                    $encoded .= '+';
                    continue;
                }
                if ( ($c >= 65 && $c <= 90) || ($c >= 97 && $c <= 122) || 
                     ($c >= 48 && $c <= 57) || $c == ord('-') || $c == ord('.') || $c == ord('_') || $c == ord('~') ) {
                    $encoded .= $url[$i];
                } else {
                    $encoded .= '%';
                    $encoded .= $chars[$c >> 4];
                    $encoded .= $chars[$c & 15];
                }
            } while( $i++ < strlen( $url ) - 1 );
        }
        return $encoded;
    }

    /*
     * Decode the given url
     * @param string $url This is the url to be encoded
     * $param bool $raw This indicates if the user used raw encoding for the url
     */
    public static function DecodeURL( string $url = '', bool $raw ){

    }

    /*
     * Parse the given url and store its components in self::$url
     * @param string $url This is the url to be parsed
     */
    public static function ParseURL( string $url ){
        // extract the scheme
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://' ;
        self::$url['scheme'] = $scheme;
        
        // determine the host's start and end
        $host_start = strlen( $scheme );
        $host_end   = strpos( substr( $url, $host_start + 1 ), '/' );
        
        // if forward slash is found
        if ( $host_end ) {
            $host = trim( substr( $url, $host_start, $host_end + 1 ) );
        } else {
            $host = trim( substr( $url, $host_start ) );
        }

        // check if the host is valid
        // and if it is try to extract it and the port
        if ( preg_match( self::REGEX['host'], $host ) ) {
            $port_pos = strpos( $host, ':' );
            if ( $port_pos ) {
                self::$url['host'] = substr( $host, 0, $port_pos );
                self::$url['port'] = substr( $host, $port_pos + 1, ($host_end) ? $host_end : strlen( $host ) );
            } else {
                self::$url['host'] = $host;
            }
        }

        // extract the url path if it exists
        if ( $host_end ) {
            $path = substr( substr( $url, $host_start ), $host_end + 1 );
            $query_pos = strpos( $path, '?' );
            if ( $query_pos ) {
                self::$url['path'] = trim( substr( $path, 0, $query_pos ) );
            } else {
                self::$url['path'] = trim( $path );
            }
        }
        // extract the query string 
        self::$url['query'] = self::parse_query( $url );
    }

    /*
     * Parse the url's query and return its parameters
     * @param string $url This is the url
     */
    public static function parse_query( string $url ){
        $arr = [];
        $queryOffset = strpos( $url, '?' );
        if ( $queryOffset > 0 ) {
            $query = @explode( '&', substr( $url, $queryOffset + 1 ) );
        }
        $querySize = sizeof( $query );
        if ( $querySize >= 1 ) {
            for ( $i = 0 ; $i < $querySize ; $i++ ) {
                $paramNameEnd = strpos( $query[$i], '=' );
                $paramName    = trim( substr( $query[$i], 0, $paramNameEnd ) );
                $paramValue   = trim( substr( $query[$i], $paramNameEnd + 1 ) );
                
                if ( !in_array( $paramName, $arr ) ) {
                    $arr[$paramName] = $paramValue;
                }
            }
        } return (object) $arr;
    }

    /*
     * Get all request headers
     */
    public static function GetReqHeaders() : void {
        if ( function_exists( 'getallheaders' ) ) {
            self::$ReqHeaders = getallheaders();
            return;
        }
        // alternatively, we can use the `$_SERVER` environment variable
        // to retrieve the headers
        foreach ( $_SERVER as $headerName => $headerValue ) {
            if ( preg_match( "/(HTTP_)/", $headerName ) ) {
                $headername = @explode( '_', substr( $headerName, strlen( 'HTTP_' ) ) );
                $headername = array_map( 'ucfirst', array_map( 'strtolower', $headername ) );
                $headername = @implode( '-', $headername );
                self::$ReqHeaders[$headername] = $headerValue;
            }
        } return;
    }

    /*
     * Get all cookies from the response
     */
    public static function GetCookies() : void {
        self::GetReqHeaders();
        $cookies = [];
        $headers = (object) self::$ReqHeaders;
        $cookies = @explode( '; ', $headers->Cookie );
        
        for ( $i = 0 ; $i < sizeof( $cookies ) ; $i++ ) {
            $pair  = @explode( '=', $cookies[$i] );
            $cname = strtolower( trim( $pair[0] ) ); $cval = trim( $pair[1] );
            self::$Cookies[$cname] = $cval;
        }
        return;
    }

    /*
     * Get the ip of the client
     * @param int $type This indicates the ip type we want IPv4 or IPv6
     */
    public static function GetClientIp( int $type ){
        //switch ( $type ) {
            
        //}
    }

    /*
     * Throw an http error with error code $errCode and error document $errDoc
     * @param int $errCode
     */
    public static function Err( int $errCode, $type ){
        global $errd;
        switch ( $type ) {
            case 'invite':
                self::BuildNetErr( 403, $errd['000'] );
                break;
            case 'http':
                self::BuildNetErr( $errCode, $errd[(string)$errCode] );
                break;
        }
    }

    /*
     * 
     */
    public static function BuildNetErr( int $errCode, string $errDoc ){
        $proto = strtoupper( $_SERVER['SERVER_PROTOCOL'] );
        if ( !function_exists( 'http_reponse_code' ) ) {
            header( $proto . ' ' . $errCode . ' ' . self::$codes[(string)$errCode] );
            require_once $_SERVER['DOCUMENT_ROOT'] . $errDoc;
            die();
        } else {
            http_response_code( $errCode );
            require_once $_SERVER['DOCUMENT_ROOT'] . $errDoc;
            die();
        }
    }

    /*
	 * Create a new session
	 * @param string $sessName The name of the session
	 * @param string $sessKey
	 * @param string $sessValue
     */
    public static function CreateSession( string $sessName, string $sessKey = '', $sessValue = '' ) {
        if ( !$sessName ) { return False; }
        $keySet = NULL;
        switch ( $sessKey ) {
            case '':
                $keySet = False;
                break;
            default:
                $keySet = ( !ctype_space( $sessKey ) ) ? True : False ;
                break;
        }

        if ( !self::SessionStarted() ) { @session_start(); }
        if ( $keySet ) {
            $_SESSION[$sessName][$sessKey] = $sessValue;
            return True;
        } else {
            $_SESSION[$sessName] = $sessValue;
            return True;
        }
    }

    /*
	 * Retrieve session data
	 * @param string $sessName The name of the session
	 * @param string $sessKey 
     */
    public static function RetrieveSession( string $sessName, string $sessKey = '' ) {
        if ( !self::SessionStarted() ) { @session_start(); }
        if ( !$sessName ) {
            return $_SESSION;
        }
        return ( $sessKey ) ? $_SESSION[$sessName][$sessKey] : $_SESSION[$sessName] ;
    }

    /*
	 * Destroy the session
	 * @param string $sessionName The name of the session to destroy
     */
    public static function DestroySession( string $sessionName = '' ){
        if ( !self::SessionStarted() ) {
            @session_start();
        }
        $sess = ($sessionName) ? $_SESSION[$sessionName] : $_SESSION ;
        unset( $sess );
        session_destroy();
    }

    /*
     * Check if a session already exists
     */
    public static function SessionStarted() : bool {
        $cmp = (defined( PHP_VERSION )) ? PHP_VERSION : phpversion();
        
        // values returned by `session_status()`
        $disabled = (defined( PHP_SESSION_DISABLED )) ? PHP_SESSION_DISABLED : 0x00 ;
        $none     = (defined( PHP_SESSION_NONE ))     ? PHP_SESSION_NONE     : 0x01 ;
        $active   = (defined( PHP_SESSION_ACTIVE ))   ? PHP_SESSION_ACTIVE   : 0x02 ;
        
        if ( version_compare( $cmp, '5.4.0' ) >= 1 ) {
            $status = @session_status();
        } else {
            $status = @session_id();
        }

        // get the status data type to determine if `session_id()` was used
        // to get the session status or `session_status()`
        switch ( gettype( $status ) ) {
            case 'string':
                // session_id() was used
                return ( $status == '' ) ? False : True ;
                break;

            case 'integer':
                // session_status() was used
                return ( $status == $none || $status == $disabled ) ? False : True ;
                break;
        }
    }
}

?>
