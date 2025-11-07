<?php

namespace Amdeu\Shape\Form\Serialization;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core;

final class ValueSerializationHandler
{
	#[AsEventListener]
	public function __invoke(ValueSerializationEvent $event): void
	{
		if ($event->isPropagationStopped()) {
			return;
		}
		$value = $event->value;
		if ($value instanceof Core\Http\UploadedFile) {
			$value = [$value];
		}
		if (is_array($value) && reset($value) instanceof Core\Http\UploadedFile) {
			// if the field was not validated or has errors, do not save the uploaded files
			if ($event->field->getValidationResult() == null || $event->field->getValidationResult()->hasErrors()) {
				$event->serializedValue = '';
				return;
			}
			$event->serializedValue = $this->saveUploadedFiles($value, $event);
		}
	}

	/**
	 * Save uploaded files to the session upload folder and return the file paths
	 * @param Core\Http\UploadedFile[] $files
	 * @param ValueSerializationEvent $event
	 * @return string[]
	 */
	protected function saveUploadedFiles(array $files, ValueSerializationEvent $event): array
	{
		$folderPath = $event->runtime->getSessionUploadFolder();
		$uploadStorage = $event->runtime->uploadStorage;
		if (!$uploadStorage->hasFolder($folderPath)) {
			$uploadStorage->createFolder($folderPath);
		}
		$filePaths = [];
		foreach ($files as $file) {
			if ($file->getError() !== UPLOAD_ERR_OK) {
				// todo: log error
				continue;
			}

			$filename = $this->sanitizeFilename($file->getClientFilename());
			try {
				$newFile = $uploadStorage->addUploadedFile(
					$file,
					$uploadStorage->getFolder($folderPath),
					$filename,
					Core\Resource\Enum\DuplicationBehavior::RENAME
				);
				$filePaths[] = $uploadStorage->getUid() . ':' . $folderPath . $newFile->getName();
			} catch (\Exception $e) {
				// todo: log exception
				continue;
			}
		}
		return $filePaths;
	}

	protected function sanitizeFilename(string $filename): string
	{
		$filename = basename($filename);

		// Split into name and extension
		$pathinfo = pathinfo($filename);
		$name = $pathinfo['filename'] ?? 'file';
		$extension = $pathinfo['extension'] ?? '';

		// Sanitize the name part only
		$name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);

		// Ensure we keep the extension
		return $extension ? "{$name}.{$extension}" : $name;
	}
}
