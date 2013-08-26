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
 * TCEmain hook class
 */
class tx_newsfeedimport_tcemain {

	/**
	 * hook to check if a tt_news record was updated and then set a corresponding flag
	 */
	public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$reference) {

			// Mark the tt_news record as edited, if a "edited field" has changed
		if ($table === 'tt_news' && $status === 'update') {
			$existingData = t3lib_befunc::getRecord('tt_news', $id);

				// Get list of fields to check for modification.
				// These are set in the Extension Manager.
			$changeableFields = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['newsfeedimport']['edited_fields']);

			foreach ($changeableFields as $field) {
				if (isset($fieldArray[$field]) && $fieldArray[$field] !== $existingData[$field]) {
					$fieldArray['tx_newsfeedimport_edited'] = 1;
				}
			}
		}
	}
}

if (defined('TYPO3_MODE') && isset($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/newsfeedimport/Classes/Tcemain.php'])) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/newsfeedimport/Classes/Tcemain.php']);
}

?>
