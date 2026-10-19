# Médias avec Scaleway Object Storage

La cible Maracuja sépare systématiquement les contenus publics et les
originaux : l'application ne dépend donc pas du filesystem cPanel pour ses
médias administrables.

## Deux buckets par site et par environnement

Pour un site `exemple`, créer dans le projet Scaleway dédié :

- `maracuja-exemple-public-prod` : variantes web, PDF publics et vidéos ;
- `maracuja-exemple-originals-prod` : originaux image, sans accès public.

Un site de préproduction ou de développement a ses propres buckets. Un client
ne partage jamais un bucket avec un autre client. L'application obtient une
clé IAM de service limitée uniquement à ces deux buckets : lecture, écriture
et suppression d'objets, sans droit d'administration sur Scaleway.

## Paramètres d'environnement

Après création, renseigner les secrets dans le `.env` de l'environnement, pas
dans Git :

```dotenv
MARACUJA_MEDIA_PUBLIC_DISK=scaleway_media_public
MARACUJA_MEDIA_ORIGINALS_DISK=scaleway_media_originals
MARACUJA_MEDIA_PROCESSING_DISK=local
SCW_OBJECT_STORAGE_REGION=fr-par
SCW_OBJECT_STORAGE_ENDPOINT=https://s3.fr-par.scw.cloud
SCW_OBJECT_STORAGE_PATH_STYLE=false
SCW_MEDIA_PUBLIC_BUCKET=maracuja-exemple-public-prod
SCW_MEDIA_ORIGINALS_BUCKET=maracuja-exemple-originals-prod
SCW_MEDIA_PUBLIC_URL=https://media.exemple.fr
```

`SCW_MEDIA_PUBLIC_URL` reste vide tant que le domaine média/Edge Services n'a
pas été validé. Dans ce cas, l'URL native du bucket est utilisée. Les originaux
ne reçoivent jamais d'URL publique.

## Préparation et garanties

L'import est traité localement dans `storage/app/private`, puis :

1. l'original est envoyé dans le bucket privé ;
2. les variantes WebP et les vidéos sont déposées dans le bucket public ;
3. seule la référence du média est enregistrée dans la base ;
4. le fichier temporaire local est supprimé ;
5. une suppression de média refuse tout média encore utilisé, puis supprime
   rendus, vignette et original.

Avant l'activation, vérifier : import image, variantes, affichage public,
original inaccessible par URL, import vidéo/vignette, réutilisation dans une
galerie et refus de suppression lorsqu'il est utilisé.

## Migration des fichiers historiques

Ne jamais basculer un site existant directement sur Scaleway. L'ordre est :

1. sauvegarde base avec `maracuja:db:backup` ;
2. audit lecture seule `maracuja:media:audit` ;
3. création et revue du manifeste `maracuja:media:migrate` ;
4. activation des deux disques Scaleway ;
5. application du manifeste sans nettoyage ;
6. contrôles public, administration et email ;
7. nettoyage explicite, puis nouvel audit à zéro anomalie.

Le manifeste, la sauvegarde et le journal de vérification restent hors Git.
