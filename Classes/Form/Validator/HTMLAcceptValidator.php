<?php

declare(strict_types=1);

namespace UBOS\Shape\Form\Validator;

use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

final class HTMLAcceptValidator extends AbstractValidator
{
	protected $supportedOptions = [
		'accept' => ['', 'HTML accept attribute string to validate against (MIME types and/or file extensions).', 'string'],
	];

	public function isValid(mixed $value): void
	{
		$this->ensureFileUploadTypes($value);

		$accept = $this->options['accept'];
		if (!$accept) {
			return;
		}

		if ($value instanceof UploadedFile) {
			$this->validateUploadedFile($value);
		} elseif ($value instanceof ObjectStorage && $value->count() > 0) {
			$index = 0;
			foreach ($value as $uploadedFile) {
				$this->validateUploadedFile($uploadedFile, $index);
				$index++;
			}
		}
	}

	protected function validateUploadedFile(UploadedFile $uploadedFile, ?int $index = null): void
	{
		$accept = $this->options['accept'];
		$fileInfo = $this->getFileInfo($uploadedFile->getTemporaryFileName());
		$mimeType = $fileInfo->getMimeType();
		$clientFilename = $uploadedFile->getClientFilename();

		if (!$this->matchesAcceptCriteria($accept, $mimeType, $clientFilename)) {
			$message = $this->translateErrorMessage(
				'validation.error.html_accept',
				'shape',
			);
			$code = 1739395517;
			if ($index !== null) {
				$this->addErrorForProperty((string)$index, $message, $code);
			} else {
				$this->addError($message, $code);
			}
		}
	}

	private function matchesAcceptCriteria(string $accept, string $mimeType, string $filename): bool
	{
		$acceptValues = array_map('trim', explode(',', $accept));

		foreach ($acceptValues as $acceptValue) {
			if ($this->matchesSingleAcceptValue($acceptValue, $mimeType, $filename)) {
				return true;
			}
		}

		return false;
	}

	private function matchesSingleAcceptValue(string $acceptValue, string $mimeType, string $filename): bool
	{
		if (str_starts_with($acceptValue, '.')) {
			return $this->matchesExtension($acceptValue, $filename);
		}

		return $this->matchesMimeType($acceptValue, $mimeType);
	}

	private function matchesExtension(string $extension, string $filename): bool
	{
		return str_ends_with(
			strtolower($filename),
			strtolower($extension)
		);
	}

	private function matchesMimeType(string $acceptMimeType, string $fileMimeType): bool
	{
		$acceptMimeType = strtolower($acceptMimeType);
		$fileMimeType = strtolower(trim($fileMimeType));

		if ($acceptMimeType === $fileMimeType) {
			return true;
		}

		if (str_ends_with($acceptMimeType, '/*')) {
			$acceptPrefix = substr($acceptMimeType, 0, -2);
			return str_starts_with($fileMimeType, $acceptPrefix . '/');
		}

		return false;
	}
}