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
