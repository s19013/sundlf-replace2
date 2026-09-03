<?php

namespace App\Exceptions;

class DuplicationException extends OriginalException
{
    public function __construct(string $attribute)
    {
        parent::__construct(
            __('response.duplication', ['attribute' => $attribute]),
            409
        );
    }
}
