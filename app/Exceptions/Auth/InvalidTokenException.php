<?php

namespace App\Exceptions\Auth;

use Exception;

class InvalidTokenException extends Exception
{
    protected $code = 400;
    protected $message = 'Invalid verification token';

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error_code' => 'INVALID_TOKEN',
            'data' => null,
            'timestamp' => now()->toISOString()
        ], $this->code);
    }
}
