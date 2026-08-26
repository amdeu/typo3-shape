<?php

if (
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('scheduler')
	&& \Amdeu\Shape\Utility\TcaUtility::getTypo3Version()->getMajorVersion() >= 14
) {
	// v13 registers this table via $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] in ext_localconf.php instead
	// (the mechanism TableGarbageCollectionTask reads on v14+ is TCA-based and didn't exist before v14).
	$GLOBALS['TCA']['tx_scheduler_task']['types'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['taskOptions']['tables']['tx_shape_email_consent'] = [
		'expireField' => 'valid_until',
	];
}
