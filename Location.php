<?php 
namespace Zchan;

class Location{

    /*
     * The service we get to use to get the ip info
     */
    public $service;

    /*
     * This is the ip we are trying to locate
     */
    public $ip;

    /*
     * The ip data we received from the service (json)
     */
    public $data;

    /*
     * This holds an error string
     */
    public static $error;

    /*
     * This is a list of the services
     */
    public const SERVICES = [
        'https://geoip-db.com/', 'https://ipinfo.io/'
    ];

    public function __construct(){}

    /*
     * Retreive the data from the service
     */
    public function Get(){
        if ( !self::IsValidIp( $this->ip ) ) {
            self::$error = "The provided ip is not valid!";
            return False;
        }
        $this->service = self::GetAvailableService();
        if ( $this->service ) {
            $this->StoreIpInfo( $this->data );
        } else {
            // fall back to GeoIP if none of the services is available
            // still need to add it        
        }
        return True;
    }

    /*
     * Check if the service we intend to use is available
     * @param string $service
     */
    public static function IsServiceAvailable( string $service ){
        $req = self::InitRequest( $service );
        if ( $req ) {
            $response = curl_exec( $req );
            $header   = curl_getinfo($req);
            return ($header['http_code'] == '200') ? true : false ;
        }
    }

    /*
     * Probe every service in the SERVICES list and set the
     * current service to the one that is available
     */
    public static function GetAvailableService(){
        for ( $i = 0 ; $i < sizeof( self::SERVICES ) ; $i++ ) {
            $service = self::SERVICES[$i];

            if ( self::IsServiceAvailable( $service ) ) {
                return $service;
            }
        } return '';
    }

    /*
     * Build the request url we are getting the ip data from
     * @param array $data
     */
    public function StoreIpInfo( &$data ){
        $geoip  = "json/" . $this->ip;
        $ipinfo = $this->ip . "/json";
        $stp    = strpos( $this->service, 'geoip' );

        $service = ($stp) ? $this->service . $geoip : $this->service . $ipinfo ;

        // get the service response
        $content = json_decode( 
                file_get_contents( $service ) 
        );
        
        // store only what we need, that is (country_code, ip, city and region)
        $data['country_code'] = ($content->country_code) ? $content->country_code : $content->country ;
        $data['region']       = ($content->region) ? $content->region : $content->state ;
        $data['ip']           = ($content->ip) ? $content->ip : $content->IPv4 ;
        $data['city']         = $content->city;
    }

    /*
     * Initialize the request
     * @param string $url
     */
    public function InitRequest( string $url ){
        $curl = curl_init();
        $opts = array(
            CURLOPT_RETURNTRANSFER => true,  CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => 100,   CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false, CURLOPT_URL            => $url
        );
        curl_setopt_array( $curl, $opts );
        return $curl;
    }

    /*
     * Set the ip we are going to look up
     * @param string $ip
     */
    public function SetIp( string $ip ){
        return ($this->ip = $ip);
    }

    /*
     * Check if the provided ip has valid format
     * @param string $ip
     */
    public static function IsValidIp( string $ip ){
        $isValid = False;
        $ver = (defined(PHP_VERSION))
                ?
            PHP_VERSION
                :
            phpversion();
        if ( version_compare( $ver, '5.0.0' ) >= 1 ) {
            $isValid = \filter_var( $ip, FILTER_VALIDATE_IP );
        } else {
            $isValid = preg_match( "/^(([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3}))$/", $ip );
        }
        return $isValid;
    }
}
?>
