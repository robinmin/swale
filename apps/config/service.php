<?php

return [
    'ip2addr' => [
        'svc_key' => 'e58f1e7a13eb4dcea09bc7275bf7ded2',
        'svc_url' => 'getLocationbyip',
        'table'   => 'SYS_IP2ADDRESS',
        'pk_name' => 'C_IP',
        'fld_map' => [
            'IP'            => 'C_IP',
            'address'       => 'C_ADDR_FULL',
            'simpleaddress' => 'C_ADDR_SIMPLE',
            'city'          => 'C_CITY',
            'city_code'     => 'C_CITY_CODE',
            'district'      => 'C_DISTRICT',
            'province'      => 'C_PROVINCE',
            'street'        => 'C_STREET',
            'street_number' => 'C_STRRET_NO',
            'baidu_lng'     => 'C_BAIDU_LNG',
            'baidu_lat'     => 'C_BAIDU_LAT',
            'google_lng'    => 'C_GOOGLE_LNG',
            'google_lat'    => 'C_GOOGLE_LAT'
        ]
    ],'latlng' => [
        'svc_key' => '552f252bc4fe4635b6d50582b008742e',
        'base_url'=> 'http://api.haoservice.com/',
        'svc_url' => 'api/getLocationinfor',
        'table'   => 'SYS_LATLNG_INFO',
        'pk_name' => ['C_GEO_LAT','C_GEO_LNG'],
        'fld_map' => [
            'old_latitude'    => 'C_GEO_LAT_OLD',
            'old_longitude'   => 'C_GEO_LNG_OLD',
            'correctlongitude'=> 'C_GEO_LNG',
            'correctlatitude' => 'C_GEO_LAT',
            'Address'         => 'C_ADDR_FULL',
            'province'        => 'C_PROVINCE',
            'city'            => 'C_CITY',
            'dist'            => 'C_DISTRICT',
            'area'            => 'C_AREA',
            'town'            => 'C_TOWN',
            'village'         => 'C_VILLAGE',
            'poi'             => 'C_POI',
            'poitype'         => 'C_POI_TYPE',
            'direction'       => 'C_DIRECTION',
            'distance'        => 'C_DISTANCE',
            'roadname'        => 'C_ROAD_NAME',
            'roadDirection'   => 'C_ROAD_DIR',
            'roadDistance'    => 'C_ROAD_DIST'
        ]
    ]
];