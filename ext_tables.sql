CREATE TABLE tx_sudhaus7wizard_domain_model_log (
	creator int(11) NOT NULL DEFAULT '0',
	level varchar(16) NOT NULL DEFAULT '',
	message TEXT,
	context TEXT,
	KEY creator_idx (creator)
);
