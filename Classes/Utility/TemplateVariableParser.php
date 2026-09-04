<?php
declare(strict_types=1);

namespace Amdeu\Shape\Utility;

/**
 * Parser for interpolating variables in templates.
 * Supports simple variable replacement, array to list conversion,
 * and object property access.
 *
 * Syntax:
 * - Simple variable: {{ variable }}
 * - Array to list: {{ array[] }}
 * - Array property to list: {{ array[].property }}
 * - Nested array to list: {{ array.nested[] }}
 * - Object property: {{ object.property }} (tries getter method first, then property)
 */
class TemplateVariableParser
{
	/**
	 * Parses a template string and replaces variables with their corresponding values.
	 *
	 * @param string $template The template string containing variables to replace
	 * @param array<string, mixed> $data Array containing the variable values
	 * @param bool $escapeHtml Whether to escape HTML special characters in the values
	 * @return string The processed template with all variables replaced
	 */
	public static function parse(string $template, array $data, bool $escapeHtml = false): string
	{
		return preg_replace_callback(
			'/\{\{\s*([^}]+?)\s*\}\}/',
			fn($matches) => self::parsePlaceholder(trim($matches[1]), $data, $escapeHtml),
			$template
		) ?? $template;
	}

	/**
	 * Parses a single placeholder and returns its replacement value.
	 */
	private static function parsePlaceholder(string $path, array $data, bool $escapeHtml): string
	{
		// Check if this is an array operation
		if (str_contains($path, '[]')) {
			return self::handleArrayOperation($path, $data, $escapeHtml);
		}

		// Handle simple variable
		$value = self::getValue($data, explode('.', $path));
		if ($value === null || is_array($value)) {
			return '{{ ' . $path . ' }}';
		}

		return $escapeHtml ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : (string)$value;
	}

	/**
	 * Handles array to list operations.
	 */
	private static function handleArrayOperation(string $path, array $data, bool $escapeHtml): string
	{
		// Split path at the [] operator
		$parts = explode('[]', $path);

		// Get the array
		$arrayPath = trim($parts[0]);
		$array = self::getValue($data, explode('.', $arrayPath));

		if ($array === '') {
			return '';
		}
		if (!is_array($array)) {
			return '{{ ' . $path . ' }}';
		}

		// If there's a property path after [], map that property
		if (isset($parts[1]) && $parts[1] !== '') {
			$propertyPath = ltrim($parts[1], '.');
			$values = [];
			foreach ($array as $item) {
				$value = self::getValue($item, explode('.', $propertyPath));
				if ($value !== null && !is_array($value)) {
					$values[] = $value;
				}
			}
		} else {
			// Simple array to list
			$values = array_filter(
				$array,
				fn($v) => $v !== null && !is_array($v)
			);
		}

		return self::formatValues($values, $escapeHtml);
	}

	/**
	 * Follows a dotted path through nested arrays/objects.
	 * For objects, tries a getter method first, then a public property.
	 *
	 * @param mixed $value The data structure to traverse
	 * @param string[] $path The path segments to follow
	 * @return mixed The value found at the path, or null if any segment is missing.
	 */
	private static function getValue(mixed $value, array $path): mixed
	{
		foreach ($path as $key) {
			if (is_array($value) && isset($value[$key])) {
				$value = $value[$key];
				continue;
			}

			if (is_object($value)) {
				$getterMethod = 'get' . ucfirst($key);
				if (method_exists($value, $getterMethod)) {
					$value = $value->$getterMethod();
					continue;
				}
				if (property_exists($value, $key)) {
					$value = $value->$key;
					continue;
				}
			}

			return null;
		}

		return $value;
	}

	/**
	 * Formats an array of values into a comma-separated string.
	 */
	private static function formatValues(array $values, bool $escapeHtml): string
	{
		if (empty($values)) {
			return '';
		}

		return $escapeHtml
			? implode(', ', array_map(fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $values))
			: implode(', ', array_map('strval', $values));
	}
}