@php
    $previewContact = new \App\Modules\Audience\Models\AudienceContact([
        'first_name' => 'Client',
        'email' => 'client@example.test',
        'accepts_email' => true,
    ]);
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
    @include('mail.segment-message', [
        'segmentMessage' => $segmentMessage,
        'contact' => $previewContact,
        'isPreview' => true,
    ])
</div>
