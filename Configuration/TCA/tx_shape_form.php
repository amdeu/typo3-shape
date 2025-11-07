<?php

use Amdeu\Shape\Utility\TcaUtility as Util;

$ctrl = [
	'label' => 'title',
	'label_alt' => 'name',
	'title' => Util::t('form.ctrl.title'),
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
	'iconfile' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/mimetypes/mimetypes-x-content-form.svg',
	'enablecolumns' => [
		'disabled' => 'hidden',
	],
	'searchFields' => 'title',
	'security' => [
		'ignorePageTypeRestriction' => true,
	],
];
$interface = [];
$columns = [
	'title' => [
		'config' => [
			'type' => 'input',
			'size' => 30,
			'eval' => 'trim',
			'required' => true,
		],
	],
	'name' => [
		'l10n_mode' => 'exclude',
		'l10n_display' => 'defaultAsReadonly',
		'config' => [
			'type' => 'slug',
			'generatorOptions' => [
				'fields' => ['title'],
				'fieldSeparator' => '-',
				'replacements' => [ '/' => '' ],
			],
			'appearance' => [
				'prefix' => \Amdeu\Shape\UserFunctions\Tca::class . '->getEmptySlugPrefix',
			],
			'fallbackCharacter' => '-',
			'eval' => 'uniqueInSite',
			'default' => '',
		],
	],
	'pages' => [
		'config' => [
			'type' => 'inline',
			'foreign_table' => 'tx_shape_form_page',
			'foreign_field' => 'form_parent',
			'foreign_sortby' => 'sorting',
			'appearance' => [
				'expandSingle' => true,
				'useSortable' => true
			],
		],
	],
	'finishers' => [
		'config' => [
			'type' => 'inline',
			'foreign_field' => 'form_parents',
			//'type' => 'select',
			//'renderType' => 'selectMultipleSideBySide',
			//'type' => 'group',
			'allowed' => 'tx_shape_finisher',
			'foreign_table' => 'tx_shape_finisher',
			'foreign_table_where' => 'AND {#tx_shape_finisher}.{#sys_language_uid}=###REC_FIELD_sys_language_uid###',
			'localizeReferences' => true,
			'localizeReferencesAtParentLocalization' => true,
			'appearance' => [
				'collapseAll' => true,
				'expandSingle' => true,
				'useSortable' => true,
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
	'layout' => [
		'label' => Util::t('form.layout'),
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
		'label' => Util::t('form.header_layout'),
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
		'label' => Util::t('form.css_class'),
		'config' => [
			'behaviour' => [
				'allowLanguageSynchronization' => true,
			],
			'type' => 'input',
		],
	]
];
foreach ($columns as $key => $column) {
	$columns[$key]['label'] = Util::t('form.' . $key);
}

$palettes = [
	'base' => [
		'showitem' => 'title, name',
	],
	'appearance' => [
		'showitem' => 'layout, --linebreak--, css_class',
	],
];
$showItem = '
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general, 
        --palette--;;base, 
        pages,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance, 
    	--palette--;;appearance,
    --div--;LLL:EXT:shape/Resources/Private/Language/locallang_db.xlf:tab.finishers, 
    	finishers,
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
			'showitem' => $showItem,

		],
	],
];
