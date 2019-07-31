<?php 
namespace Zchan;

class Cookie {

    // A list containing all of the cookies
    public static $cookie_jar = [];

    // This holds an error string
    public static $error;
    
    // Tells the script if we want to read the cookie from string
    // Parse the cookie string and check each of its components 
    public static $fromString = false;

    /*
     * Cookie name validation regex
     * 
     * According to RFC-6265 `cookie-name = token`
     * According to RFC-2616 `token = 1*<any CHAR except CTLs or separators>`
     * 
     * CTL            = <any US-ASCII control character
                        (octets 0 - 31) and DEL (127)>

     * separators     = "(" | ")" | "<" | ">" | "@"
                      | "," | ";" | ":" | "\" | <">
                      | "/" | "[" | "]" | "?" | "="
                      | "{" | "}" | SP | HT
     */
    public const IREGEX = [
        'cname' => "/[\\x00-\\x20\\x28\\x29\\x2f\\x2c\\x3a\\x3b\\x40\\x3c\\x3d\\x3f\\x3e\\x5b\\x5c\\x5d\\x7f\\x7b\\x7d]/",
        'path'  => "/[\\x00-\\x20\\x3b]/"
    ];

    // The cookie data that will be used in the Set-Cookie header
    public $CurrentCookie = [
        'name'     => '',
        'value'    => '',
        'Expires'  => '',
        'Max-Age'  => '',
        'Domain'   => '',
        'Path'     => '/',
        'HttpOnly' => false,
        'Secure'   => false
    ];

    /*
     * Set the name of the cookie
     */
    public function SetName( $name ){
        $this->CurrentCookie['name']  = $name;
    }
    
    /*
     * Set the value of the cookie
     */
    public function SetValue( $value ){
        $this->CurrentCookie['value']  = $value;
    }

    /*
     * Set the cookie expiration date (seconds)
     */
    public function SetExpiry( $seconds ){
        $this->CurrentCookie['Expires'] = $seconds;
    }

    /*
     * Set the cookie Max-Age attribute
     */
    public function SetMaxAge( $maxage ){
        $this->CurrentCookie['Max-Age'] = $maxage;
    }

    /*
     * Set the cookie Domain attribute
     */
    public function SetDomain( $domain ){
        $this->CurrentCookie['Domain'] = $domain;
    }

    /*
     * Set the cookie Path attribute
     */
    public function SetPath( $path ){
        $this->CurrentCookie['Path'] = $path;
    }

    /*
     * Set the cookie HttpOnly flag
     */
    public function HttpOnly( $flag ){
        $this->CurrentCookie['HttpOnly'] = $flag;
    }

    /*
     * Set the cookie Secure flag
     */
    public function Secure( $flag ){
        $this->CurrentCookie['Secure'] = $flag;
    }

    /*
     * Check if the cookie is ready i.e no errors occurred
     */
    public function CookieReady(){
        if ( !self::IsValidCookieName( $this->CurrentCookie['name'] ) ) {
            self::$error = "The provided cookie-name is invalid!";
            return False;
        }

        if ( $this->CurrentCookie['Max-Age'] != '' ) {
            if ( !self::IsValidMaxAge( $this->CurrentCookie['Max-Age'] ) ) {
                self::$error = "Max-Age's format is not valid!";
                return False;
            }
        }
        
        if ( $this->CurrentCookie['Domain'] != '' ) {
            if ( !self::IsValidDomain( $this->CurrentCookie['Domain'] ) ) {
                self::$error = 'The provided domain is invalid!';
                return false;
            }
            $domain = $this->CurrentCookie['Domain'];
            $this->CurrentCookie['Domain'] = '.' . $domain;
        }

        if( $this->CurrentCookie['Path'] != '' ){
            if ( !self::IsValidPath( $this->CurrentCookie['Path'] ) ) {
                self::$error = 'The provided path is invalid!';
                return false;
            }
        }

        if ( !self::IsValidExpiry( $this->CurrentCookie['Expires'] ) ) {
            self::$error = 'The provided expiry time is invalid!';
            return false;
        }
        return True;
    }

    /*
     * Add the current cookie to the jar
     */
    public function Add(){
        if ( !$this->CookieReady() ) {
            return False;
        }
        $exp = $this->CurrentCookie['Expires'];
        $this->CurrentCookie['Expires'] = gmdate( 'D, d-M-Y H:i:s T', $exp );

        // create the cookie string
        $cookieString = $this->toString( $this->CurrentCookie );
        $cname = $this->CurrentCookie['name'];

        // if the cookie string is legit and no cookie with name $cname exists
        // push the raw cookie string to the $cookie_jar 
        if ( $cookieString && !self::Exists( $cname ) ) {
            self::$cookie_jar[$cname] = $cookieString;
            // This here is necessary so that the new cookie doesn't inherit
            // any data from the previous one
            $this->ClearCookie();
            return True;
        }
        self::$error = "A cookie with the name `$cname` already exists!";
        return False;
    }

