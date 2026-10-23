<?php

namespace App\Mail;

use App\Enums\CodePurpose;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VerificationCodeMail extends Mailable
{
    public function __construct(
        public string $name,
        public string $code,
        public CodePurpose $purpose,
        public ?string $link = null, // link to the verification page (registration only)
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === CodePurpose::Register
                ? 'Verify your HELP2U account'
                : 'Your HELP2U login code',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verification-code');
    }
}
