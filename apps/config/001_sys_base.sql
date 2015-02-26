-- 001, SYS_USERS
create table if not exists SYS_USERS(
    N_UID           int unsigned not null auto_increment,
    N_INUSE         tinyint unsigned not null default 1,
    N_BANNED        tinyint unsigned not null default 0,
    N_ROLE_ID       smallint unsigned not null default 0,

    C_USER_NAME     varchar(32) not null unique,
    C_PASSWORD      varchar(32) not null,
    C_EMAIL         varchar(128) not null unique,
    C_PHONE         varchar(13) null,
    C_ALIAS         varchar(32) not null,

    C_ORG01         varchar(8) null,
    C_ORG02         varchar(8) null,
    C_ORG03         varchar(8) null,
    C_ORG04         varchar(8) null,
    C_ORG05         varchar(16) null,

    C_EXTENSION     text null,
    C_LAST_IP       varchar(16) not null,
    D_LAST_LOGIN    datetime not null,
    D_CREATE        datetime not null,
    D_UPDATE        datetime null,
    C_CREATER       varchar(32) not null,
    C_UPDATER       varchar(32) null,
    primary key(N_UID asc)
) engine=InnoDB auto_increment=1 default charset=utf8 collate=utf8_unicode_ci;

creare index IDX_SYS_USERS_D_CREATE on SYS_USERS (D_CREATE ASC);
creare index IDX_SYS_USERS_N_INUSE on SYS_USERS (N_INUSE ASC);
creare index IDX_SYS_USERS_N_BANNED on SYS_USERS (N_BANNED ASC);
creare index IDX_SYS_USERS_C_USER_NAME on SYS_USERS (C_USER_NAME ASC);
creare index IDX_SYS_USERS_C_EMAIL on SYS_USERS (C_EMAIL ASC);
