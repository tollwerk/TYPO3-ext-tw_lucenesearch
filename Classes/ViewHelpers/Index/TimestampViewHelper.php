<?php

namespace Tollwerk\TwLucenesearch\ViewHelpers\Index;

/***************************************************************
 *  Copyright notice
 *
 *  © 2013 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH
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
 * View helper for setting / altering the modification timestamp of the current frontend page
 * 
 * = Examples =
 *
 * <code title="Example">
 * <twlucene:index.timestamp timestamp="{article.tstamp}"/>
 * </code>
 *
 * Output:
 * None (there lucene indexer will write appropriate timestamp meta tags into the source code)
 * 
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2013 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author		Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class TimestampViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Set the modification timestamp of the current frontend page
	 *
	 * @param int|DateTime $timestamp		Timestamp
	 * @return string						Dummy string result (empty)
	 */
	public function render($timestamp = null) {
		if ($timestamp instanceof DateTime) {
			$timestamp = $timestamp->format('U');
		} elseif (strlen($timestamp)) {
			$timestamp = intval($timestamp);
		} else {
			$timestamp = 0;
		}
		if ($timestamp) {
			$GLOBALS['TSFE']->index_timestamp = isset($GLOBALS['TSFE']->index_timestamp) ? max($GLOBALS['TSFE']->index_timestamp, intval($timestamp)) : intval($timestamp);
		}		
		return '';
	}
}

?>
