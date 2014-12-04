<?php

namespace Tollwerk\TwLucenesearch\Utility;

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
 * Lucene indexer
 *
 * @package		tw_lucenesearch
 * @copyright	Copyright © 2014 Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>, tollwerk® GmbH (http://tollwerk.de)
 * @author		Dipl.-Ing. Joschi Kuphal <joschi@tollwerk.de>
 * @author		Christian Eßl <essl@incert.at>
 */
class Indexer implements \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * Charset object
	 * 
	 * @var t3lib_cs
	 */
	protected $_csObj = null;
	/**
	 * Indexer configuration
	 * 
	 * @var array
	 */
	protected static $_config = null;
	/**
	 * Set the page title
	 * 
	 * @var boolean
	 */
	protected static $_setPageTitle = false;
	/**
	 * Debug features are enabled
	 * 
	 * @var boolean
	 */
	protected $_debug = false;
	/**
	 * libxml-Errors
	 * 
	 * @var array
	 */
	protected static $_libxmlErrors = array(
		51 => 'XML_ERR_ATTLIST_NOT_FINISHED',
		50 => 'XML_ERR_ATTLIST_NOT_STARTED',
		40 => 'XML_ERR_ATTRIBUTE_NOT_FINISHED',
		39 => 'XML_ERR_ATTRIBUTE_NOT_STARTED',
		42 => 'XML_ERR_ATTRIBUTE_REDEFINED',
		41 => 'XML_ERR_ATTRIBUTE_WITHOUT_VALUE',
		63 => 'XML_ERR_CDATA_NOT_FINISHED',
		10 => 'XML_ERR_CHARREF_AT_EOF',
		13 => 'XML_ERR_CHARREF_IN_DTD',
		12 => 'XML_ERR_CHARREF_IN_EPILOG',
		11 => 'XML_ERR_CHARREF_IN_PROLOG',
		45 => 'XML_ERR_COMMENT_NOT_FINISHED',
		83 => 'XML_ERR_CONDSEC_INVALID',
		95 => 'XML_ERR_CONDSEC_INVALID_KEYWORD',
		59 => 'XML_ERR_CONDSEC_NOT_FINISHED',
		58 => 'XML_ERR_CONDSEC_NOT_STARTED',
		61 => 'XML_ERR_DOCTYPE_NOT_FINISHED',
		4 => 'XML_ERR_DOCUMENT_EMPTY',
		5 => 'XML_ERR_DOCUMENT_END',
		3 => 'XML_ERR_DOCUMENT_START',
		55 => 'XML_ERR_ELEMCONTENT_NOT_FINISHED',
		54 => 'XML_ERR_ELEMCONTENT_NOT_STARTED',
		79 => 'XML_ERR_ENCODING_NAME',
		90 => 'XML_ERR_ENTITY_BOUNDARY',
		87 => 'XML_ERR_ENTITY_CHAR_ERROR',
		29 => 'XML_ERR_ENTITY_IS_EXTERNAL',
		30 => 'XML_ERR_ENTITY_IS_PARAMETER',
		89 => 'XML_ERR_ENTITY_LOOP',
		37 => 'XML_ERR_ENTITY_NOT_FINISHED',
		36 => 'XML_ERR_ENTITY_NOT_STARTED',
		88 => 'XML_ERR_ENTITY_PE_INTERNAL',
		104 => 'XML_ERR_ENTITY_PROCESSING',
		14 => 'XML_ERR_ENTITYREF_AT_EOF',
		17 => 'XML_ERR_ENTITYREF_IN_DTD',
		16 => 'XML_ERR_ENTITYREF_IN_EPILOG',
		15 => 'XML_ERR_ENTITYREF_IN_PROLOG',
		22 => 'XML_ERR_ENTITYREF_NO_NAME',
		23 => 'XML_ERR_ENTITYREF_SEMICOL_MISSING',
		75 => 'XML_ERR_EQUAL_REQUIRED',
		82 => 'XML_ERR_EXT_ENTITY_STANDALONE',
		60 => 'XML_ERR_EXT_SUBSET_NOT_FINISHED',
		86 => 'XML_ERR_EXTRA_CONTENT',
		73 => 'XML_ERR_GT_REQUIRED',
		80 => 'XML_ERR_HYPHEN_IN_COMMENT',
		1 => 'XML_ERR_INTERNAL_ERROR',
		9 => 'XML_ERR_INVALID_CHAR',
		8 => 'XML_ERR_INVALID_CHARREF',
		7 => 'XML_ERR_INVALID_DEC_CHARREF',
		81 => 'XML_ERR_INVALID_ENCODING',
		6 => 'XML_ERR_INVALID_HEX_CHARREF',
		91 => 'XML_ERR_INVALID_URI',
		44 => 'XML_ERR_LITERAL_NOT_FINISHED',
		43 => 'XML_ERR_LITERAL_NOT_STARTED',
		38 => 'XML_ERR_LT_IN_ATTRIBUTE',
		72 => 'XML_ERR_LT_REQUIRED',
		74 => 'XML_ERR_LTSLASH_REQUIRED',
		62 => 'XML_ERR_MISPLACED_CDATA_END',
		101 => 'XML_ERR_MISSING_ENCODING',
		53 => 'XML_ERR_MIXED_NOT_FINISHED',
		52 => 'XML_ERR_MIXED_NOT_STARTED',
		68 => 'XML_ERR_NAME_REQUIRED',
		67 => 'XML_ERR_NMTOKEN_REQUIRED',
		94 => 'XML_ERR_NO_DTD',
		2 => 'XML_ERR_NO_MEMORY',
		103 => 'XML_ERR_NOT_STANDALONE',
		85 => 'XML_ERR_NOT_WELL_BALANCED',
		49 => 'XML_ERR_NOTATION_NOT_FINISHED',
		48 => 'XML_ERR_NOTATION_NOT_STARTED',
		105 => 'XML_ERR_NOTATION_PROCESSING',
		35 => 'XML_ERR_NS_DECL_ERROR',
		0 => 'XML_ERR_OK',
		69 => 'XML_ERR_PCDATA_REQUIRED',
		18 => 'XML_ERR_PEREF_AT_EOF',
		20 => 'XML_ERR_PEREF_IN_EPILOG',
		21 => 'XML_ERR_PEREF_IN_INT_SUBSET',
		19 => 'XML_ERR_PEREF_IN_PROLOG',
		24 => 'XML_ERR_PEREF_NO_NAME',
		25 => 'XML_ERR_PEREF_SEMICOL_MISSING',
		47 => 'XML_ERR_PI_NOT_FINISHED',
		46 => 'XML_ERR_PI_NOT_STARTED',
		71 => 'XML_ERR_PUBID_REQUIRED',
		64 => 'XML_ERR_RESERVED_XML_NAME',
		66 => 'XML_ERR_SEPARATOR_REQUIRED',
		65 => 'XML_ERR_SPACE_REQUIRED',
		78 => 'XML_ERR_STANDALONE_VALUE',
		34 => 'XML_ERR_STRING_NOT_CLOSED',
		33 => 'XML_ERR_STRING_NOT_STARTED',
		76 => 'XML_ERR_TAG_NAME_MISMATCH',
		77 => 'XML_ERR_TAG_NOT_FINISHED',
		26 => 'XML_ERR_UNDECLARED_ENTITY',
		31 => 'XML_ERR_UNKNOWN_ENCODING',
		108 => 'XML_ERR_UNKNOWN_VERSION',
		28 => 'XML_ERR_UNPARSED_ENTITY',
		32 => 'XML_ERR_UNSUPPORTED_ENCODING',
		92 => 'XML_ERR_URI_FRAGMENT',
		70 => 'XML_ERR_URI_REQUIRED',
		84 => 'XML_ERR_VALUE_REQUIRED',
		109 => 'XML_ERR_VERSION_MISMATCH',
		96 => 'XML_ERR_VERSION_MISSING',
		57 => 'XML_ERR_XMLDECL_NOT_FINISHED',
		56 => 'XML_ERR_XMLDECL_NOT_STARTED',
	);
	/**
	 * UTF-8
	 * 
	 * @var string
	 */
	const UTF8 = 'UTF-8';
	/**
	 * Document type: TYPO3 page
	 * 
	 * @var int
	 */
	const PAGE = 1;
	
	/************************************************************************************************
	 * PUBLIC METHODS
	 ***********************************************************************************************/
	
	/**
	 * Hook output after rendering of non-cached pages
	 * 
	 * @param array $params				Parameters
	 * @param object $that				Parent object
	 * @return void
	 */
	public function intPages(&$params, &$that) {
		if (!$GLOBALS['TSFE']->isINTincScript()) {
			return;
		}
		$this->_indexTSFE($params['pObj']);
	}
	
	/**
	 * Hook output after rendering of cached pages
	 * 
	 * @param array $params				Parameters
	 * @param object $that				Parent object
	 * @return void
	 */
	public function noIntPages(&$params, &$that) {
		if ($GLOBALS['TSFE']->isINTincScript()) {
			return;
		}
		$this->_indexTSFE($params['pObj']);
	}
	
	/**
	 * Return the modification timestamp of the current page
	 * 
	 * @return int							Modification timestamp
	 */
	public function pageIndexTimestamp() {
		if (isset($GLOBALS['TSFE']->index_timestamp)) {
			return intval($GLOBALS['TSFE']->index_timestamp);
		} elseif(is_array($GLOBALS['TSFE']->page) && array_key_exists('tstamp', $GLOBALS['TSFE']->page)) {
			return intval($GLOBALS['TSFE']->page['tstamp']);
		} else {
			return null;
		}
	}
	
	/************************************************************************************************
	 * PRIVAT METHODS
	 ***********************************************************************************************/
	
	/**
	 * Indexing the current frontend page
	 * 
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe			Frontend engine
	 * @return void
	 */
	protected function _indexTSFE(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe) {
		$this->_debug					= (boolean)$GLOBALS['TYPO3_CONF_VARS']['EXT']['extParams']['tw_lucenesearch']['debug'];
		
		// Debug switch: Output indexing contents only
		if ($this->_debug && array_key_exists('index_content_only', $_GET) && intval($_GET['index_content_only'])) {
			ob_end_clean();
			header('Content-Type: text/plain');
			die($this->_getPageBodytext($fe));
		}
		// If requested: Set the page title
		if (self::$_setPageTitle) {
			// If the page title has already been set ...
			if (preg_match("%\<title(\s+[^\>]*)?\>[^\<]*\<\/title\>%", $fe->content, $pageTitle)) {
				$fe->content			= str_replace($pageTitle[0], '<title'.$pageTitle[1].'>'.$fe->page['title'].'</title>', $fe->content); 
				
			// Else ...
			} else {
				preg_replace("%\<head[^\>]*\>%", "$0<title>".$fe->page['title'].'</title>', $fe->content);
			}
		}

		// If the current page should be indexed
		if (!intval($fe->page['no_search']) && self::indexConfig($fe, 'enable')) {
			// Instanciate the lucene index service
			/* @var $indexerService \Tollwerk\TwLucenesearch\Service\Lucene */
			$indexerService				= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('index', 'lucene');
			if ($indexerService instanceof \TYPO3\CMS\Core\Service\AbstractService) {
				// Construct unique page reference
				$reference				= $this->_getPageReference($fe);
				
				// Retrieve timestamp of the current page
				$timestamp				= $this->_getPageTimestamp($fe);
				
				// Try to fetch a predecessor of the current page from the index
				$hit					= null;
				$document				= $indexerService->get(self::documentUid(strval($fe->id), self::PAGE, $reference), $hit);
				
				// If there exists a predecessor ...
				if ($document instanceof \Zend_Search_Lucene_Document) {
					// If a timestamp could be retrieved for the current page and there have been no changes: Stop
					if ((!$this->_debug || !array_key_exists('index_force_reindex', $_GET) || !intval($_GET['index_force_reindex'])) && $timestamp && ($timestamp <= intval($document->getField('timestamp')->value))) {
						return;
						
					// Else: Deletion of the predecessor
					} else {
						$indexerService->delete($hit);
					}
				}
				
				// Instanciate the index document
				$this->_csObj			= \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
				$bodytext				= $this->_getPageBodytext($fe);
				$document				= $this->_createDocument(array(
					'type'				=> self::PAGE,
					'id'				=> strval($fe->id),
					'language'			=> strval($fe->lang),
					'reference'			=> $reference,
					'rootline'			=> implode(' ', $this->_getRootLine($fe)),
					'title'				=> $this->_getPageTitle($fe),
					'abstract'			=> $this->_getPageAbstract($fe, $bodytext),
					'keywords'			=> $this->_getPageKeywords($fe),
					'bodytext'			=> $bodytext,
					'timestamp'			=> $timestamp,
				));
				$indexerService->add($document);
			}
		}
	}
	
	/**
	 * Construct the unique reference identifier of the current page
	 * 
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe					Frontend engine
	 * @return string						Unique reference identifier
	 */
	public function _getPageReference(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe) {
		$reference					= array();
		$parameters					= \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule(
            \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule(array(
                'id'					=> $fe->id,
                'type'					=> $fe->type,
            ), $_GET),
            $_POST
        );
		$parameters['id']			= intval($parameters['id']);
		$parameters['type']			= intval($parameters['type']);
		
		foreach (self::indexConfig($fe, 'reference') as $key => $config) {
			$referenceVariable		= $this->_getReferenceVariable($parameters, $key, $config);
			
			if ($referenceVariable !== null) {
				$reference[$key]	= $referenceVariable;
			}
		}
		ksort($reference);
		return serialize($reference);
	}
	
	/**
	 * Return a certain reference variable
	 * 
	 * @param array $stack					Variable pool
	 * @param string $key					Variable name
	 * @param array|\stdClass $config		Variable configuration
	 * @return array|string					Variable value
	 */
	protected function _getReferenceVariable($stack, $key, $config) {
		$result									= null;
		
		// If the variable pool is available
		if (is_array($stack)) {
			
			// If the requested variable is member of the pool
			if (array_key_exists($key, $stack)) {

				// If there are further sub-keys
				if (is_array($stack[$key]) && is_array($config)) {
					$result						= array();
					foreach ($config as $subkey => $subconfig) {
						$subvalue				= $this->_getReferenceVariable($stack[$key], $subkey, $subconfig);
						if ($subvalue !== null) {
							$result[$subkey]	= $subvalue;
						}
					}
					
					if (!count($result)) {
						$result					= null;
					}
					
				// Else: If it's a scalar value
				} elseif (is_scalar($stack[$key]) && ($config instanceof \stdClass)) {
					$result						= $stack[$key];

					// If there are value restrictions and the current value is not valid: Return the default value
					if (($result !== null) && is_array($config->constraints)) {
						if (in_array($result, $config->constraints)) {
							// keep type (e.g. constraints is int, result is string)
							$constraintsKey = array_search($result, $config->constraints);
							$result = $config->constraints[$constraintsKey];
						} else {
							$result					= $config->default;
						}
					}
				}
				
			// Else: Return the default value
			} else {
				$result							= $this->_getReferenceVariable(null, $key, $config);
			}
			
		// Else: Just return the default value
		} else {
			
			// If there are further sub-keys
			if (is_array($config)) {
				$result							= array();
				foreach ($config as $subkey => $subconfig) {
					$subvalue					= $this->_getReferenceVariable(null, $subkey, $subconfig);
					if ($subvalue !== null) {
						$result[$subkey]		= $subvalue;
					}
				}
				if (!count($result)) {
					$result						= null;
				}
				
			// Else: Return the default value
			} elseif ($config instanceof \stdClass) {
				$result							= $config->default;
			}
		}
		
		return $result;
	}
	
	/**
	 * Return the curent rootline IDs
	 * 
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe			Frontend engine
	 * @return array				Rootline IDs
	 */
	protected function _getRootLine(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe) {
		$rootLine					= array();
		foreach ($fe->rootLine as $pageRecord) {
			array_unshift($rootLine, $pageRecord['uid']);
		}
		return $rootLine;
	}
	
	/**
	 * Extract the indexable text content of the current frontend page
	 *
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe			Frontend engine
	 * @return string				Indexable text content
	 */
	protected function _getPageBodytext(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe) {
		$content					= $fe->content;
		
		// Extract the <body>-part (if applicable)
		if (preg_match("%\<body[^\>]*\>(.*)\<\/body[^\>]*\>%si", $content, $body)) {
			$content				= $body[1];
		}
		
		// If there are indexing markers contained ...
		if (strpos($content, '<!--TYPO3SEARCH_begin-->') !== false) {
			$contentParts			= explode('<!--TYPO3SEARCH_begin-->', $content);
			array_shift($contentParts);
			foreach ($contentParts as $part => $content) {
				if (strpos($content, '<!--TYPO3SEARCH_end-->') !== false) {
					list($contentParts[$part]) = explode('<!--TYPO3SEARCH_end-->', $content);
				}
			}
			
		// Else: Use the complete contents
		} else {
			$contentParts			= array($content);
		}
		
		// Use libxml errors
		libxml_use_internal_errors(true);
		$texts							= null;
		$html							= new \DOMDocument('1.0', 'utf-8');
		$html->loadHTML('<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>'.implode(' ', $contentParts).'</body></html>');
		
		// See if a parse error has happened ...
		foreach (libxml_get_errors() as $libxmlError) {
			if (array_key_exists($libxmlError->code, self::$_libxmlErrors)) {
		
				// Brute force approach: strip all tags and take the remaining text content
				$texts					= trim(preg_replace(array("%\<[^\>]+\>%", "%\s+%"), ' ', implode(' ', $contentParts)));
				break;
			}
		}
		
		// Else: Extract text value
		if ($texts === null) {
			$xpath						= new \DOMXPath($html);
		
			// Strip scripts and layout directives
			foreach ($xpath->query('//*[local-name(.) = "script" or local-name(.) = "style"]') as $layout) {
				$layout->parentNode->removeChild($layout);
			}
		
			// Extract text components
			$texts						= array();
			foreach ($xpath->query('//text()') as $text) {
				$text					= trim(preg_replace("%\s+%", ' ', $text->nodeValue));
				if (strlen($text)) {
					$texts[]			= $text;
				}
			}
			$texts						= trim(implode(' ', $texts));
		}
		libxml_use_internal_errors(false);
		
		return html_entity_decode($texts, ENT_QUOTES, 'UTF-8');
	}
	
	/**
	 * Extract the title of the current frontend page
	 * 
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe			Frontend engine
	 * @return string				Page title
	 */
	protected function _getPageTitle(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe) {
		$title			= '';

		// Prefer the indexing title (if available)
		if (strlen(trim($fe->indexedDocTitle))) {
			$title		=  trim($fe->indexedDocTitle);
			
		// Else: Extract the page title from the source code if possible
		} elseif (preg_match('%\<title[^\>]*\>([^\:]*?\:)?(.*?)\<\/title%', $fe->content, $title)) {
			$title		=trim($title[2]);
		
		// Else: Extract the alternative page title if given
		} elseif(strlen(trim($fe->altPageTitle))) {
			$title		= trim($fe->altPageTitle);
		
			// Ansonsten: Ggf. den Titel des Seitenobjekts zurückgeben
		// Else: Return the page records title if given
		} elseif(isset($fe->page) && is_array($fe->page) && strlen(trim($fe->page['title']))) {
			$title		= trim($fe->page['title']);
		}
		
		return $title;
	}
	
	/**
	 * Extract the page abstract of the current frontend page
	 *
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe			Frontend engine
	 * @param string $bodytext		Fallback abstract
	 * @return string				Page abstract
	 */
	protected function _getPageAbstract(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe, $abstract = '') {
		
		// If there is an description meta tag ...
		if (($description =  $this->_getMetaContent($fe, 'description')) !== null) {
			$abstract		= $description;
			
		// Else: Return the page record abstract if given 
		} elseif(isset($fe->page) && is_array($fe->page) && strlen(trim($fe->page['abstract']))) {
			$abstract		= trim($fe->page['abstract']);
		}
				
		return $this->_csObj->strtrunc('utf-8', $abstract, 255);
	}
	
	/**
	 * Extract the keywords of the current frontend page
	 *
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe			Frontend engine
	 * @return string				Keywords
	 */
	protected function _getPageKeywords(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe) {
		$rawKeywords			= '';
		
		// If there is an keywords meta tag ...
		if (($keywords =  $this->_getMetaContent($fe, 'keywords')) !== null) {
			$rawKeywords		= $keywords;
			
		// Else: Return the page record keywords if given 
		} elseif(isset($fe->page) && is_array($fe->page) && strlen(trim($fe->page['keywords']))) {
			$rawKeywords		= trim($fe->page['abstract']);
		}
		
		return $rawKeywords;
	}
	
	/**
	 * Extract the timestamp of the current frontend page
	 *
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe			Frontend engine
	 * @return int					timestamp
	 */
	protected function _getPageTimestamp(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe) {
		$timestampMetaTags		= array('DC.Date' => null, 'Date' => null, 'Last-Modified' => null);
		$timestamp				= isset($fe->index_timestamp) ? intval($fe->index_timestamp) : null;
		if (!$timestamp) {
			$timestamp			= null;
		}
		
		// If there is a timestamp meta tag ...
		foreach ($timestampMetaTags as $tag => $dummy) {
			$tagElement			= null;
			$tagTimestamp		= $this->_getMetaContent($fe, $tag, $tagElement);
			if ($tagTimestamp !== null) {
				$timestampMetaTags[$tag]	= $tagElement[0];
				if ($timestamp === null) {
					$timestamp		= intval(@strtotime($tagTimestamp));
					if (!$timestamp) {
						$timestamp	= null;
					}
				}
			}
		}
		
		// Else: Return the page record timestamp if given 
		if (($timestamp === null) && isset($fe->page) && is_array($fe->page) && strlen(trim($fe->page['tstamp']))) {
			$timestamp			= intval($fe->page['tstamp']);
		}
		
		// Final Fallback: Current timestamp
		if (!$timestamp) {
			$timestamp			= time();
		}
		
		// If the modification timestamp should be injected into the source code
		if (self::indexConfig($fe, 'injectTimestamp')) {
			$stripMetaTags		= array();
			$addMetaTags		= array();
			
			// Remove any present timestamp meta tag
			foreach ($timestampMetaTags as $metaTag => $timestampMetaTag) {
				if ($timestampMetaTag) {
					$stripMetaTags[$timestampMetaTag]		= '';
					$addMetaTags[$metaTag]					= '<meta name="'.$metaTag.'" content="'.date('c', $timestamp).'"/>';
				}
			}
			$fe->content		= strtr($fe->content, $stripMetaTags);
			
			// Inject new timestamp meta tags
			$fe->content		= preg_replace("%\<head(\s[^\>]*)?\>%", "$0\n".implode("\n", $addMetaTags), $fe->content);
		}
		
		return $timestamp;
	}
	
	/**
	 * Extract the value of a meta tag
	 * 
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe			Frontend engine
	 * @param string $meta			Meta tag name
	 * @param array $metaTag		Meta tag parts (if available and matched by regex)
	 * @return string				Meta tag value
	 */
	protected function _getMetaContent(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe, $meta, &$metaTag = null) {
		$meta				= strtolower(trim($meta));
		$metaTag			= null;
		$content			= null;
		if (strlen($meta) && preg_match('%\<meta([^\>]+?)name\=(\042|\047)'.addcslashes($meta, '.?+*[]{}()').'\\2([^\>]*?)\>%i', $fe->content, $metaTag) && preg_match("%content\=(\042|\047)(.*?)\\1%i", $metaTag[2].$metaTag[3], $metaContent)) {
			$content		= trim($metaContent[2]);
		}
		
		return $content;
	}
	
	/**
	 * Create a lucene index document for submitting to the index
	 * 
	 * @param array $properties								Document properties
	 * @return \Tollwerk\TwLucenesearch\Domain\Model\Document		Lucene index document
	 */
	protected function _createDocument($properties) {
		$uid					= self::documentUid($properties['id'], $properties['type'], $properties['reference']);
		$document				= new \Tollwerk\TwLucenesearch\Domain\Model\Document();
		$document->addField(\Zend_Search_Lucene_Field::Keyword('type'		, $properties['type']			, self::UTF8));
		$document->addField(\Zend_Search_Lucene_Field::Keyword('id'			, $properties['id']				, self::UTF8));
		$document->addField(\Zend_Search_Lucene_Field::Keyword('language'	, $properties['language']		, self::UTF8));
		$document->addField(\Zend_Search_Lucene_Field::Keyword('reference'	, $properties['reference']		, self::UTF8));
		$document->addField(\Zend_Search_Lucene_Field::Keyword('uid'		, $uid							, self::UTF8));
		$document->addField(\Zend_Search_Lucene_Field::Text('rootline'		, $properties['rootline']		, self::UTF8));
		$document->addField(\Zend_Search_Lucene_Field::Text('title'			, $properties['title']			, self::UTF8));
		$document->addField(\Zend_Search_Lucene_Field::Text('bodytext'		, $properties['bodytext']		, self::UTF8));
		$document->addField(\Zend_Search_Lucene_Field::Keyword('timestamp'	, $properties['timestamp']		, self::UTF8));
		$document->addField(\Zend_Search_Lucene_Field::Text('keywords'		, $properties['keywords']		, self::UTF8));
		$document->addField(\Zend_Search_Lucene_Field::Text('abstract'		, $properties['abstract']		, self::UTF8));
		return $document;
	}
	
	/************************************************************************************************
	 * STATIC METHODS
	 ***********************************************************************************************/
	
	/**
	 * One-time preparation of the indexing configuration and return of a certain configuration value
	 *
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe					Frontend engine
	 * @param string|null $key				Optional: Requested configuration key
	 * @return mixed						Indexing configuration or certain configuration value
	 */
	public static function indexConfig(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $fe, $key = null) {
		if (self::$_config === null) {
			self::$_config		= self::indexConfigTS((isset($fe->tmpl) && ($fe->tmpl instanceof \TYPO3\CMS\Core\TypoScript\TemplateService) && is_array($fe->tmpl->setup) && array_key_exists('config.', $fe->tmpl->setup) && is_array($fe->tmpl->setup['config.'])) ? $fe->tmpl->setup['config.'] : array());
		}
		
		if ($key === null) {
			return self::$_config;
		} else {
			$pointer			=& self::$_config;
			foreach (explode('.', $key) as $subkey) {
				if (is_object($pointer) && isset($pointer->$subkey)) {
					$pointer	=& $pointer->$subkey;
				} elseif (is_array($pointer) && array_key_exists($subkey, $pointer)) {
					$pointer	=& $pointer[$subkey];
				} else {
					return null;
				}
			}
			return $pointer;
		}
	}
	
	/**
	 * Extract and prepare the TypoScript index configuration
	 * 
	 * @param \array $typoscript			TypoScript configuration
	 * @return \array						Index configuration
	 */
	public static function indexConfigTS(array $typoscript) {
		$config								= array(
			'enable'						=> false,
			'externals'						=> false,
			'descrLgd'						=> 300,
			'reference'						=> array('id' => (object)array('constraints' => null, 'default' => null)),
			'injectTimestamp'				=> true,
			'search'						=> (object)array(
				'restrictByRootlinePids'	=> array(),
				'restrictByLanguage'		=> true,
				'searchConfig'				=> array((object)array('field' => true, 'value' => '?', 'fuzzy' => false, 'boost' => null)),
				'limits'					=> (object)array('query' => 100, 'display' => 20),
				'highlightMatches'			=> false,
			),
		);
			
		if (!array_key_exists('no_index', $_GET) && array_key_exists('index_enable', $typoscript)) {
			$config['enable']		= (boolean)intval($typoscript['index_enable']);
		}
		if (array_key_exists('index_externals', $typoscript)) {
			$config['externals']		= (boolean)intval($typoscript['index_externals']);
		}
		if (array_key_exists('index_descrLgd', $typoscript)) {
			$config['descrLgd']		= intval($typoscript['index_descrLgd']);
		}
		$indexReference			= array_key_exists('index_reference', $typoscript) ? trim($typoscript['index_reference']) : '';
		$linkVars				= array_key_exists('linkVars', $typoscript) ? trim($typoscript['linkVars']) : '';
		if (strlen($indexReference) || strlen($linkVars)) {
			$referenceVars		= array();
			foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $linkVars.','.$indexReference) as $referenceVar) {
				if (strlen($referenceVar) && preg_match("%([a-zA-Z][^\(\s]*)(?:\s*\(([^\)]*)\))?(?:\s*\=\s*(.+))?%", trim($referenceVar), $referenceVarConfig)) {
					$referenceVar			= (object)array(
							'default'			=> null,
							'constraints'		=> null,
					);
	
					// Register value restrictions
					if ((count($referenceVarConfig) > 2) && strlen($referenceVarConfig[2])) {
						$referenceVar->constraints = array();
						foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', trim($referenceVarConfig[2])) as $constraint) {
							if (strpos($constraint, '-') !== false) {
								list($lower, $upper) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('-', $constraint);
								$referenceVar->constraints = array_merge($referenceVar->constraints, range($lower, $upper));
							} else {
								$referenceVar->constraints[]	= trim($constraint);
							}
						}
					}
	
					// Register default value
					if ((count($referenceVarConfig) > 3) && strlen($referenceVarConfig[3])) {
						$referenceVar->default = trim($referenceVarConfig[3]);
					}
	
					$pointer				=& $referenceVars;
					$referenceVarNameParts	= preg_split("%[\[\]]%", rtrim($referenceVarConfig[1], ']['));
					foreach ($referenceVarNameParts as $referenceVarNamePartIndex => $referenceVarNamePart) {
						if ($referenceVarNamePartIndex < (count($referenceVarNameParts) - 1)) {
							if (!array_key_exists($referenceVarNamePart, $pointer)) {
								$pointer[$referenceVarNamePart]		= array();
							}
							$pointer		=& $pointer[$referenceVarNamePart];
						} else {
							$pointer[$referenceVarNamePart]			= $referenceVar;
						}
					}
				}
			}
			$indexLanguageReference	= array_key_exists('index_languageReference', $typoscript) ? trim($typoscript['index_languageReference']) : '';
			if (strlen($indexLanguageReference) > 0 && array_key_exists($indexLanguageReference, $referenceVars)) {
				$languageVar = &$referenceVars[$indexLanguageReference];
				if ($languageVar->default === null) {
					if ($languageVar->constraints && count($languageVar->constraints) > 0) {
						$constraints = $languageVar->constraints;
						$languageVar->default = array_shift($constraints);
					} else {
						$languageVar->default = '0';
					}
				}
			}
			$config['reference']		= $referenceVars;
			self::_indexConfigSort($config['reference']);
			if (array_key_exists('search_lucene.', $typoscript) && is_array($typoscript['search_lucene.'])) {
				if (array_key_exists('restrictByRootlinePids', $typoscript['search_lucene.'])) {
					$config['search']->restrictByRootlinePids	= trim($typoscript['search_lucene.']['restrictByRootlinePids']);
					$config['search']->restrictByRootlinePids	= strlen($config['search']->restrictByRootlinePids) ? \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $config['search']->restrictByRootlinePids) : array();
				}
				if (array_key_exists('restrictByLanguage', $typoscript['search_lucene.'])) {
					$config['search']->restrictByLanguage		= (boolean)intval($typoscript['search_lucene.']['restrictByLanguage']);
				}
				if (array_key_exists('searchConfig', $typoscript['search_lucene.']) && strlen(trim($typoscript['search_lucene.']['searchConfig']))) {
					$config['search']->searchConfig				= array();
					foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', trim($typoscript['search_lucene.']['searchConfig'])) as $searchConfig) {
						if (preg_match("%^([^\:]+)\:([^\^\~]+)(\~(\d+(?:\.\d+)?)?)?(?:\^(\d+(?:\.\d+)?))?$%", $searchConfig, $searchConfigParams)) {
							require_once 'Zend/Search/Lucene/Search/Query/Fuzzy.php';
							$config['search']->searchConfig[]	= (object)array(
								'field'		=> ($searchConfigParams[1] == '*') ? true : $searchConfigParams[1],
								'value'		=> $searchConfigParams[2],
								'fuzzy'		=> ((count($searchConfigParams) > 3) && strlen($searchConfigParams[3])) ? ((count($searchConfigParams) > 4) && strlen($searchConfigParams[4]) ? floatval($searchConfigParams[4]) : \Zend_Search_Lucene_Search_Query_Fuzzy::DEFAULT_MIN_SIMILARITY) : false,
								'boost'		=> (count($searchConfigParams) > 5) ? floatval($searchConfigParams[5]) : null,
							);
						}
					}
				}
				if (array_key_exists('limits.', $typoscript['search_lucene.']) && is_array($typoscript['search_lucene.']['limits.'])) {
					$config['search']->limits					= (object)$typoscript['search_lucene.']['limits.'];
				}
				if (array_key_exists('highlightMatches', $typoscript['search_lucene.'])) {
					$config['search']->highlightMatches			= (boolean)intval($typoscript['search_lucene.']['highlightMatches']);
				}
			}
		}
		if (array_key_exists('index_injectTimestamp', $typoscript)) {
			$config['injectTimestamp']		= (boolean)intval($typoscript['index_injectTimestamp']);
		}
		
		return $config;
	}
	
	/**
	 * Recursive sorting of the indexing references
	 *
	 * @param array $array			Array
	 * @return void
	 */
	protected static function _indexConfigSort(array &$array) {
		uksort($array, 'strcasecmp');
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				self::_indexConfigSort($array[$key]);
			}
		}
	}
	
	/**
	 * Set the indexed and page title of the current page
	 * 
	 * @param string $title			User defined page title
	 * @param string $format		Title format with substitution markers (%S = Website title, %P = Page title, %C = User defined title as given in argument 1)
	 * @param string $pageFormat	Optional: Alternative format for page title
	 * @return void
	 */
	public static function setPageTitle($title, $format = '%S: %P - %C', $pageFormat = null) {
		$format					= trim($format);
		if (strlen($format)) {
			if ($pageFormat === null) {
				$pageFormat		= $format;
			}
			$replace			= array(
				'%S'			=> $GLOBALS['TSFE']->tmpl->setup['sitetitle'],
				'%P'			=> $GLOBALS['TSFE']->page['title'],
				'%C'			=> $title,	
			);
			$GLOBALS['TSFE']->indexedDocTitle		= strtr($format, $replace);
			$GLOBALS['TSFE']->page['title']			= strtr($pageFormat, $replace);
			self::$_setPageTitle					= true;
		}
	}
	
	/**
	 * Create a unique ID for an index document
	 * 
	 * @param \string $id			Document ID
	 * @param \string $type			Document type
	 * @param \string $reference	Serialized reference parameters
	 * @return \string				Unique document ID
	 */
	public static function documentUid($id, $type, $reference) {
		return md5($id.'-'.$type.'-'.$reference);
	}
}

?>
