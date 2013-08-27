<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$TCA['tx_newsfeedimport_feeds'] = array(
	'ctrl' => $TCA['tx_newsfeedimport_feeds']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden,starttime,endtime,title,url,errors,errors_count,targetpid'
	),
	'feInterface' => $TCA['tx_newsfeedimport_feeds']['feInterface'],
	'columns' => array(
		'hidden' => array(
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => array(
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array(
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0,0,0,12,31,2020),
					'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
				)
			)
		),
		'title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.title',
			'config' => array(
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'url' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.url',
			'config' => array(
				'type' => 'input',
				'size' => '45',
				'max' => '255',
				'checkbox' => '',
				'eval' => 'trim',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)
			)
		),
		'targetpid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.targetpid',
			'config' => array(
				'type' => 'group',	
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => array(
					'suggest' => array(    
						'type' => 'suggest',
					),
				),
			)
		),
		'overrideedited' => array(
			'exclude' => 1,	
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.overrideedited',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'importimages' => array(
			'exclude' => 1,	
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.importimages',
			'config' => array(
				'type' => 'check',
				'default' => '1'
			)
		),
		'default_hidden' => array(
			'exclude' => 1,	
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.default_hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'default_type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.default_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:tt_news/locallang_tca.php:tt_news.type.I.0', 0),
					// array('LLL:EXT:tt_news/locallang_tca.php:tt_news.type.I.1', 1),
					array('LLL:EXT:tt_news/locallang_tca.php:tt_news.type.I.2', 2),
				),
				'default' => 0
			)
		),
		'default_extension' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.default_extension',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.default_extension.I.0', 0),
					// array('LLL:EXT:tt_news/locallang_tca.php:tt_news.type.I.1', 1),
					array('LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.default_extension.I.2', 2),
				),
				'default' => 0
			)
		),
		'default_categories' => array(
			'exclude' => 1,
			// 'l10n_mode' => 'exclude', // the localizalion mode will be handled by the userfunction
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.default_category',
			'config' => array(
				'type' => 'select',
				'form_type' => 'user',
				// 'userFunc' => 'tx_ttnews_treeview->displayCategoryTree', // Function in tt_news less than 3.x
				'userFunc' => 'tx_ttnews_TCAform_selectTree->renderCategoryFields', // Function in tt_news from 3.x and onwards
				'treeView' => 1,
				'foreign_table' => 'tt_news_cat',
				// 'foreign_table_where' => $fTableWhere.'ORDER BY tt_news_cat.'.$confArr['category_OrderBy'],
				'size' => 3,
				'autoSizeMax' => 25,
				'minitems' => 0,
				'maxitems' => 500,
				// 'MM' => 'tt_news_cat_mm',
			)
		),
		'default_author' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.default_author',
			'config' => array(
				'type' => 'input',	
				'size' => '28'
			)
		),
		'default_authoremail' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:feeds.default_authoremail',
			'config' => array(
				'type' => 'input',	
				'size' => '15'
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'title, startstoptime, url, targetpid, overrideedited, importimages, default_hidden, default_type, default_extension, default_categories, --palette--;Default Values;defaultvalues, --div--; ,')
	),
	'palettes' => array(
		'startstoptime' => array(
			'showitem' => 'hidden, starttime, endtime'
		),
		'defaultvalues' => array(
			'showitem' => 'default_author, default_authoremail',
			'canNotCollapse' => 1
		)
	)
);
?>
