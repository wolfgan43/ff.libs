create table access_clients
(
    id            int auto_increment
        primary key,
    client_type   varchar(64)  null,
    client_id     varchar(64)  null,
    client_secret varchar(255) null,
    domain        varchar(64)  null,
    scopes_client varchar(255) null,
    scopes_user   varchar(255) null,
    grant_types   varchar(64)  null,
    redirect_uri  varchar(255) null,
    site_url      varchar(255) null,
    privacy_url   varchar(255) null
)
    charset = utf8;

create index client_id
    on access_clients (client_id);

create table access_devices
(
    id         int auto_increment
        primary key,
    uuid       varchar(255) null,
    client_id  varchar(64)  null,
    user_uuid  int          null,
    type       varchar(64)  null,
    updated_at int(10)      null,
    hits       int          null,
    ips        text         null
)
    charset = utf8;

create index client_id
    on access_devices (client_id);

create index user_uuid
    on access_devices (user_uuid);

create index uuid
    on access_devices (uuid);

create table access_domains
(
    id         int auto_increment
        primary key,
    name       varchar(255) null,
    expire     int(10)      null,
    created_at int(10)      null,
    updated_at int(10)      null
)
    charset = utf8;

create index name
    on access_domains (name);

create table access_roles
(
    id     int auto_increment
        primary key,
    name   varchar(64)  null,
    scopes varchar(255) not null
)
    charset = utf8;

create index name
    on access_roles (name);

create table access_tokens
(
    id          int auto_increment
        primary key,
    type        varchar(255) null,
    token       varchar(255) null,
    expire      int(10)      null,
    client_id   varchar(64)  null,
    user_uuid   varchar(255) null,
    device_uuid varchar(255) null
)
    charset = utf8;

create index client_id
    on access_tokens (client_id);

create index device_uuid
    on access_tokens (device_uuid);

create index user_uuid
    on access_tokens (user_uuid);

create table access_users
(
    id             int auto_increment
        primary key,
    uuid           varchar(64)  null,
    domain         varchar(64)  null,
    role           varchar(255) null,
    acl            varchar(255) null,
    expire         int(10)      null,
    status         int(1)       null,
    username       varchar(255) null,
    username_slug  varchar(255) null,
    display_name   varchar(128) null,
    email          varchar(255) null,
    tel            varchar(20)  null,
    password       varchar(64)  null,
    avatar         varchar(255) null,
    created_at     int(10)      null,
    updated_at     int(10)      null,
    login_at       int(10)      null,
    verified_email int(10)      null,
    verified_tel   int(10)      null,
    locale         varchar(5)   null,
    referral       varchar(255) null
)
    charset = utf8;

create index domain
    on access_users (domain);

create index uuid
    on access_users (uuid);

create table access_users_newsletter
(
    id            int auto_increment
        primary key,
    user_uuid     varchar(64)  null,
    newsletter_id int          null,
    accepted      tinyint(1)   null,
    created_at    int(10)      null,
    checksum      varchar(255) null
)
    charset = utf8;

create index user_uuid
    on access_users_newsletter (user_uuid);

create index newsletter_id
    on access_users_newsletter (newsletter_id);

create table access_users_privacy
(
    id         int auto_increment
        primary key,
    user_uuid  varchar(64)  null,
    privacy_id int          null,
    accepted   tinyint(1)   null,
    created_at int(10)      null,
    checksum   varchar(255) null
)
    charset = utf8;

create index user_uuid
    on access_users_privacy (user_uuid);

create index privacy_id
    on access_users_privacy (privacy_id);

create table access_newsletter
(
    id          int auto_increment
        primary key,
    type        varchar(64)  null,
    title       varchar(128) null,
    description text         null,
    created     int(10)      null,
    last_update int(10)      null,
    domain      varchar(255) null
)
    charset = utf8;

create index domain
    on access_newsletter (domain);

create table access_privacy
(
    id          int auto_increment
        primary key,
    type        varchar(64)          null,
    title       varchar(128)         null,
    description text                 null,
    version     int                  null,
    created     int(10)              null,
    required    tinyint(1) default 0 null,
    domain      varchar(255)         null
)
    charset = utf8;

create index domain
    on access_privacy (domain);