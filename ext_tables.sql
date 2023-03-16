
CREATE TABLE tx_sudhaus7wizard_domain_model_creator (
  uid int(11) NOT NULL auto_increment,
  pid int(11) NOT NULL DEFAULT '0',
  title varchar(255) NOT NULL DEFAULT '',
  category varchar(255) NOT NULL DEFAULT '',
  tstamp int(11) unsigned NOT NULL DEFAULT '0',
  crdate int(11) unsigned NOT NULL DEFAULT '0',
  cruser_id int(11) unsigned NOT NULL DEFAULT '0',
  deleted tinyint(4) unsigned NOT NULL DEFAULT '0',
  hidden tinyint(4) unsigned NOT NULL DEFAULT '0',
  t3_origuid int(11) NOT NULL DEFAULT '0',
  sys_language_uid int(11) NOT NULL DEFAULT '0',
  l10n_parent int(11) NOT NULL DEFAULT '0',
  l10n_diffsource mediumblob,

  base varchar(255) NOT NULL DEFAULT '',

  projektname text,
  longname text,
  shortname text,
  domainname text,
  contact text,

  reduser text,
  redpass text,
  redemail text,

  flexinfo text,
  email text,
  sourceclass varchar(255) NOT NULL DEFAULT '\\SUDHAUS7\\Sudhaus7Wizard\\Sources\\Localdatabase',



  sourcepid varchar(255) NOT NULL DEFAULT '0',

  status int(11) unsigned NOT NULL DEFAULT '0',

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY language (l10n_parent,sys_language_uid)
);
