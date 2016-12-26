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
 * @package        tw_lucenesearch
 * @copyright    Copyright © 2014 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author        Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class ModuleController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
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
    public function initializeAction()
    {

        // Instanciating the lucene index service
        /* @var $indexerService \Tollwerk\TwLucenesearch\Service\Lucene */
        try {
            $this->_indexService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('index', 'lucene');

            // Else: if no index exists ...
        } catch (\Exception $e) {
            $this->_indexException = $e;
        }

        $this->_pageUid = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
        $config = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tollwerk\\TwLucenesearch\\Utility\\Indexer');
        $this->_pageConfig = $indexer::indexConfigTS($config['config.']);
    }

    /**
     * Manage the Lucene Index in general
     *
     * @param \string $clear Clear the index
     * @return \void
     * @todo Respect the TypoScript configuration for the current index?
     */
    public function indexAction($clear = null)
    {

        // If the index service could be instanciated
        if (is_object($this->_indexService)) {

            // If the index should be cleared
            if (($clear !== null) && strlen($clear)) {

                // If the index can be successfully cleared
                if ($this->_indexService->clear(true)) {
                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mod.index.clear.success',
                            'tw_lucenesearch'),
                        '', // the header is optional
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    );

                    $this->_indexService->commit();

                    // Else: Error
                } else {
                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mod.index.clear.error',
                            'tw_lucenesearch'),
                        '',
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                    );
                }

                $flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Messaging\FlashMessageService');
                $flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages')->enqueue($message);
            }

            $this->view->assign('info', $this->_indexService->indexInfo());

            // Else: Error
        } else {
            $this->view->assign('info', null);

            $message = ($this->_indexException instanceof \Exception) ?
                \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    $this->_indexException->getMessage(),
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mod.index.error', 'tw_lucenesearch'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                ) : \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mod.index.error.unknown',
                        'tw_lucenesearch'),
                    '',
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            $flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Messaging\FlashMessageService');
            $flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages')->enqueue($message);
        }
    }

    /**
     * Manage the index entries of a particular page
     *
     * @param \array $documents Document references
     * @param \string $delete Delete the given documents from the index
     * @param \string $reindex Re-index the given documents
     * @return \void
     */
    public function pageAction(array $documents = array(), $delete = false, $reindex = false)
    {
        // Process document updates
        $this->processUpdates($documents, $delete, $reindex);

        // Determine the index reference components
        $references = array();
        foreach ($this->_pageConfig['reference'] as $key => $refConfig) {
            $refLabel = $key;
            while (!array_key_exists('default', $refConfig)) {
                $refKey = key($refConfig);
                $refLabel .= '['.$refKey.']';
                $refConfig =& $refConfig[$refKey];
            }
            $references[$key] = $refLabel;
        }

        // Determine the TSConfig
        $default = array(
            'language' => array(
                'flag' => '',
                'label' => 'Default language'
            )
        );
        $pageTSConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->_pageUid);
        if (!empty($pageTSConfig['mod.']) && !empty($pageTSConfig['mod.']['SHARED.']) && is_array($pageTSConfig['mod.']['SHARED.'])) {
            if (array_key_exists('defaultLanguageFlag', $pageTSConfig['mod.']['SHARED.'])) {
                $default['language']['flag'] = $pageTSConfig['mod.']['SHARED.']['defaultLanguageFlag'];
            }
            if (array_key_exists('defaultLanguageLabel', $pageTSConfig['mod.']['SHARED.'])) {
                $default['language']['label'] = $pageTSConfig['mod.']['SHARED.']['defaultLanguageLabel'];
            }
        }

        // Find all index documents
        $documents = $this->_indexService->getByTypeId(\Tollwerk\TwLucenesearch\Utility\Indexer::PAGE, $this->_pageUid);

        $this->view->assign('documents', $documents);
        $this->view->assign('default', $default);
        $this->view->assign('references', $references);
        $this->view->assign('config', $this->_pageConfig);
        $this->view->assign('page', $this->_pageUid);
    }

    /**
     * Manage the other index entries
     *
     * @param \array $documents Document references
     * @param \string $delete Delete the given documents from the index
     * @return \void
     */
    public function otherAction(array $documents = array(), $delete = false)
    {
        // Process document updates
        $this->processUpdates($documents, $delete);

        $documents = array();

        // Run through all registered non-page document types
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['nonpage-document-types'] as $nonPageDocumentType) {
            $documents[$nonPageDocumentType] = $this->_indexService->getByTypeId($nonPageDocumentType);
        }

        $this->view->assign('documents', $documents);
    }

    /**
     * Reindex an index document
     *
     * @param \Tollwerk\TwLucenesearch\Domain\Model\Document $document Document
     * @return \boolean                                                        Success
     */
    protected function _reindex(\Tollwerk\TwLucenesearch\Domain\Model\Document $document)
    {

        // Prepare the reference parameters
        $reference = $document->getReferenceParameters();
        if (array_key_exists('id', $reference)) {
            $pageUid = $reference['id'];
            unset($reference['id']);
        } else {
            $pageUid = $document->getPageUid();
        }
        if (array_key_exists('type', $reference)) {
            $pageType = $reference['type'];
            unset($reference['type']);
        } else {
            $pageType = 0;
        }
        $reference['index_force_reindex'] = 1;

        // Simulate a frontend environment
        \Tollwerk\TwLucenesearch\Utility\FrontendSimulator::simulateFrontendEnvironment($pageUid);

        // Create the frontend URL
        $uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($pageUid)
            ->setTargetPageType($pageType)
            ->setNoCache(true)
            ->setUseCacheHash(false)
            ->setLinkAccessRestrictedPages(true)
            ->setArguments($reference)
            ->setCreateAbsoluteUri(true)
            ->setAddQueryString(false)
            ->setArgumentsToBeExcludedFromQueryString(array())
            ->buildFrontendUri();

        // Fetch (and thus re-index) the URL
        $success = !!strlen($this->_getUrl($uri));

        // Reset the frontend environment
        \Tollwerk\TwLucenesearch\Utility\FrontendSimulator::resetFrontendEnvironment();

        return $success;
    }

    /**
     * Request an URL via GET (HTTP 1.1)
     *
     * @param \string $url Remote URL
     * @return \string                    Response content
     */
    protected function _getUrl($url)
    {

        // If cURL is available
        if (extension_loaded('curl')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; en-US) AppleWebKit/534.3 (KHTML, like Gecko) Chrome/6.0.466.4 Safari/534.3',
                CURLOPT_AUTOREFERER => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            // Else: Try via stream wrappers
        } else {
            $opts = array(
                'http' => array(
                    'method' => 'GET',
                    'protocol_version' => 1.1,
                    'user_agent' => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; en-US) AppleWebKit/534.3 (KHTML, like Gecko) Chrome/6.0.466.4 Safari/534.3',
                    'max_redirects' => 10,
                    'timeout' => 120,
                    'header' => "Accept-language: en\r\n",
                )
            );
            $context = stream_context_create($opts);
            $response = @file_get_contents($url, false, $context);
        }
        return $response;
    }

    /**
     * Process document updates
     *
     * @param \array $documents Document references
     * @param \string $delete Delete the given documents from the index
     * @param \string $reindex Re-index the given documents
     * @return \void
     */
    protected function processUpdates(array $documents = array(), $delete = false, $reindex = false)
    {
        // If document references have been submitted
        if (count($documents)) {
            $commit = 0;
            $delete = $delete !== false;
            $reindex = !$delete && ($reindex !== false);


            // If documents should be deleted
            if ($delete || $reindex) {
                foreach ($documents as $uid => $confirm) {
                    if (intval($confirm)) {
                        $hit = null;
                        $document = $this->_indexService->get($uid, $hit);
                        if ($document instanceof \Tollwerk\TwLucenesearch\Domain\Model\Document) {
                            if ($delete) {
                                $this->_indexService->delete($hit);
                                ++$commit;
                            } else {
                                $commit += $this->_reindex($document) * 1;
                            }
                        }
                    }
                }

                // Success message
                if ($commit) {
                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        sprintf(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mod.page.documents.'.($delete ? 'delete' : 'reindex').'.success',
                            'tw_lucenesearch'), count($documents)),
                        '', // the header is optional
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    );

                    // Else: Info message
                } else {
                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mod.page.documents.'.($delete ? 'delete' : 'reindex').'.error',
                            'tw_lucenesearch'),
                        '', // the header is optional
                        \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                    );
                }

                $flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Messaging\FlashMessageService');
                $flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages')->enqueue($message);
            }

            // Commit changes
            if ($commit) {
                $this->_indexService->commit();
            }
        }
    }
}

?>
