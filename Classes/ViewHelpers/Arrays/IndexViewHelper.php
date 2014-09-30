<?php

namespace Tollwerk\TwLucenesearch\ViewHelpers\Arrays;

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
 * Array by index view helper
 * 
 * Returns the value of an array element with a specific key 
 * 
 * = Examples =
 *
 * <code title="Example">
 * <twlucene:array.index array="{items}" index="{index}" />
 * </code>
 *
 * Output:
 * The value of the array element with the key {index}
 *
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2014 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author		Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class IndexViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Return an array element with the given key
	 * 
	 * @param \array $array				Array
	 * @param \string $index			Key
	 * @return \mixed					Array element value
	 */
	public function render(array $array, $index = '')
    {
        $value = null;
        if (strlen($index)) {
            $indexArr = explode('[', $index);
            foreach ($indexArr as $key => $val) {
                $indexArr[$key] = str_replace(array('[', ']'), '', $val);
            }

            if (array_key_exists($indexArr[0], $array)) {
                $value = $array[$indexArr[0]];

                for ($i = 1; $i < count($indexArr); $i++) {
                    if (array_key_exists($indexArr[$i], $value)) {
                        $value = $value[$indexArr[$i]];
                    } else {
                        break;
                    }
                }
            }
        }

        return $value;
	}
}