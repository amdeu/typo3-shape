<?php

namespace Amdeu\Shape\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Amdeu\Shape\Utility;

#[Console\Attribute\AsCommand(
	name: 'shape:mirror-form-finisher-relations',
	description: 'Mirror form finisher relation fields based on current tx_shape_form.finishers config type.',
)]
class MirrorFormFinisherRelationsCommand extends Command
{
	public function __construct(
		private readonly Utility\TcaRelationService $databaseService
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setHelp('Mirror form finisher relation fields based on current tx_shape_form.finishers config type.');
	}

	protected function execute(
		Console\Input\InputInterface $input,
		Console\Output\OutputInterface $output,
	): int {
		$this->databaseService->mirrorCurrentFormFinisherRelations();
		return Command::SUCCESS;
	}
}