<?php

namespace App\Exceptions\Auth;

use Exception;

class EmailAlreadyVerifiedException extends Exception
{
    protected $code = 400;
    protected $message = 'Email already verified';

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error_code' => 'EMAIL_ALREADY_VERIFIED',
            'data' => null,
            'timestamp' => now()->toISOString()
        ], $this->code);
    }
}
