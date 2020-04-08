<?php

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

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(
    function($extKey) {
        // Service registration
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($extKey, 'index', 'tx_twlucenesearch_sv',
            array(
                'title'       => 'Lucene indexer manager',
                'description' => 'Service for building and querying a lucene search index',
                'subtype'     => 'lucene',
                'available'   => true,
                'priority'    => 50,
                'quality'     => 50,
                'os'          => '',
                'exec'        => '',
                'className'   => \Tollwerk\TwLucenesearch\Service\Lucene::class,
            )
        );

        // Search plugin configuration
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Tollwerk.'.$extKey,
            'Lucene',
            [\Tollwerk\TwLucenesearch\Controller\LuceneController::class => 'search,results,notfound'],
            [\Tollwerk\TwLucenesearch\Controller\LuceneController::class => 'results,notfound']
        );
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Tollwerk.'.$extKey,
            'LuceneAutocomplete',
            [\Tollwerk\TwLucenesearch\Controller\LuceneController::class => 'autocomplete'],
            [\Tollwerk\TwLucenesearch\Controller\LuceneController::class => 'autocomplete']
        );
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Tollwerk.'.$extKey,
            'LuceneCE',
            [\Tollwerk\TwLucenesearch\Controller\LuceneController::class => 'search,results,notfound'],
            [\Tollwerk\TwLucenesearch\Controller\LuceneController::class => 'results,notfound'],
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );

        // Adding the "Classes" directory to the include path
        set_include_path(implode(PATH_SEPARATOR,
            array_unique(array_merge(array(
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extKey, 'Classes')
            ),
                explode(PATH_SEPARATOR, get_include_path())))));

        // Indexing hook registration
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = \Tollwerk\TwLucenesearch\Utility\Indexer::class.'->intPages';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][]    = \Tollwerk\TwLucenesearch\Utility\Indexer::class.'->noIntPages';

        // Rewriting hook provision
        if (!array_key_exists('tw_lucenesearch', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch'] = [
                'search-rewrite-hooks'   => [],
                'term-rewrite-hooks'     => [],
                'nonpage-document-types' => []
            ];
        }
        $luceneExtConf                           =& $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch'];
        $luceneExtConf['search-rewrite-hooks']   = (array)($luceneExtConf['search-rewrite-hooks'] ?? []);
        $luceneExtConf['term-rewrite-hooks']     = (array)($luceneExtConf['term-rewrite-hooks'] ?? []);
        $luceneExtConf['nonpage-document-types'] = (array)($luceneExtConf['term-rewrite-hooks'] ?? []);

        // Enables eID calls for the autocomplete feature (like /index.php?eID=eidautocomplete)
        $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['eidautocomplete'] = 'EXT:tw_lucenesearch/Classes/Utility/EidAutocomplete.php';

//        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twlucenesearch_lucene[__referrer][@extension]';
//        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twlucenesearch_lucene[__referrer][@controller]';
//        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twlucenesearch_lucene[__referrer][@action]';
//        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twlucenesearch_lucene[__referrer][@request]';
//        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twlucenesearch_lucene[__trustedProperties]';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twlucenesearch_lucene[searchterm]';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twlucenesearch_lucene[search]';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twlucenesearch_lucene[action]';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twlucenesearch_lucene[controller]';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twlucenesearch_lucene[@widget_0][currentPage]';

        // Register custom caching
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['lucene'] = [
            'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
            'backend'  => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
            'options'  => [
                'compression' => true
            ],
            'groups'   => ['pages']
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = \Tollwerk\TwLucenesearch\Utility\CacheUtility::class.'->unregisterIndexed';
    },
    'tw_lucenesearch'
);
