#
# Table structure for table 'tx_ptgsaaccounting_dtabuchrel'
#
CREATE TABLE tx_ptgsaaccounting_dtabuchrel (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	gsa_dtabuch_uid int(11) DEFAULT '0' NOT NULL,
	type varchar(100) DEFAULT 'Einzug' NOT NULL,
	bank_code varchar(100) DEFAULT '' NOT NULL,
	account_no varchar(100) DEFAULT '' NOT NULL,
	account_holder varchar(100) DEFAULT '' NOT NULL,
	related_doc_no varchar(100) DEFAULT '' NOT NULL,
	purpose varchar(100) DEFAULT '' NOT NULL,
	filename varchar(100) DEFAULT '' NOT NULL,
	invoice_date varchar(10) DEFAULT '' NOT NULL,
	due_date varchar(10) DEFAULT '' NOT NULL,
	book_date varchar(10) DEFAULT '' NOT NULL,
	gsa_book_date varchar(10) DEFAULT '' NOT NULL,
	transfer_date varchar(10) DEFAULT '' NOT NULL,
	bank_rejected tinyint(3) DEFAULT '0' NOT NULL,
	drop_user tinyint(3) DEFAULT '0' NOT NULL,
	bankaccount_check tinyint(3) DEFAULT '0' NOT NULL,
	invoice_ok_gsa tinyint(3) DEFAULT '0' NOT NULL, 
	booking_amount double(12,2) DEFAULT '0.00' NOT NULL,
	transfer_amount double(12,2) DEFAULT '0.00' NOT NULL,
	difference_amount double(12,2) DEFAULT '0.00' NOT NULL,
	reprocess tinyint(3) DEFAULT '0' NOT NULL, 
	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_ptgsaaccounting_orderCreditBalance (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	order_wrapper_uid int(11) DEFAULT '0' NOT NULL,
	gsa_uid int(11) DEFAULT '0' NOT NULL,
	related_doc_no varchar(100) DEFAULT '' NOT NULL,
	amount_invoice double(12,2) DEFAULT '0.00' NOT NULL,
	amount_customer double(12,2) DEFAULT '0.00' NOT NULL,
	book_date varchar(10) DEFAULT '' NOT NULL,
	reserved tinyint(3) DEFAULT '0' NOT NULL,
	booked tinyint(3) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);


CREATE TABLE tx_ptgsaaccounting_paymentModifier (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    orderUid int(11) DEFAULT '0' NOT NULL,
    value double(12,2) DEFAULT '0.00' NOT NULL,
    addDataType varchar(100) DEFAULT '' NOT NULL,
    addData mediumtext DEFAULT '' NOT NULL,
    PRIMARY KEY (uid),
    KEY parent (pid)
);
