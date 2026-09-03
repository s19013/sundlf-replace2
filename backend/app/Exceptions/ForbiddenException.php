<?php

namespace App\Exceptions;

class ForbiddenException extends OriginalException
{
    public function __construct(string $message)
    {
        parent::__construct(
            $message,
            403
        );
    }
}
