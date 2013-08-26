<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

	// register hook in tcemain
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:newsfeedimport/Classes/Tcemain.php:tx_newsfeedimport_tcemain';

    // unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['edited_fields'] = isset($_EXTCONF['edited_fields']) ? trim($_EXTCONF['edited_fields']) : '';


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_newsfeedimport_scheduler'] = array(
	'extension'        => 'newsfeedimport',
	'title'            => 'RSS/Atom Feed Importer',
	'description'      => 'Automates the import of the RSS/Atom Feeds',
	'additionalFields' => 'tx_newsfeedimport_scheduler_additionalfieldprovider'
);

?>