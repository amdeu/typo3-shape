<?php

namespace Amdeu\Shape\Backend;

use TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadIsExecutedEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

final class FormSubmissionDownloadFormatter
{
	#[AsEventListener]
	public function __invoke(BeforeRecordDownloadIsExecutedEvent $event): void
	{
		if ($event->getTable() !== 'tx_shape_form_submission') {
			return;
		}

		if (!in_array('form_values', $event->getColumnsToRender(), true)) {
			return;
		}

		match ($event->getFormat()) {
			'json' => $this->formatJson($event),
			'csv' => $this->formatCsv($event),
			default => null,
		};
	}

	/**
	 * Replaces the serialized form_values JSON string with the decoded structure.
	 */
	protected function formatJson(BeforeRecordDownloadIsExecutedEvent $event): void
	{
		$records = [];
		foreach ($event->getRecords() as $record) {
			$record['form_values'] = $this->decode($record['form_values'] ?? null);
			$records[] = $record;
		}
		$event->setRecords($records);
	}

	/**
	 * Explodes form_values into one "$fieldName" column per submitted field.
	 *
	 * Every record is given the full column set (missing fields as empty strings) so the columns
	 * line up in the CSV even when submissions contain different fields - csvDownloadAction() writes
	 * record values in array order, not keyed to the header.
	 */
	protected function formatCsv(BeforeRecordDownloadIsExecutedEvent $event): void
	{
		$headerRow = $event->getHeaderRow();
		unset($headerRow['form_values']);

		// '$fieldName' => 'fieldName', first-seen order preserved across all records
		$valueColumns = [];
		$decodedByRecord = [];
		foreach ($event->getRecords() as $index => $record) {
			$decoded = $this->decode($record['form_values'] ?? null);
			$decodedByRecord[$index] = is_array($decoded) ? $decoded : [];
			foreach ($decodedByRecord[$index] as $key => $value) {
				$valueColumns['$' . $key] = $key;
			}
		}

		$records = [];
		foreach ($event->getRecords() as $index => $record) {
			unset($record['form_values']);
			foreach ($valueColumns as $column => $key) {
				$value = $decodedByRecord[$index][$key] ?? '';
				$record[$column] = is_array($value) ? $this->flatten($value) : $value;
			}
			$records[] = $record;
		}

		$event->setHeaderRow(array_merge($headerRow, $valueColumns));
		$event->setRecords($records);
	}

	protected function decode(mixed $formValues): mixed
	{
		return json_decode((string)($formValues ?? ''), true);
	}

	protected function flatten(array $value): string
	{
		return implode(', ', array_map(
			static fn ($item) => is_scalar($item) ? (string)$item : json_encode($item),
			$value
		));
	}
}
