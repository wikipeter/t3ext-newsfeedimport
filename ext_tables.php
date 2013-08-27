<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$TCA['tx_newsfeedimport_feeds'] = array(
	'ctrl' => array(
		'title'  => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds',
		'label'  => 'title',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',
		'dividers2tabs' => TRUE,
		'delete' => 'deleted',	
		'enablecolumns' => array(
			'disabled'  => 'hidden',	
			'starttime' => 'starttime',	
			'endtime'   => 'endtime',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/Tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/feed.gif',
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'hidden, starttime, endtime, title, url, errors, errors_count, target',
	)
);

$tempColumns = array(
	'tx_newsfeedimport_feed' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:tt_news.feed',
		'config' => array(
			'type' => 'input',
			'size' => '2',
			'readOnly' => 1
		)
	),
	'tx_newsfeedimport_guid' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:tt_news.guid',
		'config' => array(
			'type' => 'input',
			'size' => '30',
			'readOnly' => 1
		)
	),
	'tx_newsfeedimport_edited' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:tt_news.edited',
		'config' => array(
			'type' => 'check',
			'default' => '0',
			'readOnly' => 1
		)
	),
);

t3lib_div::loadTCA('tt_news');
t3lib_extMgm::addTCAcolumns('tt_news', $tempColumns, 1);
#t3lib_extMgm::addToAllTCAtypes('tt_news','tx_newsfeedimport_uid;;;;1-1-1, tx_newsfeedimport_edited');

$tempColumns = array(
	'tx_newsfeedimport_feed' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:tt_news.feed',
		'config' => array(
			'type' => 'input',
			'size' => '2',
			'readOnly' => 1
		)
	),
	'tx_newsfeedimport_guid' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:tt_news.guid',
		'config' => array(
			'type' => 'input',
			'size' => '30',
			'readOnly' => 1
		)
	),
	'tx_newsfeedimport_edited' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:tt_news.edited',
		'config' => array(
			'type' => 'check',
			'default' => '0',
			'readOnly' => 1
		)
	),
);

t3lib_div::loadTCA('tx_news_domain_model_news');
t3lib_extMgm::addTCAcolumns('tx_news_domain_model_news', $tempColumns, 1);

?>