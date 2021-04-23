create table anagraph
(
    id          int auto_increment
        primary key,
    type_id     int          null,
    role_id     int          null,
    user_uuid   varchar(64)  null,
    name        varchar(255) null,
    email       varchar(255) null,
    tel         varchar(50)  null,
    created_at  int(10)      null,
    updated_at  int(10)      null,
    avatar      varchar(255) null,
    custom1     varchar(255) null,
    custom2     varchar(255) null,
    custom3     varchar(255) null,
    custom4     varchar(255) null,
    custom5     varchar(255) null,
    custom6     varchar(255) null,
    custom7     varchar(255) null,
    custom8     varchar(255) null,
    custom9     varchar(255) null,
)
    charset = utf8;

create index type_id
    on anagraph (type_id);
create index role_id
    on anagraph (role_id);
create index uuid
    on anagraph (uuid);

create table anagraph_person
(
    id          int auto_increment
        primary key,
    anagraph_id int          null,
    name        varchar(255) null,
    surname     varchar(255) null,
    cell        varchar(255) null,
    gender      char         null,
    birthday    date         null,
    cf          varchar(25)  null,
    piva        varchar(25)  null,
    cv          text         null,
    abstract    text         null,
    biography   text         null,
)
    charset = utf8;

create index anagraph_id
    on anagraph_person (anagraph_id);

create table anagraph_role
(
    id   int auto_increment
        primary key,
    name varchar(255) null
)
    charset = utf8;

create table anagraph_type
(
    id   int auto_increment
        primary key,
    name varchar(255) null
)
    charset = utf8;

