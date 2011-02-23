<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$TCA["tx_ptgsaaccounting_dtabuchrel"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel',		
		'label'     => 'uid',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => "ORDER BY crdate",	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_ptgsaaccounting_dtabuchrel.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "hidden, gsa_dtabuch_uid, type, bank_code, account_holder, account_no, related_doc_no, purpose, transfer_date, invoice_date, due_date, gsa_book_date, bank_rejected, reprocess, bankaccount_check, drop_user, invoice_ok_gsa, booking_amount, difference_amount, transfer_amount",
	)
);

$TCA["tx_ptgsaaccounting_orderCreditBalance"] = array (
    "ctrl" => array (
        'title'     => 'LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_orderCreditBalance',       
        'label'     => 'uid',   
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => "ORDER BY crdate",  
        'delete' => 'deleted',  
        'enablecolumns' => array (      
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_ptgsaaccounting_orderCreditBalance.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "hidden, gsa_uid, order_wrapper_uid, related_doc_no, book_date, reserved, booked, amount_invoice, amount_customer",
    )
);

$TCA["tx_ptgsaaccounting_paymentModifier"] = array (
    "ctrl" => array (
        'title'     => 'LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_paymentModifier',       
        'label'     => 'uid',   
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => "ORDER BY crdate",  
        'delete' => 'deleted',  
        'enablecolumns' => array (      
            'disabled' => 'hidden',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_ptgsaaccounting_paymentModifier.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "hidden, orderUid, value, addDataType, addData",
    )
    
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsaaccounting/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


//t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","GSA Accounting: Handle Credits");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:pt_gsaaccounting/locallang_db.xml:tt_content.list_type_pi2', $_EXTKEY.'_pi2'),'list_type');


//t3lib_extMgm::addStaticFile($_EXTKEY,"pi2/static/","GSA Accounting: MyAccount");

t3lib_extMgm::addStaticFile($_EXTKEY,"static/","GSA Accounting: General");

?>