<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

class StaleDataException extends OriginalException
{
    /**
     * @param  array<int, string>  $messages
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        private readonly array $messages,
        private readonly array $extra = []
    ) {
        parent::__construct(
            $messages[0] ?? __('response.stale_data'),
            409
        );
    }

    /** Render the conflict as a JSON response. */
    public function render(): JsonResponse
    {
        return response()->json([
            'messages' => $this->messages ?: [__('response.stale_data')],
            ...$this->extra,
        ], 409);
    }
}
