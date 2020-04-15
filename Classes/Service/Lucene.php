<?php

namespace Tollwerk\TwLucenesearch\Service;

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

use Exception;
use stdClass;
use Tollwerk\TwLucenesearch\Domain\Model\Document;
use Tollwerk\TwLucenesearch\Domain\Model\QueryHit;
use Tollwerk\TwLucenesearch\Domain\Model\QueryHits;
use Tollwerk\TwLucenesearch\Utility\Indexer;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Service\AbstractService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zend_Search_Lucene;
use Zend_Search_Lucene_Analysis_Analyzer;
use Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive;
use Zend_Search_Lucene_Exception;
use Zend_Search_Lucene_Index_Term;
use Zend_Search_Lucene_Interface;
use Zend_Search_Lucene_Search_Query;
use Zend_Search_Lucene_Search_Query_Boolean;
use Zend_Search_Lucene_Search_Query_Fuzzy;
use Zend_Search_Lucene_Search_Query_MultiTerm;
use Zend_Search_Lucene_Search_Query_Term;
use Zend_Search_Lucene_Search_Query_Wildcard;
use Zend_Search_Lucene_Search_QueryHit;
use Zend_Search_Lucene_Search_QueryLexer;
use Zend_Search_Lucene_Search_QueryParser;
use Zend_Search_Lucene_Search_QueryParserException;
use Zend_Search_Lucene_Search_QueryToken;
use Zend_Search_Lucene_Storage_Directory_Filesystem;

require_once 'Zend/Search/Lucene.php';
require_once 'Zend/Search/Lucene/Document.php';

/**
 * Lucene index service
 *
 * @package       tw_lucenesearch
 * @copyright     Copyright © 2020 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author        Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 * @author        Christian Eßl <essl@incert.at>
 */
class Lucene extends AbstractService implements SingletonInterface
{
    /**
     * Registered non-page document types
     *
     * @var array
     */
    protected static $nonPageDocumentTypes = array();
    /**
     * Index directory
     *
     * @var string
     */
    protected $_indexDirectory = null;
    /**
     * Lucene index instance
     *
     * @var Zend_Search_Lucene_Interface
     */
    protected $_index = null;
    /**
     * Optimize index on instanciation
     *
     * @var boolean
     */
    protected $_indexOptimize = true;

