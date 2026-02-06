<?php

namespace App\Exceptions\Auth;

use Exception;

class UserNotFoundException extends Exception
{
    protected $code = 404;
    protected $message = 'User not found';

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error_code' => 'USER_NOT_FOUND',
            'data' => null,
            'timestamp' => now()->toISOString()
        ], $this->code);
    }
}
