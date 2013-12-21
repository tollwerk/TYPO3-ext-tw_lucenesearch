<?php

namespace Tollwerk\TwLucenesearch\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH
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

require_once 'Zend/Search/Lucene.php';
require_once 'Zend/Search/Lucene/Document.php';

/**
 * Lucene index service
 *
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2012 tollwerk® GmbH (http://tollwerk.de)
 * @author		Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class Lucene extends \TYPO3\CMS\Core\Service\AbstractService implements \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * Index directory
	 * 
	 * @var string
	 */
	protected $_indexDirectory = null;
	/**
	 * Lucene index instance
	 * 
	 * @var \Zend_Search_Lucene_Interface
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
	public function __construct() {
		$this->_indexDirectory = PATH_site.trim($GLOBALS['TYPO3_CONF_VARS']['EXT']['extParams']['tw_lucenesearch']['indexDirectory'], DIRECTORY_SEPARATOR);
	}
	
	/**
	 * Return some information about the index
	 * 
	 * @return \stdClass												Index information
	 */
	public function indexInfo() {
		return (object)array(
			'count'		=> $this->_index()->count(),
			'buffer'	=> $this->_index()->getMaxBufferedDocs(),
			'factor'	=> $this->_index()->getMergeFactor(),
			'memory'	=> $this->_getMemUsage(),
		);
	}
	
	/**
	 * Add a document to the index
	 * 
	 * @param \Tollwerk\TwLucenesearch\Domain\Model\Document $document		Document
	 * @return boolean												Success
	 */
	public function add(\Tollwerk\TwLucenesearch\Domain\Model\Document $document) {
		$this->_index()->addDocument($document);
	}
	
	/**
	 * Fetch a document from the index
	 * 
	 * @param string $reference										Unique document reference
	 * @param \Zend_Search_Lucene_Search_QueryHit $hit				Query hit for the requested document
	 * @return \Tollwerk\TwLucenesearch\Domain\Model\Document				Requested document
	 */
	public function get($reference, &$hit = null) {
		$query					= new \Zend_Search_Lucene_Search_Query_Boolean();
		$refIDTerm				= new \Zend_Search_Lucene_Index_Term($reference, 'reference');
		$refIDQuery				= new \Zend_Search_Lucene_Search_Query_Term($refIDTerm);
		$query->addSubquery($refIDQuery, true);
		$hits					= $this->_index()->find($query);
		$hit					= count($hits) ? current($hits) : null;
		if ($hit instanceof \Zend_Search_Lucene_Search_QueryHit) {
			return \Tollwerk\TwLucenesearch\Domain\Model\Document::cast($hit->getDocument());
		} else {
			$hit				= null;
			return null;
		}
	}
	
	/**
	 * Delete a document from the index
	 *
	 * @param string $reference										Unique document reference
	 * @return void
	 */
	public function delete($id) {
		$this->_index()->delete($id);
	}
	
	/**
	 * Run an index search
	 * 
	 * @param string $searchTerm									Search terms
	 * @param \Zend_Search_Lucene_Search_Query $query				Final index search
	 * @return \Tollwerk\TwLucenesearch\Domain\Model\QueryHits				Query hits
	 */
	public function find($searchTerm, &$query = null) {
		$searchTerm				= trim($searchTerm);
		$hits					= array();
		if (strlen($searchTerm)) {
			require_once 'Zend/Search/Lucene/Search/QueryParserException.php';
			require_once 'Zend/Search/Lucene/Search/QueryLexer.php';
			
			// Apply external rewriters
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['search-rewrite-hooks'])) {
				$params				= array('search' => &$searchTerm, 'pObj' => &$this);
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['search-rewrite-hooks'] as $rewriteHook)	{
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($rewriteHook, $params, $this);
				}
			}
			
			// Extend / rewrite the search query
			$tokenTerms			= array();
			$searchTerm			= $this->_rewriteQueryTerms($searchTerm, $tokenTerms);
			
			try {
				$highlight		= intval(\Tollwerk\TwLucenesearch\Utility\Indexer::indexConfig($GLOBALS['TSFE'], 'search.highlightMatches'));
				
				// Construct the search request
				$query			= $this->query($searchTerm);
				
				// Run the search an collect the results
				$hits			= new \Tollwerk\TwLucenesearch\Domain\Model\QueryHits($this->_index(), $query, $tokenTerms, (boolean)$highlight);
				
			// In case of errors: Invalidate the query hits
			} catch(\Zend_Search_Lucene_Exception $e) {
				$hits			= null;
			}
		}
		
		return $hits;
	}
	
	/**
	 * Create a lucene search from search terms
	 * 
	 * @param string $searchTerm									Search terms
	 * @return \Zend_Search_Lucene_Search_Query						Lucene search query
	 */
	public function query($searchTerm) {
		
		// If there are meaningful search terms
		if (strlen(trim($searchTerm))) {
			$query			= new \Zend_Search_Lucene_Search_Query_Boolean();
			$termQuery		= \Zend_Search_Lucene_Search_QueryParser::parse($searchTerm);
			
			// Include term based restriction
			$query->addSubquery($termQuery, true);
			
			// If applicable: Apply language based restriction
			if (\Tollwerk\TwLucenesearch\Utility\Indexer::indexConfig($GLOBALS['TSFE'], 'search.restrictByLanguage')) {
				require_once 'Zend/Search/Lucene/Search/Query/Term.php';
				require_once 'Zend/Search/Lucene/Index/Term.php';
				$query->addSubquery(new \Zend_Search_Lucene_Search_Query_Term(new \Zend_Search_Lucene_Index_Term($GLOBALS['TSFE']->lang, 'language')), true);
			}
			
			// If applicable: Apply rootline based restriction
			$rootlinePids	= \Tollwerk\TwLucenesearch\Utility\Indexer::indexConfig($GLOBALS['TSFE'], 'search.restrictByRootlinePids');
			if (is_array($rootlinePids) && count($rootlinePids)) {
				require_once 'Zend/Search/Lucene/Search/Query/MultiTerm.php';
				require_once 'Zend/Search/Lucene/Index/Term.php';
				$rootlineQuery		= new \Zend_Search_Lucene_Search_Query_MultiTerm();
				foreach ($rootlinePids as $rootlinePid) {
					$rootlineQuery->addTerm(new \Zend_Search_Lucene_Index_Term($rootlinePid, 'rootline'), null);
				}
				$query->addSubquery($rootlineQuery, true);
			}
			
			return $query;
		}
		
		return null;
	}
	
	/**
	 * Return the search terms of a lucene search query within the context of the current index 
	 * 	
	 * @param \Zend_Search_Lucene_Search_Query $query				Lucene search query
	 * @return array												Search terms 
	 */
	public function getQueryTerms(\Zend_Search_Lucene_Search_Query $query) {
		$terms								= array();
		foreach ($query->rewrite($this->_index)->getQueryTerms() as $term) {
			if (strlen($term->text) >= 3) {
				if (!array_key_exists($term->field, $terms)) {
					$terms[$term->field]	= array($term->text);
				} else {
					$terms[$term->field][]	= $term->text;
				}
			}
		}
		return $terms;
	}
	
	/**
	 * Clear the index (delete all contained documents)
	 * 
	 * @param boolean $confirm										Approval (must be TRUE)
	 * @return boolean												Success
	 */
	public function clear($confirm) {
		if ($confirm === true) {
			while(count($hits = $this->_index()->find('timestamp:[0 TO '.time().']'))) {
				foreach ($hits as $hit) {
					$this->_index()->delete($hit);
				}
				$this->_index()->commit();
			}
			return true;
		}
		return false;
	}
	
	/************************************************************************************************
	 * PRIVAT METHODS
	 ***********************************************************************************************/
	
	/**
	 * Instanciate the Lucene index
	 * 
	 * The index will be created if it doesn't exist yet.
	 * 
	 * @return \Zend_Search_Lucene_Interface							Lucene index instance
	 * @throws Exception											If the index cannot be created
	 */
	protected function _index() {
		
		// One-time instanciation or creation of the lucene index
		if ($this->_index === null) {
			
			// Try to instanciate an existing lucene index
			try {
				$this->_index		 = \Zend_Search_Lucene::open($this->_indexDirectory);
				
			// If an error occurs ...
			} catch (\Zend_Search_Lucene_Exception $e) {
				
				// Try to create a new lucene index ...
				try {
					$this->_index	= \Zend_Search_Lucene::create($this->_indexDirectory);
					
				// If an error occurs: Failure
				} catch (\Zend_Search_Lucene_Exception $e) {
					throw new Exception(sprintf('Error creating lucene index in "%1$s", reason: "%2$s"', $this->_indexDirectory, $e->getMessage()));
				}
			}
			
			// Index setup
			\Zend_Search_Lucene_Storage_Directory_Filesystem::setDefaultFilePermissions(0664);
			\Zend_Search_Lucene_Analysis_Analyzer::setDefault(new \Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());
			\Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('UTF-8');

			// Minimize memory consumption
			$this->_index->setMaxBufferedDocs(1);
			
			// Set optimization frequency
			$this->_index->setMergeFactor(max(1, intval($GLOBALS['TYPO3_CONF_VARS']['EXT']['extParams']['tw_lucenesearch']['mergeFactor'])));
			
			// If applicable: Optimize index
			if ($this->_indexOptimize) {
				$this->_index->optimize();
			}
			
			$this->_index->commit();
			
			if (TYPO3_MODE == 'FE') {
				\Zend_Search_Lucene::setTermsPerQueryLimit(\Tollwerk\TwLucenesearch\Utility\Indexer::indexConfig($GLOBALS['TSFE'], 'search.limits.query'));
			}
		}
		
		return $this->_index;
	}
	
	/**
	 * Return the current memory consumption
	 * 
	 * @return int													Current memory consumption
	 */
	protected function _getMemUsage() {
		if (function_exists('memory_get_peak_usage')) {
			$memory = memory_get_peak_usage(true);
		} else {
			$memory = memory_get_usage(true);
		}
		return ($memory / 1024 / 1024);
	}
	
	/**
	 * Rewrite the original search in accordance to the search configuration
	 * 
	 * @param string $searchterm									Original search terms
	 * @param array $tokenTerms										Single search term tokens
	 * @return string												Rewritten search terms
	 */
	protected function _rewriteQueryTerms($searchTerm, &$tokenTerms = array()) {
		$tokenTerms			= array();

		try {
			$searchConfig	= \Tollwerk\TwLucenesearch\Utility\Indexer::indexConfig($GLOBALS['TSFE'], 'search.searchConfig');
			$lexer			= new \Zend_Search_Lucene_Search_QueryLexer();
			
			// Iterate over the extracted tokens
			/* @var $token \Zend_Search_Lucene_Search_QueryToken */
			$tokens			= array_reverse($lexer->tokenize($searchTerm, 'UTF-8'));
			$tokenCount		= count($tokens);
			foreach($tokens as $tokenIndex => $token) {
				
				// If there's a word or a phrase which has no certain field name applied
				if (
					(($token->type == \Zend_Search_Lucene_Search_QueryToken::TT_WORD) || ($token->type == \Zend_Search_Lucene_Search_QueryToken::TT_PHRASE)) &&
					(($tokenIndex == $tokenCount - 1) || ($tokens[$tokenIndex + 1]->type != \Zend_Search_Lucene_Search_QueryToken::TT_FIELD))
				) {
					$tokenTerms[]			= $token;
					$isPhrase				= ($token->type == \Zend_Search_Lucene_Search_QueryToken::TT_PHRASE);
					$tokenStr				= $isPhrase ? '"'.$token->text.'"' : $token->text;
					$tokenStrLength			= strlen($tokenStr);

					// Apply external rewriters
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['term-rewrite-hooks'])) {
						$params				= array('token' => &$token, 'pObj' => &$this);
						foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tw_lucenesearch']['term-rewrite-hooks'] as $rewriteHook)	{
							\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($rewriteHook, $params, $this);
						}
					}
					
					// If the token type is being changed from "word" dot "phrase" ...
					if (!$isPhrase && ($token->type == \Zend_Search_Lucene_Search_QueryToken::TT_PHRASE)) {
						$isPhrase			= true;
						$tokenStr			= '"'.$token->text.'"';
					}
					
					// Rewrite in accordance to the search configuration
					$tokenRewrite			= array();
					foreach ($searchConfig as $searchField) {
						$value				= ($isPhrase || $searchField->fuzzy) ? strtr($searchField->value, array('*' => '')) : $searchField->value;
						$sfTokenStr			= (($searchField->field !== true) ? $searchField->field.':' : '');
						$sfTokenStr			.= strtr($value, array('?' => $tokenStr));
						if ($searchField->fuzzy !== false) {
							$sfTokenStr		.= '~'.$searchField->fuzzy;
						}
						if ($searchField->boost !== null) {
							$sfTokenStr		.= '^'.$searchField->boost;
						}
						$tokenRewrite[]		= $sfTokenStr;
					}
					$tokenRewrite			= '('.implode(' OR ', $tokenRewrite).')';
					$searchTerm				= substr($searchTerm, 0, $token->position - $tokenStrLength).$tokenRewrite.substr($searchTerm, $token->position);
				}
			}
				
		} catch (\Zend_Search_Lucene_Search_QueryParserException $e) {}
		
		return $searchTerm;
	}
}