<?php

namespace App\Modules\Audience\Mail;

use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\SegmentMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SegmentMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SegmentMessage $segmentMessage,
        public AudienceContact $contact,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->segmentMessage->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.segment-message',
            with: [
                'segmentMessage' => $this->segmentMessage,
                'contact' => $this->contact,
            ],
        );
    }

}
