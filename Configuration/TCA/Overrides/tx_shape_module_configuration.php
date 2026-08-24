<?php

use Amdeu\Shape\Utility\TcaUtility as Util;

$moduleFlexForms = [
	'' => 'FILE:EXT:shape/Configuration/FlexForms/Module/Default.xml',
	'Amdeu\Shape\Form\Module\SaveSubmissionModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/SaveSubmissionModule.xml',
	'Amdeu\Shape\Form\Module\SaveToDatabaseModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/SaveToDatabaseModule.xml',
	'Amdeu\Shape\Form\Module\SendEmailModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/SendEmailModule.xml',
	'Amdeu\Shape\Form\Module\RedirectModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/RedirectModule.xml',
	'Amdeu\Shape\Form\Module\ShowContentElementsModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/ShowContentElementsModule.xml',
	'Amdeu\Shape\Form\Module\EmailConsentModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/EmailConsentModule.xml',
	'Amdeu\Shape\Form\Module\ShowTextModule' => 'FILE:EXT:shape/Configuration/FlexForms/Module/ShowTextModule.xml',
];
foreach ($moduleFlexForms as $moduleType => $flexForm) {
	Util::setFlexForm(
		'tx_shape_module_configuration',
		'settings',
		$moduleType,
		$flexForm
	);
}