
 -- Table : SYS_IP2ADDRESS

create table if not exists SYS_IP2ADDRESS(
    C_IP              varchar(16) not null primary key,
    C_ADDR_FULL       varchar(64) null,
    C_ADDR_SIMPLE     varchar(64) null,
    C_CITY            varchar(32) null,
    C_CITY_CODE       varchar(8) null,
    C_DISTRICT        varchar(32) null,
    C_PROVINCE        varchar(32) null,
    C_STREET          varchar(64) null,
    C_STRRET_NO       varchar(8) null,
    C_BAIDU_LNG       varchar(16) null,
    C_BAIDU_LAT       varchar(16) null,
    C_GOOGLE_LNG      varchar(16) null,
    C_GOOGLE_LAT      varchar(16) null,
    D_CREATE          datetime default now()  not null,
    C_CREATOR         varchar(32) not null
) ENGINE=MyISAM charset=utf8;

create table if not exists SYS_LATLNG_INFO(
    C_GEO_LNG       varchar(16) not null,
    C_GEO_LAT       varchar(16) not null,
    C_GEO_LAT_OLD   varchar(16) null,
    C_GEO_LNG_OLD   varchar(16) null,
    C_ADDR_FULL     varchar(64) null,
    C_PROVINCE      varchar(32) null,
    C_CITY          varchar(32) null,
    C_DISTRICT      varchar(32) null,
    C_AREA          varchar(32) null,
    C_TOWN          varchar(32) null,
    C_VILLAGE       varchar(32) null,
    C_POI           varchar(16) null,
    C_POI_TYPE      varchar(16) null,
    C_DIRECTION     varchar(16) null,
    C_DISTANCE      varchar(16) null,
    C_ROAD_NAME     varchar(16) null,
    C_ROAD_DIR      varchar(16) null,
    C_ROAD_DIST     varchar(16) null,
    D_CREATE        datetime default now()  not null,
    C_CREATOR       varchar(32) not null,
    primary key(C_GEO_LNG, C_GEO_LAT)
) ENGINE=MyISAM charset=utf8;

