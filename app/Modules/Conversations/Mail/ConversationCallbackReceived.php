<?php

namespace App\Modules\Conversations\Mail;

use App\Modules\Inquiries\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConversationCallbackReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: filled($this->inquiry->email) ? [$this->inquiry->email] : [],
            subject: (string) config('maracuja.conversations.notifications.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.conversation-callback-received',
            with: [
                'conversation' => $this->inquiry->conversation,
                'inquiry' => $this->inquiry,
                'adminUrl' => url('/admin/conversations/'.$this->inquiry->conversation_id),
            ],
        );
    }
}
