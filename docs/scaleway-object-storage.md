# Médias avec Scaleway Object Storage

Scaleway Object Storage appartient au projet **Cremona**. Il est réservé aux
objets pour lesquels le filesystem du site n'est pas suffisant : documents
métier privés, contrats, originaux sensibles, volumes importants ou besoins
de conservation particuliers. Il n'est pas le stockage par défaut de la
Médiathèque Maracuja.

## Choisir le bon stockage

| Cas | Stockage cible |
| --- | --- |
| Image de galerie, image éditoriale, petit PDF public | filesystem du site Maracuja |
| Contrat, certificat, dossier d'instrument, export métier | bucket privé Cremona |
| Média appartenant à un instrument ou archet Cremona | Cremona, avec projection publique contrôlée si nécessaire |
| Vidéo lourde ou conservation longue | décision dédiée : Scaleway ou fournisseur vidéo |

Un site Maracuja ne reçoit une clé Scaleway que pour un besoin explicite. Les
médias éditoriaux ordinaires restent sur son propre hébergement.

## Buckets Cremona

Lorsqu'ils sont nécessaires, les premiers buckets de production sont :

- `cremona-private-prod` : contrats, originaux et objets métier sans accès
  public ;
- `cremona-public-prod` : uniquement les rendus métier explicitement publiés.

Une identité IAM est créée par application avec les droits minimaux requis.
Une application de site ne reçoit jamais la clé générale de Cremona.

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
SCW_MEDIA_PUBLIC_BUCKET=cremona-public-prod
SCW_MEDIA_ORIGINALS_BUCKET=cremona-private-prod
SCW_MEDIA_PUBLIC_URL=https://media.cremona.example
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

## Cas exceptionnel : un site utilise Scaleway

Ne basculer un site existant qu'après décision explicite. L'ordre est :

1. sauvegarde base avec `maracuja:db:backup` ;
2. audit lecture seule `maracuja:media:audit` ;
3. création et revue du manifeste `maracuja:media:migrate` ;
4. activation des deux disques Scaleway ;
5. application du manifeste sans nettoyage ;
6. contrôles public, administration et email ;
7. nettoyage explicite, puis nouvel audit à zéro anomalie.

Le manifeste, la sauvegarde et le journal de vérification restent hors Git.
