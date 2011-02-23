<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Dorit Rottner (rottner@punkt.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Main dta Handling module for the 'pt_gsaaccounting' extension.
 *
 * $Id$
 *
 * @author	Dorit Rottner <rottner@punkt.de>
 */


#require_once(PATH_tslib.'class.tslib_pibase.php');
/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_gsashop').'res/class.tx_ptgsashop_gsaTransactionAccessor.php';  // GSA accounting Transaction Accesor class
require_once 'Console/Getopt.php';  // PEAR Console_Getopt: parsing params as options (see http://pear.php.net/manual/en/package.console.console-getopt.php)
require_once('Payment/DTA.php');    // PEAR Payment_DTA: generate DTAUS file for credit and debit

require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_cliHandler.php';


/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionHandler.php';  // GSA accounting Transaction Handler class
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php';  // GSA accounting Transaction Accesor class
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_dtabuchrel.php'; // TYPO3 dtabuchrel specific stuff
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_dtabuchrelAccessor.php'; // TYPO3 dtabuchrel Accessor specific stuff
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_dtabuchrelCollection.php'; // TYPO3 dtabuchrelCollection specific stuff
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_dtabuch.php'; // GSA dtabuch specific stuff
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/class.tx_ptgsaaccounting_dtabuchCollection.php'; // GSA dtabuchCollection specific stuff
require_once t3lib_extMgm::extPath('pt_gsaaccounting').'res/staticlib/class.tx_ptgsaaccounting_div.php';  // static Methods of extension pt_gsaaccounting

class tx_ptgsaaccounting_dta  {

    /**
     * Constants
     */
    const PRECISSION = 4;
    const EXT_KEY     = 'pt_gsaaccounting';               // (string) the extension key
    const LL_FILEPATH = 'cronmod/locallang.xml';           // (string) path to the locallang file to use within this class
	

    /**
     * Poperties
     */
    public $prefixId = 'tx_ptgsaaccounting_dta';		// Same as class name
	public $scriptRelPath = 'cronmod/class.tx_ptgsaaccounting_dta.php';	// Path to this script relative to the extension dir.
	public $extKey = 'pt_gsaaccounting';	// The extension key.
	/**
	 * [Put your description here]
	 */
    protected $command;
    protected $useDueDate = true;
    protected $filename = 'DTAUS';
    protected $path = '';
    protected $bookDate = '';
    protected $testMode = false;
    protected $hbciId = '';
    protected $invoiceDocNo = '';
    protected $amount = '';

    protected $importCharset = 'ISO-8859-1'; // (string) Charset used by datdafiles to import 
    protected $siteCharset = 'UTF-8';     // (string) Charset used by the website (TYPO3 FE and BE) (this property has no effect if $charsetConvEnabled is set to 0)

    
    /***************************************************************************
    *   CONSTRUCTOR & RUN METHOD
    ***************************************************************************/
    
    /**
     * Class constructor: define CLI options, set class properties 
     *
     * @param   void
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-29
     */
    public function __construct() {
            
            // for TYPO3 3.8.0+: enable storage of last built SQL query in $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery for all query building functions of class t3lib_DB
            $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
            //echo 'TYPO3_MODE: '.TYPO3_MODE;                        
            // define command line options
            $this->shortOptions = 'hc:f:p:tnd:b:'; // (string) short options for Console_Getopt  (see http://pear.php.net/manual/en/package.console.console-getopt.intro-options.php)
            $this->longOptionsArr = array('help', 'command=',  'filename=', 'path=', 'test', 'noduedate', 'bookdate='); // (array) long options for Console_Getopt (see http://pear.php.net/manual/en/package.console.console-getopt.intro-options.php)
            $this->helpString = "Availabe options:\n".
                                "-h/--help      Help: this list of available options\n".
                                "-c/--command   Name of command to process (required):\n".
                                "               e.g. generate_dta, book_dta ...\n".
                                "-f/--filename  Name of DTAUS File without path (used and required for command 'book_dta')\n".
                                "-p/--Path      path of DTAUS File (optional)\n".
                                "-b/--hbciId    Id of the related hbci Record (used in command book_dta to write relations) if not specified no entries to hbci relation table are generated.\n".
                                "-d/--date      Booking date (optional used for command 'book_dta') if not specified Date of today is used\n".
                                "-t/--test      Command runs in TEST mode (optional)\n".
                                "-n/--noduedate Do not use duedate for selecting DTABUCH Records (used for command 'generate_data')\n".
                                "\n";
            
            // start script output
            echo "\n".
                 "---------------------------------------------------------------------\n".
                 "CLI DTA processing started...\n".
                 "---------------------------------------------------------------------\n";
                
            // get extension configuration configured in Extension Manager (from localconf.php) - NOTICE: this has to be placed *before* the first call of $this->cliHandler->cliMessage()!!
            $this->extConfArr = tx_pttools_div::returnExtConfArray($this->extKey);
            if (!is_array($this->extConfArr)) {
                fwrite(STDERR, "[ERROR] No extension configuration found!\nScript terminated.\n\n");
                die();
            }
            
            // invoke CLI handler with extension configuration
            $this->cliHandler = new tx_pttools_cliHandler($this->scriptName, 
                                                          $this->extConfArr['cliAdminEmailRecipient'],
                                                          $this->extConfArr['cliEmailSender'], 
                                                          $this->extConfArr['cliHostName'],
                                                          $this->extConfArr['cliQuietMode'],
                                                          $this->extConfArr['cliEnableLogging'],
                                                          $this->extConfArr['cliLogDir']
                                                         );
            $this->cliHandler->cliMessage('Script initialized', false, 2, true); // start new audit log entry
            $this->cliHandler->cliMessage('$this->extConfArr = '.print_r($this->extConfArr, 1));

            $this->conf = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_ptgsaaccounting.']; // Config of gsaaccounting          
            // dev only
            #fwrite(STDERR, "[TRACE] died: STOP \n\n"); die();
    }
    
    /**
     * Run of the CLI class: executes the business logic 
     * @param   void    
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-06-30
     */
    public function run() {
        $GLOBALS['trace'] = 2;
        $this->gsaAccountingTransactionHandlerObj = new tx_ptgsaaccounting_gsaTransactionHandler();
        try {
            
            $this->processOptions();
            
            switch ($this->command) {
                case 'generate_dta':
                    $this->llArray = $GLOBALS['TSFE']->readLLfile(t3lib_extMgm::extPath(self::EXT_KEY).self::LL_FILEPATH); // get locallang data
                    trace($this->llArray,0,'$this->llArray');
                    $this->generateDta('Einzug');
                    $this->generateDta('Abbuchung');
                    break;
                case 'book_dta':
                    if ($this->filename=='DTAUS') {
                        echo 
                         "---------------------------------------------------------------------\n".
                         "Filename missing. Please specify for which DTA file booking will be executed."."\n".
                         "---------------------------------------------------------------------\n";
                        return false;
                    }
                    trace($this->hbciId,0,'$this->hbciId');
                    if ($this->hbciId) {
                        if(!is_numeric($this->hbciId)) {
                            echo 
                             "---------------------------------------------------------------------\n".
                             "HBCI Id has to be numeric."."\n".
                             "---------------------------------------------------------------------\n";
                            return false;
                        }
                    }
                    $this->bookDta();
                    break;
                default:
                    $this->cliHandler->cliMessage("Invalid command '".$this->command."'.", true, 1);
                    echo 
                     "---------------------------------------------------------------------\n".
                     "Invalid command ".$this->command."\n".
                     "---------------------------------------------------------------------\n";
                    return false;
            }
            
        } catch (tx_pttools_exception $excObj) {
            
            // if an exception has been catched, handle it and display error message
            $this->cliHandler->cliMessage($excObj->__toString()."\n".$excObj->returnTraceString(), true, 1);
            
        }
        
    }
    
    
    
    /***************************************************************************
    *   BUSINESS LOGIC METHODS
    ***************************************************************************/
    
    /** 
     * Processes the command line arguments as options and sets the resulting class properties
     *
     * @param   void
     * @return  void       
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-28
     */
    private function processOptions() {
        trace('[METHOD] '.__METHOD__);
        
        
        $console = new Console_Getopt;  // PEAR module (see http://pear.php.net/manual/en/package.console.console-getopt.php)
        $parsedOptionsArr = $this->cliHandler->getOptions($console, $this->shortOptions, $this->helpString, $this->longOptionsArr, true);
        
        // set default Date
        $this->bookDate = date('Y-m-d');
        // evaluate options, set properties
        for ($i=0; $i<sizeOf($parsedOptionsArr); $i++) {
            if ($parsedOptionsArr[$i][0] == 'h' || $parsedOptionsArr[$i][0] == '--help') {
                die($this->helpString);
            }
            if ($parsedOptionsArr[$i][0] == 'c' || $parsedOptionsArr[$i][0] == '--command') {
                $this->command = $parsedOptionsArr[$i][1];
            }
            if ($parsedOptionsArr[$i][0] == 'f' || $parsedOptionsArr[$i][0] == '--filename') {
                $this->filename = $parsedOptionsArr[$i][1];
            }
            if ($parsedOptionsArr[$i][0] == 'p' || $parsedOptionsArr[$i][0] == '--path') {
                $this->path = $parsedOptionsArr[$i][1];
            }
            if ($parsedOptionsArr[$i][0] == 'b' || $parsedOptionsArr[$i][0] == '--hbciid') {
                $this->hbciId = $parsedOptionsArr[$i][1];
            }
            if ($parsedOptionsArr[$i][0] == 't' || $parsedOptionsArr[$i][0] == '--test') {
                $this->testMode = true;
            }
            if ($parsedOptionsArr[$i][0] == 'd' || $parsedOptionsArr[$i][0] == '--date') {
                trace($parsedOptionsArr[$i][1],0,'$parsedOptionsArr[$i][1]');
                $this->bookDate  = tx_ptgsaaccounting_div::convertDateToMySqlFormat($parsedOptionsArr[$i][1]);
                if ($this->bookDate == '') {
                    throw new tx_pttools_exception('Wrong DateFormat', 3, 'Date '.$parsedOptionsArr[$i][1].' has no valid Format. Allowed Formats are \'Y-m-d\' \'d.m.Y\' \'d.m.y\'');
                }
            }
            if ($parsedOptionsArr[$i][0] == 'n' || $parsedOptionsArr[$i][0] == '--noduedate') {
                $this->useDueDate = false;
            }
        }
        
            
    }
    

    /**
     * generate DTAUS - file and store it in Typo3 DTA-Record  
     * @param   void       
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-06-30
     */
    
    private function generateDta($type){
        trace('[CMD] '.__METHOD__);

        if ($type == 'Einzug') {
            $dta_file = new DTA(DTA_DEBIT);
        } else {
            $dta_file = new DTA(DTA_CREDIT);
        }
        $gsaTransactionAccessorObj = tx_ptgsaaccounting_gsaTransactionAccessor::getInstance();
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();
        
        trace($this->conf,0,'$this->conf');

        $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;  

        /**
        * Set file sender. This is also the default sender for transactions.
        */
        $sender = array(            
            "name"           => $this->conf['shopOperatorName'],
            "bank_code"      => $this->conf['shopOperatorBankCode'],
            "account_number" => $this->conf['shopOperatorAccountNo']
        );
        
        $filename = $this->filename;
        if ($type == 'Abbuchung') {
            $filename = $this->filename . '_CREDIT';
        }    
        $filename .= '_'.date('Y-m-d_H-i-s');
        trace($sender,0,'AccountFileSender');
        $dta_file->setAccountFileSender($sender);
        trace($dta_file->getFileContent(),0,'dtaFileContent');
        
        //get last gsa_uid from GSA-DB for this type
        $gsaUid = tx_ptgsaaccounting_dtabuchrelAccessor::getInstance()->selectLastGsaUid($type);
        if (!$gsaUid) {
            $gsaUid = 0;
        }

        
        // get DTABUCH Records from GSA-DB
        if ($this->useDueDate == true) {
            // Use due Date if Production
            $dtabuchCollection = new tx_ptgsaaccounting_dtabuchCollection($gsaUid,$type,true);
        } else { 
        // don't use dueDate for Testing 
            $dtabuchCollection = new tx_ptgsaaccounting_dtabuchCollection($gsaUid,$type, false);
        }
        trace($dtabuchCollection,0,'$dtabuchCollection');
        
        // add possible reprocessing
        if ($type == 'Einzug') { // only for DEBIT 
            $dtabuchrelCollection = new tx_ptgsaaccounting_dtabuchrelCollection('reprocess');
            trace($dtabuchrelCollection,0,'$dtabuchrelCollection');
            foreach ($dtabuchrelCollection as $dtabuchrel) {
                $dtabuchCollection->addItem(new tx_ptgsaaccounting_dtabuch($dtabuchrel->get_gsaDtabuchUid()), $dtabuchrel->get_gsaDtabuchUid());
                $dtabuchrel->set_reprocess(false);
                $dtabuchrel->storeSelf();
            }            
        }
        
        
        trace($dtabuchCollection,0,'$dtabuchCollection');
        
        
        // for each Record of DTABUCH 
        foreach ($dtabuchCollection as $dtabuch) {
            /**
            * Add transaction.
            */
            trace ($dtabuch,0,'$dtabuch');
            if ($dtabuch->get_bookingSum() && $dtabuch->get_name()) {
                $purpose = '';
                $name = $dtabuch->get_name().$dtabuch->get_name2() ;
                $bankCode = $dtabuch->get_bankCode();

                // find cancellation and creditmemo and subtract it from bookinSum
                $relatedDocNo = $dtabuch->get_purpose();
                if ($type == 'Einzug') {
                $documentArr = $gsaShopTransactionAccessorObj->selectTransactionDocumentData($relatedDocNo);
                    $paymentAmount = $gsaTransactionAccessorObj->selectPaymentAmount($relatedDocNo);
                    $cancellationAmount = $gsaTransactionAccessorObj->selectCancellationAmount($relatedDocNo);
                    $creditMemoAmount = $gsaTransactionAccessorObj->selectCreditMemoAmount($relatedDocNo);
                    $bookingSumPrecise = bcsub($dtabuch->get_bookingSum() ,bcadd($cancellationAmount, bcadd($creditMemoAmount,bcadd($paymentAmount, $documentArr['SKONTOBETRAG'],self::PRECISSION),self::PRECISSION),self::PRECISSION),self::PRECISSION) ;
                    $bookingSum = $dtabuch->get_bookingSum() - $cancellationAmount - $creditMemoAmount - $paymentAmount- $documentArr['SKONTOBETRAG'];
                    trace($bookingSum,0,'$bookingSum');
                    trace($bookingSumPrecise,0,'$bookingSumPrecise');
                } else {
                    $bookingSum = $dtabuch->get_bookingSum();
                }
                if ($bookingSum != $dtabuch->get_bookingSum() && $type == 'Einzug') {
                    $purpose2 = $GLOBALS['TSFE']->getLLL('fl_dtaPurposeDebit', $this->llArray);
                } else if ($type == 'Abbuchung') {
                    $purpose2 = $GLOBALS['TSFE']->getLLL('fl_dtaPurposeCredit', $this->llArray);
                } else {
                    $purpose2 = '.';
                }
                if ($dtabuch->get_purpose2()){ 
                    $purpose = $dtabuch->get_purpose2();
                } else  {
                    // TODO set it in constant editor
                	$purpose = $GLOBALS['TSFE']->getLLL('fl_dtaPurpose', $this->llArray);
                }              
                
                // generate dtabuchrelObj
                $dtabuchrel = new tx_ptgsaaccounting_dtabuchrel();
                $dtabuchrel->set_gsaDtabuchUid($dtabuch->get_dtauid());
                $dtabuchrel->set_transferDate(date('Y-m-d'));
                $dtabuchrel->set_invoiceDate($dtabuch->get_bookingDate());
                $dtabuchrel->set_dueDate($dtabuch->get_dueDate());
                $dtabuchrel->set_bookingAmount($dtabuch->get_bookingSum());
                $dtabuchrel->set_transferAmount($bookingSum);
                $dtabuchrel->set_accountHolder($name);
                $dtabuchrel->set_relatedDocNo($relatedDocNo. $purpose2);
                $dtabuchrel->set_type($type);
                $dtabuchrel->set_purpose($purpose);
                $dtabuchrel->set_bankCode($dtabuch->get_bankCode());
                $dtabuchrel->set_accountNo($dtabuch->get_bankAccount());
                $dtabuchrel->set_filename($filename);
                #$dtabuchrel->set_bookDate('');
                #$dtabuchrel->set_gsaBookDate('');
                $dtabuchrel->set_differenceAmount(0);
                $dtabuchrel->set_reprocess(false);
                if ($documentArr['AUFTRAGOK'] == 1) {
                    trace('Auftrag ok in GSA-DB');
                    $dtabuchrel->set_invoiceOkGsa(true);
                } else {
                    $dtabuchrel->set_invoiceOkGsa(false);
                }
                
                $this->checkBankAccount($dtabuchrel);
                if (strpos($dtabuchrel->get_accountHolder(),'+DROP+') !== false) {
                    $dtabuchrel->set_dropUser(true);
                } else {
                    $dtabuchrel->set_dropUser(false);
                }

                $dtaSum = $this->insertDta($dtabuchrel,$dta_file,$dtaSum);
                trace($dtaSum,0,'$dtaSum');
                trace($dtabuchrel,0,'$dtabuchrel');
                if ($this->testMode == false) {
                    // Insert if cmdmode not test    
                    $dtabuchrel->storeSelf();
                }

                #break;
            }
        }   

        if ($dtaSum) {
            $dtaSum = tx_pttools_finance::getFormattedPriceString($dtaSum);
            $fileNameWithSum = $filename. '_'.(string)$dtaSum; 
            trace($fileNameWithSum,'0','$fileNameWithSum');
            tx_ptgsaaccounting_dtabuchrelAccessor::getInstance()->updateFilename($filename,$fileNameWithSum);
            
            $filename = $this->path.$fileNameWithSum;
            trace($filename,'0','$filename');
    
            // Output DTA-File.
            trace($dta_file->getFileContent(),0,'');

            // Write DTA-File.
            $dta_file->saveFile($filename);
            trace ($this);
        }

    }



    /**
     * get all open DTA Record and book them as payed in GSA-DB  
     * @param   void       
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-11
     */
    
    private function bookDta(){
        trace('[CMD] '.__METHOD__);

       
        $this->gsaAccountingTransactionHandlerObj = new tx_ptgsaaccounting_gsaTransactionHandler();
        $gsaShopTransactionAccessorObj = tx_ptgsashop_gsaTransactionAccessor::getInstance();

        // get DTABUCH Records from Typo3 
        $dtabuchrelCollection = new tx_ptgsaaccounting_dtabuchrelCollection('book',$this->filename, true);
        
        #trace($dtabuchrelCollection,0,'$dtabuchrelCollection');
        
        // for each Record of DTABUCH 
        foreach ($dtabuchrelCollection as $dtabuchrel) {
            /**
            * book as payed in GSA.
            */
            trace ($dtabuchrel,0,'$dtabuchrel');
            
            $relatedDocNo = $dtabuchrel->get_relatedDocNo();
            list($this->invoiceDocNo,$rest) = explode(' ',$relatedDocNo);
            list($this->invoiceDocNo,$rest) = explode('.',$this->invoiceDocNo);
            trace($this->invoiceDocNo,0,'$this->invoiceDocNo'); 
            $dtabuchrel->set_bookDate(date('Y-m-d'));
            $dtabuchrel->set_gsaBookDate($this->bookDate);
            if ($this->invoiceDocNo) {
                
                $documentArr = $gsaShopTransactionAccessorObj->selectTransactionDocumentData($this->invoiceDocNo);
        
                #trace($documentArr,0,'$documentArr');
                $outstandingAmount = $documentArr['ENDPRB'] - $documentArr['GUTSUMME'] - $documentArr['BEZSUMME']-$documentArr['SKONTOBETRAG'];
                $differenceAmount = bcsub($dtabuchrel->get_transferAmount(),$outstandingAmount, self::PRECISSION);
                trace($outstandingAmount,0,' $outstandingAmount');
                trace($differenceAmount,0,' $differenceAmount');
                trace($dtabuchrel->get_transferAmount(),0,' transferAmount');
                if ($documentArr['AUFTRAGOK'] == 1) {
                    trace('Auftrag ok in GSA-DB');
                    $dtabuchrel->set_invoiceOkGsa(true);
                } else {
                    $dtabuchrel->set_invoiceOkGsa(false);
                }
                if ($differenceAmount <= 0 ) {
                    $this->amount = $dtabuchrel->get_transferAmount();
                    trace('korrekte Bezahlung');
                } else {
                    // Difference Betrag
                    $this->amount = $outstandingAmount;
                    $dtabuchrel->set_differenceAmount($differenceAmount);
                    trace($differenceAmount,0,'Difference Amount');
                } 
                $rturnHook = true;
                trace($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting'],0,'TYPO3_CONF_VARS][EXTCONF][pt_gsaaccounting]');
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['dta_hooks']['bookDta_setRelation'])) {
                    foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsaaccounting']['dta_hooks']['bookDta_setRelation'] as $className) {
                        trace($className,0,'$className');
                        $hookObj = &t3lib_div::getUserObj($className); // returns an object instance from the given class name
                        //trace($hookObj,0,'Call hook');
                        $returnHook = $hookObj->bookDta_setRelation($this->invoiceDocNo, $this->hbciId, $this->amount, $this->testMode);
                    }
                }
                
                if($returnHook == false) {
                    $dtabuchrel->set_bankRejected(true);
                }
                if ($this->testMode == true) {
                    trace('In testmode');
                } else {
                    trace('dtabuchrel schreiben');
                    // Payment is until now ok
                    if ($returnHook == true) {
                        $dtabuchrel->storeSelf();
                        // Bezahlung verbuchen
                        if ($documentArr['AUFTRAGOK'] != 1) {
                            trace('Bezahlung buchen');
                            // GSA-DB allows only 40 charcters so short it
                            $additonalNote = str_replace('DTAUS_','',str_replace('-','',$dtabuchrel->get_filename()));
                            $this->gsaAccountingTransactionHandlerObj->bookPayment($this->invoiceDocNo, $this->amount,0,false, $this->bookDate,$additonalNote);
                        }

                    }
                }
                trace($dtabuchrel,0,'$dtabuchrel gebucht');
                
                #break;
            }
        }   
        trace ($this);
    }



    /**
     * insert in Dta  
     * @param   object      Dtabuchrel 
     * @param   object      Object for dta file
     * @param   double      bookingSum for all DTA's in the File before inserting
     * @return  double      bookingSum for all DTA's in the File after inserting   
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-09-27
     */
    private function insertDta($dtabuchrel,$dta_file,$dtaSum) {
        $name = iconv($this->siteCharset, 'ISO-8859-1', $dtabuchrel->get_accountHolder());
        if ($dtabuchrel->get_transferAmount() >0 && $dtabuchrel->get_dropUser() == false && $dtabuchrel->get_invoiceOkGsa() == false) {
            $dtaSum = bcadd($dtaSum,$dtabuchrel->get_transferAmount(),self::PRECISSION);
            trace('Insert in DTA'); 
            $dta_file->addExchange(
                array(
                    "name"           => "$name",                          // Name of account owner.
                    "bank_code"      => $dtabuchrel->get_bankCode(),                      // Bank code.
                    "account_number" => $dtabuchrel->get_accountNo(),        // Account number.
                ),
                $dtabuchrel->get_transferAmount(),
                #$bookingSum,                                      // Amount of money.
                array(                                      // Description of the transaction ("Verwendungszweck").
                    $dtabuchrel->get_relatedDocNo(),
                    #"$relatedDocNo$purpose2",
                    $dtabuchrel->get_purpose()
                    #"$purpose"
                )
            );

        } else {
            $dtabuchrel->set_transferDate('');
        }
        return $dtaSum;
    }

    /**
     * check Bank Account of Dtabuchrel with command ktoblzcheck on this host  
     * @param   object      dtabuchrel 
     * @return  string      ErrorMsg if Bankaccount is not valid 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-15
     */
    private function checkBankAccount($dtabuchrel) {
        trace('[METHOD] '.__METHOD__);
        $cmd = '/usr/local/bin/'.
            'ktoblzcheck '.
            $dtabuchrel->get_bankCode(). ' '.
            $dtabuchrel->get_accountNo();
        trace ($cmd,0,'$cmd');
        $resultArr = array();
        exec($cmd, $resultArr);
        trace ($resultArr,0,'$result in'.__METHOD__);
        if (substr($resultArr[2],0,strlen('Result is: ')) == 'Result is: ') {
            $returncode = intval(substr($resultArr[2],strlen('Result is: ('),1));
            switch ($returncode) {
                case 0:
                    // Bank verification ok
                    $errorMsg = '';
                    $dtabuchrel->set_bankaccountCheck(true);
                    break;
                case 1:
                    // Unknown, e.g. checksum not implemented or such
                    $errorMsg = '';
                    $dtabuchrel->set_bankaccountCheck(false);
                    break;
                case 2;
                    // Account and/or bank not ok
                    $errorMsg = 'Kontonummer und BLZ stimmen nicht überein.';
                    $dtabuchrel->set_bankaccountCheck(false);
                    break;
                case 3;
                    // Bank not found
                    $errorMsg = 'BLZ nicht gefunden.';
                    $dtabuchrel->set_bankaccountCheck(false);
                    break;
            }
            
        } else {
            // should never happen
            $errorMsg = 'Fehler beim Aufruf';
        }
        trace($errorMsg,0,'$errorMsg');
        return $errorMsg;
    }



}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/cronmod/class.tx_ptgsaaccounting_dta.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsaaccounting/cronmod/class.tx_ptgsaaccounting_dta.php']);
}

?>
