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

		$records = [];
		$format = $event->getFormat();

		if ($format === 'json') {
			foreach ($event->getRecords() as $record) {
				$formValues = json_decode($record['form_values'], true);
				$record['form_values'] = $formValues;
				$records[] = $record;
			}
		}

		if ($format === 'csv') {
			$additionalHeaders = [];
			foreach ($event->getRecords() as $record) {
				$formValues = json_decode($record['form_values'], true);
				foreach ($formValues as $key => $value) {
					$col = '$'.$key;
					$additionalHeaders[$col] = $key;
					if (is_array($value)) {
						try {
							$record[$col] = implode(',', $value);
						} catch (\Exception $e) {
							$record[$col] = json_encode($value);
						}
					} else {
						$record[$col] = $value;
					}

				}
				unset($record['form_values']);
				$records[] = $record;
			}

			$headerRow = $event->getHeaderRow();
			unset($headerRow['form_values']);
			$event->setHeaderRow(array_merge($headerRow, $additionalHeaders));
		}

		$event->setRecords($records);
	}
}