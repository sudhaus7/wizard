CREATE table tt_content (
    myrelations varchar(255) NOT NULL DEFAULT ''
);

CREATE TABLE pages (
    tx_template_related_pages int(11) unsigned DEFAULT 0 NOT NULL
);

CREATE TABLE tt_content (
    tx_template_related_pages varchar(255) DEFAULT NULL,
    tx_template_optional_link text DEFAULT NULL
);
