<?php

class service extends base_ctrl{
    /**
     * ip2addr : translate ip address to localtion information
     * @param  string $client_ip ip address
     * @return None
     */
    public function ip2addr($client_ip=''){
        $websvc = $this->get_model('service/websvc_model');
        $result = $websvc->ip2addr( $client_ip );
        echo '<pre>';
        var_dump($result);
        echo '</pre>';
    }

    /**
     * latlng : get locaton information by latitude and longitude
     * @param  string $lat latitude
     * @param  string $lng longitude
     * @return None
     */
    public function latlng($lat='', $lng=''){
        $websvc = $this->get_model('service/websvc_model');
        $result = $websvc->latlng( $lat, $lng );
        echo '<pre>';
        var_dump($result);
        echo '</pre>';
    }
}
