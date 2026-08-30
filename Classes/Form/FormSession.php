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

	/**
	 * Domain-separation label mixed into the key derivation so this ciphertext can't be
	 * swapped with any other sodium payload the site produces from the same encryptionKey.
	 */
	const SECRET = 'tx_shape:FormSession';

	public function getId(): string
	{
		$this->id = $this->id ?: Core\Utility\GeneralUtility::makeInstance(Core\Crypto\Random::class)->generateRandomHexString(40);
		return $this->id;
	}

	/**
	 * Produces a self-contained, encrypted-and-authenticated token for the session.
	 *
	 * The token travels to the browser (hidden form field) and is persisted in the email-consent
	 * DB row, so it must be opaque and tamper-proof without any server-side lookup. sodium_secretbox
	 * (XSalsa20-Poly1305) gives both confidentiality and integrity, so no separate HMAC is needed.
	 */
	public static function serialize(FormSession $session): string
	{
		$plaintext = serialize($session);
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$token = base64_encode($nonce . sodium_crypto_secretbox($plaintext, $nonce, self::encryptionKey()));
		sodium_memzero($plaintext);
		return $token;
	}

	/**
	 * @throws Exception\InvalidSessionException if the token is missing, malformed, tampered with,
	 *         or was produced with a different encryption key.
	 */
	public static function validateAndUnserialize(string $token): FormSession
	{
		try {
			$binary = base64_decode($token, true);
			if ($binary === false
				|| strlen($binary) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES
			) {
				throw new \RuntimeException('Malformed session token');
			}

			$nonce = substr($binary, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$ciphertext = substr($binary, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

			$plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, self::encryptionKey());
			if ($plaintext === false) {
				throw new \RuntimeException('Session token failed authentication');
			}

			$session = unserialize($plaintext, ['allowed_classes' => [self::class]]);
			sodium_memzero($plaintext);

			if (!$session instanceof self) {
				throw new \InvalidArgumentException('Decrypted data is not a FormSession', 1741370001);
			}
			return $session;
		} catch (\Throwable $e) {
			throw new Exception\InvalidSessionException(
				'Session validation failed',
				1741370002,
				$e
			);
		}
	}

	/**
	 * 32-byte secretbox key derived from the site's encryption key.
	 */
	private static function encryptionKey(): string
	{
		$encryptionKey = (string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? '');
		if ($encryptionKey === '') {
			throw new \RuntimeException(
				'Cannot secure the form session: $GLOBALS[TYPO3_CONF_VARS][SYS][encryptionKey] is not set.',
				1741370003
			);
		}
		return sodium_crypto_generichash(
			self::SECRET . ':' . $encryptionKey,
			'',
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES
		);
	}
}
