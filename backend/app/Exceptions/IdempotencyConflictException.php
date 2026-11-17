<?php

namespace App\Exceptions;

use Exception;

class IdempotencyConflictException extends Exception
{
    // Custom exception thrown when idempotency key is reused with a different payload fingerprint
}
