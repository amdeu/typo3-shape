<?php

namespace Amdeu\Shape\UserFunctions;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSlug;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class Tca
{

	/**
	 * Provide label for inline field records
	 */
	public function getFormattedFieldLabel(array &$params): void
	{
		if (!isset($params['row'])) {
			return;
		}
		$row = $params['row'];
		$type = is_array($row['type']) ? $row['type'][0] ?? '' : $row['type'];
		$label = is_array($row['label']) ? $row['label'][0] ?? '' : $row['label'];
		if (!$row['label'] && !$row['type']) {
			return;
		}
		$required = '';
		if ($row['required']) {
			$required = '*';
		}
		$type = BackendUtility::getProcessedValue('tx_shape_field', 'type', $type);
		$params['title'] = "{$label}{$required}  <small style='opacity:.7;'> {$type}</small>";
	}

	/**
	 * Provide empty slug prefix
	 */
	public function getEmptySlugPrefix(array $parameters, TcaSlug $reference): string
	{
		return "";
	}

}
