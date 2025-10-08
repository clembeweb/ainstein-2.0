<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public ?Tenant $tenant;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, ?Tenant $tenant = null)
    {
        $this->user = $user;
        $this->tenant = $tenant ?? $user->tenant;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Ainstein Platform!',
            from: config('mail.from.address', 'noreply@ainstein.com'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'user' => $this->user,
                'tenant' => $this->tenant,
                'loginUrl' => route('login'),
                'dashboardUrl' => route('tenant.dashboard'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
