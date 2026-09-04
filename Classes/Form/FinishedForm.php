<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The state needed to render a form's "finished" page on the request *after* the one that finished it.
 *
 * finishForm() runs the finish modules on the submitting request; the finished page is then shown via
 * a redirect, so a reload can't re-run the modules. This object is parked in the visitor's frontend
 * session under a random one-time token that travels in the redirect URL - the same
 * carry-state-across-a-request idea as {@see FormSession}, for the other end of the lifecycle.
 */
final readonly class FinishedForm
{
	private const SESSION_KEY_PREFIX = 'tx_shape_finish_';

	/**
	 * @param array<string, mixed> $variables Variables a finish module added via addFinishedVariables()
	 * @param array<string, mixed> $formValues The submitted values, for the finished template
	 */
	public function __construct(
		public string $template,
		public array $variables,
		public array $formValues,
	) {}

	public static function fromFinishEvent(FormFinishEvent $event): self
	{
		return new self(
			$event->finishedTemplate,
			$event->finishedVariables,
			$event->getRuntime()->session->values,
		);
	}

	/**
	 * Parks this state in the frontend session and returns the one-time token for the redirect URL.
	 */
	public function stash(ServerRequestInterface $request): string
	{
		$token = bin2hex(random_bytes(16));
		$request->getAttribute('frontend.user')?->setAndSaveSessionData(
			self::SESSION_KEY_PREFIX . $token,
			['template' => $this->template, 'variables' => $this->variables, 'formValues' => $this->formValues],
		);
		return $token;
	}

	public static function restore(ServerRequestInterface $request, string $token): ?self
	{
		if ($token === '') {
			return null;
		}
		$data = $request->getAttribute('frontend.user')?->getSessionData(self::SESSION_KEY_PREFIX . $token);
		if (!is_array($data)) {
			return null;
		}
		return new self(
			(string)($data['template'] ?? ''),
			(array)($data['variables'] ?? []),
			(array)($data['formValues'] ?? []),
		);
	}
}
