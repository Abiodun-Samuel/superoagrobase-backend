<?php

namespace App\Exceptions\Auth;

use Exception;

class NoTokenFoundException extends Exception
{
    protected $code = 400;
    protected $message = 'No verification token found';

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error_code' => 'NO_TOKEN_FOUND',
            'data' => null,
            'timestamp' => now()->toISOString()
        ], $this->code);
    }
}
