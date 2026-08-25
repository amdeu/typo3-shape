<?php

use Amdeu\Shape\Form\Module;
use Amdeu\Shape\Utility\TcaUtility as Util;

$langSync = ['settings' => ['behaviour' => ['allowLanguageSynchronization' => true]]];

Util::addModuleType(
	Util::t('module.type.item.save_submission'),
	'saveSubmission',
	Module\SaveSubmissionModule::class,
	'content-elements-mailform',
	'FILE:EXT:shape/Configuration/FlexForms/Module/SaveSubmissionModule.xml',
	$langSync
);
Util::addModuleType(
	Util::t('module.type.item.send_email'),
	'sendEmail',
	Module\SendEmailModule::class,
	'content-message',
	'FILE:EXT:shape/Configuration/FlexForms/Module/SendEmailModule.xml'
);
Util::addModuleType(
	Util::t('module.type.item.email_consent'),
	'emailConsent',
	Module\EmailConsentModule::class,
	'overlay-approved',
	'FILE:EXT:shape/Configuration/FlexForms/Module/EmailConsentModule.xml'
);
Util::addModuleType(
	Util::t('module.type.item.save_to_database'),
	'saveToDatabase',
	Module\SaveToDatabaseModule::class,
	'content-database',
	'FILE:EXT:shape/Configuration/FlexForms/Module/SaveToDatabaseModule.xml',
	$langSync
);
Util::addModuleType(
	Util::t('module.type.item.show_content_elements'),
	'showContentElements',
	Module\ShowContentElementsModule::class,
	'form-content-element',
	'FILE:EXT:shape/Configuration/FlexForms/Module/ShowContentElementsModule.xml')
;
Util::addModuleType(
	Util::t('module.type.item.show_text'),
	'showText',
	Module\ShowTextModule::class,
	'form-textarea',
	'FILE:EXT:shape/Configuration/FlexForms/Module/ShowTextModule.xml'
);
Util::addModuleType(
	Util::t('module.type.item.redirect'),
	'redirect',
	Module\RedirectModule::class,
	'apps-pagetree-page-shortcut-external', 'FILE:EXT:shape/Configuration/FlexForms/Module/RedirectModule.xml'
);

Util::setFlexForm(
	'tx_shape_module_configuration',
	'settings',
	'',
	'FILE:EXT:shape/Configuration/FlexForms/Module/Default.xml'
);
