# Starter de démonstration — LWS

## Cible vérifiée

- URL : https://starter.maracujadigital.fr
- Racine SFTP : `/htdocs/starter.maracujadigital.fr`.
- Connexion existante : profil `LWS Maracuja`, contexte `maracujadigital`, dans
  le fichier local non versionné `/Users/ivocorreiademelo/Sites/.vscode/sftp.json`.
  Ne jamais recopier ses identifiants dans le dépôt ou les sorties de commande.
- Cet accès accepte SFTP uniquement, pas les commandes SSH.
- Journal Laravel : `storage/logs/laravel.log` sous cette racine.

## Correctif ciblé

1. Tester localement et comparer les fichiers concernés avec la production.
2. Commiter uniquement les fichiers du correctif, pousser et vérifier le SHA distant.
3. Transférer uniquement les fichiers runtime concernés depuis le commit poussé.
   Conserver une copie privée temporaire des fichiers remplacés pour un retour arrière.
4. Vérifier le contenu distant après transfert, puis la page et le comportement touchés.
5. Contrôler les nouvelles erreurs du journal.

Ne pas remplacer `.env`, `vendor`, les médias, la base de données ou les fichiers
distants sans rapport. Un changement limité à une méthode de modèle ne nécessite
ni migration, ni compilation des assets, ni invalidation des caches de routes/configuration.
Pour un déploiement plus large nécessitant Artisan, utiliser le terminal de
l'hébergement : ne pas créer de point d'entrée HTTP d'exécution de commandes.

## Incident du 22 septembre 2026

Le formulaire `/admin/login` répondait en 200, mais sa soumission provoquait
`This password does not use the Bcrypt algorithm` pour un hash importé `$2b$`.
`User::getAuthPassword()` présente désormais les hashes `$2b$` bien formés sous
leur variante équivalente `$2y$`, sans modifier les données stockées et sans
désactiver la vérification d'algorithme Laravel. Les deux variantes sont
[équivalentes selon crypt_blowfish](https://www.openwall.com/crypt/).
