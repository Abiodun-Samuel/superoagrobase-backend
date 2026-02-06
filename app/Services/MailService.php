<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MailService
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('mail.mailers.zeptomail.base_url');
        $this->apiKey = config('mail.mailers.zeptomail.api_key');
    }

    private function sendEmail(array $data): array
    {
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'authorization' => "Zoho-enczapikey {$this->apiKey}",
            'cache-control' => 'no-cache',
            'content-type' => 'application/json'
        ])
            ->timeout(60)
            ->post($this->apiUrl, $data);

        if (!$response->successful()) {
            $this->handleApiError($response);
        }

        return $response->json();
    }

    private function handleApiError($response): void
    {
        $statusCode = $response->status();
        $responseBody = $response->json();

        $errorMessage = 'Email service error occurred';

        if (isset($responseBody['error']['details'][0]['message'])) {
            $errorMessage = $responseBody['error']['details'][0]['message'];
        } elseif (isset($responseBody['message'])) {
            $errorMessage = $responseBody['message'];
        } elseif (isset($responseBody['error'])) {
            $errorMessage = is_string($responseBody['error'])
                ? $responseBody['error']
                : json_encode($responseBody['error']);
        }

        $userMessage = match ($statusCode) {
            400 => 'Invalid email request. Please check the email details.',
            401 => 'Email service authentication failed.',
            403 => 'Not authorized to send emails.',
            429 => 'Too many email requests. Please try again later.',
            500, 502, 503, 504 => 'Email service is temporarily unavailable.',
            default => "Failed to send email: {$errorMessage}",
        };
        throw new \Exception($userMessage, $statusCode);
    }

    private function buildRecipientsArray(array $recipients): array
    {
        return array_map(fn($recipient) => [
            "email_address" => [
                "address" => $recipient['email'],
                "name" => $recipient['name'] ?? ''
            ]
        ], $recipients);
    }

    public function sendContactFormToAdmin(array $contactData): array
    {
        $adminEmail = config('mail.from.address', 'contact@superoagrobase.com');

        $data = [
            "mail_template_key" => config('mail.template.contact'),
            "from" => [
                "address" => $adminEmail,
                "name" => config('mail.from.name', 'SuperoAgrobase')
            ],
            "to" => [
                [
                    "email_address" => [
                        "address" => $adminEmail,
                        "name" => "Admin"
                    ]
                ]
            ],
            "merge_info" => [
                "name" => $contactData['name'],
                "email" => $contactData['email'],
                "phone" => $contactData['phone'] ?? 'Not provided',
                "subject" => $contactData['subject'] ?? 'General Inquiry',
                "message" => $contactData['message'],
            ],
            "reply_to" => [
                [
                    "address" => $contactData['email'],
                    "name" => $contactData['name']
                ]
            ]
        ];

        return $this->sendEmail($data);
    }

    public function sendContactFormAutoReply(array $contactData): array
    {
        $recipients = [
            [
                'email' => $contactData['email'],
                'name' => $contactData['name']
            ]
        ];

        $data = [
            "mail_template_key" => config('mail.template.contact_auto_reply'),
            "from" => [
                "address" => config('mail.from.address', 'contact@superoagrobase.com'),
                "name" => config('mail.from.name', 'SuperoAgrobase')
            ],
            "to" => $this->buildRecipientsArray($recipients),
            "merge_info" => [
                "name" => $contactData['name'],
                "subject" => $contactData['subject'] ?? 'General Inquiry',
                "products_url" => config('app.frontendUrl') . '/products',
                "faqs_url" => config('app.frontendUrl') . '/faqs',
            ]
        ];

        return $this->sendEmail($data);
    }

    public function sendWelcomeVerificationEmail(array $userData): array
    {
        $recipients = [
            [
                'email' => $userData['email'],
                'name' => $userData['name']
            ]
        ];

        $data = [
            "mail_template_key" => config('mail.template.welcome_verification'),
            "from" => [
                "address" => config('mail.from.address', 'noreply@superoagrobase.com'),
                "name" => config('mail.from.name', 'SuperoAgrobase')
            ],
            "to" => $this->buildRecipientsArray($recipients),
            "merge_info" => [
                "name" => $userData['name'],
                "verify_url" => $userData['verify_url'],
            ]
        ];

        return $this->sendEmail($data);
    }

    public function sendEmailVerification(array $userData): array
    {
        $recipients = [
            [
                'email' => $userData['email'],
                'name' => $userData['name']
            ]
        ];

        $data = [
            "mail_template_key" => config('mail.template.email_verification'),
            "from" => [
                "address" => config('mail.from.address', 'noreply@superoagrobase.com'),
                "name" => config('mail.from.name', 'SuperoAgrobase')
            ],
            "to" => $this->buildRecipientsArray($recipients),
            "merge_info" => [
                "verify_url" => $userData['verify_url'],
            ]
        ];

        return $this->sendEmail($data);
    }

    public function sendPasswordResetEmail(array $userData): array
    {
        $recipients = [
            [
                'email' => $userData['email'],
                'name' => $userData['name']
            ]
        ];

        $data = [
            "mail_template_key" => config('mail.template.password_reset'),
            "from" => [
                "address" => config('mail.from.address', 'noreply@superoagrobase.com'),
                "name" => config('mail.from.name', 'SuperoAgrobase')
            ],
            "to" => $this->buildRecipientsArray($recipients),
            "merge_info" => [
                "name" => $userData['name'],
                "reset_url" => $userData['reset_url'],
            ]
        ];

        return $this->sendEmail($data);
    }

    public function sendPasswordResetConfirmation(array $userData): array
    {
        $recipients = [
            [
                'email' => $userData['email'],
                'name' => $userData['name']
            ]
        ];

        $data = [
            "mail_template_key" => config('mail.template.password_reset_confirmation'),
            "from" => [
                "address" => config('mail.from.address', 'noreply@superoagrobase.com'),
                "name" => config('mail.from.name', 'SuperoAgrobase')
            ],
            "to" => $this->buildRecipientsArray($recipients),
            "merge_info" => [
                "name" => $userData['name'],
            ]
        ];

        return $this->sendEmail($data);
    }

    public function sendVendorRequestToAdmin(array $requestData): array
    {
        $adminEmail = config('mail.from.address', 'contact@superoagrobase.com');

        $data = [
            "mail_template_key" => config('mail.template.vendor_request_admin'),
            "from" => [
                "address" => config('mail.from.address', 'noreply@superoagrobase.com'),
                "name" => config('mail.from.name', 'SuperoAgrobase')
            ],
            "to" => [
                [
                    "email_address" => [
                        "address" => $adminEmail,
                        "name" => "Admin"
                    ]
                ]
            ],
            "merge_info" => [
                "name" => $requestData['name'],
                'admin_url' => $requestData['admin_url'],
                'name' => $requestData['name'],
                'email' => $requestData['email'],
                'phone_number' => $requestData['phone_number'],
                'company_name' => $requestData['company_name'],
            ]
        ];

        return $this->sendEmail($data);
    }

    public function sendVendorRequestApproved(array $vendorData): array
    {
        $recipients = [
            [
                'email' => $vendorData['email'],
                'name' => $vendorData['name']
            ]
        ];

        $data = [
            "mail_template_key" => config('mail.template.vendor_request_approved'),
            "from" => [
                "address" => config('mail.from.address', 'noreply@superoagrobase.com'),
                "name" => config('mail.from.name', 'SuperoAgrobase')
            ],
            "to" => $this->buildRecipientsArray($recipients),
            "merge_info" => [
                "name" => $vendorData['name'],
                "email" => $vendorData['email'],
                "reset_password_url" => $vendorData['reset_password_url'],
            ]
        ];

        return $this->sendEmail($data);
    }

    /**
     * Send vendor application rejected email
     *
     * @param array $vendorData
     * @return array
     */
    public function sendVendorRequestRejected(array $vendorData): array
    {
        $recipients = [
            [
                'email' => $vendorData['email'],
                'name' => $vendorData['name']
            ]
        ];

        $data = [
            "mail_template_key" => config('mail.template.vendor_request_rejected'),
            "from" => [
                "address" => config('mail.from.address', 'noreply@superoagrobase.com'),
                "name" => config('mail.from.name', 'SuperoAgrobase')
            ],
            "to" => $this->buildRecipientsArray($recipients),
            "merge_info" => [
                "name" => $vendorData['name'],
                "email" => $vendorData['email'],
                "rejection_reason" => $vendorData['rejection_reason'],
                "reapply_url" => $vendorData['reapply_url'],
            ]
        ];

        return $this->sendEmail($data);
    }
}
