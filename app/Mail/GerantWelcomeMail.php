<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GerantWelcomeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $gerant,
        public string $complexeName,
        public string $resetUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to PlaySpace — Set your password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gerant-welcome',
        );
    }
}
