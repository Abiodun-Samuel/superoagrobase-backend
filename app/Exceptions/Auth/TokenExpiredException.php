<?php

namespace App\Exceptions\Auth;

use Exception;

class TokenExpiredException extends Exception
{
    protected $code = 400;
    protected $message = 'Verification token has expired';
    private $expiredAt;

    public function __construct($expiredAt = null)
    {
        parent::__construct($this->message);
        $this->expiredAt = $expiredAt;
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error_code' => 'TOKEN_EXPIRED',
            'data' => [
                'expired_at' => $this->expiredAt
            ],
            'timestamp' => now()->toISOString()
        ], $this->code);
    }
}