    /************************************************************************************************
     * PUBLIC METHODS
     ***********************************************************************************************/

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $indexDirectory        = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tw_lucenesearch']['indexDirectory'];
        $this->_indexDirectory = Environment::getPublicPath().DIRECTORY_SEPARATOR.
                                 trim($indexDirectory, DIRECTORY_SEPARATOR);
    }

    /**
     * Return some information about the index
     *
     * @return stdClass                                                Index information
     * @throws Zend_Search_Lucene_Exception
     */
    public function indexInfo()
    {
        return (object)array(
            'count'  => $this->_index()->count(),
            'buffer' => $this->_index()->getMaxBufferedDocs(),
            'factor' => $this->_index()->getMergeFactor(),
            'memory' => $this->_getMemUsage(),
            'size'   => $this->_getSize(),
        );
    }

    /**
     * Instanciate the Lucene index
     *
     * The index will be created if it doesn't exist yet.
     *
     * @return Zend_Search_Lucene_Interface Lucene index instance
     * @throws Zend_Search_Lucene_Exception
     * @throws Exception
     */
    protected function _index()
    {
        // One-time instanciation or creation of the lucene index
        if ($this->_index === null) {

            // Try to instanciate an existing lucene index
            try {
                $this->_index = Zend_Search_Lucene::open($this->_indexDirectory);

                // If an error occurs ...
            } catch (Zend_Search_Lucene_Exception $e) {

                // Try to create a new lucene index ...
                try {
                    $this->_index = Zend_Search_Lucene::create($this->_indexDirectory);

                    // If an error occurs: Failure
                } catch (Zend_Search_Lucene_Exception $e) {
                    throw new Exception(sprintf('Error creating lucene index in "%1$s", reason: "%2$s"',
                        $this->_indexDirectory,
                        $e->getMessage()));
                }
            }

            // Index setup
            Zend_Search_Lucene_Storage_Directory_Filesystem::setDefaultFilePermissions(0664);
            Zend_Search_Lucene_Analysis_Analyzer::setDefault(
                new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive()
            );
            Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('UTF-8');

            // Minimize memory consumption
            $this->_index->setMaxBufferedDocs(1);

            // Set optimization frequency
            $this->_index->setMergeFactor(max(1,
                intval($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tw_lucenesearch']['mergeFactor'])));

            // If applicable: Optimize index
            if ($this->_indexOptimize) {
                $this->_index->optimize();
            }

            $this->_index->commit();

            if (TYPO3_MODE == 'FE') {
                Zend_Search_Lucene::setTermsPerQueryLimit(
                    Indexer::indexConfig($GLOBALS['TSFE'], 'search.limits.query')
                );

                $minCharacters = max(1, Indexer::indexConfig($GLOBALS['TSFE'], 'search.minCharacters'));
                Zend_Search_Lucene_Search_Query_Fuzzy::setDefaultPrefixLength($minCharacters);
                Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength($minCharacters);
            }
        }

        return $this->_index;
    }

    /**
     * Return the current memory consumption
     *
     * @return int                                                    Current memory consumption
     */
    protected function _getMemUsage()
    {
        if (function_exists('memory_get_peak_usage')) {
            $memory = memory_get_peak_usage(true);
        } else {
            $memory = memory_get_usage(true);
        }

        return ($memory / 1024 / 1024);
    }

    /**
     * Return the byte size of the complete index
     *
     * @return string                                                Byte size
     */
    protected function _getSize()
    {
        $bytes = 0;
        foreach (scandir($this->_indexDirectory) as $indexFile) {
            if (@is_file($this->_indexDirectory.DIRECTORY_SEPARATOR.$indexFile)) {
                $bytes += @filesize($this->_indexDirectory.DIRECTORY_SEPARATOR.$indexFile);
            }
        }

        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2).' KB';
        } else {
            $bytes = intval($bytes).' Bytes';
        }

        return $bytes;
    }

    /**
     * Add a document to the index
     *
     * @param Document $document Document
     *
     * @return boolean                                                Success
     */
    public function add(Document $document)
    {
        $this->_index()->addDocument($document);
    }

    /**
     * Fetch a document from the index
     *
     * @param string $uid                             Unique document ID
     * @param Zend_Search_Lucene_Search_QueryHit $hit Query hit for the requested document
     *
     * @return Document        Requested document
     */
    public function get($uid, &$hit = null)
    {
        $refIDTerm = new Zend_Search_Lucene_Index_Term($uid, 'uid');
        $hits      = $this->_index()->termDocs($refIDTerm);
        $hit       = count($hits) ? current($hits) : null;
        if ($hit !== null) {
            return Document::cast($this->_index()->getDocument($hit));
        } else {
            $hit = null;

            return null;
        }
    }

    /**
     * Fetch a document by it's type and ID
     *
     * @param string $type        Document type
     * @param string $id          Document ID
     * @param boolean $returnHits Return hits instead of documents
     *
     * @return array Requested document
     * @throws Zend_Search_Lucene_Exception
     */
    public function getByTypeId($type = null, $id = null, $returnHits = false)
    {
        $documents = array();

        // Query all index documents for the current page
        $query = new Zend_Search_Lucene_Search_Query_Boolean();

        // Add a type query
        if (($type !== null) && strlen($type)) {
            $typeTerm  = new Zend_Search_Lucene_Index_Term($type, 'type');
            $typeQuery = new Zend_Search_Lucene_Search_Query_Term($typeTerm);
            $query->addSubquery($typeQuery, true);
        }

        // Add an ID query
        if (($id !== null) && strlen($id)) {
            $idTerm  = new Zend_Search_Lucene_Index_Term($id, 'id');
            $idQuery = new Zend_Search_Lucene_Search_Query_Term($idTerm);
            $query->addSubquery($idQuery, true);
        }

        foreach ($this->_index()->find($query) as $hit) {
            $documents[] = $returnHits ?
                QueryHit::cast($hit, count($documents)) :
                Document::cast($hit->getDocument());
        }

        return $documents;
    }

    /**
     * Delete a document from the index
     *
     * @param string $id Internal document identifier
     *
     * @return void
     * @throws Zend_Search_Lucene_Exception
     */
    public function delete($id)
    {
        $this->_index()->delete($id);
    }

    /**
     * Run an index search
     *
     * @param string $searchTerm                     Search terms
     * @param Zend_Search_Lucene_Search_Query $query Final index search
     *
     * @return array|QueryHits[]        Query hits
     * @throws Exception
     */
    public function find(
        string $searchTerm,
        Zend_Search_Lucene_Search_Query &$query = null,
        int $limit = null
    ) {
        $searchTerm = trim($searchTerm);
        $hits       = [];

        // If there are meaningful search terms
        if (strlen($searchTerm)) {
            require_once 'Zend/Search/Lucene/Search/QueryParserException.php';
            require_once 'Zend/Search/Lucene/Search/QueryLexer.php';

            // Apply external rewriters
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['search-rewrite-hooks'])) {
                $params = array('search' => &$searchTerm, 'pObj' => &$this);
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['search-rewrite-hooks'] as $rewriteHook) {
                    GeneralUtility::callUserFunction($rewriteHook, $params, $this);
                }
            }

            // Extend / rewrite the search query
            $tokenTerms = [];
            $searchTerm = $this->_rewriteQueryTerms($searchTerm, $tokenTerms);

            // If a result limit has been specified
            if ($limit !== null) {
                $currentResultSetLimit = Zend_Search_Lucene::getResultSetLimit();
                Zend_Search_Lucene::setResultSetLimit($limit);
            }

            try {
                $highlight = intval(Indexer::indexConfig($GLOBALS['TSFE'], 'search.highlightMatches'));

                // Construct the search request
                $query = $this->query($searchTerm);

                // Run the search an collect the results
                $hits = new QueryHits($this->_index(), $query, $tokenTerms, (boolean)$highlight);

                // In case of errors: Invalidate the query hits
            } catch (Zend_Search_Lucene_Exception $e) {
                $hits = null;
            }

            // If a result limit has been specified: Reset the original limit
            if ($limit !== null) {
                Zend_Search_Lucene::setResultSetLimit($currentResultSetLimit);
            }
        }

        return $hits;
    }

    /**
     * Rewrite the original search in accordance to the search configuration
     *
     * @param string $searchterm Original search terms
     * @param array $tokenTerms  Single search term tokens
     *
     * @return string                                                Rewritten search terms
     */
    protected function _rewriteQueryTerms($searchTerm, &$tokenTerms = array())
    {
        $tokenTerms = array();

        try {
            $searchConfig = Indexer::indexConfig($GLOBALS['TSFE'],
                'search.searchConfig');
            $lexer        = new Zend_Search_Lucene_Search_QueryLexer();

            // Iterate over the extracted tokens
            /* @var $token Zend_Search_Lucene_Search_QueryToken */
            $tokens     = array_reverse($lexer->tokenize($searchTerm, 'UTF-8'));
            $tokenCount = count($tokens);
            foreach ($tokens as $tokenIndex => $token) {

                // If there's a word or a phrase which has no certain field name applied
                if (
                    (($token->type == Zend_Search_Lucene_Search_QueryToken::TT_WORD) || ($token->type == Zend_Search_Lucene_Search_QueryToken::TT_PHRASE)) &&
                    (($tokenIndex == $tokenCount - 1) || ($tokens[$tokenIndex + 1]->type != Zend_Search_Lucene_Search_QueryToken::TT_FIELD))
                ) {
                    $tokenTerms[]   = $token;
                    $isPhrase       = ($token->type == Zend_Search_Lucene_Search_QueryToken::TT_PHRASE);
                    $tokenStr       = $isPhrase ? '"'.$token->text.'"' : $token->text;
                    $tokenStrLength = mb_strlen($tokenStr);

                    // Apply external rewriters
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['term-rewrite-hooks'])) {
                        $params = ['token' => &$token, 'pObj' => &$this];
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['term-rewrite-hooks'] as $rewriteHook) {
                            GeneralUtility::callUserFunction($rewriteHook, $params, $this);
                        }
                    }

                    // If the token type is being changed from "word" dot "phrase" ...
                    if (!$isPhrase && ($token->type == Zend_Search_Lucene_Search_QueryToken::TT_PHRASE)) {
                        $isPhrase = true;
                        $tokenStr = '"'.$token->text.'"';
                    }

                    // Rewrite in accordance to the search configuration
                    $tokenRewrite = array();
                    foreach ($searchConfig as $searchField) {
                        $value      = ($isPhrase || $searchField->fuzzy) ? strtr($searchField->value,
                            array('*' => '')) : $searchField->value;
                        $sfTokenStr = (($searchField->field !== true) ? $searchField->field.':' : '');
                        $sfTokenStr .= strtr($value, array('?' => $tokenStr));
                        if ($searchField->fuzzy !== false) {
                            $sfTokenStr .= '~'.$searchField->fuzzy;
                        }
                        if ($searchField->boost !== null) {
                            $sfTokenStr .= '^'.$searchField->boost;
                        }
                        $tokenRewrite[] = $sfTokenStr;
                    }
                    $tokenRewrite = '('.implode(' OR ', $tokenRewrite).')';
                    $searchTerm   = mb_substr($searchTerm, 0, $token->position - $tokenStrLength)
                                    .$tokenRewrite.mb_substr($searchTerm, $token->position);
                }
            }

        } catch (Zend_Search_Lucene_Search_QueryParserException $e) {
        }

        return $searchTerm;
    }

    /**
     * Create a lucene search from search terms
     *
     * @param string $searchTerm Search terms
     *
     * @return Zend_Search_Lucene_Search_Query                        Lucene search query
     * @throws Zend_Search_Lucene_Exception
     * @throws Zend_Search_Lucene_Search_QueryParserException
     */
    public function query($searchTerm)
    {

        // If there are meaningful search terms
        if (strlen(trim($searchTerm))) {
            $query     = new Zend_Search_Lucene_Search_Query_Boolean();
            $termQuery = Zend_Search_Lucene_Search_QueryParser::parse($searchTerm);

            // Include term based restriction
            $query->addSubquery($termQuery, true);

            $typeQueries = array();

            // Run through all search types
            foreach (Indexer::indexConfig($GLOBALS['TSFE'], 'search.searchTypes') as $searchType) {

                // Page documents
                if ($searchType == Indexer::PAGE) {
                    require_once 'Zend/Search/Lucene/Search/Query/Term.php';
                    require_once 'Zend/Search/Lucene/Index/Term.php';

                    $pageQueries = array(
                        new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($searchType,
                            'type'))
                    );

                    // If applicable: Apply language based restriction
                    if (Indexer::indexConfig($GLOBALS['TSFE'], 'search.restrictByLanguage')) {
                        $language      = $GLOBALS['TYPO3_REQUEST']->getAttribute('language')->getTwoLetterIsoCode();
                        $pageQueries[] = new Zend_Search_Lucene_Search_Query_Term(
                            new Zend_Search_Lucene_Index_Term($language, 'language')
                        );
                    }

                    // If applicable: Apply rootline based restriction
                    $rootlinePids = Indexer::indexConfig($GLOBALS['TSFE'], 'search.restrictByRootlinePids');
                    if (is_array($rootlinePids) && count($rootlinePids)) {
                        require_once 'Zend/Search/Lucene/Search/Query/MultiTerm.php';
                        require_once 'Zend/Search/Lucene/Index/Term.php';
                        $rootlineQuery = new Zend_Search_Lucene_Search_Query_MultiTerm();
                        foreach ($rootlinePids as $rootlinePid) {
                            $rootlineQuery->addTerm(new Zend_Search_Lucene_Index_Term($rootlinePid, 'rootline'), null);
                        }
                        $pageQueries[] = $rootlineQuery;
                    }

                    $typeQueries[] = new Zend_Search_Lucene_Search_Query_Boolean($pageQueries, true);

                    // Other non-page documents
                } else {
                    $typeQuery = array(
                        new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($searchType,
                            'type'))
                    );

                    // If applicable: Apply language based restriction
                    if (Indexer::indexConfig($GLOBALS['TSFE'], 'search.restrictByLanguage')) {
                        $typeQuery[] = new Zend_Search_Lucene_Search_Query_Term(
                            new Zend_Search_Lucene_Index_Term($GLOBALS['TSFE']->lang, 'language')
                        );
                    }

                    $typeQueries[] = new Zend_Search_Lucene_Search_Query_Boolean($typeQuery, true);
                }
            }

            $typesQuery = Zend_Search_Lucene_Search_QueryParser::parse('('.implode(') OR (', $typeQueries).')');
            $query->addSubquery($typesQuery, true);

            return $query;
        }

        return null;
    }

    /**
     * Return the search terms of a lucene search query within the context of the current index
     *
     * @param Zend_Search_Lucene_Search_Query $query Lucene search query
     *
     * @return array                                                Search terms
     */
    public function getQueryTerms(Zend_Search_Lucene_Search_Query $query)
    {
        $terms = array();
        foreach ($query->rewrite($this->_index)->getQueryTerms() as $term) {
            if (!array_key_exists($term->field, $terms)) {
                $terms[$term->field] = array($term->text);
            } else {
                $terms[$term->field][] = $term->text;
            }
        }

        return $terms;
    }

    /**
     * Clear the index (delete all contained documents)
     *
     * @param boolean $confirm Approval (must be TRUE)
     *
     * @return boolean Success
     * @throws Zend_Search_Lucene_Exception
     */
    public function clear($confirm)
    {
        if ($confirm === true) {
            while (count($hits = $this->_index()->find('timestamp:[0 TO '.time().']'))) {
                foreach ($hits as $hit) {
                    $this->_index()->delete($hit);
                }
                $this->_index()->commit();
            }

            return true;
        }

        return false;
    }

    /**
     * Get autocomplete suggestions for specific search terms
     *
     * @param string $searchTerm Search terms
     *
     * @return array                                                Autocomplete suggestions
     * @throws Zend_Search_Lucene_Exception
     * @todo Rewrite hooks? Language / rootline restrictions
     */
    public function autocomplete($searchTerm)
    {
        $suggestions = array();

        // If there are meaningful search terms
        if (strlen(trim($searchTerm))) {
            $index = Zend_Search_Lucene::open($this->_indexDirectory);

            $query              = new Zend_Search_Lucene_Search_Query_Boolean();
            $searchTermWildcard = $searchTerm."*";
            $pattern            = new Zend_Search_Lucene_Index_Term($searchTermWildcard, null);
            $userQuery          = new Zend_Search_Lucene_Search_Query_Wildcard($pattern);
            $signs              = true;
            $query->addSubquery($userQuery, $signs);

            $hits         = $index->find($query);
            $matchedArray = array();
            foreach ($hits as $hit) {
                foreach ($hit->getIndex()->terms() as $term) {
                    $text    = $term->text;
                    $textKey = trim(strtolower($text));

                    if (substr($text, 0, strlen($searchTerm)) === $searchTerm) {
                        if (!array_key_exists($textKey, $matchedArray)) {
                            $suggestions[] = array($text, $text);
                        }
                        $matchedArray[$textKey] = true;
                    }
                }
            }
        }

        return $suggestions;
    }

    /**
     * Commit all pending index changes
     *
     * @return void
     */
    public function commit()
    {
        if ($this->_index instanceof Zend_Search_Lucene_Interface) {
            $this->_index->commit();
        }
    }
}
