<?php

use Amdeu\Shape\Utility\TcaUtility as Util;

$ctrl = [
	'label' => 'title',
	'label_alt_force' => true,
	'title' => Util::t('form_page.ctrl.title'),
	'tstamp' => 'tstamp',
	'crdate' => 'crdate',
	'origUid' => 't3_origuid',
	'sortby' => 'sorting',
	'delete' => 'deleted',
	'versioningWS' => true,
	'languageField' => 'sys_language_uid',
	'transOrigPointerField' => 'l10n_parent',
	'transOrigDiffSourceField' => 'l10n_diffsource',
	'translationSource' => 'l10n_source',
	'iconfile' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/form/form-page.svg',
	'enablecolumns' => [
		'disabled' => 'hidden',
	],
	'searchFields' => 'title',
	'typeicon_column' => 'type',
	'typeicon_classes' => [
		'default' => 'form-page',
		'page' => 'form-page',
		'summary' => 'form-summary-page',
	],
];
$interface = [];
$columns = [
	'title' => [
		'config' => [
			'type' => 'input',
			'size' => 30,
			'eval' => 'trim',
		],
	],
	'type' => [
		'config' => [
			'type' => 'select',
			'renderType' => 'selectSingle',
			'items' => [
				['Page', 'page', 'form-page'],
				['Summary', 'summary', 'form-summary-page'],
			],
		],
	],
	'form_parent' => [
		'config' => [
			'type' => 'group',
			'allowed' => 'tx_shape_form',
			'foreign_table' => 'tx_shape_form',
			'size' => 1,
			'localizeReferences' => true,
			//'foreign_table_where' => 'AND {#tx_shape_form}.{#sys_language_uid}=###REC_FIELD_sys_language_uid###',
			'fieldWizard' => [
				'tableList' => [
					'disabled' => true,
				],
			]
		],
	],
	'fields' => [
		'displayCond' => 'FIELD:type:!=:summary',
		'config' => [
			'type' => 'inline',
			'foreign_field' => 'page_parents',
			//'type' => 'select',
			//'renderType' => 'selectMultipleSideBySide',
			//'type' => 'group',
			'allowed' => 'tx_shape_field',
			'foreign_table' => 'tx_shape_field',
			'foreign_table_where' => 'AND {#tx_shape_field}.{#sys_language_uid}=###REC_FIELD_sys_language_uid###',
			'localizeReferences' => true,
			'localizeReferencesAtParentLocalization' => true,
			'appearance' => [
				'expandSingle' => true,
				'useSortable' => true
			],
			'fieldControl' => [
				'editPopup' => [
					'disabled' => false,
				],
				'addRecord' => [
					'disabled' => false,
				],
			],
			'fieldWizard' => [
				'recordsOverview' => [
					'disabled' => true,
				],
				'tableList' => [
					'disabled' => true,
				],
				'selectIcons' => [
					'disabled' => false,
				],
			]
		],
	],
	'display_condition' => [
		'config' => [
			'type' => 'input',
			'size' => 40,
		],
	],
	'prev_label' => [
		'config' => [
			'type' => 'input',
			'size' => 30,
			'eval' => 'trim',
		],
	],
	'next_label' => [
		'config' => [
			'type' => 'input',
			'size' => 30,
			'eval' => 'trim',
		],
	],
	'layout' => [
		'label' => Util::t('form_page.layout'),
		'config' => [
			'behaviour' => [
				'allowLanguageSynchronization' => true,
			],
			'type' => 'select',
			'renderType' => 'selectSingle',
			'items' => [
				[Util::t('general.default'), 'default'],
			]
		],
	],
	'header_layout' => [
		'label' => Util::t('form_page.header_layout'),
		'config' => [
			'behaviour' => [
				'allowLanguageSynchronization' => true,
			],
			'type' => 'select',
			'renderType' => 'selectSingle',
			'items' => [
				[Util::t('general.default'), 'default'],
				[Util::t('general.hidden'), 'hidden'],
			]
		],
	],
	'css_class' => [
		'label' => Util::t('form_page.css_class'),
		'config' => [
			'behaviour' => [
				'allowLanguageSynchronization' => true,
			],
			'type' => 'input',
		],
	]
];
$langSyncColumns = [
	'type',
	'display_condition',
];

foreach ($columns as $key => $column) {
	$columns[$key]['label'] = Util::t('form_page.' . $key);
	if (in_array($key, $langSyncColumns)) {
		if (!isset($columns[$key]['config']['behaviour'])) {
			$columns[$key]['config']['behaviour'] = [];
		}
		$columns[$key]['config']['behaviour']['allowLanguageSynchronization'] = true;
		if (!isset($columns[$key]['config']['fieldWizard'])) {
			$columns[$key]['config']['fieldWizard'] = [];
		}
		$columns[$key]['config']['fieldWizard']['localizationStateSelector'] = [
			'disabled' => false,
		];
	}
}
$palettes = [
	'title' => [
		'showitem' => 'form_parent, --linebreak--, title, type',
	],
	'button-labels' => [
		'showitem' => 'prev_label, next_label',
	],
	'appearance' => [
		'showitem' => 'layout, header_layout, --linebreak--, css_class',
	],
];
$showItem = '
	--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general, 
		--palette--;;title,
		fields,
		--palette--;;button-labels,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance, 
    	--palette--;;appearance,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, 
        sys_language_uid, 
        l10n_parent, 
        l10n_diffsource, 
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, 
        hidden';

return [
	'ctrl' => $ctrl,
	'interface' => $interface,
	'columns' => $columns,
	'palettes' => $palettes,
	'types' => [
		'0' => [
			'showitem' => $showItem
		],
	],
];
