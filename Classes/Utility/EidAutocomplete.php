<?php

/** @var \Tollwerk\TwLucenesearch\Utility\EidDispatcher $eidDispatcher */
$eidDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tollwerk\\TwLucenesearch\\Utility\\EidDispatcher');
$eidDispatcher
    ->setVendorName('Tollwerk')
    ->setExtensionName('TwLucenesearch')
    ->setPluginName('LuceneAutocomplete')
    ->setControllerName('Lucene')
    ->setActionName('autocomplete')
    ->dispatch();
