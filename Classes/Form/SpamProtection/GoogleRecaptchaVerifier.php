<?php

declare(strict_types=1);

namespace UBOS\Shape\SpamProtection\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Verifies Google reCAPTCHA (v2 and v3)
 */
final class GoogleRecaptchaVerifier
{
	private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

	public function __construct(
		protected readonly RequestFactory $requestFactory
	) {}

	#[AsEventListener]
	public function __invoke(SpamAnalysisEvent $event): void
	{
		$runtime = $event->runtime;
		$config = $runtime->settings['spamProtection']['googleRecaptcha'] ?? [];

		if (!($config['enabled'] ?? false)) {
			return;
		}

		$secretKey = $config['secretKey'] ?? '';
		if (empty($secretKey)) {
			return;
		}

		$token = $runtime->postValues['g-recaptcha-response'] ?? '';
		if (empty($token)) {
			$event->addSpamReason('google_recaptcha_missing', [
				'message' => 'reCAPTCHA response missing',
			]);
			return;
		}

		$result = $this->verify($token, $secretKey, $runtime->request);

		// v2: simple success/fail
		if (($config['version'] ?? 'v2') === 'v2') {
			if (!$result['success']) {
				$event->addSpamReason('google_recaptcha_failed', [
					'message' => 'reCAPTCHA verification failed',
				]);
			}
			return;
		}

		// v3: score-based
		$score = $result['score'] ?? 0.0;
		$minimumScore = (float)($config['minimumScore'] ?? 0.5);

		if ($score < $minimumScore) {
			$event->addSpamReason('google_recaptcha_low_score', [
				'message' => sprintf('reCAPTCHA score too low: %.2f < %.2f', $score, $minimumScore),
				'score' => $score,
			]);
		}
	}

	protected function verify(string $token, string $secretKey, $request): array
	{
		try {
			$response = $this->requestFactory->request(
				self::VERIFY_URL,
				'POST',
				[
					'form_params' => [
						'secret' => $secretKey,
						'response' => $token,
						'remoteip' => $request->getAttribute('normalizedParams')?->getRemoteAddress(),
					],
				]
			);

			return json_decode($response->getBody()->getContents(), true) ?? ['success' => false];

		} catch (\Exception $e) {
			// Fail open: if Google API is down, don't block legitimate users
			return ['success' => true, 'failOpen' => true];
		}
	}
}