<?php

use Amdeu\Shape\Controller;
use Amdeu\Shape\Utility\PluginUtility;

defined('TYPO3') or die();

PluginUtility::configure(
	'Form',
	[Controller\FormController::class => 'render, run, finished'],
	[Controller\FormController::class => 'run, finished'],
);

PluginUtility::configure(
	'Consent',
	[Controller\ConsentController::class => 'consentVerification'],
	[Controller\ConsentController::class => 'consentVerification'],
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['shape'] = ['Amdeu\Shape\ViewHelpers'];
$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['tx_shape_input_field'] = 'EXT:shape/Configuration/RTE/InputField.yaml';

$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['shape'] = [
	'finishers' => [
		'sendEmail' => [
			'templates' => [
				'Finisher/SendEmail/Default' => [
					'label' => 'Default',
					'format' => \TYPO3\CMS\Core\Mail\FluidEmail::FORMAT_BOTH,
				],
			],
		]
	]
];

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('scheduler')) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['tx_shape_email_consent'] = [
		'expireField' => 'valid_until',
	];
}

// Configure logging for ALL environments
$GLOBALS['TYPO3_CONF_VARS']['LOG']['Amdeu']['Shape']['writerConfiguration'] = [
	// Log everything from DEBUG level up
	\TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
		// Write to database for Log module
		\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => [],
		// Write to file for backup/debugging
		\TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
			'logFileInfix' => 'shape',
		],
	],
];

// Ensure the threshold doesn't filter out our logs
$GLOBALS['TYPO3_CONF_VARS']['LOG']['Amdeu']['Shape']['processorConfiguration'] = [
	\TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
		\TYPO3\CMS\Core\Log\Processor\IntrospectionProcessor::class => [],
	],
];
