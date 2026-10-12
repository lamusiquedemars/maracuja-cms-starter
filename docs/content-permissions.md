# Permissions éditoriales

Les modules sont activés pour une installation entière par les variables
`MARACUJA_MODULE_*`. Les permissions éditoriales limitent ensuite les rubriques
visibles et accessibles pour un compte donné. Elles ne remplacent pas les modules.

## Comptes existants

Les rôles restent `admin`, `editor` et `viewer`.

- Un administrateur a accès à tout.
- Un éditeur ou lecteur dont `content_permissions` vaut `null` conserve le
  comportement historique du starter.
- Dès que `content_permissions` contient un tableau, celui-ci est restrictif :
  seules les rubriques explicitement listées sont accessibles. Un tableau vide
  ne donne accès à aucune rubrique administrable.

Les contrôles sont appliqués par les autorisations Laravel. Filament retire les
rubriques du menu et refuse également les URL directes.

## Créer un éditeur limité

```sh
php artisan maracuja:ensure-restricted-editor client@example.test \
  --name="Client" \
  --permission=notices \
  --permission=inquiries \
  --permission=contacts
```

La commande demande le mot de passe sans l’afficher. Elle crée le compte ou le
met à jour et remplace sa liste de capacités.

Capacités disponibles :

```txt
pages, notices, content_slots, news, articles, inquiries, contacts,
media, gallery, events, venues, appointments, conversations, audience
```

Pour les installations existantes, déployer le code et la migration
`2026_10_12_120000_add_content_permissions_to_users_table.php`, exécuter les
migrations selon la procédure de l’hébergement, puis créer ou mettre à jour les
comptes concernés. Répéter ce déploiement dans chaque projet client qui doit
utiliser des comptes éditoriaux restreints.
