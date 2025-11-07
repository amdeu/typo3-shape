<?php

namespace Amdeu\Shape\Form;

use TYPO3\CMS\Core;

class FormSession
{
	public function __construct(
		protected string $id = '',
		public array $values = [],
		public int $returnPageIndex = 1,
	)
	{}

	const SECRET = '__session';

	public function getId(): string
	{
		$this->id = $this->id ?: Core\Utility\GeneralUtility::makeInstance(Core\Crypto\Random::class)->generateRandomHexString(40);
		return $this->id;
	}

	public static function serialize(FormSession $session): string
	{
		$hashService = Core\Utility\GeneralUtility::makeInstance(Core\Crypto\HashService::class);
		return $hashService->appendHmac(
			base64_encode(serialize($session)),
			FormSession::SECRET
		);
	}
	public static function validateAndUnserialize(string $serializedSessionWithHmac): FormSession
	{
		$hashService = Core\Utility\GeneralUtility::makeInstance(Core\Crypto\HashService::class);
		try {
			$serializedSession = $hashService->validateAndStripHmac(
				$serializedSessionWithHmac,
				self::SECRET
			);
			$session = unserialize(base64_decode($serializedSession));
			if (!$session instanceof self) {
				throw new \InvalidArgumentException('Unserialized data is not a FormSession', 1741370001);
			}
			return $session;
		} catch (\Exception $e) {
			throw new Exception\InvalidSessionException(
				'Session validation failed',
				1741370002,
				$e
			);
		}
	}
}