<?php

namespace Tollwerk\TwLucenesearch\Utility;

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
 * Frontend simulator
 *
 * @package tw_lucenesearch
 * @copyright Copyright © 2020 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 * @author Christian Eßl <essl@incert.at>
 */
class FrontendSimulator
{
    /**
     * Frontend engine backup
     *
     * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected static $_tsfeBackup = null;
    /**
     * HTTP_HOST backup
     *
     * @var string
     */
    protected static $_httpHostBackup = null;

    /**
     * Instanciates a frontend engine
     *
     * @param \int $pid Current page ID
     * @return void
     */
    public static function simulateFrontendEnvironment($pid)
    {
        self::$_tsfeBackup = isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : null;
        self::$_httpHostBackup = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;

        $GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
            $GLOBALS['TYPO3_CONF_VARS'], $pid, 0, true);
        $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\NullTimeTracker();

        /* @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
        $tsfe =& $GLOBALS['TSFE'];
        $tsfe->tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
        $tsfe->tmpl->init();
        $tsfe->initFEuser();
        // 		$tsfe->fe_user->fetchGroupData();
        // 		$tsfe->includeTCA();
        $tsfe->fetch_the_id();
        $tsfe->getConfigArray();
        // 		$tsfe->includeLibraries($tsfe->tmpl->setup['includeLibs.']);
        // 		$tsfe->newCObj();

        // Tweak the HTTP host name
        $rootLine = $tsfe->sys_page->getRootLine($pid);
        $domain = \TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord($rootLine);
        if (!$domain) {
            $domain = array_key_exists('baseURL', $tsfe->config) ? parse_url($tsfe->config['baseURL'],
                PHP_URL_HOST) : '';
        }
        $_SERVER['HTTP_HOST'] = $domain;
    }

    /**
     * Resets the frontend engine
     *
     * @return void
     */
    public static function resetFrontendEnvironment()
    {
        $GLOBALS['TSFE'] = self::$_tsfeBackup;
        $_SERVER['HTTP_HOST'] = self::$_httpHostBackup;
    }
}
