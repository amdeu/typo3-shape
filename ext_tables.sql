CREATE TABLE tx_shape_field
(
    page_parents  varchar(1024) DEFAULT NULL,
    field_parent  int UNSIGNED DEFAULT '0' NOT NULL,
    default_value varchar(1024) DEFAULT NULL,
    min           varchar(255)  DEFAULT NULL,
    max           varchar(255)  DEFAULT NULL,
);

CREATE TABLE tx_shape_field_option
(
    field_parent int UNSIGNED DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_shape_form_page
(
    form_parent int UNSIGNED DEFAULT '0' NOT NULL,
    fields      VARCHAR(1024) DEFAULT '',
);

CREATE TABLE tx_shape_form_submission
(
    form       int UNSIGNED DEFAULT '0' NOT NULL,
    plugin     int UNSIGNED DEFAULT '0' NOT NULL,
    user_agent LONGTEXT DEFAULT NULL,
);

CREATE TABLE tx_shape_email_consent
(
    session text,
    status  int UNSIGNED DEFAULT '0' NOT NULL,
);