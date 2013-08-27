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
 * workflow: 
 * 0. find the newsfeedimport records, and get the URLs out of it (outside this class)
 * 1. parse the feed of each import item, and make db records (incl. Metadata) out of it
 *   2. (optional) resolve categories each record
 * 3. check against the DB if the import needs to happen
 *   4. (optional) add categories for the new records to the DB
 *   5. import non-existant records into DB
 *      6. import pictures into the right place
 *   7. make the proper connections of the records to the categories
 **/
class Tx_Newsfeedimport_Import {
	const NEWSTYPE_NEWS = 0;
	const NEWSTYPE_EXTERNAL = 2;

	/**
	 * the TYPO3 DB record of the tx_newsfeedimport item
	 * containing the URL, mapping, configuration stuff etc
	 */
	protected $feedImportRecord = NULL;
	
	/**
	 * the SimplePie object
	 */
	protected $feedObj = NULL;

	// category handling
	protected $existingCategories = array();
	protected $feedCategories = array();


	protected $feedPid = NULL;
	protected $newsPid = NULL;
	protected $categoryPid = NULL;

	public function __construct($feedImportRecord) {

		$this->feedImportRecord = $feedImportRecord;

		if ($this->feedImportRecord['default_extension'] == 0) {

			t3lib_div::loadTCA('tt_news');

		} elseif ($this->feedImportRecord['default_extension'] == 2) {
			t3lib_div::loadTCA('tx_news_domain_model_news');
			t3lib_div::loadTCA('tx_news_domain_model_link');
			t3lib_div::loadTCA('tx_news_domain_model_media');
		}

		// check if notificationmails need to be sent
		if ($this->feedImportRecord['emailnotification']) {
			$sendNotification = TRUE;
		}

		// load the feed URL
		require_once('SimplePie/autoloader.php');
		$this->feedObj = new SimplePie();
		$this->feedObj->set_feed_url('https://www.facebook.com/feeds/page.php?id=167595049958235&format=rss20');
		$this->feedObj->force_feed(TRUE);
/* 		$this->feedObj->set_cache_duration(3600); */
		$this->feedObj->set_cache_duration(1);
		$this->feedObj->set_cache_location(PATH_site . 'typo3temp');
		$this->feedObj->init();

		$this->feedPid = intval($this->feedImportRecord['pid']);
		$this->newsPid = intval($this->feedImportRecord['targetpid']);
		$this->feedExtension = intval($this->feedImportRecord['default_extension']);

		if (!$this->newsPid) {
			$this->newsPid = $this->feedPid;
		}
		$this->categoryPid = intval($this->feedImportRecord['targetpid']);
		if (!$this->categoryPid) {
			$this->categoryPid = $this->feedPid;
		}

		// get all existing categories
		$this->loadAllExistingCategories();
	}
	
	/**
	 * does the magic, called from outside
	 */
	public function doImportFeed() {
		$feedItems = $this->feedObj->get_items();

		if (count($feedItems) > 0) {

			// Temporarily set high power...
			$oldAdmin = $GLOBALS['BE_USER']->user['admin'];
			$GLOBALS['BE_USER']->user['admin'] = 1;

			// make different queries for tt_news and news
			if ($this->feedExtension == 0) {
				// disable all news items in this storage folder
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tt_news',
					'tx_newsfeedimport_guid != "" AND tx_newsfeedimport_feed = ' . intval($this->feedImportRecord['uid']) . ' AND pid = ' . intval($this->newsPid) . t3lib_BEfunc::deleteClause('tt_news'),
					array(
						'hidden' => 1
					)
				);
			} elseif ($this->feedExtension == 2) {
				// disable all news items in this storage folder
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tt_news',
					'tx_newsfeedimport_guid != "" AND tx_newsfeedimport_feed = ' . intval($this->feedImportRecord['uid']) . ' AND pid = ' . intval($this->newsPid) . t3lib_BEfunc::deleteClause('tx_news_domain_model_news'),
					array(
						'hidden' => 1
					)
				);
			}

			// set a flag that no notificationmail has been sent by now
			$notificationMailSent = FALSE;

