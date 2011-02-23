<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_ptgsaaccounting_dtabuchrel"] = array (
	"ctrl" => $TCA["tx_ptgsaaccounting_dtabuchrel"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "hidden,gsa_dtabuch_uid, type, bank_code, account_no, account_holder, related_doc_no, purpose, filename, transfer_date, invoice_date, due_date, book_date, gsa_book_date, bank_rejected, reprocess, bankaccount_check, drop_user, invoice_ok_gsa, booking_amount, difference_amount, transfer_amount"
	),
	"feInterface" => $TCA["tx_ptgsaaccounting_dtabuchrel"]["feInterface"],
	"columns" => array (
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"gsa_dtabuch_uid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.gsa_dtabuch_uid",		
			"config" => Array (
                'type' => 'input',    
                'size' => '10',    
                'max' => '10',    
                'eval' => 'required,int,nospace',
			)
		),
        "type" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.type",     
            "config" => Array (
                "type" => "input",  
                "size" => "30", 
                "max" => "100",  
                "eval" => "required,trim",
            )
        ),
        "bank_code" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.bank_code",     
            "config" => Array (
                "type" => "input",  
                "size" => "30", 
                "max" => "100",  
                "eval" => "required,trim",
            )
        ),
        "account_no" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.account_no",     
            "config" => Array (
                "type" => "input",  
                "size" => "30", 
                "max" => "100",  
                "eval" => "required,trim",
            )
        ),
        "account_holder" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.account_holder",     
            "config" => Array (
                "type" => "input",  
                "size" => "30", 
                "max" => "100",  
                "eval" => "required,trim",
            )
        ),
        "related_doc_no" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.related_doc_no",     
            "config" => Array (
                "type" => "input",  
                "size" => "30", 
                "max" => "100",  
                "eval" => "required,trim",
            )
        ),
        "purpose" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.purpose",     
            "config" => Array (
                "type" => "input",  
                "size" => "30", 
                "max" => "100",  
                "eval" => "required,trim",
            )
        ),
        "filename" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.filename",     
            "config" => Array (
                "type" => "input",  
                "size" => "30", 
                "max" => "100",  
                "eval" => "trim",
            )
        ),
		"transfer_date" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.transfer_date",		
			"config" => Array (
				"type" => "input",	
				"size" => "10",	
				"max" => "10",	
				"eval" => "trim",
			)
		),
        "invoice_date" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.invoice_date",     
            "config" => Array (
                "type" => "input",  
                "size" => "10", 
                "max" => "10",  
                "eval" => "trim",
            )
        ),
        "due_date" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.due_date",     
            "config" => Array (
                "type" => "input",  
                "size" => "10", 
                "max" => "10",  
                "eval" => "trim",
            )
        ),
        "book_date" => Array (      
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.book_date",     
            'config' => array(
                'type' => 'check',
            )
        ),
		"gsa_book_date" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.gsa_book_date",		
            'config' => array(
                'type' => 'check',
            )
		),
		"bank_rejected" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.bank_rejected",		
            'config' => array(
                'type' => 'check',
            )
		),
        "reprocess" => Array (      
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.reprocess",     
            'config' => array(
                'type' => 'check',
            )
        ),
        "bankaccount_check" => Array (      
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.bankaccount_check",     
            'config' => array(
                'type' => 'check',
            )
        ),
        "drop_user" => Array (      
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.drop_user",     
            'config' => array(
                'type' => 'check',
            )
        ),
        "invoice_ok_gsa" => Array (      
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.invoice_ok_gsa",     
            'config' => array(
                'type' => 'check',
            )
        ),
        "booking_amount" => Array (        
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.booking_amount",       
            'config' => array(
                'type' => 'input',    
                'size' => '10',    
                'max' => '10',    
                'eval' => 'double2',
            )
        ),
		"transfer_amount" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_dtabuchrel.transfer_amount",		
            'config' => array(
                'type' => 'input',    
                'size' => '10',    
                'max' => '10',    
                'eval' => 'double2',
            )
		),
	),
	"types" => array (
		"0" => array("showitem" => "hidden;;1;;1-1-1, gsa_dtabuch_uid, type, bank_code, account_no, account_holder, related_doc_no, purpose,filename,  transfer_date, invoice_date, due_date, book_date, gsa_book_date, bank_rejected, reprocess, bankaccount_check, drop_user, invoice_ok_gsa, booking_amount, transfer_amount, difference_amount")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);


