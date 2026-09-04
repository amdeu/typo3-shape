<?php

namespace Amdeu\Shape\Utility;

use TYPO3\CMS\Core;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

/**
 * Utility class for TCA manipulation
 */
class TcaUtility
{

	/**
	 * Returns LLL:EXT:...:... string
	 * @param string $key
	 * @param string $file
	 * @return string
	 */
	public static function t(
		string $key,
		string $file = 'LLL:EXT:shape/Resources/Private/Language/locallang_db.xlf'
	): string
	{
		return "{$file}:{$key}";
	}

	/**
	 * Adds new fields to the showitem of tx_shape_field types
	 * @param string $newFields
	 * @param string $typeList The types to add the fields to, by default all types
	 * @param string $position By default, the new fields are added after the "extended" tab
	 */
	public static function addToFields(
		string $newFields,
		string $typeList = '',
		string $position = 'after:--div--;LLL:EXT:shape/Resources/Private/Language/locallang_db.xlf:tab.extended,'
	): void
	{
		Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
			'tx_shape_field',
			$newFields,
			$typeList,
			$position
		);
	}

	/**
	 * Adds a new type to tx_shape_field
	 * @param string $label
	 * @param string $value
	 * @param string $icon
	 * @param string $group
	 * @param array $typeDefinition The TCA definition for the new type
	 * @param string $baseType The base type to extend
	 */
	public static function addFieldType(
		string $label,
		string $value,
		string $icon = 'form-text',
		string $group = 'special',
		array $typeDefinition = [],
		string $baseType = ''
	): void
	{
		$GLOBALS['TCA']['tx_shape_field']['columns']['type']['config']['items'][] = [
			'label' => $label,
			'value' => $value,
			'icon' => $icon,
			'group' => $group,
		];
		$GLOBALS['TCA']['tx_shape_field']['ctrl']['typeicon_classes'][$value] = $icon;

		if ($baseType && $GLOBALS['TCA']['tx_shape_field']['types'][$baseType]) {
			$baseTypeDefinition = $GLOBALS['TCA']['tx_shape_field']['types'][$baseType];
			Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
				$baseTypeDefinition,
				$typeDefinition
			);
			$typeDefinition = $baseTypeDefinition;
		}
		if ($typeDefinition) {
			$GLOBALS['TCA']['tx_shape_field']['types'][$value] = $typeDefinition;
		}
	}

	/**
	 * Adds a module type to the TCA select, and optionally wires up a FlexForm and columnsOverrides
	 * for its settings. Call this from a TCA/Overrides file.
	 *
	 * This does NOT register the identifier with ModuleRegistry - TCA/Overrides files only execute
	 * while the TCA cache is being (re)built, not on every request, so ModuleRegistry's in-memory
	 * mapping (needed on every request to resolve the identifier back to a class) has to be registered
	 * separately from ext_localconf.php, which does run every request.
	 *
	 * @param string $columnsOverrides  Optional TCA columnsOverrides for this type (e.g. to enable language sync on settings)
	 */
	public static function addModuleType(
		string $label,
		string $identifier,
		string $icon = 'shape-module-default',
		string $flexForm = '',
		array $columnsOverrides = []
	): void {
		$GLOBALS['TCA']['tx_shape_module_configuration']['columns']['type']['config']['items'][] = [
			'label' => $label,
			'value' => $identifier,
			'icon'  => $icon,
		];
		$GLOBALS['TCA']['tx_shape_module_configuration']['ctrl']['typeicon_classes'][$identifier] = $icon;
		$baseShowItem = $GLOBALS['TCA']['tx_shape_module_configuration']['types']['0']['showitem'] ?? '';
		$GLOBALS['TCA']['tx_shape_module_configuration']['types'][$identifier] = [
			'showitem' => $baseShowItem,
			'columnsOverrides' => $columnsOverrides,
		];
		if ($flexForm) {
			static::setFlexForm('tx_shape_module_configuration', 'settings', $identifier, $flexForm);
		}
	}


	protected static ?Core\Information\Typo3Version $typo3Version = null;
	public static function getTypo3Version(): Core\Information\Typo3Version
	{
		if (static::$typo3Version === null) {
			static::$typo3Version = Core\Utility\GeneralUtility::makeInstance(Core\Information\Typo3Version::class);
		}
		return static::$typo3Version;
	}

	/**
	 * Normalizes a valuePicker item list across TYPO3 versions.
	 *
	 * Define items in the v14 associative form ([['label' => ..., 'value' => ...], ...]); on v13 the
	 * FormEngine still expects the legacy numeric tuples, and core's TCA migration doesn't reach items
	 * nested in overrideChildTca / columnsOverrides, so they are downgraded here.
	 *
	 * @param array<int, array{label: string, value: string}> $items
	 * @return array<int, array>
	 */
	public static function valuePickerItems(array $items): array
	{
		if (static::getTypo3Version()->getMajorVersion() >= 14) {
			return $items;
		}
		return array_map(static fn(array $item): array => [$item['label'], $item['value']], $items);
	}

	public static function setFlexForm(
		string $table,
		string $field,
		string $type,
		string $flexForm,
	): void
	{
		if (!$type) {
			if (static::getTypo3Version()->getMajorVersion() < 14) {
				$GLOBALS['TCA'][$table]['columns'][$field]['config']['ds']['default'] = $flexForm;
			} else {
				$GLOBALS['TCA'][$table]['columns'][$field]['config']['ds'] = $flexForm;
			}
			return;
		}
		if (static::getTypo3Version()->getMajorVersion() < 14) {
			$GLOBALS['TCA'][$table]['columns'][$field]['config']['ds'][$type] = $flexForm;
		} else {
			$GLOBALS['TCA'][$table]['types'][$type]['columnsOverrides'][$field]['config']['ds'] = $flexForm;
		}
	}
}