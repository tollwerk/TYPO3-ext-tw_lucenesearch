<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tw_lucenesearch".
 *
 * Auto generated 14-08-2013 12:21
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'tollwerk Lucene search',
	'description' => 'Simple and lightweight implementation of the Apache Lucene Index as frontend search solution for TYPO3, built on extbase / fluid, supporting wildcard and fuzzy searches, search term highlighting, indexing of uncached pages, custom search term rewrite hooks and much more â without any further software requirements (Java application server, Apache Solr etc.)',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.6.2',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/tw_lucenesearch',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Dipl.-Ing. Joschi Kuphal',
	'author_email' => 'joschi@tollwerk.de',
	'author_company' => 'tollwerkÂ® GmbH',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' => 
	array (
		'depends' => 
		array (
			'extbase' => '6.0',
			'fluid' => '6.0',
			'typo3' => '6.0',
			'php' => '5.2.0-0.0.0',
		),
		'conflicts' => '',
		'suggests' => 
		array (
		),
	),
);

?>