$TCA["tx_ptgsaaccounting_orderCreditBalance"] = array (
    "ctrl" => $TCA["tx_ptgsaaccounting_orderCreditBalance"]["ctrl"],
    "interface" => array (
        "showRecordFieldList" => "hidden,gsa_uid, order_wrapper_uid, related_doc_no, book_date, reserved, booked, amount_invoice, amount_customer"
    ),
    "feInterface" => $TCA["tx_ptgsaaccounting_orderCreditBalance"]["feInterface"],
    "columns" => array (
        'hidden' => array (     
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config'  => array (
                'type'    => 'check',
                'default' => '0'
            )
        ),
        "gsa_uid" => Array (        
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_orderCreditBalance.gsa_uid",       
            "config" => Array (
                'type' => 'input',    
                'size' => '10',    
                'max' => '10',    
                'eval' => 'required,int,nospace',
            )
        ),
        "order_wrapper_uid" => Array (        
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_orderCreditBalance.order_wrapper_uid",       
            "config" => Array (
                'type' => 'input',    
                'size' => '10',    
                'max' => '10',    
                'eval' => 'required,int,nospace',
            )
        ),
        "related_doc_no" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_orderCreditBalance.related_doc_no",     
            "config" => Array (
                "type" => "input",  
                "size" => "30", 
                "max" => "100",  
                "eval" => "trim",
            )
        ),
        "book_date" => Array (      
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_orderCreditBalance.book_date",     
            'config' => array(
                'type' => 'check',
            )
        ),
        "amount_invoice" => Array (        
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_orderCreditBalance.amount_invoice",       
            'config' => array(
                'type' => 'input',    
                'size' => '10',    
                'max' => '10',    
                'eval' => 'double2',
            )
        ),
        "amount_customer" => Array (        
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_orderCreditBalance.amount_customer",       
            'config' => array(
                'type' => 'input',    
                'size' => '10',    
                'max' => '10',    
                'eval' => 'double2',
            )
        ),
        "reserved" => Array (      
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_orderCreditBalance.reserved",     
            'config' => array(
                'type' => 'check',
            )
        ),
        "booked" => Array (      
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_orderCreditBalance.booked",     
            'config' => array(
                'type' => 'check',
            )
        ),
    ),
    "types" => array (
        "0" => array("showitem" => "hidden;;1;;1-1-1, gsa_uid, order_wrapper_uid, related_doc_no, book_date, reserved, booked, amount_invoice, amount_customer")
    ),
    "palettes" => array (
        "1" => array("showitem" => "")
    )
);


$TCA["tx_ptgsaaccounting_paymentModifier"] = array (
    "ctrl" => $TCA["tx_ptgsaaccounting_paymentModifier"]["ctrl"],
    "interface" => array (
        "showRecordFieldList" => "hidden,orderUid, value, addDataType, addData"
    ),
    "feInterface" => $TCA["tx_ptgsaaccounting_paymentModifier"]["feInterface"],
    "columns" => array (
        'hidden' => array (     
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config'  => array (
                'type'    => 'check',
                'default' => '0'
            )
        ),
        "orderUid" => Array (        
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_paymentModifier.orderUid",       
            "config" => Array (
                'type' => 'input',    
                'size' => '10',    
                'max' => '10',    
                'eval' => 'required,int,nospace',
            )
        ),
        "value" => Array (        
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_paymentModifier.value",       
            'config' => array(
                'type' => 'input',    
                'size' => '10',    
                'max' => '10',    
                'eval' => 'double2',
            )
        ),
        "addDataType" => Array (      
            "exclude" => 0,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_paymentModifier.addDataType",     
            "config" => Array (
                "type" => "input",  
                "size" => "30", 
                "max" => "100",  
                "eval" => "trim",
            )
        ),
        "addData" => Array (        
            "exclude" => 1,     
            "label" => "LLL:EXT:pt_gsaaccounting/locallang_db.xml:tx_ptgsaaccounting_paymentModifier.amount_customer",       
            'config' => array(
                'type' => 'text',
                'cols' => '48', 
                'rows' => '5',
                    )
        ),
    ),
    "types" => array (
        "0" => array("showitem" => "hidden;;1;;1-1-1, orderUid, value, addDataType, addData")
    ),
    "palettes" => array (
        "1" => array("showitem" => "")
    )
);?>