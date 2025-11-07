<?php

use Amdeu\Shape\Utility\TcaUtility as Util;

$ctrl = [
	'label' => 'title',
	'label_alt' => 'type',
	'title' => Util::t('finisher.ctrl.title'),
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
	'iconfile' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/form/form-finisher.svg',
	'enablecolumns' => [
		'disabled' => 'hidden',
	],
	'searchFields' => 'title',
	'security' => [
		'ignorePageTypeRestriction' => true,
	],
	'type' => 'type',
	'typeicon_column' => 'type',
	'typeicon_classes' => [
		'default' => 'form-finisher',
		'Amdeu\Shape\Form\Finisher\SaveSubmissionFinisher' => 'content-elements-mailform',
		'Amdeu\Shape\Form\Finisher\SaveToDatabaseFinisher' => 'content-database',
		'Amdeu\Shape\Form\Finisher\SendEmailFinisher' => 'content-message',
		'Amdeu\Shape\Form\Finisher\ShowContentElementsFinisher' => 'form-content-element',
		'Amdeu\Shape\Form\Finisher\RedirectFinisher' => 'apps-pagetree-page-shortcut-external',
		'Amdeu\Shape\Form\Finisher\EmailConsentFinisher' => 'overlay-approved',

	],
];
$interface = [];
$columns = [
	'form_parents' => [
		'config' => [
			'type' => 'group',
			'allowed' => 'tx_shape_form',
			'foreign_table' => 'tx_shape_form',
			//'foreign_field' => 'finishers',
			'size' => 1,
			'localizeReferences' => true,
//			'foreign_table_where' => 'AND {#tx_shape_form_page}.{#sys_language_uid}=###REC_FIELD_sys_language_uid###',
			'fieldWizard' => [
				'tableList' => [
					'disabled' => true,
				],
			]
		],
	],
	'title' => [
		'config' => [
			'type' => 'input',
			'size' => 30,
			'eval' => 'trim',
		],
	],
	'type' => [
		'l10n_mode' => 'exclude',
		'l10n_display' => 'defaultAsReadonly',
		'config' => [
			'type' => 'select',
			'renderType' => 'selectSingle',
			'required' => true,
			'items' => [
				['', ''],
				[Util::t('finisher.type.item.save_submission'),
					'Amdeu\Shape\Form\Finisher\SaveSubmissionFinisher',
					'content-elements-mailform'],
				[Util::t('finisher.type.item.send_email'),
					'Amdeu\Shape\Form\Finisher\SendEmailFinisher',
					'content-message'],
				[Util::t('finisher.type.item.email_consent'),
					'Amdeu\Shape\Form\Finisher\EmailConsentFinisher',
					'overlay-approved'],
				[Util::t('finisher.type.item.save_to_database'),
					'Amdeu\Shape\Form\Finisher\SaveToDatabaseFinisher',
					'content-database'],
				[Util::t('finisher.type.item.show_content_elements'),
					'Amdeu\Shape\Form\Finisher\ShowContentElementsFinisher',
					'form-content-element'],
				[Util::t('finisher.type.item.redirect'),
					'Amdeu\Shape\Form\Finisher\RedirectFinisher',
					'apps-pagetree-page-shortcut-external'],
			],
		],
	],
	'condition' => [
		'l10n_mode' => 'exclude',
		'l10n_display' => 'defaultAsReadonly',
		'description' => Util::t('finisher.condition.description'),
		'config' => [
			'type' => 'input',
			'size' => 100,
			'valuePicker' => [
				'items' => [
					['Field value is true / not empty', 'value("field-id")'],
					['Field value is equal to', 'value("field-id") == "some-value"'],
					['URL Parameter is equal to', 'traverse(request.getQueryParams(), "parameter/path") == "some-value"'],
					['Consent was approved', 'isConsentApproved()'],
					['Consent was dismissed', 'isConsentDismissed()'],
					['Before Consent Confirmation', 'isBeforeConsent()'],

				],
			],
		],
	],
	'settings' => [
		'displayCond' => 'FIELD:type:REQ:true',
		'config' => [
			'behaviour' => [
				//'allowLanguageSynchronization' => true,
			],
			'fieldWizard' => [
//				'localizationStateSelector' => [
//					'disabled' => false,
//				],
				'otherLanguageContent' => [
					'disabled' => false,
				],
			],
			'type' => 'flex',
			'ds' => [
				'default' => 'FILE:EXT:shape/Configuration/FlexForms/Finisher/Default.xml',
				'Amdeu\Shape\Form\Finisher\SaveSubmissionFinisher' => 'FILE:EXT:shape/Configuration/FlexForms/Finisher/SaveSubmissionFinisher.xml',
				'Amdeu\Shape\Form\Finisher\SaveToDatabaseFinisher' => 'FILE:EXT:shape/Configuration/FlexForms/Finisher/SaveToDatabaseFinisher.xml',
				'Amdeu\Shape\Form\Finisher\SendEmailFinisher' => 'FILE:EXT:shape/Configuration/FlexForms/Finisher/SendEmailFinisher.xml',
				'Amdeu\Shape\Form\Finisher\RedirectFinisher' => 'FILE:EXT:shape/Configuration/FlexForms/Finisher/RedirectFinisher.xml',
				'Amdeu\Shape\Form\Finisher\ShowContentElementsFinisher' => 'FILE:EXT:shape/Configuration/FlexForms/Finisher/ShowContentElementsFinisher.xml',
				'Amdeu\Shape\Form\Finisher\EmailConsentFinisher' => 'FILE:EXT:shape/Configuration/FlexForms/Finisher/EmailConsentFinisher.xml',
			],
			'ds_pointerField' => 'type',
		],
	],
];
foreach ($columns as $key => $column) {
	$columns[$key]['label'] = Util::t('finisher.' . $key);
}
$palettes = [
	'base' => [
		'showitem' => 'form_parents, --linebreak--, title, type',
	],
];
$showItem = '
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general, 
		--palette--;;base,
		settings,
	--div--;LLL:EXT:shape/Resources/Private/Language/locallang_db.xlf:tab.condition,
    	condition,	
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
		'Amdeu\Shape\Form\Finisher\SaveSubmissionFinisher' => [
			'showitem' => $showItem,
			'columnsOverrides' => [
				'settings' => [
					//'l10n_mode' => 'exclude',
					//'l10n_display' => 'defaultAsReadonly',
					'behaviour' => [
						'allowLanguageSynchronization' => true,
					],
				]
			]
		],
		'Amdeu\Shape\Form\Finisher\SaveToDatabaseFinisher' => [
			'showitem' => $showItem,
			'columnsOverrides' => [
				'settings' => [
					//'l10n_mode' => 'exclude',
					//'l10n_display' => 'defaultAsReadonly',
					'behaviour' => [
						'allowLanguageSynchronization' => true,
					],
				]
			]
		]
	],
];
