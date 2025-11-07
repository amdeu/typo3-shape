<?php

namespace Amdeu\Shape\Utility;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

class PluginUtility
{
	public static function getPluginSignature(
		string $pluginName,
		string $extensionName = 'Shape',
	): string
	{
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
		return strtolower($extensionName . '_' . $pluginName);
	}

	public static function configure(
		string $pluginName,
		array $controllerActions,
		array $nonCacheableControllerActions = [],
		string $extensionName = 'Shape',
	): string
	{
		ExtensionUtility::configurePlugin(
			$extensionName,
			$pluginName,
			$controllerActions,
			$nonCacheableControllerActions,
			'CType'
		);
		$pluginSignature = self::getPluginSignature($pluginName, $extensionName);
		ExtensionManagementUtility::addTypoScript(
			'Shape',
			'setup',
			'
tt_content.' . $pluginSignature . ' >
tt_content.' . $pluginSignature . ' = EXTBASEPLUGIN
tt_content.' . $pluginSignature . ' {
	extensionName = ' . $extensionName . '
    pluginName = ' . $pluginName . '
}',
			'defaultContentRendering');
		return $pluginSignature;
	}

	public static function register(
		string $name,
		string $label,
		string $icon,
		string $group,
		string $description = '',
		string $flexForm = '',
		array $typeDefinition = [],
		string $extensionKey = 'shape'
	): string
	{
		$pluginSignature = ExtensionUtility::registerPlugin(
			$extensionKey,
			$name,
			$label,
			$icon,
			$group,
			$description,
		);
		if ($flexForm) {
			$GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['*' . ',' . $pluginSignature] = $flexForm;
		}
		if ($typeDefinition) {
			$GLOBALS['TCA']['tt_content']['types'][$pluginSignature] = $typeDefinition;
		}
		return $pluginSignature;
	}
}