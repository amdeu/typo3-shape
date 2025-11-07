<?php

use Amdeu\Shape\Utility\TcaUtility as Util;

$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
	'label' =>  Util::t('page.shape-folder'),
	'value' => 'shape',
	'icon' => 'shape-form',
];

$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-shape'] = 'shape-folder';