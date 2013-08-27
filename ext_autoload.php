<?php
/*
 * Register necessary class names with autoloader
 */
$extensionPath = t3lib_extMgm::extPath('newsfeedimport');
return array(
	'tx_newsfeedimport_import' => $extensionPath . 'Classes/Import.php',
	'tx_newsfeedimport_scheduler' => $extensionPath . 'Classes/Scheduler.php',
	'tx_newsfeedimport_scheduler_additionalfieldprovider'	=> $extensionPath . 'Classes/Scheduler_Additionalfieldprovider.php'
);

?>