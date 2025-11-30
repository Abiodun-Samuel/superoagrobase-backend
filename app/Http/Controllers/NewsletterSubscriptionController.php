<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeNewsletterRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class NewsletterSubscriptionController extends Controller
{
    public function store(SubscribeNewsletterRequest $request): JsonResponse
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => $request->email,
            'subscribed_at' => now(),
        ]);
        return $this->successResponse($subscriber, 'Successfully subscribed to newsletter.');
    }
}
