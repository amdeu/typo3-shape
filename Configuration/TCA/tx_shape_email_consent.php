<?php

use Amdeu\Shape\Utility\TcaUtility as Util;

$ctrl = [
	'label' => 'form',
	'label_alt' => 'email, tstamp',
	'label_alt_force' => 1,
	'title' => Util::t('email_consent.ctrl.title'),
	'tstamp' => 'tstamp',
	'crdate' => 'crdate',
	'sortby' => 'sorting',
	'delete' => 'deleted',
	'iconfile' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/overlay/overlay-approved.svg',
	'enablecolumns' => [
		'disabled' => 'hidden',
	],
	'searchFields' => 'email',
	'security' => [
		'ignorePageTypeRestriction' => true,
	],
];
$interface = [];

// todo: add validation link? would enable manual approval from admins if user for some reason cannot click the link
$columns = [
	'status' => [
		'config' => [
			'type' => 'select',
			'renderType' => 'selectSingle',
			'readOnly' => true,
			'items' => [
				[Util::t('email_consent.status.item.pending'), 0],
				[Util::t('email_consent.status.item.approved'), 1],
				[Util::t('email_consent.status.item.dismissed'), 2],
			]
		]
	],
	'form' => [
		'config' => [
			'type' => 'select',
			'renderType' => 'selectSingle',
			'foreign_table' => 'tx_shape_form',
			'size' => 1,
			'maxitems' => 1,
			'hideSuggest' => true,
			'fieldWizard' => [
				'tableList' => [
					'disabled' => true,
				]
			],
			'readOnly' => true,
		],
	],
	'plugin' => [
		'config' => [
			'type' => 'select',
			'renderType' => 'selectSingle',
			'foreign_table' => 'tt_content',
			'size' => 1,
			'maxitems' => 1,
			'hideSuggest' => true,
			'foreign_table_where' => 'AND {#tt_content}.{#CType}=\'shape_form\'',
			'fieldWizard' => [
				'tableList' => [
					'disabled' => true,
				]
			],
			'readOnly' => true,
		],
	],
	'email' => [
		'config' => [
			'type' => 'email',
			'readOnly' => true,
		],
	],
	'valid_until' => [
		'config' => [
			'type' => 'datetime',
			'readOnly' => true,

		],
	],
	'tstamp' => [
		'config' => [
			'type' => 'datetime',
			'readOnly' => true,

		],
	],
	'session' => [
		'config' => [
			'type' => 'input',
			'readOnly' => true,
		],
	],
	'finisher_settings' => [
		'config' => [
			'type' => 'json',
			'readOnly' => true,
		],
	],
	'validation_hash' => [
		'config' => [
			'type' => 'input',
			'readOnly' => true,
		],
	],
	'approve_link' => [
		'config' => [
			'type' => 'text',
			'size' => 2,
			'readOnly' => true,
		],
	],
];
foreach ($columns as $key => $column) {
	$columns[$key]['label'] = Util::t('email_consent.' . $key);
}
$palettes = [
	'general' => [
		'showitem' => '
		tstamp, valid_until,
		--linebreak--,
		status, email,
		--linebreak--,
		plugin, form,
		--linebreak--,
		validation_hash, session,
		--linebreak--,
		finisher_settings, approve_link',
	],
];
$showItem = '
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general, 
    	--palette--;;general,
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
