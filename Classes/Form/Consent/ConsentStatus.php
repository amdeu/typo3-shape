<?php

namespace Amdeu\Shape\Form\Consent;

enum ConsentStatus: int
{
	case Pending = 0;
	case Approved = 1;
	case Dismissed = 2;
}