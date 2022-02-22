<?php

namespace Tollwerk\TwLucenesearch\ViewHelpers\Search;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Tollwerk\TwLucenesearch\Service\Lucene;

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

/**
 * View helper for highlighting search terms in search results
 *
 * = Examples =
 *
 * <code title="Example">
 * <twlucene:search.highlight text="{hit.document.bodytext}" search="{query}" crop="500"/>
 * </code>
 *
 * Output:
 * The given bodytext will be returned with highlighted search terms (als implied by the
 * given search query) and cropped to max. 500 characters
 *
 * @package   tw_lucenesearch
 * @copyright Copyright © 2016 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author    Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class HighlightViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Highlighting document
     *
     * @var \Zend_Search_Lucene_Document_Html
     */
    protected $_doc;
    /**
     * Temporary content object
     *
     * @var    tslib_cObj
     */
    protected $contentObject;
    /**
     * Backup of the current $GLOBALS['TSFE'] if used in BE mode
     *
     * @var    t3lib_fe
     */
    protected $tsfeBackup;
    /**
     * Extbase configuration
     *
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;
    /**
     * Search term cache
     *
     * @var array
     */
    protected static $_queryTermCache = array();

    /**
     * Extbase configuration manager dependency injection
     *
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager Configuration manager
     *
     * @return void
     */
    public function injectConfigurationManager(
        \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
    ) {
        $this->configurationManager = $configurationManager;
        $this->contentObject        = $this->configurationManager->getContentObject();
    }

    /**
     * Initialize arguments
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('text', 'string', 'Text');
        $this->registerArgument('search', 'mixed', 'Terms to be highlighted (string or lucene search query)');
        $this->registerArgument('crop', 'integer', 'Max. number of characters length');
        $this->registerArgument('append', 'string', 'Prefix in case of text being cropped at the beginning');
        $this->registerArgument('prepend', 'string', 'Max. number of characters length');
        $this->registerArgument('field', 'string', 'Lucene document field to be used (if the search terms have to be found retroactively)');
    }

    /**
     * Highlighting of terms within a text
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $text  = trim(strlen(trim($arguments['text'])) ? $arguments['text'] : self::renderChildren());
        $terms = array();

        // If there is a reasonable text given
        if (strlen($text)) {

            // If a list with search terms have been given ...
            if (is_array($arguments['search'])) {
                $terms = $arguments['search'];
                usort($terms, array(self(), 'sortByLengthDesc'));

                // Else: If query hits have been given ...
            } elseif ($arguments['search'] instanceof \Tollwerk\TwLucenesearch\Domain\Model\QueryHits) {
                $terms = (array)$arguments['search']->getHighlight($arguments['field']);
                usort($terms, array(self(), 'sortByLengthDesc'));

                // Else: If a lucene search query or a literal search term has been given
            } elseif (($arguments['search'] instanceof \Zend_Search_Lucene_Search_Query) || strlen($arguments['search'])) {

                // Instanciation of the lucene index service
                /* @var $indexerService Lucene */
                $indexerService = GeneralUtility::makeInstanceService('index', 'lucene');
                if ($indexerService instanceof \TYPO3\CMS\Core\Service\AbstractService) {

                    // Converting search term to lucene search query if necessary
                    if (!($arguments['search'] instanceof \Zend_Search_Lucene_Search_Query)) {
                        $search = $indexerService->query($arguments['search']);
                    }

                    // If there is a valid lucene search query available
                    if ($arguments['search'] instanceof \Zend_Search_Lucene_Search_Query) {

                        $searchHash = md5("$search");
                        if (!array_key_exists($searchHash, self::$_queryTermCache)) {
                            self::$_queryTermCache[$searchHash] = array();
                            foreach ($indexerService->getQueryTerms($search) as $termField => $fieldTerms) {
                                usort($fieldTerms, array(self(), 'sortByLengthDesc'));
                                self::$_queryTermCache[$searchHash][$termField] = $fieldTerms;
                            }
                        }

                        $terms = self::$_queryTermCache[$searchHash];
                        $field = trim($arguments['field']);
                        $terms = (strlen($field) && array_key_exists($field, $terms)) ? $terms[$field] : array();
                    }
                }
            }
        }

        // Check if the text has to be cropped ...
        $crop = (($crop !== null) && intval($crop) && (strlen($text) > $crop)) ? intval($crop) : false;

        // If the text is more than 33% too long and highlighting has to be applied: Cropping also at the beginning of the text
        if ($crop && ((strlen($text) / $crop) > 1.5) && count($terms)) {

            // Find the first highlighting in the text ...
            $firstHighlight = $this->firstMatch($terms, $text);

            // If there is at least one highlighting ...
            if ($firstHighlight !== false) {
                $beforeHighlight = strrev(trim(substr($text, 0, $firstHighlight)));

                // Keep the last 3 words before the highlighting ...
                $words = preg_split("%\s+%", $beforeHighlight, 4);
                if (count($words) > 3) {
                    $beforeHighlight = strrev(implode(' ', array_slice($words, 0, 3)));
                    $text            = $prepend.$beforeHighlight.' '.substr($text, $firstHighlight);
                }
            }
        }

        // If there are search terms to be highlighted in the text ...
        if (count($terms)) {
            $text = $this->highlight($terms, $text, $crop);
        }

        // If the text has to be cropped ...
        if ($crop) {

            if (TYPO3_MODE === 'BE') {
                $this->simulateFrontendEnvironment();
            }

            $respectHtml = true;
            $text        = $respectHtml ?
                $this->contentObject->cropHTML($text, $crop.'|'.$append.'|1') :
                $this->contentObject->crop($text, $crop.'|'.$append.'|1');

            if (TYPO3_MODE === 'BE') {
                $this->resetFrontendEnvironment();
            }
        }

        return $text;
    }

    /**
     * Highlighting
     *
     * @param array $terms Terms to be highlighted
     * @param string $str  Text
     * @param string $crop Max. text length
     * @param string                                Text with highlighted search terms
     */
    public function highlight(array $terms, $str, $crop = false)
    {

        // Registering the max. text length (if applicable)
        if ($crop === false) {
            $crop = strlen($str);
        } else {
            $crop = min(strlen($str), $crop);
        }

        // If there are a reasonable text and terms to be highlighted ...
        if (count($terms) && strlen($str)) {

            // Removal of redundant text at the end of the text (because it's going to be cropped anyway ...)
            $maxLength = strlen($terms[0]);
            $trailer   = substr($str, $crop + $maxLength);
            $str       = substr($str, 0, $crop + $maxLength);

            // Iterate over all terms and build a highlighting index
            $emphasizeIndex = array();
            foreach ($terms as $term) {
                $offset = 0;
                $length = strlen($term);

                // While there are matches within the max. text length ...
                while ((($pos = stripos($str, $term, $offset)) !== false) && ($offset < $crop)) {

                    // Register the passage to be highlighed
                    for ($emphasize = $pos; $emphasize < ($pos + $length); ++$emphasize) {
                        ++$emphasizeIndex[$emphasize];
                    }

                    $offset = $pos + $length;
                }
            }

            // Do highlighting
            ksort($emphasizeIndex, SORT_NUMERIC);
            $emphasized = '';
            $lastLevel  = 0;
            $lastChar   = -1;
            foreach ($emphasizeIndex as $char => $level) {

                // If there have been more than one characters between this one and the last match ...
                if ($char > ($lastChar + 1)) {

                    // Finalize active highlighting (if applicable)
                    while ($lastLevel > 0) {
                        $emphasized .= '</strong>';
                        --$lastLevel;
                    }

                    $emphasized .= substr($str, $lastChar + 1, $char - ($lastChar + 1));

                    // Start a new highlighting
                    while ($lastLevel < $level) {
                        $emphasized .= '<strong>';
                        ++$lastLevel;
                    }

                    // Else: Change highlighting level
                } else {

                    // Finalize active highlighting (if applicable)
                    while ($lastLevel > $level) {
                        $emphasized .= '</strong>';
                        --$lastLevel;
                    }

                    // Start a new highlighting
                    while ($lastLevel < $level) {
                        $emphasized .= '<strong>';
                        ++$lastLevel;
                    }
                }

                $emphasized .= $str[$char];
                $lastChar   = $char;
                $lastLevel  = $level;
            }

            // Finalize active highlighting (if applicable)
            while ($lastLevel > 0) {
                $emphasized .= '</strong>';
                --$lastLevel;
            }

            // Append remaining text trailer
            $emphasized .= substr($str, $lastChar + 1).$trailer;
        }

        return $emphasized;
    }

    /**
     * Sorting of two strings by length
     *
     * @param string $a String 1
     * @param string $b String 2
     *
     * @return int                                    Sorting
     */
    public function sortByLengthDesc($a, $b)
    {
        $al = strlen($a);
        $bl = strlen($b);

        return ($al == $bl) ? 0 : (($al < $bl) ? 1 : -1);
    }

    /**
     * Return the position of the first occurence of a term out of a list of terms within a given text
     *
     * @param array $terms Terms
     * @param string $str  Text
     *
     * @return int                                    First occurence position
     */
    public function firstMatch(array $terms, $str)
    {
        $pos = strlen($str);
        if ($pos) {
            foreach ($terms as $term) {
                $match = stripos($str, $term);
                if ($match !== false) {
                    $pos = min($pos, $match);
                }
            }
        }

        return ($pos == strlen($str)) ? false : $pos;
    }
}

?>
