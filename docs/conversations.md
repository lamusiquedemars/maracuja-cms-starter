# Conversations

Le module `Conversations` fournit un accueil conversationnel progressif, une
orientation vers les canaux configurés et une boîte de réception Filament. Il
ne remplace ni un professionnel ni un canal d’urgence.

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

La clé reste exclusivement dans l’environnement serveur. Le fournisseur, le
modèle et ses limites techniques restent également dans l’environnement.
L’adaptateur utilise
la Responses API avec une sortie JSON Schema stricte, `store: false`, un
historique borné et un identifiant de sécurité pseudonymisé. Un autre
fournisseur implémente simplement `ConversationAiProvider`.

## Paramétrage par site

Après la migration, la page **Accueil > Accueil conversationnel** permet de
configurer :

- le contexte de l’organisation, la langue et le ton ;
- les informations minimales que l’assistant peut demander ;
- les critères d’urgence et les moments où proposer une orientation ;
- WhatsApp, la demande de contact et les canaux autorisés ;
- les textes publics, les messages WhatsApp et les notifications ;
- des instructions particulières bornées.

Ces réglages sont enregistrés dans `conversation_settings`, séparément des
secrets. `ConversationInstructionsBuilder` assemble le profil du site avec les
protections universelles codées dans le starter. Un site ne peut donc pas
remplacer librement le prompt système ni retirer ces protections.

## WhatsApp

WhatsApp se configure dans l’administration. Aucun lien n’est présenté avant
que l’IA ait compris le contexte minimal ou détecté un autre déclencheur
configuré. Le signal structuré `offer_contact_options` contrôle cet affichage :
l’IA choisit le moment de la proposition, jamais le canal à la place du
visiteur.

Après l’orientation, le lien `wa.me` contient uniquement le texte configuré et
la référence publique.
Il ne contient jamais le résumé ou les messages. Cette V1 ne synchronise aucun
message WhatsApp. Un futur adaptateur WhatsApp Business pourra recevoir et
émettre des messages en conservant `ConversationChannel::WhatsApp`.

Si la demande de contact est activée, le second choix lance une collecte
progressive dans le fil : nom d’usage, canal, coordonnée et consentement. Une
`Inquiry` n’est créée qu’après ce consentement explicite.

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

Les conversations inactives sans `Inquiry` sont archivées après un délai court.
Les conversations déjà clôturées ou archivées sont ensuite supprimées après la
durée de rétention :

```dotenv
MARACUJA_CONVERSATIONS_ARCHIVE_INACTIVE_AFTER_HOURS=48
MARACUJA_CONVERSATIONS_RETENTION_DAYS=90
```

```bash
php artisan conversations:prune --dry-run
php artisan conversations:prune
```

La commande est planifiée chaque jour à 03:30. Le serveur doit exécuter le
scheduler Laravel pour que cette règle soit automatique.

La suppression cascade vers les messages, mais ne supprime pas automatiquement
le contact central, qui peut être utilisé par d’autres modules.

## Références publiques

La longueur est un réglage technique :

```dotenv
MARACUJA_CONVERSATIONS_REFERENCE_LENGTH=8
```

Le minimum appliqué est 6 et le maximum 16. Les références utilisent uniquement
des majuscules et chiffres non ambigus, sans `0`, `O`, `1`, `I` ni `L`. Une
contrainte unique en base et une nouvelle génération en cas de collision
garantissent l’unicité.

## Points d’extension

Les événements `ConversationStarted`, `MessageAdded` et
`HumanHandoverRequested` permettent aux futurs modules CRM, notifications,
rendez-vous ou documents de réagir sans dépendre de l’interface publique.
