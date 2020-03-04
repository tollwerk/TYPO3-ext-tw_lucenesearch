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

namespace Tollwerk\TwLucenesearch\ViewHelpers\Search;

use Closure;
use Tollwerk\TwLucenesearch\Domain\Model\QueryHits;
use Tollwerk\TwLucenesearch\Service\Lucene;
use TYPO3\CMS\Core\Service\AbstractService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Zend_Search_Lucene_Exception;
use Zend_Search_Lucene_Search_Query;
use Zend_Search_Lucene_Search_QueryParserException;

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
 * @copyright Copyright © 2020 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author    Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class HighlightViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;
    /**
     * Search term cache
     *
     * @var array
     */
    protected static $_queryTermCache = [];
    /**
     * Escape output
     *
     * @var boolean
     * @api
     */
    protected $escapeOutput = false;

    /**
     * Render a resource URL for Fractal, possibly treated with the `path` view helper
     *
     * @param array $arguments
     * @param Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     * @throws Zend_Search_Lucene_Exception
     * @throws Zend_Search_Lucene_Search_QueryParserException
     * @throws Exception
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $text  = trim(strlen(trim($arguments['text'])) ? $arguments['text'] : $renderChildrenClosure());
        $terms = array();

        // If there is a reasonable text given
        if (strlen($text)) {
            $search = $arguments['search'];

            // If a list with search terms have been given ...
            if (is_array($search)) {
                $terms = $search;
                usort($terms, [static::class, 'sortByLengthDesc']);

                // Else: If query hits have been given ...
            } elseif ($search instanceof QueryHits) {
                $terms = (array)$search->getHighlight($arguments['field']);
                usort($terms, [static::class, 'sortByLengthDesc']);

                // Else: If a lucene search query or a literal search term has been given
            } elseif (($search instanceof Zend_Search_Lucene_Search_Query) || strlen($search)) {

                // Instanciation of the lucene index service
                /* @var $indexerService Lucene */
                $indexerService = GeneralUtility::makeInstanceService('index', 'lucene');
                if ($indexerService instanceof AbstractService) {

                    // Converting search term to lucene search query if necessary
                    if (!($search instanceof Zend_Search_Lucene_Search_Query)) {
                        $search = $indexerService->query($search);
                    }

                    // If there is a valid lucene search query available
                    if ($search instanceof Zend_Search_Lucene_Search_Query) {

                        $searchHash = md5("$search");
                        if (!array_key_exists($searchHash, self::$_queryTermCache)) {
                            self::$_queryTermCache[$searchHash] = array();
                            foreach ($indexerService->getQueryTerms($search) as $termField => $fieldTerms) {
                                usort($fieldTerms, [static::class, 'sortByLengthDesc']);
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
        $crop = (($arguments['crop'] !== null) && intval($arguments['crop']) && (strlen($text) > $arguments['crop'])) ?
            intval($arguments['crop']) : false;

        // If the text is more than 33% too long and highlighting has to be applied: Cropping also at the beginning of the text
        if ($crop && ((strlen($text) / $crop) > 1.5) && count($terms)) {

            // Find the first highlighting in the text ...
            $firstHighlight = static::firstMatch($terms, $text);

            // If there is at least one highlighting ...
            if ($firstHighlight !== false) {
                $beforeHighlight = strrev(trim(substr($text, 0, $firstHighlight)));

                // Keep the last 3 words before the highlighting ...
                $words = preg_split("%\s+%", $beforeHighlight, 4);
                if (count($words) > 3) {
                    $beforeHighlight = strrev(implode(' ', array_slice($words, 0, 3)));
                    $text            = $arguments['prepend'].$beforeHighlight.' '.substr($text, $firstHighlight);
                }
            }
        }

        // If there are search terms to be highlighted in the text ...
        if (count($terms)) {
            $text = self::highlight($terms, $text, $crop);
        }

        // If the text has to be cropped ...
        if ($crop) {
            $contentObject = GeneralUtility::makeInstance(ObjectManager::class)
                                           ->get(ConfigurationManager::class)
                                           ->getContentObject();
            $respectHtml   = true;
            $text          = $respectHtml ?
                $contentObject->cropHTML($text, $crop.'|'.$arguments['append'].'|1') :
                $contentObject->crop($text, $crop.'|'.$arguments['append'].'|1');
        }

        return $text;
    }

    /**
     * Return the position of the first occurence of a term out of a list of terms within a given text
     *
     * @param array $terms Terms
     * @param string $str  Text
     *
     * @return int                                    First occurence position
     */
    public static function firstMatch(array $terms, $str)
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

    /**
     * Highlighting
     *
     * @param array $terms Terms to be highlighted
     * @param string $str  Text
     * @param string $crop Max. text length
     * @param string                                Text with highlighted search terms
     *
     * @return string
     */
    public static function highlight(array $terms, $str, $crop = false)
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
    public static function sortByLengthDesc($a, $b)
    {
        $al = strlen($a);
        $bl = strlen($b);

        return ($al == $bl) ? 0 : (($al < $bl) ? 1 : -1);
    }

    /**
     * Initialize arguments
     *
     * @api
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('text', 'string', 'Text', false, null);
        $this->registerArgument(
            'search', 'mixed', 'Terms to be highlighted (string or lucene search query)', false,
            null
        );

        $this->registerArgument('crop', 'int', 'Max. number of characters length', false, null);
        $this->registerArgument('append', 'string', 'Suffix in case of text being cropped at the end', false, '…');
        $this->registerArgument('prepend', 'string', 'Prefix in case of text being cropped at the beginning', false,
            '…');
        $this->registerArgument('field', 'string',
            'Lucene document field to be used (if the search terms have to be found retroactively)', false, 'bodytext');
    }
}
