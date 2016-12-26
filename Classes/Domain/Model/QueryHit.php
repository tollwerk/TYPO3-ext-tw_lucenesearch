<?php

namespace Tollwerk\TwLucenesearch\Domain\Model;

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

require_once 'Zend/Search/Lucene/Document.php';
require_once 'Zend/Search/Lucene/Search/QueryHit.php';

/**
 * Single search result (query hit)
 *
 * @package tw_lucenesearch
 * @copyright Copyright © 2016 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class QueryHit extends \Zend_Search_Lucene_Search_QueryHit
{
    /**
     * Result index
     *
     * @var \int
     */
    protected $_result = 0;
    /**
     * Result cycle index
     *
     * @var \int
     */
    protected $_cycle = 0;

    /**
     * Constructor
     *
     * @param \Zend_Search_Lucene_Interface $index Index
     * @param \int Result index
     * @return void
     */
    public function __construct(\Zend_Search_Lucene_Interface $index, $result = 0)
    {
        $this->_index = $index;
        $this->_result = intval($result);
        $this->_cycle = $this->_result + 1;
    }

    /**
     * Return the associated index document
     *
     * @return \Tollwerk\TwLucenesearch\Domain\Model\Document            Associated index document
     * @see \Zend_Search_Lucene_Search_QueryHit::getDocument()
     */
    public function getDocument()
    {
        if (!($this->_document instanceof \Tollwerk\TwLucenesearch\Domain\Model\Document)) {
            $this->_document = \Tollwerk\TwLucenesearch\Domain\Model\Document::cast(parent::getDocument());
        }
        return $this->_document;
    }

    /**
     * Cast a standard Zend lucene query hit as extended instance
     *
     * @param \Zend_Search_Lucene_Search_QueryHit $hit Query hit
     * @param \int Result index
     * @return \Tollwerk\TwLucenesearch\Domain\Model\QueryHit            Extended query hit
     */
    public static function cast(\Zend_Search_Lucene_Search_QueryHit $hit, $result)
    {
        $extHit = new self($hit->_index, $result);
        foreach (get_object_vars($hit) as $key => $value) {
            if (($key != '_index') && ($key != '_document')) {
                $extHit->$key = $value;
            }
        }
        return $extHit;
    }

    /**
     * Return the result index
     *
     * @return \int            Result index
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Return the result cycle index
     *
     * @return \int            Result cycle index
     */
    public function getCycle()
    {
        return $this->_cycle;
    }

}
