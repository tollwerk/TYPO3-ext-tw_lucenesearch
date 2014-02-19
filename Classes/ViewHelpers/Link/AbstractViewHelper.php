<?php

namespace Tollwerk\TwLucenesearch\ViewHelpers\Link;

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
 * Abstract base class for link view helpers
 * 
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2014 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author		Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
abstract class AbstractViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper {
	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController contains a backup of the current $GLOBALS['TSFE'] if used in BE mode
	 */
	protected $tsfeBackup;

	/**
	 * Sets the global variables $GLOBALS['TSFE']->csConvObj and $GLOBALS['TSFE']->renderCharset in Backend mode
	 * This somewhat hacky work around is currently needed because the crop() and cropHTML() functions of tslib_cObj rely on those variables to be set
	 *
	 * @return void
	 */
	protected function simulateFrontendEnvironment($pid) {
		$this->tsfeBackup			= isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : NULL;
		$GLOBALS['TSFE']			= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], $pid, 0, true);
		$GLOBALS['TT']				= new \TYPO3\CMS\Core\TimeTracker\NullTimeTracker();
		
		/* @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
		$tsfe						=& $GLOBALS['TSFE'];
		$tsfe->tmpl					= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
		$tsfe->tmpl->init();
		$tsfe->initFEuser();
// 		$tsfe->fe_user->fetchGroupData();
// 		$tsfe->includeTCA();
		$tsfe->fetch_the_id();
// 		$tsfe->getConfigArray();
// 		$tsfe->includeLibraries($tsfe->tmpl->setup['includeLibs.']);
// 		$tsfe->newCObj();
	}
	
	/**
	 * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
	 *
	 * @return void
	 * @see simulateFrontendEnvironment()
	 */
	protected function resetFrontendEnvironment() {
		$GLOBALS['TSFE']			= $this->tsfeBackup;
	}
}

?>