create table if not exists t4_users
(
    user_id        int auto_increment
        primary key,
    user_email     varchar(255)           not null,
    user_pwhash    varchar(255)           not null,
    user_created   date default curdate() null,
    user_lastlogin datetime               null,
    user_firstname varchar(255)           null,
    user_lastname  varchar(255)           null
)
    comment 'Users Table';

create table if not exists t4_documents
(
    document_id      int auto_increment
        primary key,
    document_owner   int                                   not null,
    document_title   text      default curdate()           null,
    document_created timestamp default current_timestamp() not null,
    document_edited  timestamp default current_timestamp() not null on update current_timestamp(),
    constraint DOCOWNER
        foreign key (document_owner) references t4_users (user_id)
);

create table if not exists t4_deltas
(
    delta_id       int auto_increment
        primary key,
    delta_owner    int                                   not null,
    delta_content  text                                  null,
    delta_creation timestamp default current_timestamp() not null,
    delta_document int                                   not null,
    constraint DELTADOC
        foreign key (delta_document) references t4_documents (document_id),
    constraint DELTAOWNER
        foreign key (delta_owner) references t4_users (user_id)
);

create table if not exists t4_sessions
(
    session_id      int auto_increment
        primary key,
    session_token   varchar(255) default uuid()              null,
    session_user    int                                      null,
    session_created timestamp    default current_timestamp() not null,
    session_expires timestamp                                null,
    constraint Session_USER
        foreign key (session_user) references t4_users (user_id)
);

create table if not exists t4_shared
(
    user_id     int not null,
    document_id int not null,
    constraint document_id
        foreign key (document_id) references t4_documents (document_id),
    constraint document_user
        foreign key (user_id) references t4_users (user_id)
);


