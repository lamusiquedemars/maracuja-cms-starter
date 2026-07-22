# État initial de l'audit Media

Ce rapport décrit le starter avant la migration vers le contrat de stockage
Maracuja. Il sert de référence à la phase d'assainissement et ne constitue pas
un état acceptable en production.

Commande utilisée:

```bash
php artisan maracuja:media:audit
```

## Inventaire initial

```txt
Fichiers physiques: 53
Poids total: 53,7 Mo
Groupes de doublons par SHA-256: 17
Références détectées dans la base locale: 0
```

Répartition des anomalies de fichiers avant ajout du contrôle des éditeurs
riches:

```txt
22 fichiers dans storage/app/public
6 médias publics probables dans storage/app/private
22 fichiers publics hors de public/storage/media
8 configurations FileUpload vers des dossiers métier statiques
```

L'audit renforcé détecte également le dossier dynamique des galeries et chaque
`RichEditor` qui utilise encore les pièces jointes natives de Filament. Le
nombre exact d'anomalies est donc destiné à diminuer au fil des phases et peut
augmenter lorsqu'un nouveau contrôle est ajouté.

## Observations

- La majorité des fichiers de `storage/app/public` sont des copies exactes de
  fichiers présents dans `public/storage`.
- Plusieurs images existent simultanément dans les stockages public, ancien
  public et privé.
- Des images ajoutées par les éditeurs riches sont stockées directement à la
  racine de `public/storage`.
- Les imports CSV Audience présents dans `storage/app/private/imports` sont
  des fichiers privés légitimes.
- La base locale auditée ne contient actuellement aucune référence média; la
  commande doit être rejouée sur chaque base de site avant sa migration.

## Critère de fin

Après déploiement complet du module et migration, la commande doit retourner:

```txt
Anomalies: 0
```

Tous les fichiers publics administrables devront alors être catalogués et
stockés exclusivement sous `public/storage/media`.

Le compteur de groupes de doublons reste informatif : il peut inclure des
imports privés ou des fichiers transitoires `livewire-tmp`. Une fin de migration
exige l’absence de copie publique héritée, mais pas la suppression arbitraire
de fichiers privés légitimes.

## État final du starter

Le 22 juillet 2026, après application et nettoyage du manifeste privé
`starter-phase8-plan.json`, l’audit du starter retourne zéro anomalie. Aucun
média public administrable ne subsiste dans un ancien dossier métier.
