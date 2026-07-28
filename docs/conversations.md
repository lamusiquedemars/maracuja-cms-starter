# Conversations

Le module `Conversations` fournit un accueil conversationnel progressif, une
reprise humaine et une boîte de réception Filament. Il ne remplace ni un
professionnel ni un canal d’urgence.

## Activation

```dotenv
MARACUJA_MODULE_CONVERSATIONS=true
MARACUJA_CONVERSATIONS_AI_PROVIDER=fake
```

Le fournisseur `fake` permet le développement et les démonstrations sans appel
externe. Pour OpenAI :

```dotenv
MARACUJA_CONVERSATIONS_AI_PROVIDER=openai
OPENAI_API_KEY=
OPENAI_CONVERSATIONS_MODEL=gpt-5.6-luna
OPENAI_CONVERSATIONS_REASONING_EFFORT=low
OPENAI_CONVERSATIONS_MAX_OUTPUT_TOKENS=600
```

La clé reste exclusivement dans l’environnement serveur. L’adaptateur utilise
la Responses API avec une sortie JSON Schema stricte, `store: false`, un
historique borné et un identifiant de sécurité pseudonymisé. Un autre
fournisseur implémente simplement `ConversationAiProvider`.

Les instructions génériques peuvent être remplacées avec
`MARACUJA_CONVERSATIONS_AI_INSTRUCTIONS`. Chaque site doit définir son propre
profil métier, ses limites, sa langue et ses règles de transfert humain.

## WhatsApp

```dotenv
MARACUJA_CONVERSATIONS_WHATSAPP_ENABLED=true
MARACUJA_CONVERSATIONS_WHATSAPP_NUMBER=33612345678
MARACUJA_CONVERSATIONS_WHATSAPP_DIRECT_MESSAGE="Bonjour, je souhaite contacter votre équipe."
MARACUJA_CONVERSATIONS_WHATSAPP_MESSAGE="Bonjour, ma référence est {{reference}}."
```

Le lien direct utilise un message sans référence tant qu’aucune conversation
n’existe. Après un échange, le lien `wa.me` contient uniquement un texte
configuré et la référence publique.
Il ne contient jamais le résumé ou les messages. Cette V1 ne synchronise aucun
message WhatsApp. Un futur adaptateur WhatsApp Business pourra recevoir et
émettre des messages en conservant `ConversationChannel::WhatsApp`.

## Contacts et Audience

`contacts` porte l’identité et les coordonnées partagées. `audience_contacts`
reste responsable du consentement marketing, des segments, désabonnements,
rebonds et identifiants Brevo. La migration rattache les anciennes données par
email puis téléphone normalisés sans supprimer les colonnes Audience.

`inquiries` représente une demande qualifiée ou un résultat de handover. Une
conversation n’est donc pas une Inquiry : les deux peuvent converger plus tard
par une action métier explicite.

## Confidentialité et rétention

Les notes internes ne sont jamais renvoyées par les endpoints publics. Les
erreurs IA journalisent uniquement l’identifiant de conversation, le fournisseur
et la classe d’erreur.

Les conversations clôturées ou archivées peuvent être supprimées après la durée
configurée :

```dotenv
MARACUJA_CONVERSATIONS_RETENTION_DAYS=90
```

```bash
php artisan conversations:prune --dry-run
php artisan conversations:prune
```

La suppression cascade vers les messages, mais ne supprime pas automatiquement
le contact central, qui peut être utilisé par d’autres modules.

## Points d’extension

Les événements `ConversationStarted`, `MessageAdded` et
`HumanHandoverRequested` permettent aux futurs modules CRM, notifications,
rendez-vous ou documents de réagir sans dépendre de l’interface publique.
