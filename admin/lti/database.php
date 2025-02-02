<?php

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}lti_result",
"drop table if exists {$CFG->dbprefix}lti_service",
"drop table if exists {$CFG->dbprefix}lti_membership",
"drop table if exists {$CFG->dbprefix}lti_link",
"drop table if exists {$CFG->dbprefix}lti_link_activity",
"drop table if exists {$CFG->dbprefix}lti_link_user_activity",
"drop table if exists {$CFG->dbprefix}lti_context",
"drop table if exists {$CFG->dbprefix}lti_user",
"drop table if exists {$CFG->dbprefix}lti_issuer",
"drop table if exists {$CFG->dbprefix}lti_key",
"drop table if exists {$CFG->dbprefix}lti_nonce",
"drop table if exists {$CFG->dbprefix}lti_message",
"drop table if exists {$CFG->dbprefix}lti_domain",
"drop table if exists {$CFG->dbprefix}lti_external",
"drop table if exists {$CFG->dbprefix}cal_event",
"drop table if exists {$CFG->dbprefix}cal_key",
"drop table if exists {$CFG->dbprefix}cal_context",
"drop table if exists {$CFG->dbprefix}tsugi_string",
"drop table if exists {$CFG->dbprefix}sessions",
"drop table if exists {$CFG->dbprefix}profile"
);

