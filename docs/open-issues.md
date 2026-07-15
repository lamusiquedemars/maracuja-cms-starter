# Bugs et corrections ouverts

## MCS-001 - Images absentes dans la galerie principale de la home

- Statut : ouvert.
- Signale le : 15 juillet 2026.
- Environnement constate : `http://maracuja-cms-starter.test` sous Herd.

Les images de la galerie principale de la page d'accueil ne s'affichent pas,
alors que des fichiers de `public/storage` sont accessibles directement.
Verifier les enregistrements de galerie, les chemins stockes, le disque Laravel
et le rendu de la home.

## MCS-002 - Remplacer SQLite par MySQL dans le Starter

- Statut : correction demandee.
- Signale le : 15 juillet 2026.

Le Starter ne doit plus utiliser SQLite. Faire de MySQL la base locale et la
base documentee par defaut, adapter l'installation, les exemples de
configuration, les tests et la procedure de migration. Eviter un parcours ou
SQLite doit d'abord etre gere puis remplace par MySQL.
