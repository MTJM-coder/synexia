# Migrations Laravel — WandaMarket V2

66 fichiers de migration, un par table, générés directement à partir du schéma SQL déjà validé (`wandamarket_v2_schema.sql`).

## Installation

1. Copier tous les fichiers `2024_01_01_XXXXXX_create_..._table.php` dans le dossier `database/migrations/` de ton projet Laravel.
2. Vérifier que `.env` pointe vers ta base MySQL/MariaDB.
3. Lancer :
   ```
   php artisan migrate
   ```

L'ordre d'exécution (numéros de séquence) respecte les dépendances de clés étrangères : chaque table n'est créée qu'après celles qu'elle référence (ex: `shops` avant `products`, `products` avant `product_variants`, `warehouses` avant `stocks`, etc.). La seule auto-référence (`categories.parent_id → categories.id`) est gérée nativement par Laravel dans le même `Schema::create`.

## Ce qui a été vérifié

- **Syntaxe PHP** : les 66 fichiers passent `php -l` sans erreur.
- **Ordre des dépendances** : script de vérification automatique — aucune contrainte `foreignId()->constrained()` ne référence une table créée plus tard dans la séquence.
- **Fidélité au schéma** : chaque migration a été générée par un script qui parse directement les `CREATE TABLE` du fichier SQL déjà testé sur MariaDB (66 tables, 106 FK, insertions réelles réussies) — colonnes, types, ENUM, valeurs par défaut, nullable, unicités composites et index fulltext sont repris fidèlement.

## Limite de cet environnement

Je n'ai pas pu exécuter `php artisan migrate` avec un vrai framework Laravel ici : l'environnement sandbox n'a pas accès à `packagist.org` (nécessaire pour `composer create-project laravel/laravel`), donc impossible d'installer Illuminate/Database pour un test de bout en bout. La validation s'est donc arrêtée à la syntaxe PHP + la cohérence de l'ordre des migrations + la fidélité au DDL déjà validé en base réelle. Si tu peux lancer `php artisan migrate` de ton côté (ou me donner accès à un projet Laravel existant), je peux vérifier l'exécution réelle avec toi.

## Détails techniques des conventions utilisées

- `$table->id()` pour les clés primaires auto-incrémentées.
- `$table->foreignId('xxx_id')->constrained('table')` pour les FK avec contrainte (`->cascadeOnDelete()` ou `->nullOnDelete()` selon le schéma d'origine).
- `$table->unsignedBigInteger('xxx_id')` pour les colonnes de référence **sans** contrainte FK explicite dans le schéma d'origine (volontaire — ex: `orders.coupon_id`, pour éviter des dépendances circulaires entre modules).
- `$table->softDeletes()` pour toutes les tables avec `deleted_at`.
- `$table->timestamp('created_at')->useCurrent()` / `updated_at` avec `useCurrentOnUpdate()` pour reproduire les `DEFAULT CURRENT_TIMESTAMP` du SQL.
- Contrainte `CHECK` sur `reviews.rating` ajoutée via `DB::statement(...)` après la création de table (Laravel Blueprint ne supporte pas nativement les `CHECK` multi-versions).

## Prochaine étape suggérée

Générer les **modèles Eloquent** correspondants (relations `belongsTo`/`hasMany`/`belongsToMany`, casts, scopes multi-tenant `shop_id`) pour que le code applicatif s'appuie directement sur ces migrations.
