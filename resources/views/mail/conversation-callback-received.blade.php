<p>Une personne demande à être recontactée depuis le site.</p>

<p><strong>Référence :</strong> {{ $conversation->public_reference }}</p>
<p><strong>Nom :</strong> {{ $inquiry->name }}</p>
@if (filled($inquiry->email))
    <p><strong>Email :</strong> {{ $inquiry->email }}</p>
@endif
@if (filled($inquiry->phone))
    <p><strong>Téléphone :</strong> {{ $inquiry->phone }}</p>
@endif
@if (filled($conversation->urgency?->label()))
    <p><strong>Urgence :</strong> {{ $conversation->urgency->label() }}</p>
@endif
@if (filled($conversation->summary))
    <p><strong>Résumé :</strong> {{ $conversation->summary }}</p>
@endif

<p><a href="{{ $adminUrl }}">Ouvrir la conversation dans l’administration</a></p>
