<?php

/***************************************************************
 *  Copyright notice
 *
 *  © 2016 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH
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

$GLOBALS['TYPO3_CONF_VARS']['EXT']['extParams'][$_EXTKEY] = unserialize($_EXTCONF);

// Service registration
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY, 'index', 'tx_twlucenesearch_sv',
    array(
        'title' => 'Lucene indexer manager',
        'description' => 'Service for building and querying a lucene search index',
        'subtype' => 'lucene',
        'available' => true,
        'priority' => 50,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Classes/Service/Lucene.php',
        'className' => 'Tollwerk\\TwLucenesearch\\Service\\Lucene',
    )
);

// Search plugin configuration
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Tollwerk.'.$_EXTKEY,
    'Lucene',
    array(
        'Lucene' => 'search,results,notfound',
    ),
    array(
        'Lucene' => 'results,notfound',
    )
);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Tollwerk.'.$_EXTKEY,
    'LuceneCE',
    array(
        'Lucene' => 'search,results,notfound',
    ),
    array(
        'Lucene' => 'results,notfound',
    ),
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Adding the "Classes" directory to the include path
set_include_path(implode(PATH_SEPARATOR,
    array_unique(array_merge(array(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes')),
        explode(PATH_SEPARATOR, get_include_path())))));

// Indexing hook registration
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:tw_lucenesearch/Classes/Utility/Indexer.php:&Tollwerk\\TwLucenesearch\\Utility\\Indexer->intPages';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = 'EXT:tw_lucenesearch/Classes/Utility/Indexer.php:&Tollwerk\\TwLucenesearch\\Utility\\Indexer->noIntPages';

// Rewriting hook provision
if (!array_key_exists('tw_lucenesearch', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch'] = array(
        'search-rewrite-hooks' => array(),
        'term-rewrite-hooks' => array()
    );
}
if (!array_key_exists('search-rewrite-hooks',
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']) || !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['search-rewrite-hooks'])
) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['search-rewrite-hooks'] = array();
}
if (!array_key_exists('term-rewrite-hooks',
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']) || !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['term-rewrite-hooks'])
) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['term-rewrite-hooks'] = array();
}
if (!array_key_exists('nonpage-document-types',
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']) || !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['nonpage-document-types'])
) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['nonpage-document-types'] = array();
}

// Enables eID calls for the autocomplete feature (like /index.php?eID=eidautocomplete)
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['eidautocomplete'] = 'EXT:tw_lucenesearch/Classes/Utility/EidAutocomplete.php';