			foreach ($feedItems as $feedItem) {

					// deal with categories
				$categories = $feedItem->get_categories();
				if (is_array($categories)) {
					foreach ($categories as $cat) {
						$this->feedCategories[$cat->get_label()] = $cat->get_label();
					}
				}
				
					// filter out which items need to be updated (or which are there already)
				if (!$this->isItemAlreadyImported($feedItem) || $this->feedImportRecord['overrideedited']) {
					// If you quote this in, the cronjob will not run through: echo 'Importing ' . $feedItem->get_title() . CRLF;
					$this->importItem($feedItem);

					if ($sendNotification && !$notificationMailSent) {
						$receivers = explode(',', $this->feedImportRecord['notificationreceivers']);

						foreach ($receivers as $receiver) {
							if ($receiver) {
								$message = (new \TYPO3\CMS\Core\Mail\MailMessage())
									->setFrom(array('info@breuningeradministration.de' => 'Breuninger System'))
									->setTo(array($receiver => 'Redakteur'))
									->setSubject($this->feedImportRecord['notificationmailsubject'])
									->setBody($this->feedImportRecord['notificationmailtext']);
								$message->send();
							}
						}

						$notificationMailSent = TRUE;
					}
				}
			}

			// Temporarily set high power - unset it again
			$GLOBALS['BE_USER']->user['admin'] = $oldAdmin;
		}

		return TRUE;
	}

	protected function importItem($feedItem) {
		$dbRecordId = $this->getNewsIdFromFeedItem($feedItem);
		if (!$dbRecordId) {
			$isNewRecord = TRUE;
		} else {
			$isNewRecord = FALSE;
		}

			// insert into DB
		$rec = array(
			'pid'       => $this->newsPid,
			'hidden'    => ($this->feedImportRecord['default_hidden'] ? '1' : '0'),
			'type'      => ($this->feedImportRecord['default_type'] ? intval($this->feedImportRecord['default_type']) : '0'),
			'title'     => trim(htmlspecialchars_decode($feedItem->get_title())),
			'short'     => trim(htmlspecialchars_decode($feedItem->get_description())),
			'datetime'  => $feedItem->get_date('U'),
			'bodytext'  => trim(htmlspecialchars_decode($feedItem->get_content())),
			'author'       => ($feedItem->get_author() ? $feedItem->get_author()->get_name() : ''),
			'author_email' => ($feedItem->get_author() ? $feedItem->get_author()->get_email() : ''),
			'tx_newsfeedimport_guid' => $feedItem->get_id(),
			'tx_newsfeedimport_feed' => $this->feedImportRecord['uid'],
			'datetime' => strtotime($feedItem->get_date())
		);

			// add more default values
		if ($isNewRecord && empty($rec['author']) && empty($rec['author_email'])) {
			$rec['author'] = $this->feedImportRecord['default_author'];
			$rec['author_email'] = $this->feedImportRecord['default_authoremail'];
		}

			// add the links
		$linkData = '';
		$additionalLinks = $feedItem->get_item_tags('http://www.w3.org/2005/Atom', 'link');
		foreach ($additionalLinks as $lnk) {
			if ($lnk['attribs']['']['rel'] == 'related') {
				$linkData .= $lnk['attribs']['']['href'] . ($lnk['attribs']['']['title'] ? ',' . $lnk['attribs']['']['title'] : '') . CRLF;
			}
		}
		
		if ($linkData) {
			$rec['links'] = trim($linkData);
		}


			// use TCEmain to store the record
		if (!$isNewRecord) {
		
			unset($rec['pid']);
			// unset($rec['hidden']);
			$rec['hidden'] = '0';
			unset($rec['type']);
			unset($rec['tx_newsfeedimport_guid']);
			unset($rec['tx_newsfeedimport_feed']);
		
				// update the record
			$data = array();

			// use the table regarding to the extension chosen
			if ($this->feedExtension == 0) {
				$data['tt_news'][$dbRecordId] = $rec;
			} elseif ($this->feedExtension == 2) {
				$data['tx_news_domain_model_news'][$dbRecordId] = $rec;
			}

			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
			$tce->dontProcessTransformations = 1;
			$tce->start($data, array());
			$tce->process_datamap();
		
		} else {
				// add a new record
			$tcemainId = uniqid();
			$data = array();

			// if we have the extension news create an a new tx_news_domain_model_link
			if ($this->feedExtension == 2) {
				if ($isNewRecord) {
					if ($feedItem->get_link()) {
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_news_domain_model_link', array('uri' => $feedItem->get_link()));
						$insertId = $GLOBALS['TYPO3_DB']->sql_insert_id();
					}
					if ($insertId > 0) {
						$rec['related_links'] = $insertId;
					}
				}
			}

			if ($this->feedExtension == 0) {
				$data['tt_news']['NEW' . $tcemainId] = $rec;
			} elseif ($this->feedExtension == 2) {
				$data['tx_news_domain_model_news']['NEW' . $tcemainId] = $rec;
			}

			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
			$tce->dontProcessTransformations = 1;
			$tce->start($data, array());
			$tce->process_datamap();
	
				// get record ID
			$newIds = $tce->substNEWwithIDs;
			$dbRecordId = $newIds['NEW' . $tcemainId];
		}

		// connect categories
		$categories = $feedItem->get_categories();
		$categoryIds = $this->resolveCategoryRecords($categories);
		
		foreach ($categoryIds as $catId) {
			$addCategoryRelationship = TRUE;
			if (!$isNewRecord) {
				// check if the newsrecord was updated but nothing happened on the category relationship
				// different queries for the possible chosen extensions

				if ($this->feedExtension == 0) {
					$checkRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid_local',
						'tt_news_cat_mm',
						'uid_local = ' . intval($dbRecordId) . ' AND uid_foreign = ' . $catId
					);
				} elseif ($this->feedExtension == 2) {
					$checkRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid_local',
						'tx_news_domain_model_news_category_mm',
						'uid_local = ' . intval($dbRecordId) . ' AND uid_foreign = ' . $catId
					);
				}

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($checkRes) > 0) {
					$addCategoryRelationship = FALSE;
				}
			}
			
			if ($addCategoryRelationship) {
				$insertData = array(
					'uid_local' => $dbRecordId,
					'uid_foreign' => $catId
				);

				if ($this->feedExtension == 0) {
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_news_cat_mm', $insertData);
				} elseif ($this->feedExtension == 2) {
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_news_domain_model_news_category_mm', $insertData);
				}

			}
		}

			// download image into the specified upload folder (check for duplicate names)
			// update the DB record
		if ($dbRecordId && $this->feedImportRecord['importimages'] == '1') {
			$this->retrieveImages($feedItem, $dbRecordId, $isNewRecord);
		}

		if ($dbRecordId) {
			$this->retrieveAttachment($feedItem, $dbRecordId);
		}

		return $dbRecordId;
	}

	/**
	 * compare the GUID of the item, (and in the future, possibly if the record has changed)
	 */
	protected function isItemAlreadyImported($feedItem) {
		return ($this->getNewsIdFromFeedItem($feedItem) > 0) ? TRUE : FALSE;
	}


	/**
	 * fetch the ID of a record of the already imported record
	 * 
	 * @param SimplePie_Item $feedItem
	 * @return integer	the uid of the tt_news item
	 */
	protected function getNewsIdFromFeedItem($feedItem) {
		$guid = $feedItem->get_id();
		
		// fetch SQL record with this GUID
		// query according to selected extension
		if ($this->feedExtension == 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'tt_news',
				'pid = ' . $this->newsPid . ' AND tx_newsfeedimport_guid = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($guid, 'tt_news') . t3lib_BEfunc::deleteClause('tt_news')
			);
		} elseif ($this->feedExtension == 2) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'tx_news_domain_model_news',
				'pid = ' . $this->newsPid . ' AND tx_newsfeedimport_guid = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($guid, 'tx_news_domain_model_news') . t3lib_BEfunc::deleteClause('tx_news_domain_model_news')
			);
		}

		if ($guid && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			return $row['uid'];
		} else {
			return 0;
		}
	}


	/**
	 * takes the category information 
	 */
	protected function resolveCategoryRecords($categories) {
		$categoryIds = array();
		if ($this->feedImportRecord['default_categories']) {
			$categoryIds = t3lib_div::trimExplode(',', $this->feedImportRecord['default_categories'], TRUE);
		}
		if (is_array($categories)) {
			foreach ($categories as $category) {
				$name = $category->get_label();
				if (isset($this->existingCategories[$name])) {
					$categoryIds[] = $this->existingCategories[$name];
				} else {
						// add category to DB 
					$insertData = array(
						'pid' => $this->categoryPid,
						// TODO: do we want a parent_category set by default?
						'title' => $name
					);

					if ($this->feedExtension == 0) {
						$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_news_cat', $insertData);
					} elseif ($this->feedExtension == 2) {
						$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_news_domain_model_category', $insertData);
					}

					$categoryId = $GLOBALS['TYPO3_DB']->sql_insert_id();
				
					$this->existingCategories[$name] = $categoryId;
					$categoryIds[] = $categoryId;
				}
			}
		}
		return $categoryIds;
	}
	
	protected function loadAllExistingCategories() {
		$categories = array();

		if ($this->feedExtension == 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tt_news_cat',
				'pid = ' . $this->categoryPid . t3lib_BEfunc::deleteClause('tt_news_cat')
			);
		} elseif ($this->feedExtension == 2) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tx_news_domain_model_category',
				'pid = ' . $this->categoryPid . t3lib_BEfunc::deleteClause('tx_news_domain_model_category')
			);
		}

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// TODO: what about hierarchies?
			$categories[$row['title']] = $row['uid'];
		}
		$this->existingCategories = $categories;
		return $categories;
	}




	/**
	 * download image into the specified upload folder (check for duplicate names)
	 * update the DB record
	 *
	 * @param	string	$feedItem	the SimplePie API class
	 * @param	integer	$dbRecordId	the ID of the news DB record, so
	 * @param 	bool $isNewRecord
	 * @return	void
	 */
	protected function retrieveImages($feedItem, $dbRecordId, $isNewRecord) {
		$images = array();
		$additionalLinks = $feedItem->get_item_tags('http://www.w3.org/2005/Atom', 'link');

		// special treatment for facebook rss feeds
		// we need to crop the link several times to
		// get the fbid, the id of the image
		if (empty($additionalLinks)) {
			$linkToCrop = $feedItem->get_content();
			$linkToCrop = explode('src', $linkToCrop);
			$linkToCrop = $linkToCrop[1];
			$linkToCrop = explode('/', $linkToCrop);

			foreach ($linkToCrop as $linkPart) {
				if (strpos($linkPart, '.jpg') !== FALSE || strpos($linkPart, '.png') !== FALSE || strpos($linkPart, '.gif') !== FALSE) {
					$linkId = $linkPart;
					if (strpos($linkId, ' ')) {
						$linkId = explode(' ', $linkId);
						$linkId = $linkId[0];
					}
				}
			}

			$finalLinkId = str_replace('_s', '_n', $linkId);

			$finalImageLink = 'http://sphotos-c.ak.fbcdn.net/hphotos-ak-ash3/s720x720/' . $finalLinkId ;

			$images[]['href'] = $finalImageLink;
		}

		foreach ($additionalLinks as $lnk) {
			if ($lnk['attribs']['']['rel'] == 'image') {
				$images[] = $lnk['attribs'][''];
			}
		}

		if ($this->feedExtension == 0) {
			$destinationPath = PATH_site . $GLOBALS['TCA']['tt_news']['columns']['image']['config']['uploadfolder'] . '/';
		} elseif ($this->feedExtension == 2) {
			$destinationPath = PATH_site . $GLOBALS['TCA']['tx_news_domain_model_media']['columns']['image']['config']['uploadfolder'] . '/';
		}


		$fileFunc = t3lib_div::makeInstance('t3lib_extFileFunctions');
		$fileFunc->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);

		$newImages = array();
		$newImageLabels = array();
		foreach ($images as $imageMetadata) {
			$imageFile = trim($imageMetadata['href'], '"');

				// Only continue if one of following file extensions match
			// $type = explode('/', strtolower($type));
			// if ($type[0] == 'image' && ($type[1] == 'gif' || $type[1] == 'jpeg' || $type[1] == 'png')) {

			// $imageFile = $imageObj->get_src();
			list($prefix, $imageBasename) = t3lib_div::revExplode('/', $imageFile, 2);
			$imageBasename = $fileFunc->cleanFileName($imageBasename);
			$finalFilename = $fileFunc->getUniqueName($imageBasename, $destinationPath);

			// fill the file
			$imageData = t3lib_div::getURL($imageFile);
			t3lib_div::writeFile($finalFilename, $imageData);

				// e.g. Kasper_02.jpg
			$newImages[] = basename($finalFilename);
			$newImageLabels[] = $imageMetadata['title'];
		}
		$data = array();

		if ($isNewRecord) {
			if ($this->feedExtension == 0) {
				$data['tt_news'][$dbRecordId]['image'] = implode(',', $newImages);
				$data['tt_news'][$dbRecordId]['imagetitletext'] = implode(CRLF, $newImageLabels);
			} elseif ($this->feedExtension == 2) {
				$insertData = array(
					'showinpreview' => 1,
					'image' => $imageBasename,
					'parent' => $dbRecordId
				);

				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_news_domain_model_media',$insertData);
				$data['tx_news_domain_model_news'][$dbRecordId]['media'] = 1;
			}
		}


			// store that to the DB
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->dontProcessTransformations = 1;
		$tce->start($data, array());
		$tce->process_datamap();
	}





	/**
	 * download attachments into the specified upload folder (check for duplicate names)
	 * update the DB record
	 *
	 * @param	string	$feedItem	the SimplePie API class
	 * @param	integer	$dbRecordId	the ID of the DB record, so 
	 * @return	void
	 */
	protected function retrieveAttachment($feedItem, $dbRecordId) {
		$attachments = array();
		$additionalLinks = $feedItem->get_item_tags('http://www.w3.org/2005/Atom', 'link');
		foreach ($additionalLinks as $lnk) {
			if ($lnk['attribs']['']['rel'] == 'attachment') {
				$attachments[] = $lnk['attribs'][''];
			}
		}

		if ($this->feedExtension == 0) {
			$destinationPath = PATH_site . $GLOBALS['TCA']['tt_news']['columns']['news_files']['config']['uploadfolder'] . '/';
		} elseif ($this->feedExtension == 2) {
			$destinationPath = PATH_site . $GLOBALS['TCA']['tx_news_domain_model_file']['columns']['file']['config']['uploadfolder'] . '/';
		}


		$fileFunc = t3lib_div::makeInstance('t3lib_extFileFunctions');
		$fileFunc->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);

		$newFiles = array();
		foreach ($attachments as $attachmentData) {
			$attachmentFile = $attachmentData['href'];
			list($prefix, $attachmentBasename) = t3lib_div::revExplode('/', $attachmentFile, 2);
			$imageBasename = $fileFunc->cleanFileName($attachmentBasename);
			$finalFilename = $fileFunc->getUniqueName($attachmentBasename, $destinationPath);

			// fill the file
			$fileData = t3lib_div::getURL($attachmentFile);
			t3lib_div::writeFile($finalFilename, $fileData);

				// e.g. Kasper_02.jpg
			$newFiles[] = basename($finalFilename);
		}
		$data = array();

		if ($this->feedExtension == 0) {
			$data['tt_news'][$dbRecordId]['news_files'] = implode(',', $newFiles);
		} elseif ($this->feedExtension == 2) {
			$data['tx_news_domain_model_news'][$dbRecordId]['news_files'] = implode(',', $newFiles);
		}


			// store that to the DB
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->dontProcessTransformations = 1;
		$tce->start($data, array());
		$tce->process_datamap();
	}

}

if (defined('TYPO3_MODE') && isset($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/newsfeedimport/Classes/Import.php'])) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/newsfeedimport/Classes/Import.php']);
}
?>
