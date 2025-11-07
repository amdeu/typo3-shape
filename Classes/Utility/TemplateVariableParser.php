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
		);
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
			return '{{' . $path . '}}';
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
			return '{{' . $path . '}}';
		}

		// If there's a property path after [], map that property
		if (isset($parts[1]) && $parts[1] !== '') {
			$propertyPath = ltrim($parts[1], '.');
			$values = [];
			foreach ($array as $item) {
				$value = self::getValue([$item], explode('.', $propertyPath));
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
	 * Gets a value from a nested array/object structure using a path array.
	 * For objects, tries getter method first, then property access.
	 *
	 * @param array $data The data structure to traverse
	 * @param array $path The path segments to follow
	 * @return mixed The value found at the path, or null if not found
	 */
	private static function getValue(array $data, array $path): mixed
	{
		$value = $data;

		foreach ($path as $key) {
			$current = is_array($value) ? ($value[0] ?? $value) : $value;

			// Try array access first
			if (is_array($current) && isset($current[$key])) {
				$value = $current[$key];
				continue;
			}

			// Try object access (getter first, then property)
			if (is_object($current)) {
				// Try getter method
				$getterMethod = 'get' . ucfirst($key);
				if (method_exists($current, $getterMethod)) {
					$value = $current->$getterMethod();
					continue;
				}

				// Fall back to property access
				if (property_exists($current, $key)) {
					$value = $current->$key;
					continue;
				}
			}

			// If we get here, we couldn't find the value
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