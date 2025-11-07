<?php

namespace Amdeu\Shape\Enum;

enum ConsentStatus: int
{
	case Pending = 0;
	case Approved = 1;
	case Dismissed = 2;
}