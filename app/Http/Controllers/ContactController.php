<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\MailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);
        try {
            $this->mailService->sendContactFormToAdmin($data);
            $this->mailService->sendContactFormAutoReply($data);
            return $this->successResponse(null, 'Thank you for contacting us! We will get back to you as soon as possible.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
