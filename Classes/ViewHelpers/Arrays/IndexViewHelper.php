<?php

namespace Tollwerk\TwLucenesearch\ViewHelpers\Arrays;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

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
 * @package tw_lucenesearch
 * @copyright Copyright © 2016 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class IndexViewHelper extends AbstractViewHelper
{

    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('array', 'array', 'Array', false, []);
        $this->registerArgument('index', 'string', 'Index', false, null);
    }

    /**
     * Render the heading
     *
     * @return string Value
     */
    public function render()
    {
        $value = null;
        if (strlen($this->arguments['index'])) {
            $indexArr = explode('[', $this->arguments['index']);
            foreach ($indexArr as $key => $val) {
                $indexArr[$key] = str_replace(array('[', ']'), '', $val);
            }

            if (array_key_exists($indexArr[0], $this->arguments['array'])) {
                $value = $this->arguments['array'][$indexArr[0]];

                $countIndex = count($indexArr);

                for ($i = 1; $i < $countIndex; $i++) {
                    if (!array_key_exists($indexArr[$i], $value)) {
                        break;
                    }

                    $value = $value[$indexArr[$i]];
                }
            }
        }

        return $value;
    }
}
