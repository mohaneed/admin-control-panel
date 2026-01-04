<?php

namespace App\Domain\Enum;

enum ActionResult: string
{
    case SUCCESS = 'success';
    case REJECTED = 'rejected';
    case INVALID_STATE = 'invalid_state';
}
