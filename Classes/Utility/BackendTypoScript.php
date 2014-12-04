<?php

namespace Tollwerk\TwLucenesearch\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/***************************************************************
 *  Copyright notice
 *
 *  © 2014 David Frerich <dfrerich@cdfre.de>
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
 * BackendTypoScript Utility
 *
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2014 David Frerich
 * @author      David Frerich <dfrerich@cdfre.de>
 */
class BackendTypoScript
{
    /**
     * @var array
     */
    private static $typoScripts = array();

    /**
     * @var ObjectManager
     */
    private static $objectManager;

    /**
     * @var PageRepository
     */
    private static $pageRepository;

    /**
     * @param integer $pageUid
     * @return array
     */
    static public function get($pageUid)
    {
        if (!array_key_exists($pageUid, self::$typoScripts)) {
            $typoScript = self::getObjectManager()
                ->get('TYPO3\CMS\Core\TypoScript\ExtendedTemplateService');
            $rootLine = self::getPageRepository()->getRootLine($pageUid);

            $typoScript->tt_track = 0;
            $typoScript->init();
            $typoScript->runThroughTemplates($rootLine);
            $typoScript->generateConfig();

            self::$typoScripts[$pageUid] = $typoScript->setup;
        }

        return self::$typoScripts[$pageUid];
    }

    /**
     * @return PageRepository
     */
    static protected function getPageRepository()
    {
        if (!self::$pageRepository) {
            self::$pageRepository = self::getObjectManager()
                ->get('TYPO3\CMS\Frontend\Page\PageRepository');
        }

        return self::$pageRepository;
    }

    /**
     * @return ObjectManager
     */
    static protected function getObjectManager()
    {
        if (!self::$objectManager) {
            self::$objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        }

        return self::$objectManager;
    }
}