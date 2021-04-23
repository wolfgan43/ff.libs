create table support_translations
(
    id         int auto_increment
        primary key,
    lang       varchar(3)   null,
    code       varchar(255) null,
    text       text         null,
    created_at int(10)      null,
    updated_at int(10)      null
);

create index code
    on support_translations (code);

create index lang
    on support_translations (lang);

