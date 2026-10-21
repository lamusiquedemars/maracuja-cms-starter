# Bugs et corrections ouverts

## MCS-001 - Images absentes dans la galerie principale de la home

- Statut : resolu le 16 juillet 2026.
- Signale le : 15 juillet 2026.
- Environnement constate : `http://maracuja-cms-starter.test` sous Herd.

Les images de la galerie principale de la page d'accueil ne s'affichent pas,
alors que des fichiers de `public/storage` sont accessibles directement.
Cause : les trois SVG de demonstration avaient ete supprimes alors que la base
locale les referenciait encore. Les assets, le seeding et les controles HTTP
ont ete retablis.

## MCS-002 - Remplacer SQLite par MySQL dans le Starter

- Statut : resolu le 16 juillet 2026.
- Signale le : 15 juillet 2026.

Le Starter utilise maintenant MySQL pour l'installation locale et les tests.
La configuration SQLite, sa creation Composer et le fichier local ont ete
retires. La migration MySQL a aussi revele et corrige un nom d'index trop long
et une requete de test non portable.

## MCS-003 - Exception dans l’administration des Intervenants

- Statut : a reproduire et diagnostiquer.
- Signale le : 21 octobre 2026.
- Environnement constate : site temoin local sous Herd.

L’ouverture de la ressource `Intervenants` provoque une exception. Ne pas
conclure a un défaut du package `site-core-events` sans conserver le message et
la trace de l’exception. A reprendre avec la page exacte, l’utilisateur et la
trace Laravel correspondante.

## MCS-004 - Données Audience historiques non rapprochées des Contacts

- Statut : correction de données locale a planifier.
- Signale le : 21 octobre 2026.
- Environnement constate : site temoin local.

La base locale contient deux abonnés, deux segments et une campagne historiques,
mais aucune fiche `Contact` associée : les deux `audience_contacts.contact_id`
sont nuls. Ces données ne sont créées ni par les tests ni par le seeder actuel.
La cible reste : un `Contact` obligatoire pour toute personne connue du site,
et un enregistrement `Audience` facultatif pour le consentement marketing,
les segments et les campagnes. Prévoir une réparation idempotente des liens
existants avant d’utiliser ce jeu de données comme démonstration.
