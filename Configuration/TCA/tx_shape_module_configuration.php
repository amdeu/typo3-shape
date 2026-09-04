<?php

use Amdeu\Shape\Utility\TcaUtility as Util;

$showItem = '
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
		--palette--;;base,
		settings,
		form_parents,
	--div--;LLL:EXT:shape/Resources/Private/Language/locallang_db.xlf:tab.condition,
    	condition,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
        sys_language_uid,
        l10n_parent,
        l10n_diffsource,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        hidden';

return [
	'ctrl' => [
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
		'enablecolumns' => ['disabled' => 'hidden'],
		'searchFields' => 'title',
		'security' => ['ignorePageTypeRestriction' => true],
		'type' => 'type',
		'typeicon_column' => 'type',
		'typeicon_classes' => ['default' => 'shape-module-default'],
	],
	'columns' => [
		'form_parents' => [
			'label' => Util::t('module.form_parents'),
			'config' => [
				'type' => 'group',
				'allowed' => 'tx_shape_form',
				'foreign_table' => 'tx_shape_form',
				'size' => 1,
				'localizeReferences' => true,
				'fieldWizard' => ['tableList' => ['disabled' => true]],
			],
		],
		'title' => [
			'label' => Util::t('module.title'),
			'config' => [
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim',
			],
		],
		'type' => [
			'label' => Util::t('module.type'),
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'required' => true,
				'items' => [
					['label' => '', 'value' => ''],
				],
			],
		],
		'condition' => [
			'label' => Util::t('module.condition'),
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'description' => Util::t('module.condition.description'),
			'config' => [
				'type' => 'input',
				'size' => 100,
				'valuePicker' => [
					'items' => Util::valuePickerItems([
						['label' => 'Field value is true / not empty', 'value' => 'value("field-id")'],
						['label' => 'Field value is equal to', 'value' => 'value("field-id") == "some-value"'],
						['label' => 'URL Parameter is equal to', 'value' => 'traverse(request.getQueryParams(), "parameter/path") == "some-value"'],
						['label' => 'Consent was approved', 'value' => 'isConsentApproved()'],
						['label' => 'Consent was dismissed', 'value' => 'isConsentDismissed()'],
						['label' => 'Before Consent Confirmation', 'value' => 'isBeforeConsent()'],
					]),
				],
			],
		],
		'settings' => [
			'label' => Util::t('module.settings'),
			'displayCond' => 'FIELD:type:REQ:true',
			'config' => [
				'fieldWizard' => ['otherLanguageContent' => ['disabled' => false]],
				'type' => 'flex',
				'ds_pointerField' => 'type'
			],
		],
	],
	'palettes' => [
		'base' => ['showitem' => 'title, type'],
	],
	'types' => [
		'0' => ['showitem' => $showItem],
	],
];
