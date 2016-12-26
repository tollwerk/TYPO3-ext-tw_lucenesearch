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
 * Array key view helper
 *
 * Returns the key of an element at the given numeric position within an array
 *
 * = Examples =
 *
 * <code title="Example">
 * <twlucene:array.key array="{items}" position="{position}" />
 * </code>
 *
 * Output:
 * The key of the array element at position {position}
 *
 * @package        tw_lucenesearch
 * @copyright    Copyright © 2014 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author        Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class KeyViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * Return an array key at a certain position within the array
     *
     * @param array $array Array
     * @param int $position Position
     * @return mixed                    Array key
     */
    public function render(array $array, $position = 0)
    {
        $keys = array_keys($array);
        return array_key_exists(intval($position), $keys) ? $keys[intval($position)] : null;
    }
}

?>
