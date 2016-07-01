<?php

namespace Tollwerk\TwLucenesearch\Domain\Model;

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
 * Search result set (query hits)
 *
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2014 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author		Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class QueryHits implements \TYPO3\CMS\Extbase\Persistence\QueryResultInterface {
	/**
	 * Lucene index
	 * 
	 * @var \Zend_Search_Lucene_Proxy
	 */
	protected $_index = null;
	/**
	 * Lucene index query
	 * 
	 * @var \Zend_Search_Lucene_Search_Query
	 */
	protected $_query = null;
	/**
	 * Results
	 * 
	 * @var array
	 */
	protected $_hits = array();
	/**
	 * Offset within the result set
	 * 
	 * @var int
	 */
	protected $_offset = 0;
	/**
	 * Result limit
	 * 
	 * @var int
	 */
	protected $_limit = null;
	/**
	 * Query terms
	 * 
	 * @var array
	 */
	protected $_terms = null;
	/**
	 * Query terms that should be highlighted
	 * 
	 * @var array
	 */
	protected $_highlight = null;
	
	/************************************************************************************************
	 * PUBLIC METHODS
	 ***********************************************************************************************/
	
	/**
	 * Constructor
	 * 
	 * @param \Zend_Search_Lucene_Interface					Lucene index instance
	 * @param \Zend_Search_Lucene_Search_Query $query		Lucene query
	 * @param array $terms									Original search terms
	 * @param boolean $highlight							Highlight search terms in results
	 * @return void
	 */
	public function __construct(\Zend_Search_Lucene_Interface $index, \Zend_Search_Lucene_Search_Query $query, array $terms = array(), $highlight = false) {
		$this->_index			= $index;
		$this->_query			= $query;
		$this->_terms			= $terms;
		$this->_index->addReference();

		// Run the Lucene search and register the results
		foreach ($this->_index->find($this->_query) as $hit) {
			$this->_hits[]		= \Tollwerk\TwLucenesearch\Domain\Model\QueryHit::cast($hit, count($this->_hits));
		}
		
		// If search terms should be highlighted ...
		if (count($this->_hits) && $highlight) {
			$this->_highlight	= array();
			foreach ($this->_query->rewrite($this->_index)->getQueryTerms() as $term) {
				if (!array_key_exists($term->field, $this->_highlight)) {
					$this->_highlight[$term->field] = array($term->text);
				} else {
					$this->_highlight[$term->field][] = $term->text;
				}
			}
		}
	}
	
	/**
	 * Destructor
	 * 
	 * @return void
	 */
	public function __destruct() {
		if ($this->_index instanceof \Zend_Search_Lucene_Interface) {
			$this->_index->removeReference();
		}
	}

	/**
	 * Set the result limit
	 * 
	 * @param int $limit		Result limit
	 * @return void
	 */
	public function setLimit($limit) {
		$this->_limit			= intval($limit);
	}
	
	/**
	 * Set the result offste
	 * 
	 * @param int $offset		Result offset
	 * @return void
	 */
	public function setOffset($offset) {
		$this->_offset			= intval($offset);
	}
	
	/**
	 * Return the current results (result page)
	 * 
	 * @return array			Current results
	 */
	public function execute() {
		$hits					= array_slice($this->_hits, $this->_offset, $this->_limit);
		return array_combine(range($this->_offset + 1, min($this->_offset + $this->_limit, $this->_offset + count($hits))), $hits);
	}
	
	/**
	 * Return the number of results
	 * 
	 * @return int				Number of results
	 * @see Countable::count()
	 */
	public function count () {
		return count($this->_hits);
	}
	
	/**
	 * Reset the result set
	 * 
	 * @return void
	 */
	public function rewind() {
		reset($this->_hits);
	}
	
	/**
	 * Return the current result within the result set
	 * 
	 * @return array			Result
	 */
	public function current() {
		return current($this->_hits);
	}

	/**
	 * Return the current result index
	 * 
	 * @return int				Result index
	 */
	public function key() {
		return key($this->_hits);
	}
	
	/**
	 * Iterate over the result set and return the next result
	 * 
	 * @return array			Next result
	 */
	public function next() {
		return next($this->_hits);
	}
	
	/**
	 * Check if there is a valid result at the current result index
	 * 
	 * @return boolean			Valid result
	 */
	public function valid() {
		return ($this->current() !== false);
	}
	
	/**
	 * Add a result to the result set
	 * 
	 * @param int $offset		Position
	 * @param array $hit		result
	 * @return void
	 */
	public function offsetSet($offset, $hit) {
		if (is_null($hit)) {
			$this->_hits[] = $hit;
		} else {
			$this->_hits[$offset] = $hit;
		}
	}
	
	/**
	 * Check if a result index exists
	 * 
	 * @param int $hit			Position
	 * @return boolean			Position exists
	 */
	public function offsetExists($hit) {
		return isset($this->_hits[$hit]);
	}
	
	/**
	 * Remove a result from the result set
	 * 
	 * @param int $hit			Position
	 * @return void
	 */
	public function offsetUnset($hit) {
		unset($this->_hits[$hit]);
	}
	
	/**
	 * Get the result at a certain index
	 * 
	 * @param int $hit			Position
	 * @return array			Result
	 */
	public function offsetGet($hit) {
		return isset($this->_hits[$hit]) ? $this->_hits[$hit] : null;
	}
	
	/**
	 * Return the search query (in fact: return a self reference)
	 * 
	 * @return \Tollwerk\TwLucenesearch\Domain\Model\QueryHits		Self reference
	 */
	public function getQuery() {
		return $this;
	}
	
	/**
	 * Return the first result
	 * 
	 * @return \Tollwerk\TwLucenesearch\Domain\Model\QueryHit		First result
	 */
	public function getFirst() {
		return count($this->_hits) ? $this->_hits[0] : null;
	}
	
	/**
	 * Return the results as array
	 * 
	 * @return array					Result array
	 */
	public function toArray() {
		return $this->_hits;
	}
	
	/**
	 * Return the current result page offset
	 * 
	 * @return int						Result page offset
	 */
	public function getOffset() {
		return $this->_offset;
	}
	
	/**
	 * Return the result limit
	 * 
	 * @return int						Result limit
	 */
	public function getLimit() {
		return $this->_limit;
	}
	
	/**
	 * Return the absolute index / position of the first result on the current result page
	 * 
	 * @return int						Absolute index / position of the first result on the current result page
	 */
	public function getStart() {
		return $this->_offset + 1;
	}
	
	/**
	 * Return the absolute index / position of the last result on the current result page
	 *
	 * @return int						Absolute index / position of the last result on the current result page
	 */
	public function getEnd() {
		return min($this->count(), $this->_offset + $this->_limit);
	}
	
	/**
	 * Return the total number of results
	 *
	 * @return int						Total number of results
	 */
	public function getCount() {
		return $this->count();
	}
	
	/**
	 * Return the search terms
	 * 
	 * @return array					Search terms
	 */
	public function getTerms() {
		return $this->_terms;
	}
	
	/**
	 * Return the search terms to be highlighted
	 * 
	 * @param string $field				Optional: Return only search terms for a certain document field
	 * @return array					Search terms to be highlighted
	 */
	public function getHighlight($field = null) {
		return (($field === null) || ($this->_highlight === null)) ? $this->_highlight : (($field && array_key_exists($field, $this->_highlight)) ? $this->_highlight[$field] : null);
	}
}

?>