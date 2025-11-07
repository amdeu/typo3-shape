<?php

use Amdeu\Shape\Utility\TcaUtility as Util;
use Amdeu\Shape\Utility\PluginUtility;


defined('TYPO3') or die();

PluginUtility::register(
	'Form',
	label: Util::t('plugin.form'),
	icon: 'content-form',
	group: 'forms',
	description: Util::t('plugin.form.description'),
	flexForm: 'FILE:EXT:shape/Configuration/FlexForms/Form.xml',
	typeDefinition: [
		'showitem' => '	--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
		--palette--;;general,
		--palette--;;header,
		pi_flexform,',
		'columnsOverrides' => []
	]
);

PluginUtility::register(
	'Consent',
	label: Util::t('plugin.consent'),
	icon: 'content-form',
	group: 'forms',
	description: Util::t('plugin.consent.description'),
);
