<?php

namespace Tollwerk\TwLucenesearch\Controller;

use Tollwerk\TwLucenesearch\Domain\Model\QueryHits;
use Tollwerk\TwLucenesearch\Service\Lucene;
use Tollwerk\TwLucenesearch\Utility\Indexer;
use TYPO3\CMS\Core\Service\AbstractService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use Zend_Search_Lucene_Exception;

/***************************************************************
 *  Copyright notice
 *
 *  © 2020 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH
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
 * Lucene search controller
 *
 * @package   tw_lucenesearch
 * @copyright Copyright © 2020 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author    Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 * @author    Christian Eßl <essl@incert.at>
 */
class LuceneController extends ActionController
{
    /**
     * Search engine signatures
     *
     * @var array
     */
    protected static $_searchEngineSignatures = array(
        'daum'           => 'q',
        'eniro'          => 'search_word',
        'naver'          => 'query',
        'pchome'         => 'q',
        'images.google'  => 'q',
        'google'         => 'q',
        'yahoo'          => array('p', 'q'),
        'msn'            => 'q',
        'bing'           => 'q',
        'aol'            => array('query', 'q'),
        'lycos'          => array('q', 'query'),
        'ask'            => 'q',
        'netscape'       => 'query',
        'cnn'            => 'query',
        'about'          => 'terms',
        'mamma'          => 'q',
        'voila'          => 'rdata',
        'virgilio'       => 'qs',
        'live'           => 'q',
        'baidu'          => 'wd',
        'alice'          => 'qs',
        'yandex'         => 'text',
        'najdi'          => 'q',
        'seznam'         => 'q',
        'rakuten'        => 'qt',
        'biglobe'        => 'q',
        'goo.ne'         => 'MT',
        'wp'             => 'szukaj',
        'onet'           => 'qt',
        'yam'            => 'k',
        'kvasir'         => 'q',
        'ozu'            => 'q',
        'terra'          => 'query',
        'rambler'        => 'query',
        'conduit'        => 'q',
        'babylon'        => 'q',
        'search-results' => 'q',
        'avg'            => 'q',
        'comcast'        => 'q',
        'incredimail'    => 'q',
        'startsiden'     => 'q'
    );

    /**
     * Rendering a search box
     *
     * @param string $searchterm Search terms
     *
     * @return void
     */
    public function searchAction(string $searchterm = '')
    {
        $this->view->assign('searchterm', trim($searchterm));
        $this->view->assign('page', intval($this->settings['defaultResultsPage'] ?: $GLOBALS['TSFE']->id));
    }

    /**
     * Search & display of search results
     *
     * @param string $searchterm Search term(s)
     * @param int $pointer       Result pointer
     * @param boolean $notfound  Indicator for 404 based search
     *
     * @return void
     * @throws Zend_Search_Lucene_Exception
     */
    public function resultsAction(string $searchterm = '', int $pointer = 0, bool $notfound = false)
    {
        $settings = Indexer::indexConfig($GLOBALS['TSFE']);
        ArrayUtility::mergeRecursiveWithOverrule(
            $settings,
            is_array($this->settings) ? $this->settings : []
        );
        $this->settings = $settings;
        $page           = intval($this->settings['defaultResultsPage']) ?
            intval($this->settings['defaultResultsPage']) : $GLOBALS['TSFE']->id;
        $limit          = intval($this->settings['search']->limits->pages ?? 10) *
                          intval($this->settings['search']->limits->display ?? 20);
        $indexInfo      = null;
        $hits           = [];
        $error          = false;
        $query          = null;

        // Instantiating the lucene index service
        /* @var $indexerService Lucene */
        $indexerService = GeneralUtility::makeInstanceService('index', 'lucene');
        if ($indexerService instanceof AbstractService) {
            $indexInfo = $indexerService->indexInfo();

            // Run the search
            $hits = $indexerService->find($searchterm, $query, $limit);

            // If the search didn't complete successful
            if (!($hits instanceof QueryHits)) {
                $error = true;
            }
        }

        $this->view->assign('settings', $this->settings);
        $this->view->assign('error', $error);
        $this->view->assign('searchterm', trim($searchterm));
        $this->view->assign('page', $page);
        $this->view->assign('indexInfo', $indexInfo);
        $this->view->assign('hits', $hits);
        $this->view->assign('query', $query);
        $this->view->assign('notfound', $notfound * 1);
        $this->view->assign('baseUrl', $_SERVER['SERVER_NAME']);
    }

    /**
     * 404-based search
     *
     * This action can be used to run a specialised search as after a 404 error ("Page not found")
     * has occured. The action tries to detect a search engine and search terms in the referrer URL
     * and forwards them to an internal index search.
     *
     * @return void
     * @throws StopActionException
     */
    public function notfoundAction()
    {
        $referer = array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : null;

        // If there is a referrer URL ...
        if (strlen($referer)) {
            $referer = parse_url($referer);
            $host    = array_key_exists('host', $referer) ? $referer['host'] : null;
            parse_str(array_key_exists('query', $referer) ? $referer['query'] : '', $query);

            // If host an GET parameters are available ...
            if ($host && count($query)) {
                foreach (self::$_searchEngineSignatures as $seHost => $seParams) {
                    foreach ((array)$seParams as $seParam) {

                        // If a certain search engine seems to be matched ...
                        if ((stripos($host, $seHost) !== false) && array_key_exists($seParam,
                                $query) && strlen($query[$seParam])
                        ) {
                            $getParams  = array('searchterm' => $query[$seParam], 'pointer' => 0, 'notfound' => true);
                            $uriBuilder = $this->controllerContext->getUriBuilder();
                            $uriBuilder->setArguments(array($uriBuilder->getArgumentPrefix() => $getParams));
                            $this->controllerContext->setUriBuilder($uriBuilder);
                            $this->request->setArguments($getParams);
                            $this->forward('results', null, null, $getParams);
                        }
                    }
                }
            }
        }
    }

    /**
     * Autocomplete feature
     *
     * @param string $searchterm Search terms
     *
     * @return string          JSON encoded autocomplete suggestions
     * @throws Zend_Search_Lucene_Exception
     */
    public function autocompleteAction($searchterm = '')
    {
        $this->response->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->response->sendHeaders();

        // Instanciating the lucene index service
        /* @var $indexerService Lucene */
        $indexerService = GeneralUtility::makeInstanceService('index', 'lucene');
        $suggestions    = ($indexerService instanceof AbstractService) ? $indexerService->autocomplete($searchterm) : array();

        return json_encode($suggestions);
    }

    /**
     * Return the lucene search configuration
     *
     * @return string Reference parameters
     * @throws InvalidConfigurationTypeException
     */
    public function configAction()
    {
        $config  = $this->objectManager->get(ConfigurationManager::class)
                                       ->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $indexer = GeneralUtility::makeInstance(Indexer::class);

        return json_encode($indexer::indexConfigTS($config['config.']));
    }
}
