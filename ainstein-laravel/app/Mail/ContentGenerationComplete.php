<?php

namespace App\Mail;

use App\Models\User;
use App\Models\ContentGeneration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContentGenerationComplete extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public ContentGeneration $generation;
    public bool $isSuccess;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, ContentGeneration $generation, bool $isSuccess = true)
    {
        $this->user = $user;
        $this->generation = $generation;
        $this->isSuccess = $isSuccess;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isSuccess
            ? 'Content Generation Complete ✅'
            : 'Content Generation Failed ❌';

        return new Envelope(
            subject: $subject . ' - Ainstein Platform',
            from: config('mail.from.address', 'noreply@ainstein.com'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.content-generation',
            with: [
                'user' => $this->user,
                'generation' => $this->generation,
                'isSuccess' => $this->isSuccess,
                'dashboardUrl' => route('tenant.dashboard'),
                'generationsUrl' => route('tenant.generations'),
                'generationUrl' => route('tenant.generations') . '#' . $this->generation->id,
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
