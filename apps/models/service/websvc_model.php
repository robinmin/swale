<?php

class websvc_model extends base_model{
    /**
     * $_config : internal config array
     * @var array
     */
    private $_config = array();

    /**
     * __construct : ctor
     * @param None
     */
    public function __construct( ) {
        parent::__construct();
        $this->_config = require APP_PATH.'/config/service.php';
    }

    /**
     * ip2addr : translate ip address to localtion information
     * @param  string $client_ip ip address
     * @return array            location information
     */
    public function ip2addr($client_ip=''){
        // get options
        $option = isset($this->_config[__FUNCTION__]) ? $this->_config[__FUNCTION__] : array();
        // get data from database or web service
        return $this->load_data(
            $option,
            'C_IP=:ip',
            ['ip'=>$client_ip]
        );
    }

    /**
     * latlng : get locaton information by latitude and longitude
     * @param  string $lat latitude
     * @param  string $lng longitude
     * @return array         location information
     */
    public function latlng($lat='', $lng=''){
        // get options
        $option = isset($this->_config[__FUNCTION__]) ? $this->_config[__FUNCTION__] : array();
        // get data from database or web service
        return $this->load_data(
            $option,
            'C_GEO_LAT=:lat and C_GEO_LNG=:lng',
            ['lat'=>$lat, 'lng'=>$lng],
            ['latlng'=>$lat.','.$lng]
        );
    }

    /**
     * load_data : load data from database or web service
     * @param  array  $option     web service options
     * @param  string $condition  condition to check whether current data exists or not
     * @param  array  $params     parameters
     * @return array              loaded data
     */
    public function load_data($option, $condition, $params=array(), $params2=array()){
        if( empty($option) ){
            $option = array();
        }
        if( empty($params) ){
            $params = array();
        }
        if( empty($params2) ){
            $params2 = $params;
        }
        $url     = isset($option['svc_url']) ? $option['svc_url'] : '';
        $table   = isset($option['table']) ? $option['table'] : '';
        $fld_map = isset($option['fld_map']) ? $option['fld_map'] : '';

        // get existing data by primary key from database
        $result = $this->find_by($table, $condition, array_values($fld_map), $params);
        if( empty($result) ){
            // get new data from web service
            $params2['key'] = isset($option['svc_key']) ? $option['svc_key'] : '';
            $result = $this->load_json($url, $params2, isset($option['base_url']) ? $option['base_url'] : '');

            // map the result as db fields
            $new_data = array();
            foreach ($result as $key => $value) {
                if( isset($fld_map[$key]) ){
                    $new_data[$fld_map[$key]] = strval($value);
                }
            }
            $new_data['C_CREATOR'] = 'sys';

            // store result into database
            if( false===$this->insert($table, $new_data) ){
                log_message('error', 'Failed to insert data into '.$table);
            }
        }else{
            if( count($result)>0 ){
                $result = array_values($result)[0];
            }
            foreach ($fld_map as $key_ext => $key_in) {
                if( isset($result[$key_in]) && $key_ext!==$key_in ){
                    $result[$key_ext] = $result[$key_in];
                    unset($result[$key_in]);
                }
            }
        }
        return $result;
    }

    /**
     * load_json : load json data from web
     * @param  string $url    url
     * @param  array  $params parameters
     * @return array         response object
     */
    private function load_json($url, $params=array(), $base_url=''){
        // define default config
        $config = [
            'base_url' => empty($base_url) ? 'http://apis.haoservice.com/' : $base_url
        ];

        try {
            // create client instance
            $client = new \GuzzleHttp\Client($config);

            $response = $client->get($url, ['query' => $params]);
            $json = $response->json();

            if( is_array($json) && isset($json['error_code']) && isset($json['result']) && $json['error_code']==0 && is_array($json['result']) ){
                return $json['result'];
            }else{
                return array();
            }
        } catch (ClientException $e) {
            log_message('error', $e->getRequest());
            log_message('error', $e->getResponse());
        }
        return array();
    }
}
