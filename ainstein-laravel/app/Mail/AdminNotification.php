<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $notificationType;
    public string $title;
    public string $message;
    public array $data;
    public ?User $relatedUser;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $notificationType,
        string $title,
        string $message,
        array $data = [],
        ?User $relatedUser = null
    ) {
        $this->notificationType = $notificationType;
        $this->title = $title;
        $this->message = $message;
        $this->data = $data;
        $this->relatedUser = $relatedUser;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $emoji = match($this->notificationType) {
            'new_user' => '👥',
            'high_usage' => '⚡',
            'error' => '🚨',
            'maintenance' => '🔧',
            'security' => '🛡️',
            default => '📢'
        };

        return new Envelope(
            subject: $emoji . ' ' . $this->title . ' - Ainstein Platform',
            from: config('mail.from.address', 'noreply@ainstein.com'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-notification',
            with: [
                'notificationType' => $this->notificationType,
                'title' => $this->title,
                'message' => $this->message,
                'data' => $this->data,
                'relatedUser' => $this->relatedUser,
                'adminUrl' => url('/admin'),
                'dashboardUrl' => url('/admin/dashboard'),
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
