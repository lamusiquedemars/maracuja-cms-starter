<?php

namespace App\Modules\ContactForm\Data;

class ContactMessage
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $subject,
        public readonly string $message,
        public readonly ?string $attributionSource = null,
        public readonly ?string $attributionMedium = null,
        public readonly ?string $attributionCampaign = null,
        public readonly ?array $attributionFirstTouch = null,
        public readonly ?array $attributionLastTouch = null,
        public readonly ?string $attributionMethod = null,
        public readonly ?float $attributionConfidence = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            subject: $data['subject'] ?? null,
            message: $data['message'],
            attributionSource: $data['attribution_source'] ?? null,
            attributionMedium: $data['attribution_medium'] ?? null,
            attributionCampaign: $data['attribution_campaign'] ?? null,
            attributionFirstTouch: $data['attribution_first_touch'] ?? null,
            attributionLastTouch: $data['attribution_last_touch'] ?? null,
            attributionMethod: $data['attribution_method'] ?? null,
            attributionConfidence: isset($data['attribution_confidence']) ? (float) $data['attribution_confidence'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'message' => $this->message,
            'attribution_source' => $this->attributionSource,
            'attribution_medium' => $this->attributionMedium,
            'attribution_campaign' => $this->attributionCampaign,
            'attribution_first_touch' => $this->attributionFirstTouch,
            'attribution_last_touch' => $this->attributionLastTouch,
            'attribution_method' => $this->attributionMethod,
            'attribution_confidence' => $this->attributionConfidence,
        ];
    }
}
