<?php

########################################################################
# Extension Manager/Repository config file for ext: "pt_gsaaccounting"
#
# Auto generated 06-08-2009 11:02
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'GSA Accounting',
	'description' => 'Provides miscellaneous accounting functionality based on a data layer compatible to the German ERP system "GS AUFTRAG Professional". Requires PEAR Console_Getopt and PHP to be compiled with --enable-bcmath!',
	'category' => 'General Shop Applications',
	'author' => 'Dorit Rottner',
	'author_email' => 'rottner@punkt.de',
	'shy' => '',
	'dependencies' => 'pt_tools,pt_gsasocket,pt_gsashop',
	'conflicts' => '',
	'priority' => '',
	'module' => 'cronmod',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.5',
	'constraints' => array(
		'depends' => array(
			'pt_tools' => '0.4.1-',
			'pt_gsasocket' => '0.3.0-',
			'pt_gsashop' => '0.14.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'PHP with --enable-bcmath (THIS IS JUST A HINT, please ignore if your server is correctly configured)' => '',
			'PEAR Console_Getopt (THIS IS JUST A HINT, please ignore if your server is correctly configured)' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:64:{s:9:"ChangeLog";s:4:"f570";s:10:"README.txt";s:4:"ee2d";s:21:"ext_conf_template.txt";s:4:"7a18";s:12:"ext_icon.gif";s:4:"5837";s:17:"ext_localconf.php";s:4:"c4ef";s:14:"ext_tables.php";s:4:"86eb";s:14:"ext_tables.sql";s:4:"36fb";s:38:"icon_tx_ptgsaaccounting_dtabuchrel.gif";s:4:"475a";s:46:"icon_tx_ptgsaaccounting_orderCreditBalance.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"8733";s:7:"tca.php";s:4:"1c48";s:40:"cronmod/class.tx_ptgsaaccounting_dta.php";s:4:"822f";s:50:"cronmod/class.tx_ptgsaaccounting_listgenerator.php";s:4:"a973";s:19:"cronmod/cli_dta.php";s:4:"77f7";s:29:"cronmod/cli_listgenerator.php";s:4:"6408";s:16:"cronmod/conf.php";s:4:"d52b";s:24:"cronmod/faketsfe.inc.php";s:4:"0889";s:21:"cronmod/locallang.xml";s:4:"8f2d";s:14:"doc/DevDoc.txt";s:4:"c5e7";s:19:"doc/wizard_form.dat";s:4:"9d2a";s:20:"doc/wizard_form.html";s:4:"7894";s:36:"pi1/class.tx_ptgsaaccounting_pi1.php";s:4:"deaa";s:17:"pi1/locallang.xml";s:4:"d74e";s:24:"pi1/static/editorcfg.txt";s:4:"7280";s:36:"pi2/class.tx_ptgsaaccounting_pi2.php";s:4:"65c8";s:17:"pi2/locallang.xml";s:4:"cebe";s:24:"pi2/static/editorcfg.txt";s:4:"9a54";s:40:"res/class.tx_ptgsaaccounting_dtabuch.php";s:4:"9672";s:48:"res/class.tx_ptgsaaccounting_dtabuchAccessor.php";s:4:"bbca";s:50:"res/class.tx_ptgsaaccounting_dtabuchCollection.php";s:4:"9d77";s:43:"res/class.tx_ptgsaaccounting_dtabuchrel.php";s:4:"a2f1";s:51:"res/class.tx_ptgsaaccounting_dtabuchrelAccessor.php";s:4:"636f";s:53:"res/class.tx_ptgsaaccounting_dtabuchrelCollection.php";s:4:"41ba";s:54:"res/class.tx_ptgsaaccounting_dueCustomerCollection.php";s:4:"ed3f";s:44:"res/class.tx_ptgsaaccounting_erpDocument.php";s:4:"36d5";s:54:"res/class.tx_ptgsaaccounting_erpDocumentCollection.php";s:4:"5e43";s:44:"res/class.tx_ptgsaaccounting_erpPosition.php";s:4:"397d";s:54:"res/class.tx_ptgsaaccounting_erpPositionCollection.php";s:4:"6ed9";s:55:"res/class.tx_ptgsaaccounting_gsaTransactionAccessor.php";s:4:"18f9";s:54:"res/class.tx_ptgsaaccounting_gsaTransactionHandler.php";s:4:"ec7e";s:51:"res/class.tx_ptgsaaccounting_orderCreditBalance.php";s:4:"a6eb";s:59:"res/class.tx_ptgsaaccounting_orderCreditBalanceAccessor.php";s:4:"a2ed";s:48:"res/class.tx_ptgsaaccounting_paymentModifier.php";s:4:"e74c";s:56:"res/class.tx_ptgsaaccounting_paymentModifierAccessor.php";s:4:"92e2";s:58:"res/class.tx_ptgsaaccounting_paymentModifierCollection.php";s:4:"fc3f";s:30:"res/css/tx_ptgsaaccounting.css";s:4:"e675";s:71:"res/hooks/class.tx_ptgsaaccounting_hooks_ptgsashop_orderPresentator.php";s:4:"73d2";s:58:"res/hooks/class.tx_ptgsaaccounting_hooks_ptgsashop_pi3.php";s:4:"30a8";s:23:"res/hooks/locallang.xml";s:4:"2c42";s:22:"res/js/jquery-1.1.3.js";s:4:"cc7d";s:33:"res/js/jquery.tablesorter.pack.js";s:4:"f737";s:19:"res/js/myAccount.js";s:4:"4c55";s:30:"res/js/outstandingItemsSort.js";s:4:"f5d9";s:20:"res/js/image/asc.gif";s:4:"f8a1";s:19:"res/js/image/bg.gif";s:4:"c01a";s:21:"res/js/image/desc.gif";s:4:"a548";s:33:"res/smarty_tpl/myAccount.tpl.html";s:4:"82ee";s:37:"res/smarty_tpl/orderoverview.tpl.html";s:4:"8160";s:43:"res/smarty_tpl/outstandingItemList.tpl.html";s:4:"901e";s:44:"res/smarty_tpl/outstandingPositions.tpl.html";s:4:"bdcf";s:34:"res/smarty_tpl/searchForm.tpl.html";s:4:"3887";s:46:"res/staticlib/class.tx_ptgsaaccounting_div.php";s:4:"6128";s:20:"static/constants.txt";s:4:"23c2";s:16:"static/setup.txt";s:4:"8397";}',
	'suggests' => array(
	),
);

?>