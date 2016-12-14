<?php

namespace Tollwerk\TwLucenesearch\ViewHelpers;

use Tollwerk\TwLucenesearch\Utility\BackendTypoScript;
use TYPO3\CMS\Backend\Utility\BackendUtility;

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
 * View helper for language flag image source
 * 
  * = Examples =
 *
 * <code title="Example">
 * <img src="{twlucene:languageFlag(language: document.language, referenceParameters: document.referenceParameters)}" alt="" />
 * </code>
 *
 * Output:
 * The given bodytext will be returned with highlighted search terms (als implied by the
 * given search query) and cropped to max. 500 characters
 * 
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2014 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author		David Frerich <dfrerich@cdfre.de>
 */
class LanguageFlagViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var array
     */
    static protected $languageFlags = array();

    /**
     * @var array
     */
    static protected $languageReference = array();

	/**
     * @param string $language
     * @param array $referenceParameters
     * @return string
	 */
	public function render($language, $referenceParameters = null)
    {
        $key = $language . '_' . serialize($referenceParameters);
        if (!array_key_exists($key, self::$languageFlags)) {
            self::$languageFlags[$key] = $language;

            if (array_key_exists($this->getLanguageReference(), $referenceParameters)) {
                $sysLanguageUid = (int)$referenceParameters[$this->getLanguageReference()];
                $sysLanguage = BackendUtility::getRecord('sys_language', $sysLanguageUid, 'flag');
                if ($sysLanguage && $sysLanguage['flag']) {
                    self::$languageFlags[$key] = $sysLanguage['flag'];
                }
            }
        }

        return 'sysext/t3skin/images/flags/' . self::$languageFlags[$key] . '.png';
    }

    /**
     * @return string
     */
    protected function getLanguageReference()
    {
        $pageUid = (int)$_GET['id'];
        if (!array_key_exists($pageUid, self::$languageReference)) {
            $typoScript = BackendTypoScript::get($pageUid);
            self::$languageReference[$pageUid] = $typoScript['config.']['index_languageReference'];
        }

        return self::$languageReference[$pageUid];
    }
}
