# Parcours Contact

Ce document décrit le chemin des messages entrés par le formulaire public, puis éventuellement stockés et réutilisés dans l’admin.

## Objectif

Le parcours contact doit rester simple pour le visiteur et lisible pour l’équipe admin.

Il sépare trois responsabilités:

- `ContactForm`: formulaire public, validation, emails;
- `Inquiries`: stockage et suivi des demandes entrantes;
- `Audience`: contacts, segments et messages ciblés.

## Flux

1. Le visiteur envoie un formulaire.
2. `ContactForm` valide les champs et construit le message.
3. Le système envoie l’email admin si l’adresse de contact est configurée.
4. Le système envoie éventuellement un email de confirmation au visiteur.
5. Si `Inquiries` est actif, la demande est stockée en base.
6. Si `Audience` est actif, une demande peut être transformée en contact de segment.

## Règles produit

- Le formulaire public ne doit pas dépendre de `Audience`.
- Le stockage des demandes n’est pas obligatoire pour faire fonctionner le contact.
- L’import vers `Audience` est un usage admin, pas une étape bloquante.
- Une demande doit pouvoir vivre seule comme simple message, puis être réutilisée plus tard si besoin.

## Configuration

Le comportement varie selon les modules activés:

```env
MARACUJA_MODULE_CONTACT_FORM=true
MARACUJA_MODULE_INQUIRIES=true
MARACUJA_MODULE_AUDIENCE=false
```

Si `Inquiries` est désactivé, le contact reste utilisable, mais la demande ne s’écrit pas en base.

## Mode connecté à Cremona

Une instance peut activer le bridge par `MARACUJA_CREMONA_ENABLED=true` et
fournir l'endpoint, le jeton et la référence de site dans son `.env`. Dans ce
mode, le formulaire garde validation et accusé de réception, mais ne crée pas
de demande locale : une livraison chiffrée et rejouable est envoyée à Cremona.
Les demandes et réponses sont alors traitées dans Cremona.

## Admin

Dans l’admin, le flux doit rester lisible:

- liste des demandes;
- statut clair;
- lien de réponse ou reprise;
- éventuelle création de contact;
- export ou reprise vers `Audience` si le module est présent.

## Ce Qu’on Évite

- ne pas mélanger formulaire public et mini-CRM;
- ne pas rendre `Audience` obligatoire pour le contact;
- ne pas dupliquer les mêmes données dans plusieurs tables sans raison;
- ne pas faire du contact un objet générique trop lourd.