    /*
     * Clear all cookie data
     */
    public function ClearCookie(){
        $this->CurrentCookie['name']     = '';
        $this->CurrentCookie['value']    = '';
        $this->CurrentCookie['Expires']  = '';
        $this->CurrentCookie['Max-Age']  = '';
        $this->CurrentCookie['Domain']   = '';
        $this->CurrentCookie['Path']     = '';
        $this->CurrentCookie['HttpOnly'] = false;
        $this->CurrentCookie['Secure']   = false;
    }

    /*
     * Delete a cookie by name
     * @param string $name This is the name of the cookie to be deleted
     */
    public function Delete( $name ){
        if ( self::Exists( $name ) ) { unset( self::$cookie_jar[$name] ); }
        if ( isset( $_COOKIE[$name] ) ) {
            $this->SetName( $name );
            $this->SetValue( '' );
            $this->SetExpiry( time() - 86600 );
            $this->Add();
            $this->Dispatch( $name );
        }
    }

    /*
     * Check if a cookie with the same `$name` already exists
     * @param string $name This is the name of the cookie to be checked
     */
    public static function Exists( $name ){
        foreach( self::$cookie_jar as $cname => $cval ){
            if ( strcmp( $name, $cname ) == 0 ) {
                return True;
            }
        } return False;
    }

    /*
     * Build the cookie header
     */
    public static function CookieBuildHeader( $cstr ){
        $hname = 'Set-Cookie: ';
        return ($cstr) ? $hname . $cstr : '' ;
    }

    /*
     * Build the cookie string from the provided cookie params in `$currentCookie`
     * @param array $currentCookie This is the array with the cookie data
     */
    public function toString( $currentCookie ){
        $ctype = strtolower( gettype( $currentCookie ) );
        switch ( $ctype ) {
            case 'array':
                $string = '';
                $string .= $currentCookie['name'] . "=" . urlencode( $currentCookie['value'] );
                foreach ( $currentCookie as $k => $v ) {
                    if ( $k !== 'name' && $k !== 'value' && $k !== 'HttpOnly' && $k !== 'Secure' ) {
                        $string .= ($v != '') ? "; " . $k . "=" . $v : '';
                    } else {
                        if ( $k == 'HttpOnly' ) { if ( $v ) { $string .= "; " . $k; } }
                        if ( $k == 'Secure' )   { if ( $v ) { $string .= "; " . $k; } }
                    }
                }
                break;
            default:
                return False;
        }
        return trim( $string );
    }

    /*
     * Send the cookie to the browser
     * @param string $cname This is the name of the cookie to look for in the cookie jar
     */
    public function Dispatch( $cname ){
        if ( !self::Exists( $cname ) ) {
            self::$error = "Cookie $cname does not exist!";
            return False;
        }
        $header = self::CookieBuildHeader( self::$cookie_jar[$cname] );
        if ( $header ) {
            header( $header, false );
        }
    }

    /*
     * Check if Max-Age attribute is valid
     * @param int $maxAge This is the value to be checked
     */
    public static function IsValidMaxAge( $maxAge ){
        return ( !is_numeric( $maxAge ) || (int) $maxAge <= 0 ) ? False : True;
    }

    /*
     * Check if Expires attribute is valid
     * @param int $expiry This is the value to be checked
     */
    public static function IsValidExpiry( $expiry ){
        return ( !is_numeric( $expiry ) || !$expiry ) ? False : True;
    }

    /*
     * Check if the name of the cookie is a valid one
     * @param string $name This is the value to be checked
     */
    public static function IsValidCookieName( $name ){
        if ( gettype( $name ) !== 'string' || mb_strlen( $name ) <= 0
            || preg_match( self::IREGEX['cname'], $name ) ) {
            return False;
        }
        return True;
    }

    /*
     * Check if the cookie domain is a valid one
     * @param string $domain This is the value to be checked
     */
    public static function IsValidDomain( $domain ){
        if ( $domain == '' || filter_var( $domain, FILTER_VALIDATE_IP ) 
            || !strpos( $domain, '.' ) ) {
            return False;
        }
        return True;
    }

    /*
     * Check if the cookie path is a valid one
     * @param string $path This is the value to be checked
     */
    public static function IsValidPath( $path ){
        if ( $path == '' 
            || substr( $path, 0, 1 ) !== '/' || preg_match( self::IREGEX['path'], $path ) ) {
            return False;
        }
        return True;
    }
}
?>
