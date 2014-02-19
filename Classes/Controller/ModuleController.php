<?php

namespace Tollwerk\TwLucenesearch\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  © 2013 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH
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
 * Lucene backend module controller
 *
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2013 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author		Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class ModuleController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
	/**
	 * Index service instance
	 * 
	 * @var \Tollwerk\TwLucenesearch\Service\Lucene
	 */
	protected $_indexService = null;
	/**
	 * Index exception
	 * 
	 * @var \Exception
	 */
	protected $_indexException = null;
	/**
	 * Current page ID
	 *
	 * @var \int
	 */
	protected $_pageUid = 0;
	/**
	 * Page specific index configuration
	 * 
	 * @var \array
	 */
	protected $_pageConfig = null;
	
	/**
	 * General initialization
	 * 
	 * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::initializeAction()
	 */
	public function initializeAction() {
		
		// Instanciating the lucene index service
		/* @var $indexerService \Tollwerk\TwLucenesearch\Service\Lucene */
		try {
			$this->_indexService	= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('index', 'lucene');
		
		// Else: if no index exists ...
		} catch (\Exception $e) {
			$this->_indexException	= $e;
		}
		
		$this->_pageUid				= intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
		$config						= $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		$indexer					= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tollwerk\\TwLucenesearch\\Utility\\Indexer');
		$this->_pageConfig			= $indexer::indexConfigTS($config['config.']);
	}

	/**
	 * Manage the Lucene Index in general
	 * 
	 * @param \string $clear		Clear the index
	 * @return \void
	 * @todo Respect the TypoScript configuration for the current index?
	 */
	public function indexAction($clear = null) {
		
		// If the index service could be instanciated
		if (is_object($this->_indexService)) {
			
			// If the index should be cleared
			if (($clear !== null) && strlen($clear)) {
				
				// If the index can be successfully cleared
				if ($this->_indexService->clear(true)) {
					$message			= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						 \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mod.index.clear.success', 'tw_lucenesearch'),
						'', // the header is optional
						\TYPO3\CMS\Core\Messaging\FlashMessage::OK
					);
					
				// Else: Error
				} else {
					$message			= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mod.index.clear.error', 'tw_lucenesearch'),
						'',
						\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
					);
				}
				
				$flashMessageService	= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Messaging\FlashMessageService');
				$flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages')->enqueue($message);
			}
			
			$this->view->assign('info', $this->_indexService->indexInfo());
			
		// Else: Error
		} else {
			$this->view->assign('info', null);
			
			$message					= ($this->_indexException instanceof \Exception) ?
			\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$this->_indexException->getMessage(),
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mod.index.error', 'tw_lucenesearch'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			) : \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mod.index.error.unknown', 'tw_lucenesearch'),
				'',
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
			$flashMessageService		= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Messaging\FlashMessageService');
			$flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages')->enqueue($message);
		}
	}

	/**
	 * Manage the index entries of a particular page
	 * 
	 * @return \void
	 */
	public function pageAction() {
		
		// Determine the index reference components
		$references						= array();
		foreach ($this->_pageConfig['reference'] as $key => $refConfig) {
			$refLabel					= $key;
			while (!array_key_exists('default', $refConfig)) {
				$refKey					= key($refConfig);
				$refLabel				.= '['.$refKey.']';
				$refConfig				=& $refConfig[$refKey];
			}
			$references[$key]			= $refLabel;
		}
		
		// Determine the TSConfig
		$default						= array('language' => array(
			'flag'						=> '',	
			'label'						=> 'Default language'
		));
		$pageTSConfig					= \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->_pageUid);
		if (!empty($pageTSConfig['mod.']) && !empty($pageTSConfig['mod.']['SHARED.']) && is_array($pageTSConfig['mod.']['SHARED.'])) {
			if (array_key_exists('defaultLanguageFlag', $pageTSConfig['mod.']['SHARED.'])) {
				$default['language']['flag']	= $pageTSConfig['mod.']['SHARED.']['defaultLanguageFlag'];
			}
			if (array_key_exists('defaultLanguageLabel', $pageTSConfig['mod.']['SHARED.'])) {
				$default['language']['label']	= $pageTSConfig['mod.']['SHARED.']['defaultLanguageLabel'];
			}
		}
		
		// Find all index documents
		$documents						= $this->_indexService->getByTypeId(\Tollwerk\TwLucenesearch\Utility\Indexer::PAGE, $this->_pageUid);
		
		$this->view->assign('documents', $documents);
		$this->view->assign('default', $default);
		$this->view->assign('references', $references);
		$this->view->assign('config', $this->_pageConfig);
	}

	/**
	 * Manage the other index entries
	 * 
	 * @return \void
	 */
	public function otherAction() {
		
	}
}

?>
