<?php

use Amdeu\Shape\Controller;
use Amdeu\Shape\Form\Module;
use Amdeu\Shape\Utility\PluginUtility;

defined('TYPO3') or die();

PluginUtility::configure(
	'Form',
	[Controller\FormController::class => 'render, run, finished'],
	[Controller\FormController::class => 'run, finished'],
);

PluginUtility::configure(
	'Consent',
	[Controller\ConsentController::class => 'consentForm, consentConfirmation'],
	[Controller\ConsentController::class => 'consentForm, consentConfirmation'],
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['shape'] = ['Amdeu\Shape\ViewHelpers'];
$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['tx_shape_input_field'] = 'EXT:shape/Configuration/RTE/InputField.yaml';

$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['shape'] = [
	'modules' => [
		'sendEmail' => [
			'templates' => [
				'Module/SendEmail/Default' => [
					'label' => 'Default',
					'format' => \TYPO3\CMS\Core\Mail\FluidEmail::FORMAT_BOTH,
				],
			],
		]
	]
];

Module\ModuleRegistry::register('saveSubmission', Module\SaveSubmissionModule::class);
Module\ModuleRegistry::register('sendEmail', Module\SendEmailModule::class);
Module\ModuleRegistry::register('emailConsent', Module\EmailConsentModule::class);
Module\ModuleRegistry::register('saveToDatabase', Module\SaveToDatabaseModule::class);
Module\ModuleRegistry::register('showContentElements', Module\ShowContentElementsModule::class);
Module\ModuleRegistry::register('showText', Module\ShowTextModule::class);
Module\ModuleRegistry::register('redirect', Module\RedirectModule::class);

if (
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('scheduler')
	&& \Amdeu\Shape\Utility\TcaUtility::getTypo3Version()->getMajorVersion() < 14
) {
	// TYPO3 v14+ reads this from TCA instead (see Configuration/TCA/Overrides/tx_scheduler_task.php);
	// setting it here on v14+ would still work, but via a path that emits an E_USER_DEPRECATED warning
	// on every read and is removed entirely in v15.
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['tx_shape_email_consent'] = [
		'expireField' => 'valid_until',
	];
}

// Logging: in production only warnings and above, to a file. In dev the full DEBUG stream
// goes to the database (Log module) and file, with call-site introspection.
// DEBUG + DatabaseWriter on a busy form means a sys_log INSERT per module event on every submission.
if (\TYPO3\CMS\Core\Core\Environment::getContext()->isProduction()) {
	$GLOBALS['TYPO3_CONF_VARS']['LOG']['Amdeu']['Shape']['writerConfiguration'] = [
		\TYPO3\CMS\Core\Log\LogLevel::WARNING => [
			\TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
				'logFileInfix' => 'shape',
			],
		],
	];
} else {
	$GLOBALS['TYPO3_CONF_VARS']['LOG']['Amdeu']['Shape']['writerConfiguration'] = [
		\TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
			\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => [],
			\TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
				'logFileInfix' => 'shape',
			],
		],
	];
	$GLOBALS['TYPO3_CONF_VARS']['LOG']['Amdeu']['Shape']['processorConfiguration'] = [
		\TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
			\TYPO3\CMS\Core\Log\Processor\IntrospectionProcessor::class => [],
		],
	];
}