// Note that the TEXT xxx_key fields are UNIQUE but not
// marked as UNIQUE because of MySQL key index length limitations.

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}lti_issuer",
"create table {$CFG->dbprefix}lti_issuer (
    issuer_id           INTEGER NOT NULL AUTO_INCREMENT,
    issuer_sha256       CHAR(64) NOT NULL,
    issuer_key          TEXT NOT NULL,  -- iss from the JWT
    issuer_client    TEXT NOT NULL,  -- aud from the JWT
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    -- This is the owner of this issuer - it is not a foreign key
    -- We might use this if we end up with self-service issuers
    user_id             INTEGER NULL,

    lti13_oidc_auth     TEXT NULL,
    lti13_keyset_url    TEXT NULL,
    lti13_keyset        TEXT NULL,
    lti13_platform_pubkey TEXT NULL,
    lti13_kid           TEXT NULL,
    lti13_pubkey        TEXT NULL,
    lti13_privkey       TEXT NULL,
    lti13_token_url     TEXT NULL,

    json                MEDIUMTEXT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}lti_issuer_const_1` UNIQUE(issuer_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_issuer_const_pk` PRIMARY KEY (issuer_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// https://stackoverflow.com/questions/28418360/jwt-json-web-token-audience-aud-versus-client-id-whats-the-difference

// Key is in effect "tenant" (like a billing endpoint)
// We need to be able to look this up by either oauth_consumer_key or
// (issuer, client_id, deployment_id)
array( "{$CFG->dbprefix}lti_key",
"create table {$CFG->dbprefix}lti_key (
    key_id              INTEGER NOT NULL AUTO_INCREMENT,
    key_sha256          CHAR(64) NULL,
    key_key             TEXT NOT NULL,   -- oauth_consumer_key
    deploy_sha256       CHAR(64) NULL,
    deploy_key          TEXT NULL,
    issuer_id           INTEGER NULL,

    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    secret              TEXT NULL,
    new_secret          TEXT NULL,

    -- This is the owner of this key - it is not a foreign key
    -- on purpose to avoid potential circular foreign keys
    user_id             INTEGER NULL,

    caliper_url         TEXT NULL,
    caliper_key         TEXT NULL,

    json                MEDIUMTEXT NULL,
    settings            MEDIUMTEXT NULL,
    settings_url        TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}lti_key_ibfk_1`
        FOREIGN KEY (`issuer_id`)
        REFERENCES `{$CFG->dbprefix}lti_issuer` (`issuer_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_key_const_1` UNIQUE(key_sha256, deploy_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_key_const_2` UNIQUE(issuer_id, deploy_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_key_const_pk` PRIMARY KEY (key_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

/* If MySQL had constraints - these would be nice in lti_key - for now
   we will just need to be careful in code.

    CONSTRAINT `{$CFG->dbprefix}lti_key_both_not_null`
    CHECK (
        (key_sha256 IS NOT NULL OR deploy_sha256 IS NOT NULL)
    )

    CONSTRAINT `{$CFG->dbprefix}lti_key_deploy_linked`
    CHECK (
        (deploy_key IS NOT NULL AND issuer_id IS NOT NULL)
     OR (deploy_key NOT NULL AND issuer_id NOT NULL)
    )
 */

array( "{$CFG->dbprefix}lti_user",
"create table {$CFG->dbprefix}lti_user (
    user_id             INTEGER NOT NULL AUTO_INCREMENT,
    user_sha256         CHAR(64) NULL,
    user_key            TEXT NULL,
    subject_sha256      CHAR(64) NULL,
    subject_key         TEXT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    key_id              INTEGER NOT NULL,
    profile_id          INTEGER NULL,

    displayname         TEXT NULL,
    email               TEXT NULL,
    locale              CHAR(63) NULL,
    image               TEXT NULL,
    subscribe           SMALLINT NULL,

    json                MEDIUMTEXT NULL,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    -- Google classroom token for this user
    gc_token            TEXT NULL,

    ipaddr              VARCHAR(64),
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_user_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_user_const_1` UNIQUE(key_id, user_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_user_const_2` UNIQUE(key_id, subject_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_user_const_pk` PRIMARY KEY (user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

/* If MySQL had CHECK constraints - these would be nice in lti_user - for now
   we will just need to be careful in code.

    CONSTRAINT `{$CFG->dbprefix}lti_user_both_not_null`
    CHECK (
        (user_sha256 IS NOT NULL OR subject_sha256 IS NOT NULL)
    )
 */

array( "{$CFG->dbprefix}lti_context",
"create table {$CFG->dbprefix}lti_context (
    context_id          INTEGER NOT NULL AUTO_INCREMENT,
    context_sha256      CHAR(64) NOT NULL,
    context_key         TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    secret              VARCHAR(128) NULL,
    gc_secret           VARCHAR(128) NULL,

    key_id              INTEGER NOT NULL,

    -- If this course was created by a user within a key
    -- For example Google Glassroom - or an ad-hoc group
    user_id             INTEGER NULL,

    path                TEXT NULL,

    title               TEXT NULL,

    lessons             MEDIUMTEXT NULL,

    json                MEDIUMTEXT NULL,
    settings            MEDIUMTEXT NULL,
    settings_url        TEXT NULL,
    ext_memberships_id  TEXT NULL,
    ext_memberships_url TEXT NULL,
    memberships_url     TEXT NULL,
    lineitems_url       TEXT NULL,
    lti13_lineitems     TEXT NULL,
    lti13_membership_url  TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_context_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_context_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_context_const_1` UNIQUE(key_id, context_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_context_const_pk` PRIMARY KEY (context_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_link",
"create table {$CFG->dbprefix}lti_link (
    link_id             INTEGER NOT NULL AUTO_INCREMENT,
    link_sha256         CHAR(64) NOT NULL,
    link_key            TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    context_id          INTEGER NOT NULL,

    path                TEXT NULL,
    lti13_lineitem      TEXT NULL,

    title               TEXT NULL,

    json                MEDIUMTEXT NULL,
    settings            MEDIUMTEXT NULL,
    settings_url        TEXT NULL,

    placementsecret     VARCHAR(64) NULL,
    oldplacementsecret  VARCHAR(64) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_link_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_link_const_1` UNIQUE(link_sha256, context_id),
    CONSTRAINT `{$CFG->dbprefix}lti_link_const_pk` PRIMARY KEY (link_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_link_activity",
"create table {$CFG->dbprefix}lti_link_activity (
    link_id             INTEGER NOT NULL,
    event               INTEGER NOT NULL,

    link_count          INTEGER UNSIGNED NOT NULL DEFAULT 0,
    activity            VARBINARY(1024) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_link_activity_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY (link_id,event)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),


array( "{$CFG->dbprefix}lti_membership",
"create table {$CFG->dbprefix}lti_membership (
    membership_id       INTEGER NOT NULL AUTO_INCREMENT,

    context_id          INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,

    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    role                SMALLINT NULL,
    role_override       SMALLINT NULL,

    json                MEDIUMTEXT NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_membership_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_membership_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_membership_const_1` UNIQUE(context_id, user_id),
    CONSTRAINT `{$CFG->dbprefix}lti_membership_const_pk` PRIMARY KEY (membership_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_link_user_activity",
"create table {$CFG->dbprefix}lti_link_user_activity (
    link_id             INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,
    event               INTEGER NOT NULL,

    link_user_count     INTEGER UNSIGNED NOT NULL DEFAULT 0,
    activity            VARBINARY(1024) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_link_user_activity_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_link_user_activity_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_link_user_activity_const_pk` PRIMARY KEY (link_id, user_id, event)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_service",
"create table {$CFG->dbprefix}lti_service (
    service_id          INTEGER NOT NULL AUTO_INCREMENT,
    service_sha256      CHAR(64) NOT NULL,
    service_key         TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    key_id              INTEGER NOT NULL,

    format              VARCHAR(1024) NULL,

    json                MEDIUMTEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_service_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_service_const_1` UNIQUE(key_id, service_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_service_const_pk` PRIMARY KEY (service_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// service_id/sourcedid are for LTI 1.x
// result_url is for LTI 2.x
// Sometimes we might get both
array( "{$CFG->dbprefix}lti_result",
"create table {$CFG->dbprefix}lti_result (
    result_id          INTEGER NOT NULL AUTO_INCREMENT,
    link_id            INTEGER NOT NULL,
    user_id            INTEGER NOT NULL,
    deleted            TINYINT(1) NOT NULL DEFAULT 0,

    result_url         TEXT NULL,

    sourcedid          TEXT NULL,
    service_id         INTEGER NULL,
    gc_submit_id       TEXT NULL,

    ipaddr             VARCHAR(64),

    grade              FLOAT NULL,
    note               MEDIUMTEXT NULL,
    server_grade       FLOAT NULL,

    json               MEDIUMTEXT NULL,
    entity_version     INTEGER NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NULL,
    deleted_at         TIMESTAMP NULL,
    retrieved_at       TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_result_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_result_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_result_ibfk_3`
        FOREIGN KEY (`service_id`)
        REFERENCES `{$CFG->dbprefix}lti_service` (`service_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    -- Note service_id is not part of the key on purpose
    -- It is data that can change and can be null in LTI 2.0
    CONSTRAINT `{$CFG->dbprefix}lti_result_const_1` UNIQUE(link_id, user_id),
    CONSTRAINT `{$CFG->dbprefix}lti_result_const_pk`  PRIMARY KEY (result_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Nonce is not connected using foreign key for performance
// and because it is effectively just a temporary cache
array( "{$CFG->dbprefix}lti_nonce",
"create table {$CFG->dbprefix}lti_nonce (
    nonce          CHAR(128) NOT NULL,
    key_id         INTEGER NOT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `{$CFG->dbprefix}nonce_indx_1` USING HASH (`nonce`),
    CONSTRAINT `{$CFG->dbprefix}lti_nonce_const_1` UNIQUE(key_id, nonce)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// This is for messaging if web sockets is not present
// These records should never last more than 5 minutes
// No foreign key on link_id - orphan records will expire
array( "{$CFG->dbprefix}lti_message",
"create table {$CFG->dbprefix}lti_message (
    link_id             INTEGER NOT NULL,
    room_id             INTEGER NOT NULL DEFAULT 0,

    message             TEXT NULL,

    micro_time          DOUBLE NOT NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}lti_message_const_pk` PRIMARY KEY (link_id,room_id, micro_time)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_domain",
"create table {$CFG->dbprefix}lti_domain (
    domain_id   INTEGER NOT NULL AUTO_INCREMENT,
    key_id      INTEGER NOT NULL,
    context_id  INTEGER NULL,
    deleted     TINYINT(1) NOT NULL DEFAULT 0,
    domain      VARCHAR(128),
    port        INTEGER NULL,
    consumer_key  TEXT,
    secret      TEXT,
    json        TEXT NULL,
    created_at  TIMESTAMP NOT NULL,
    updated_at         TIMESTAMP NULL,
    deleted_at         TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_ibfk_2`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_const_pk` PRIMARY KEY (domain_id),
    CONSTRAINT `{$CFG->dbprefix}lti_domain_const_1` UNIQUE(key_id, context_id, domain, port)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_external",
"create table {$CFG->dbprefix}lti_external (
    external_id  INTEGER NOT NULL AUTO_INCREMENT,
    endpoint        VARCHAR(128),
    name        TEXT,
    url         VARCHAR(128),
    description TEXT,
    fa_icon     VARCHAR(128),
    pubkey      TEXT,
    privkey     TEXT,
    deleted     TINYINT(1) NOT NULL DEFAULT 0,
    json        TEXT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_external_const_pk` PRIMARY KEY (external_id),
    CONSTRAINT `{$CFG->dbprefix}lti_external_const_1` UNIQUE(endpoint)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// String table - Not normalized at all - very costly
// Enable with $CFG->checktranslation = true
array( "{$CFG->dbprefix}tsugi_string",
"create table {$CFG->dbprefix}tsugi_string (
    string_id       INTEGER NOT NULL AUTO_INCREMENT,
    domain          VARCHAR(128) NOT NULL,
    string_text     TEXT,
    updated_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    string_sha256   CHAR(64) NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_string_const_pk` PRIMARY KEY (string_id),
    CONSTRAINT `{$CFG->dbprefix}lti_string_const_1` UNIQUE(domain, string_sha256)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Sessions is used if we are storing session data
// in the database if we are storing sessions elsewhere
// this will remain empty
array( "{$CFG->dbprefix}sessions",
"CREATE TABLE {$CFG->dbprefix}sessions (
        sess_id VARCHAR(128) NOT NULL PRIMARY KEY,
        sess_data BLOB NOT NULL,
        sess_time INTEGER UNSIGNED NOT NULL,
        sess_lifetime MEDIUMINT NOT NULL,
        created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at          TIMESTAMP NULL
) COLLATE utf8_bin, ENGINE = InnoDB;"),

// Profile is denormalized and not tightly connected to allow
// for disconnecting and reconnecting various user_id values
array( "{$CFG->dbprefix}profile",
"create table {$CFG->dbprefix}profile (
    profile_id          INTEGER NOT NULL AUTO_INCREMENT,
    profile_sha256      CHAR(64) NOT NULL UNIQUE,
    profile_key         TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    key_id              INTEGER NOT NULL,

    displayname         TEXT NULL,
    email               TEXT NULL,
    image               TEXT NULL,
    locale              CHAR(63) NULL,
    subscribe           SMALLINT NULL,

    json                MEDIUMTEXT NULL,
    login_at            TIMESTAMP NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}profile_const_pk` PRIMARY KEY (profile_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Caliper tables - event oriented - no foreign keys to the lti_tables

// "FIFO" buffer of events no explicit foreign key
// relationships as these are short-lived records
array( "{$CFG->dbprefix}cal_event",
"create table {$CFG->dbprefix}cal_event (
    event_id        INTEGER NOT NULL AUTO_INCREMENT,
    event           INTEGER NOT NULL,

    state           SMALLINT NULL,

    link_id         INTEGER NULL,
    key_id          INTEGER NULL,
    context_id      INTEGER NULL,
    user_id         INTEGER NULL,

    nonce           BINARY(16) NULL,
    launch          MEDIUMTEXT NULL,
    json            MEDIUMTEXT NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}cal_event_const_pk` PRIMARY KEY (event_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}cal_key",
"create table {$CFG->dbprefix}cal_key (
    key_id              INTEGER NOT NULL AUTO_INCREMENT,
    key_sha256          CHAR(64) NOT NULL,
    key_key             TEXT NOT NULL,

    activity            VARBINARY(8192) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}cal_key_const_1` UNIQUE(key_sha256),
    CONSTRAINT `{$CFG->dbprefix}cal_key_const_pk` PRIMARY KEY (key_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}cal_context",
"create table {$CFG->dbprefix}cal_context (
    context_id          INTEGER NOT NULL AUTO_INCREMENT,
    context_sha256      CHAR(64) NOT NULL,
    context_key         TEXT NOT NULL,

    key_id              INTEGER NOT NULL,

    activity            VARBINARY(8192) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}cal_context_const_1` UNIQUE(key_id, context_sha256),
    CONSTRAINT `{$CFG->dbprefix}cal_context_const_pk` PRIMARY KEY (context_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

// Called after a table has been created...
$DATABASE_POST_CREATE = function($table) {
    global $CFG, $PDOX;

    if ( $table == "{$CFG->dbprefix}lti_key") {
        $sql= "insert into {$CFG->dbprefix}lti_key (key_sha256, key_key, secret) values
            ( '5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5', '12345', 'secret')";
        error_log("Post-create: ".$sql);
        echo("Post-create: ".$sql."<br/>\n");
        $q = $PDOX->queryDie($sql);

        // Secret is big ugly string for the google key - in case we launch internally in Koseu
        $secret = bin2hex(openssl_random_pseudo_bytes(16));
        $sql = "insert into {$CFG->dbprefix}lti_key (key_sha256, secret, key_key) values
            ( 'd4c9d9027326271a89ce51fcaf328ed673f17be33469ff979e8ab8dd501e664f', '$secret', 'google.com')";
        error_log("Post-create: ".$sql);
        echo("Post-create: ".$sql."<br/>\n");
        $q = $PDOX->queryDie($sql);
    }

    if ( $table == "{$CFG->dbprefix}lti_nonce") {
        $sql = "CREATE EVENT IF NOT EXISTS {$CFG->dbprefix}lti_nonce_auto
            ON SCHEDULE EVERY 1 HOUR DO
            DELETE FROM {$CFG->dbprefix}lti_nonce WHERE created_at < (UNIX_TIMESTAMP() - 3600)";
        error_log("Post-create: ".$sql);
        echo("Post-create: ".$sql."<br/>\n");
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }
    }

};

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    // Removed the 2014 - 2017 migrations - 2019-06-15

    if ( $oldversion < 201801271430 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}mail_bulk
                  DROP FOREIGN KEY `{$CFG->dbprefix}mail_bulk_ibfk_2`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_bulk ADD
                   CONSTRAINT `{$CFG->dbprefix}mail_bulk_ibfk_2`
                   FOREIGN KEY (`user_id`)
                   REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
                   ON DELETE NO ACTION ON UPDATE NO ACTION";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent
                  DROP FOREIGN KEY `{$CFG->dbprefix}mail_sent_ibfk_2`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent ADD
                CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_2`
                FOREIGN KEY (`link_id`)
                REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
                ON DELETE CASCADE ON UPDATE CASCADE";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent
                  DROP FOREIGN KEY `{$CFG->dbprefix}mail_sent_ibfk_3`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent ADD
                CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_3`
                FOREIGN KEY (`user_to`)
                REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
                ON DELETE CASCADE ON UPDATE CASCADE";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent
                  DROP FOREIGN KEY `{$CFG->dbprefix}mail_sent_ibfk_4`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent ADD
                CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_4`
                FOREIGN KEY (`user_from`)
                REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
                ON DELETE CASCADE ON UPDATE CASCADE";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }
    }

    // Add the deleted_at column to columns if they are not there.
    // Double check created_at and updated_at
    $tables = array( 'lti_key', 'lti_context', 'lti_link', 'lti_user',
        'lti_membership', 'lti_service', 'lti_result', 'lti_domain',
         'profile');
    foreach($tables as $table) {
        if ( ! $PDOX->columnExists('deleted_at', "{$CFG->dbprefix}".$table) ) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} ADD deleted_at TIMESTAMP NULL";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
        if ( ! $PDOX->columnExists('updated_at', "{$CFG->dbprefix}".$table) ) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} ADD updated_at TIMESTAMP NULL";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
        if ( ! $PDOX->columnExists('created_at', "{$CFG->dbprefix}".$table) ) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} ADD created_at NOT NULL DEFAULT CURRENT_TIMESTAMP";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
    }

    if ( ! $PDOX->columnExists('lti13_lineitem', "{$CFG->dbprefix}lti_link") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_link ADD lti13_lineitem TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( ! $PDOX->columnExists('lti13_lineitems', "{$CFG->dbprefix}lti_context") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD lti13_lineitems TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( ! $PDOX->columnExists('lti13_membership_url', "{$CFG->dbprefix}lti_context") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD lti13_membership_url TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // New for the LTI Advantage issuer refactor
    if ( ! $PDOX->columnExists('deploy_sha256', "{$CFG->dbprefix}lti_key") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD deploy_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key DROP CONSTRAINT `{$CFG->dbprefix}lti_key_ibfk_1`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD
                CONSTRAINT `{$CFG->dbprefix}lti_key_ibfk_1`
                FOREIGN KEY (`issuer_id`)
                REFERENCES `{$CFG->dbprefix}lti_issuer` (`issuer_id`)
                ON DELETE SET NULL ON UPDATE CASCADE";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( ! $PDOX->columnExists('deploy_key', "{$CFG->dbprefix}lti_key") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD deploy_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( ! $PDOX->columnExists('issuer_id', "{$CFG->dbprefix}lti_key") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD issuer_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Remove lti13_lineitem from lti_result - no longer used
    if ( $PDOX->columnExists('lti13_lineitem', "{$CFG->dbprefix}lti_result") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result DROP lti13_lineitem";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201905111039 improvements - Prepare for issuer refactor
    if ( $oldversion < 201905111039 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key MODIFY key_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key MODIFY key_key CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Note still have to edit the entry to get the sha256 properly set
    if ( $PDOX->columnExists('issuer_issuer', "{$CFG->dbprefix}lti_issuer") &&
         ! $PDOX->columnExists('issuer_key', "{$CFG->dbprefix}lti_issuer") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_issuer CHANGE issuer_issuer issuer_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( $PDOX->columnExists('issuer_client_id', "{$CFG->dbprefix}lti_issuer") &&
         ! $PDOX->columnExists('issuer_client', "{$CFG->dbprefix}lti_issuer") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_issuer CHANGE issuer_client_id issuer_client TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( $PDOX->columnExists('user_subject', "{$CFG->dbprefix}lti_user") &&
         ! $PDOX->columnExists('subject_key', "{$CFG->dbprefix}lti_user") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user CHANGE user_subject subject_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( ! $PDOX->columnExists('subject_sha256', "{$CFG->dbprefix}lti_user") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user ADD subject_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user ADD
            CONSTRAINT `{$CFG->dbprefix}lti_user_const_2` UNIQUE(key_id, subject_sha256)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

    }

    // Version 201905270930 improvements
    if ( $oldversion < 201905270930 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user MODIFY user_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user MODIFY user_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user MODIFY subject_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // 20190610 - Remove lti13 fields from lti_key
    $remove_from_lti_key = array(
        'lti13_oidc_auth', 'lti13_keyset_url', 'lti13_keyset', 'lti13_platform_pubkey',
        'lti13_kid', 'lti13_pubkey', 'lti13_privkey', 'lti13_token_url'
    );
    foreach($remove_from_lti_key as $key) {
        if ( $PDOX->columnExists($key, "{$CFG->dbprefix}lti_key") ) {
            $sql= "ALTER TABLE {$CFG->dbprefix}lti_key DROP $key";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
        }
    }

    // Remove from lti2
    $remove_from_lti_key = array(
        'consumer_profile', 'new_consumer_profile',
        'tool_profile', 'new_tool_profile', 'ack',
    );
    foreach($remove_from_lti_key as $column) {
        if ( $PDOX->columnExists($column, "{$CFG->dbprefix}lti_key") ) {
            $sql= "ALTER TABLE {$CFG->dbprefix}lti_key DROP $column";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
        }
    }

    // When you increase this number in any database.php file,
    // make sure to update the global value in setup.php
    return 201906101708;

}; // Don't forget the semicolon on anonymous functions :)

