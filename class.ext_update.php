<?php

/***************************************************************
 *  Copyright notice
 *
 *  © 2014 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Auxiliary class for managing the Lucene index
 * 
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2014 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author		Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class ext_update  {

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main()	{
		$content			= '';
		$error				= false;
		
		// Instanciating the lucene index service
		/* @var $indexerService \Tollwerk\TwLucenesearch\Service\Lucene */
		try {
			$indexService			= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('index', 'lucene');
			if ($indexService instanceof \TYPO3\CMS\Core\Service\AbstractService) {
				$clear				= \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('clear');
					
				// If the lucene index should be cleared
				if ($clear) {
					
					// If the index could successfully be cleared
					if ($indexService->clear(true)) {				
						$content	.= '<p>'.$GLOBALS['LANG']->sL('LLL:EXT:tw_lucenesearch/Resources/Private/Language/locallang_db.xlf:update.clear.success').'</p>';
					} else {
						$error		.= '<p>'.$GLOBALS['LANG']->sL('LLL:EXT:tw_lucenesearch/Resources/Private/Language/locallang_db.xlf:update.clear.error.unknown').'</p>';
					}
					
				// Else
				} else {
					$content		.= '</form><form action="'.htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript()).'" method="post">';
					$content		.= '<p>'.sprintf($GLOBALS['LANG']->sL('LLL:EXT:tw_lucenesearch/Resources/Private/Language/locallang_db.xlf:update.clear.description'), $indexService->indexInfo()->count).'</p><br/>';
					$content		.= '<input type="submit" name="clear" value="'.htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:tw_lucenesearch/Resources/Private/Language/locallang_db.xlf:update.clear.submit')).'" />';
				}
				
			} else {
				$error				= $GLOBALS['LANG']->sL('LLL:EXT:tw_lucenesearch/Resources/Private/Language/locallang_db.xlf:update.clear.noindex');
			}
			
		// Else: if no index exists ...
		} catch (Exception $e) {
			$error					= $e->getMessage()."\n".$e->getTraceAsString();
		}

		// If an error occured ...
		if ($error) {
			$content				.= '<p class="error">'.$GLOBALS['LANG']->sL('LLL:EXT:tw_lucenesearch/Resources/Private/Language/locallang_db.xlf:update.clear.error').'</p>';
			$content				.= '<p class="error">'.$error.'</p>';
		}
		
		$content .= '</form>';

		return $content;
	}
	
	/**
	 * Access control
	 * 
	 * @return boolean				Grant access
	 */
	public function access() {
		return true;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tw_lucenesearch/class.ext_update.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tw_lucenesearch/class.ext_update.php']);
}