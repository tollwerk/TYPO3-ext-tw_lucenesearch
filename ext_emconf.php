<?php

########################################################################
# Extension Manager/Repository config file for ext "tw_lucenesearch".
#
# Auto generated 22-01-2014 22:50
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
    'title' => 'tollwerk Lucene search',
    'description' => 'Simple and lightweight implementation of the Apache Lucene Index as frontend search solution for TYPO3',
    'category' => 'plugin',
    'shy' => 0,
    'version' => '2.0.0',
    'dependencies' => 'extbase,fluid',
    'conflicts' => '',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'typo3temp/tw_lucenesearch',
    'modify_tables' => '',
    'clearcacheonload' => 0,
    'lockType' => '',
    'author' => 'Dipl.-Ing. Joschi Kuphal',
    'author_email' => 'joschi@tollwerk.de',
    'author_company' => 'tollwerk® GmbH',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => array(
        'depends' => array(
            'extbase' => '7.5.0-0.0.0',
            'fluid' => '7.5.0-0.0.0',
            'php' => '5.6.0-0.0.0',
            'typo3' => '7.5.0-0.0.0',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
    'suggests' => array(),
);
