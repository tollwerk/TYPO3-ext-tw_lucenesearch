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

namespace Tollwerk\TwLucenesearch\ViewHelpers\Arrays;

use Closure;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

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
 * @package   tw_lucenesearch
 * @copyright Copyright © 2020 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author    Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 */
class IndexViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Render a resource URL for Fractal, possibly treated with the `path` view helper
     *
     * @param array $arguments
     * @param Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $value = null;
        if (strlen($arguments['index'])) {
            $indexArr = explode('[', $arguments['index']);
            foreach ($indexArr as $key => $val) {
                $indexArr[$key] = str_replace(array('[', ']'), '', $val);
            }

            if (array_key_exists($indexArr[0], $arguments['array'])) {
                $value = $arguments['array'][$indexArr[0]];

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

    /**
     * Initialize arguments
     *
     * @api
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('array', 'array', 'Array', true);
        $this->registerArgument('index', 'mixed', 'Index', true);
    }
}
