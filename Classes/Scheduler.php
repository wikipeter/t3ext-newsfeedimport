<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Benjamin Mack (benni@typo3.org)
 *
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
 * Class "tx_newsfeedimport_scheduler" allows to add a new task
 * to import a specific feed.
 *
 * @package		TYPO3
 * @subpackage	newsfeedimport
 */
class tx_newsfeedimport_scheduler extends tx_scheduler_Task {

	/**
	 * uid of the feed record
	 *
	 * @var	integer	$feed
	 */
	public $feed = NULL;
	
	/**
	 * function executed from the Scheduler.
	 *
	 * @return	void
	 */
	public function execute() {
		$row = t3lib_BEfunc::getRecord('tx_newsfeedimport_feeds', $this->feed);
		if (is_array($row)) {
			$importer = t3lib_div::makeInstance('Tx_Newsfeedimport_Import', $row);
			return $importer->doImportFeed();
		} else {
			return FALSE;
		}
	}

	/**
	 * This method returns additional information metadata
	 * about the feed setting.
	 *
	 * @return	string	Information to display
	 */
	public function getAdditionalInformation() {
		$row = t3lib_BEfunc::getRecord('tx_newsfeedimport_feeds', $this->feed);
		return $GLOBALS['LANG']->sL('LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:scheduler.recordselected') . ': ' . htmlspecialchars($row['title']);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/newsfeedimport/Classes/Scheduler.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/newsfeedimport/Classes/Scheduler.php']);
}

?>