<?php

use Amdeu\Shape\Utility\TcaUtility as Util;

$ctrl = [
	'label' => 'label',
	'title' => Util::t('field_option.ctrl.title'),
	'tstamp' => 'tstamp',
	'crdate' => 'crdate',
	'origUid' => 't3_origuid',
	'sortby' => 'sorting',
	'delete' => 'deleted',
	'versioningWS' => true,
	'languageField' => 'sys_language_uid',
	'transOrigPointerField' => 'l10n_parent',
	'transOrigDiffSourceField' => 'l10n_diffsource',
	'iconfile' => 'EXT:shape/Resources/Public/Icons/form-option.svg',
	'enablecolumns' => [
		'disabled' => 'hidden',
	],
	'searchFields' => 'label',
	'hideTable' => true,
];
$interface = [];
$columns = [
	'label' => [
		'config' => [
			'type' => 'input',
			'size' => 30,
			'eval' => 'trim',
		],
	],
	'value' => [
		'config' => [
			'type' => 'input',
			'size' => 30,
			'eval' => 'trim',
			'behaviour' => [
				'allowLanguageSynchronization' => true,
			],
			'fieldWizard' => [
				'localizationStateSelector' => [
					'disabled' => false,
				],
			],
		],
	],
	'group_label' => [
		'config' => [
			'type' => 'input',
			'size' => 30,
			'eval' => 'trim',
		],
	],
	'selected' => [
		'config' => [
			'type' => 'check',
			'behaviour' => [
				'allowLanguageSynchronization' => true,
			],
			'fieldWizard' => [
				'localizationStateSelector' => [
					'disabled' => false,
				],
			],
		],
	],
	'field_parent' => [
		'config' => [
			'type' => 'select',
			'foreign_table' => 'tx_shape_field',
			'minitems' => 0,
			'maxitems' => 1,
		],
	],
];
foreach ($columns as $key => $column) {
	$columns[$key]['label'] = Util::t('field_option.' . $key);
}
$palettes = [
	'base' => [
		'showitem' => 'label, value, group_label, selected',
	],
];
$showItem = '
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general, 
        --palette--;;base,';

return [
	'ctrl' => $ctrl,
	'interface' => $interface,
	'columns' => $columns,
	'palettes' => $palettes,
	'types' => [
		'0' => [
			'showitem' => $showItem,
		],
	],
];
