# Module Identity — portage dans la structure définitive

## Ce qui a changé par rapport à la première version

- `HasFactory` ajouté à `Role`, `Permission`, `ShopEmployee`, `ShopEmployeePermission` (manquait pour que les Factories fonctionnent).
- `IdentityServiceProvider` fusionné : garde les bindings/event listeners d'origine, ajoute `loadMigrationsFrom()` et `Factory::guessFactoryNamesUsing()` du générateur.
- Nouveau : `Database/Factories/` (5 factories), `Database/Seeders/RolesAndPermissionsSeeder.php`, `Tests/Unit/PermissionResolverTest.php`.
- `Http/Controllers`, `Http/Requests`, `Http/Resources` volontairement laissés vides (juste les `.gitkeep` du générateur) — voir plus bas.

## ⚠️ Dépendance externe bloquante : le module Marketplace n'existe pas encore

`ShopEmployeeFactory`, `PermissionResolverTest` et le Seeder dépendent tous, directement ou indirectement, de `Modules\Marketplace\Models\Shop` **avec sa propre Factory**. Tant que Marketplace n'a que son squelette vide (créé via `make:module Marketplace` mais pas encore de code), :

- Le Seeder fonctionnera (il ne touche pas à `Shop`).
- `ShopEmployeeFactory` et donc `PermissionResolverTest` **échoueront** à l'exécution avec une erreur du type `Class "Modules\Marketplace\Models\Shop" not found` ou `Call to undefined method Shop::factory()`.

Ce n'est pas un bug à corriger maintenant — c'est attendu, le test est écrit par avance pour le jour où Marketplace existera. Je te le signale pour que tu ne perdes pas de temps à chercher une erreur côté Identity qui n'en est pas une.

## Étapes de configuration chez toi

1. **Enregistrer le Seeder** — dans `apps/api/database/seeders/DatabaseSeeder.php` :
   ```php
   public function run(): void
   {
       $this->call(\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder::class);
   }
   ```
   Puis (une fois Marketplace prêt pour les tests, mais le seeder marche déjà maintenant) :
   ```powershell
   php artisan db:seed
   ```

2. **`phpunit.xml`** — Laravel ne scanne par défaut que `tests/`, pas `Modules/*/Tests/`. Ajoute ce testsuite dans `apps/api/phpunit.xml` (à côté des `<testsuite>` existants `Unit` et `Feature`) :
   ```xml
   <testsuite name="Modules">
       <directory>Modules/*/Tests/Unit</directory>
       <directory>Modules/*/Tests/Feature</directory>
   </testsuite>
   ```

3. **`config/auth.php`** — pointer le guard vers notre modèle :
   ```php
   'providers' => [
       'users' => [
           'driver' => 'eloquent',
           'model' => Modules\Identity\Models\User::class,
       ],
   ],
   ```

## Limite honnête de cette livraison

Je n'ai validé que la **syntaxe PHP** (25 fichiers, 0 erreur) — je n'ai pas pu exécuter le test `PermissionResolverTest` pour de vrai dans mon bac à sable (pas d'accès à Packagist pour installer un vrai Laravel ici, comme déjà expliqué pour les migrations). Il faudra le lancer chez toi une fois Marketplace scaffoldé, et me dire ce qu'il donne — même logique qu'on a suivie pour les migrations.

## Prochaine étape suggérée

Scaffolder **Marketplace** (Shop + factory en premier, pour débloquer `ShopEmployeeFactory` et le test), ou continuer directement sur **Sales**/**Inventory** si tu préfères avancer sur la logique métier d'abord et revenir sur les tests plus tard.
