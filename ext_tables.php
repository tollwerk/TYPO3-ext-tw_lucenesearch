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

// Plugin registration
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    $_EXTKEY,
    'Lucene',
    'LLL:EXT:tw_lucenesearch/Resources/Private/Language/locallang_db.xlf:feplugin'
);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    $_EXTKEY,
    'LuceneCE',
    'LLL:EXT:tw_lucenesearch/Resources/Private/Language/locallang_db.xlf:feplugin'
);

// Plugin integration into the backend forms
$pluginSignature = str_replace('_', '', $_EXTKEY).'_lucene';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature,
    'FILE:EXT:'.$_EXTKEY.'/Configuration/FlexForms/ControllerActions.xml');
$pluginSignature = str_replace('_', '', $_EXTKEY).'_lucenece';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('*',
    'FILE:EXT:'.$_EXTKEY.'/Configuration/FlexForms/ControllerActions.xml', $pluginSignature);

$TCA['tt_content']['types']['twlucenesearch_lucenece'] = $TCA['tt_content']['types']['list'];
$TCA['tt_content']['types']['twlucenesearch_lucenece']['showitem'] =
    "--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,pi_flexform;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:list_type_formlabel,select_key;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:select_key_formlabel,pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.list_formlabel,recursive,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access, include_countries, exclude_countries,
	--div--;LLL:EXT:frntend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended,
	--div--;LLL:EXT:flux/Resources/Private/Language/locallang.xlf:tt_content.tabs.relation, tx_flux_parent, tx_flux_column, tx_flux_children;LLL:EXT:flux/Resources/Private/Language/locallang.xlf:tt_content.tx_flux_children";

if (TYPO3_MODE == 'BE') {
    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['Tollwerk\\TwLucenesearch\\Wizicon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY,
        'Classes'.DIRECTORY_SEPARATOR.'Utility'.DIRECTORY_SEPARATOR.'Wizicon.php');
}

// Static TypoScript registration
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript',
    'tollwerk Lucene search');

// Adding the "Classes" directory to the include path
set_include_path(implode(PATH_SEPARATOR,
    array_unique(array_merge(array(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Classes')),
        explode(PATH_SEPARATOR, get_include_path())))));

// Adding the backend module
if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Tollwerk.'.$_EXTKEY,
        'web',            // Main area
        'lucene',        // Name of the module
        '',                // Position of the module
        array(            // Allowed controller action combinations
            'Module' => 'page,other,index',
        ),
        array(          // Additional configuration
            'access' => 'user,group',
            'icon' => 'EXT:'.$_EXTKEY.'/Resources/Public/Icons/module-lucenesearch.svg',
            'labels' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_mod.xml',
        )

    );
}
