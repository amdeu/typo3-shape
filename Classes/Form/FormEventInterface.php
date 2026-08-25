<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form;

interface FormEventInterface
{
	public function getRuntime(): FormRuntime;
}
