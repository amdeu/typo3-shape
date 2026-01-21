<?php

use Amdeu\Shape\Utility\TcaUtility as Util;

$ctrl = [
	'label' => 'title',
	'label_alt' => 'type',
	'title' => Util::t('module.ctrl.title'),
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
	'iconfile' => 'EXT:shape/Resources/Public/Icons/module-default.svg',
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
		'default' => 'shape-module-default',
		'Amdeu\Shape\Form\Module\SaveSubmissionModule' => 'content-elements-mailform',
		'Amdeu\Shape\Form\Module\SaveToDatabaseModule' => 'content-database',
		'Amdeu\Shape\Form\Module\SendEmailModule' => 'content-message',
		'Amdeu\Shape\Form\Module\ShowContentElementsModule' => 'form-content-element',
		'Amdeu\Shape\Form\Module\RedirectModule' => 'apps-pagetree-page-shortcut-external',
		'Amdeu\Shape\Form\Module\EmailConsentModule' => 'overlay-approved',
		'Amdeu\Shape\Form\Module\ShowTextModule' => 'form-textarea',
	],
];
$interface = [];
$columns = [
	'form_parents' => [
		'config' => [
			'type' => 'group',
			'allowed' => 'tx_shape_form',
			'foreign_table' => 'tx_shape_form',
			//'foreign_field' => 'modules',
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
				[Util::t('module.type.item.save_submission'),
					'Amdeu\Shape\Form\Module\SaveSubmissionModule',
					'content-elements-mailform'],
				[Util::t('module.type.item.send_email'),
					'Amdeu\Shape\Form\Module\SendEmailModule',
					'content-message'],
				[Util::t('module.type.item.email_consent'),
					'Amdeu\Shape\Form\Module\EmailConsentModule',
					'overlay-approved'],
				[Util::t('module.type.item.save_to_database'),
					'Amdeu\Shape\Form\Module\SaveToDatabaseModule',
					'content-database'],
				[Util::t('module.type.item.show_content_elements'),
					'Amdeu\Shape\Form\Module\ShowContentElementsModule',
					'form-content-element'],
				[Util::t('module.type.item.show_text'),
					'Amdeu\Shape\Form\Module\ShowTextModule',
					'form-textarea'],
				[Util::t('module.type.item.redirect'),
					'Amdeu\Shape\Form\Module\RedirectModule',
					'apps-pagetree-page-shortcut-external'],
			],
		],
	],
	'condition' => [
		'l10n_mode' => 'exclude',
		'l10n_display' => 'defaultAsReadonly',
		'description' => Util::t('module.condition.description'),
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
				'default' => 'FILE:EXT:shape/Configuration/FlexForms/Module/Default.xml',
				'Amdeu\Shape\Form\Module\SaveSubmissionModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/SaveSubmissionModule.xml',
				'Amdeu\Shape\Form\Module\SaveToDatabaseModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/SaveToDatabaseModule.xml',
				'Amdeu\Shape\Form\Module\SendEmailModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/SendEmailModule.xml',
				'Amdeu\Shape\Form\Module\RedirectModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/RedirectModule.xml',
				'Amdeu\Shape\Form\Module\ShowContentElementsModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/ShowContentElementsModule.xml',
				'Amdeu\Shape\Form\Module\EmailConsentModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/EmailConsentModule.xml',
				'Amdeu\Shape\Form\Module\ShowTextModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/ShowTextModule.xml',
			],
			'ds_pointerField' => 'type',
		],
	],
];
foreach ($columns as $key => $column) {
	$columns[$key]['label'] = Util::t('module.' . $key);
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
		'Amdeu\Shape\Form\Module\SaveSubmissionModule' => [
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
		'Amdeu\Shape\Form\Module\SaveToDatabaseModule' => [
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
