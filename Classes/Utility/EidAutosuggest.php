<?php

namespace Tollwerk\TwLucenesearch\Utility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  © 2014 Christian Eßl <essl@incert.at>, INCERT eBusiness GmbH
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
 * Autosuggest feature 
 *
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2014 Christian Eßl <essl@incert.at>, INCERT eBusiness GmbH
 * @author		Christian Eßl <essl@incert.at>
 */
class EidAutosuggest {
 
	/**
	 * configuration
	 *
	 * @var \array
	 */
	protected $configuration;

	/**
	 * bootstrap
	 *
	 * @var \array
	 */
	protected $bootstrap;

	/**
	 * Generates the output
	 *
	 * @return \string		from action
	 */
	public function run() {
		return $this->bootstrap->run('', $this->configuration);
	}
 
	/**
	 * Initialize Extbase
	 *
	 * @param \array $TYPO3_CONF_VARS 			The global $TYPO3_CONF_VARS array. Will be set internally in ->TYPO3_CONF_VARS
	 */
	public function __construct($TYPO3_CONF_VARS) {
		$this->configuration = array(
			'pluginName' => 'LuceneAutosuggest', 
			'vendorName' => 'Tollwerk',
			'extensionName' => 'TwLucenesearch',
			'controller' => 'Lucene',
			'action' => 'autosuggest', 
			'mvc' => array(
				'requestHandlers' => array(
					'TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler' => 'TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler'
				)
			),
			'settings' => array() 
		);
		$_POST['tx_twlucenesearch_lucene']['action'] = 'autosuggest'; // set action
		$_POST['tx_twlucenesearch_lucene']['controller'] = 'Lucene'; // set action 

		$this->bootstrap = new \TYPO3\CMS\Extbase\Core\Bootstrap(); 
	}
}
 
global $TYPO3_CONF_VARS; 
$GLOBALS['TSFE'] = GeneralUtility::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], GeneralUtility::_GP('id'), '');
$GLOBALS['TSFE']->connectToDB();
$GLOBALS['TSFE']->initFEuser();
$GLOBALS['TSFE']->checkAlternativeIdMethods();
$GLOBALS['TSFE']->determineId();
$GLOBALS['TSFE']->getCompressedTCarray();
$GLOBALS['TSFE']->initTemplate();
$GLOBALS['TSFE']->getConfigArray();
 
$eid = GeneralUtility::makeInstance('Tollwerk\TwLucenesearch\Utility\EidAutosuggest', $TYPO3_CONF_VARS);
echo $eid->run(); // print content
?>
