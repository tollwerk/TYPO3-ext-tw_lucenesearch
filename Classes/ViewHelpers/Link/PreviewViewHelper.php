<?php

namespace Tollwerk\TwLucenesearch\ViewHelpers\Link;

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
use TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper;
use Tollwerk\TwLucenesearch\Utility\FrontendSimulator;

/**
 * Index page preview view helper
 *
 * Renders a preview link to a particular page
 *
 * = Examples =
 *
 * <code title="Example">
 * <twlucene:link.preview pageUid="1" additionalParams="{document.referenceParameters}"/>
 * </code>
 *
 * Output:
 * A index preview link for the specified page
 *
 * @package tw_lucenesearch
 * @copyright Copyright © 2016 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 * @author Steffen Düsel
 */
class PreviewViewHelper extends PageViewHelper
{
    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('reference', 'array', 'Specifies some references', true, []);
    }

    /**
     * @param int|NULL $pageUid target page. See TypoLink destination
     * @param array $additionalParams query parameters to be attached to the resulting URI
     * @param int $pageType type of the target page. See typolink.parameter
     * @param bool $noCache set this to disable caching for the target page. You should not need this.
     * @param bool $noCacheHash set this to suppress the cHash query parameter created by TypoLink. You should not need this.
     * @param string $section the anchor to be added to the URI
     * @param bool $linkAccessRestrictedPages If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.
     * @param bool $absolute If set, the URI of the rendered link is absolute
     * @param bool $addQueryString If set, the current query parameters will be kept in the URI
     * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
     * @param string $addQueryStringMethod Set which parameters will be kept. Only active if $addQueryString = TRUE
     * @return string Rendered page URI
     */
    public function render(
        $pageUid = null,
        array $additionalParams = [],
        $pageType = 0,
        $noCache = false,
        $noCacheHash = false,
        $section = '',
        $linkAccessRestrictedPages = false,
        $absolute = false,
        $addQueryString = false,
        array $argumentsToBeExcludedFromQueryString = [],
        $addQueryStringMethod = null
    ) {
        $reference = $this->arguments['reference'];

        $pageUid = 0;
        if (array_key_exists('id', $reference)) {
            $pageUid = $reference['id'];
            unset($reference['id']);
        }

        $pageType = 0;
        if (array_key_exists('type', $reference)) {
            $pageType = $reference['type'];
            unset($reference['type']);
        }

        $reference['index_content_only'] = 1;

        if (TYPO3_MODE === 'BE') {
            FrontendSimulator::simulateFrontendEnvironment($pageUid);
        }

        $uriBuilder = $this->controllerContext->getUriBuilder();
        $uri = $uriBuilder
            ->reset()
            ->setTargetPageUid($pageUid)
            ->setTargetPageType($pageType)
            ->setNoCache(true)
            ->setUseCacheHash(false)
            ->setLinkAccessRestrictedPages(true)
            ->setArguments($reference)
            ->setCreateAbsoluteUri(true)
            ->setAddQueryString(false)
            ->setArgumentsToBeExcludedFromQueryString(array())
            ->buildFrontendUri();
        if (strlen($uri)) {
            $this->tag->addAttribute('href', $uri);
            $this->tag->setContent($this->renderChildren());
            $result = $this->tag->render();
        } else {
            $result = $this->renderChildren();
        }

        if (TYPO3_MODE === 'BE') {
            FrontendSimulator::resetFrontendEnvironment();
        }

        return $result;
    }
}
