<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 IMIA net based solutions (info@imia.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

namespace Tollwerk\TwLucenesearch\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Utility\EidUtility;

/**
 * @package     tw_lucenesearch
 * @subpackage  Utility
 * @author      David Frerich <d.frerich@imia.de>
 */
class EidDispatcher
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $vendorName;

    /**
     * @var string
     */
    protected $extensionName;

    /**
     * @var string
     */
    protected $pluginName;

    /**
     * @var string
     */
    protected $controllerName;

    /**
     * @var string
     */
    protected $actionName;

    /**
     * @var array
     */
    protected $arguments;

    public function dispatch()
    {
        $this->init();
        $configuration = [
            'vendorName'                  => $this->vendorName,
            'extensionName'               => $this->extensionName,
            'pluginName'                  => $this->pluginName,
            'switchableControllerActions' => [
                $this->controllerName => [$this->actionName],
            ],
        ];

        $bootstrap = $this->getObjectManager()->get('TYPO3\CMS\Extbase\Core\Bootstrap');
        $bootstrap->initialize($configuration);
        $bootstrap->cObj = $GLOBALS['TSFE']->cObj;

        $request = $this->getObjectManager()->get('TYPO3\CMS\Extbase\Mvc\Web\Request');
        $request->setControllerVendorName($this->vendorName);
        $request->setControllerExtensionName($this->extensionName);
        $request->setPluginName($this->pluginName);
        $request->setControllerName($this->controllerName);
        $request->setControllerActionName($this->actionName);
        $request->setArguments($this->arguments ?: (array)GeneralUtility::_GP('arguments'));

        /** @var \TYPO3\CMS\Extbase\Mvc\Web\Response $response */
        $response = $this->getObjectManager()->get('TYPO3\CMS\Extbase\Mvc\Web\Response');
        $response->setHeader('Access-Control-Allow-Origin', '*');

        $dispatcher = $this->getObjectManager()->get('TYPO3\CMS\Extbase\Mvc\Dispatcher');
        $dispatcher->dispatch($request, $response);

        $response->send();
    }

    /**
     * @param integer $pageUid
     * @return $this
     */
    public function init($pageUid = null)
    {
        $pageRepository = $this->getObjectManager()->get('TYPO3\CMS\Frontend\Page\PageRepository');
        if (!is_numeric($pageUid)) {
            $pageUid = $pageRepository->getDomainStartPage($_SERVER['HTTP_HOST']);
        }

        $GLOBALS['TSFE'] = $this->getObjectManager()->get('TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController',
            $GLOBALS['TYPO3_CONF_VARS'], $pageUid, 0, 1);

        $GLOBALS['TSFE']->sys_page = $pageRepository;

        EidUtility::initLanguage();
        EidUtility::initTCA();

        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getConfigArray();

        $GLOBALS['TSFE']->renderCharset = 'utf-8';

        $GLOBALS['TSFE']->cObj = $this->getObjectManager()->get('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
        $GLOBALS['TSFE']->settingLanguage();
        $GLOBALS['TSFE']->settingLocale();

        return $this;
    }

    /**
     * @param string $vendorName
     * @return $this
     */
    public function setVendorName($vendorName)
    {
        $this->vendorName = $vendorName;

        return $this;
    }

    /**
     * @param string $extensionName
     * @return $this
     */
    public function setExtensionName($extensionName)
    {
        $this->extensionName = $extensionName;

        return $this;
    }

    /**
     * @param string $pluginName
     * @return $this
     */
    public function setPluginName($pluginName)
    {
        $this->pluginName = $pluginName;

        return $this;
    }

    /**
     * @param string $controllerName
     * @return $this
     */
    public function setControllerName($controllerName)
    {
        $this->controllerName = $controllerName;

        return $this;
    }

    /**
     * @param string $actionName
     * @return $this
     */
    public function setActionName($actionName)
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     * @return EidDispatcher
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    public function getObjectManager()
    {
        if (!$this->objectManager) {
            $this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        }

        return $this->objectManager;
    }
}