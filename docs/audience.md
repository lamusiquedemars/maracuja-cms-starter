# Audience

`Audience` est un module laboratoire destiné aux contacts, segments et messages ciblés.

Il peut devenir vendable dans un profil `univers`, mais il n’est pas encore une promesse commerciale standard.

## Ce Que Fait Le Module

- gérer des contacts;
- regrouper des contacts en segments;
- importer un CSV de contacts depuis un CRM externe;
- importer des contacts depuis les demandes entrantes;
- envoyer un message à un segment;
- suivre les livraisons envoyées ou échouées;
- conserver la préférence email de base et l’état de désinscription.

## Ce Que Le Module N’Est Pas

- ce n’est pas un clone complet de Brevo;
- ce n’est pas un gros CRM;
- ce n’est pas une newsletter marketing avancée;
- ce n’est pas un outil d’automatisation multi-étapes;
- ce n’est pas un panier d’e-commerce.

## Objets Métier

### Contacts

Un contact représente une personne réutilisable dans le temps.

Champs clés:

- nom;
- email;
- consentement email;
- date de désinscription;
- date de dernier contact.

### Segments

Un segment est un groupe de contacts.

Le segment doit rester une vue de pilotage, pas une fiche qui affiche 200 lignes de contacts en dur.

L’interface la plus saine est:

- liste de segments;
- synthèse du segment;
- accès vers la liste de contacts filtrée;
- actions groupées.

### Messages ciblés

Un message ciblé correspond à un envoi vers un segment.

Il doit conserver:

- objet;
- contenu;
- date d’envoi;
- nombre de destinataires;
- état des livraisons;
- erreurs éventuelles.

## Import CSV

L’import CSV se fait depuis `Relation client > Contacts`.

Colonnes recommandées:

```csv
email,first_name,last_name,accepts_email,segments,notes
alice@example.com,Alice,Durand,1,"Tous les clients;Clients en location","Location violon"
bernard@example.com,Bernard,Martin,1,"Tous les clients","Client atelier"
claire@example.com,Claire,Petit,0,"Tous les clients","Refus email"
```

Règles:

- `email` est obligatoire et sert de clé de mise à jour;
- `segments` accepte plusieurs segments séparés par `;`, `,` ou `|`;
- l’import peut aussi ajouter un segment commun à tout le fichier, par exemple `Tous les clients`;
- un contact existant est mis à jour au lieu d’être doublonné;
- les segments absents sont créés automatiquement;
- les emails invalides sont ignorés et remontés dans le résumé d’import.

## UX Recommandée

- page `Contacts` comme base de pilotage;
- page `Segments` comme vue de synthèse;
- page `Messages` comme historique d’envoi;
- filtres, recherche et actions groupées;
- avertissement avant envoi réel;
- estimation claire du nombre de destinataires;
- exclusion des contacts désinscrits ou refusant les emails.

## Garde-Fous

- un contact ne doit pas recevoir deux fois le même envoi;
- un contact désinscrit est exclu;
- un contact sans consentement ne part pas;
- un envoi doit laisser une trace exploitable;
- les gros volumes doivent rester lisibles sans fiche surchargée.

## Brevo

La bonne logique aujourd’hui est:

- exporter d’abord en CSV;
- intégrer l’API plus tard seulement si le besoin marketing devient réel;
- ne pas ajouter l’intégration tant que le cas d’usage reste léger.

## Position Produit

`Audience` sert quand le client a besoin d’un pilotage simple des contacts et d’envois ciblés intégrés au CMS.

Si le besoin devient newsletter marketing sérieuse, automatisation ou délivrabilité avancée, il faut plutôt brancher un outil dédié.
