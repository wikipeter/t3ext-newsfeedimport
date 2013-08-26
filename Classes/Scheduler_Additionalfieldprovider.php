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
 * Aditional fields provider class for usage with the EXT:newsfeedimport
 *
 * @package		TYPO3
 * @subpackage	newsfeedimport
 */
class Tx_Newsfeedimport_Scheduler_Additionalfieldprovider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * This method is used to define new fields for adding or editing a task
	 * In this case, it adds a feed field
	 *
	 * @param	array	$taskInfo: reference to the array containing the info used in the add/edit form
	 * @param	object	$task: when editing, reference to the current task object. Null when adding.
	 * @param	tx_scheduler_Module	$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	array	Array containg all the information pertaining to the additional fields
	 *			The array is multidimensional, keyed to the task class name and each field's id
	 *			For each field it provides an associative sub-array with the following:
	 *				['code']		=> The HTML code for the field
	 *				['label']		=> The label of the field (possibly localized)
	 *				['cshKey']		=> The CSH key for the field
	 *				['cshLabel']	=> The code of the CSH label
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {

			// Initialize extra field value
		if (empty($taskInfo['feed'])) {
			if ($parentObject->CMD == 'add') {
				// In case of new task and if field is empty,
				// set default sleep time
				$taskInfo['feed'] = 0;
			} elseif ($parentObject->CMD == 'edit') {
				// In case of edit, set to internal
				// value if no data was submitted already
				$taskInfo['feed'] = $task->feed;
			} else {
				// Otherwise set an empty value,
				// as it will not be used anyway
				$taskInfo['feed'] = '';
			}
		}

		// write the code for the field
		$fieldID = 'task_feed';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
			'tx_newsfeedimport_feeds',
			'deleted = 0'
		);
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$selected = ($row['uid'] == $taskInfo['feed']) ? ' selected="selected" ' : '';
				$options .= '<option ' . $selected . 'value="' . $row['uid'] . '">' . $row['title'] . '</option>';
				
			}
		} else {
			// TODO: db problem
		}
		
			// no options
		if ($options == '') {
			$parentObject->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:scheduler.norecord'),
				t3lib_FlashMessage::ERROR
			);
		}
		else {
			$fieldCode = '<select name="tx_scheduler[feed]" size="1" id="' . $fieldID . '" >' . $options . '</select>';
			$additionalFields = array();
			$additionalFields[$fieldID] = array(
				'code'     => $fieldCode,
				'label'    => 'LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:scheduler.record',
				'cshKey'   => '_MOD_tools_txschedulerM1',
				'cshLabel' => $fieldID
			);
		}

		return $additionalFields;
	}

	/**
	 * This method checks any additional data that is relevant to the specific task
	 * If the task class is not relevant, the method is expected to return true
	 *
	 * @param	array					$submittedData: reference to the array containing the data submitted by the user
	 * @param	tx_scheduler_Module		$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	boolean					True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {
		$submittedData['feed'] = trim($submittedData['feed']);

		if (empty($submittedData['feed'])) {
			$parentObject->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:newsfeedimport/Resources/Private/Language/db.xml:scheduler.norecord'),
				t3lib_FlashMessage::ERROR
			);
			$result = FALSE;
		} else {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * This method is used to save any additional input into the current task object
	 * if the task class matches
	 *
	 * @param	array				$submittedData: array containing the data submitted by the user
	 * @param	tx_scheduler_Task	$task: reference to the current task object
	 * @return	void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->feed = $submittedData['feed'];
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/newsfeedimport/Classes/Scheduler_Additionalfieldprovider.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/newsfeedimport/Classes/Scheduler_Additionalfieldprovider.php']);
}

?>