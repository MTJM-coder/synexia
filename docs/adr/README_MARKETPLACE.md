# Module Marketplace — boutiques, abonnements, commissions

Troisième module DDD, construit avec la même grille de lecture. Deux
invariants réels ici (contre un seul pour Identity/Inventory) :

1. **Les limites de plan** (`PlanLimits` + `PlanLimitCheckerContract`) — combien de produits/employés/entrepôts une boutique peut créer selon son abonnement.
2. **Le calcul de commission** (`CommissionRate` + `CommissionCalculatorContract`) — quel taux s'applique, avec une priorité stricte : règle boutique+catégorie > règle boutique seule > règle catégorie globale > taux du plan.

## Ce qui débloque Identity

`Shop` + `ShopFactory` existent maintenant → `ShopEmployeeFactory` et
`PermissionResolverTest` (écrits en attente dans Identity) devraient
fonctionner. À vérifier chez toi une fois ce module en place :

```powershell
php artisan test --filter=PermissionResolverTest
```

## Dépendance externe

`Shop::owner()` et `ShopFactory` référencent `Modules\Identity\Models\User`
— déjà construit, donc pas de blocage cette fois (contrairement à quand
Identity avait été livré avant Marketplace).

## Ce qui n'est PAS encore câblé

`ShopCreated` est émis par `CreateShopAction`, mais **aucun Listener ne
l'écoute encore côté Identity** pour créer automatiquement le `ShopEmployee`
"Owner" du propriétaire. C'est noté dans le docblock de l'event. À faire
quand on reviendra sur Identity : un Listener `CreateOwnerEmployeeOnShopCreated`
dans `Modules\Identity\Listeners`, qui appelle `CreateShopEmployeeAction`.

## Installation chez toi

1. Copier `Modules/Marketplace/*` par-dessus le squelette existant (mêmes précautions que pour Identity : les migrations sont déjà en place depuis le zip précédent, ne pas les écraser par erreur).
2. Ajouter `Modules\Marketplace\MarketplaceServiceProvider::class` dans `bootstrap/providers.php`.
3. Enregistrer le seeder dans `DatabaseSeeder.php` :
   ```php
   $this->call([
       \Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder::class,
       \Modules\Marketplace\Database\Seeders\SubscriptionPlansSeeder::class,
   ]);
   ```
4. `composer dump-autoload` puis `php artisan db:seed` (le seeder est `firstOrCreate`, donc rejouable sans dupliquer les 3 plans).
5. Ajouter le testsuite `Modules` dans `phpunit.xml` si pas déjà fait pour Identity — les tests de ce module utilisent le même mécanisme.

## Limite honnête

Syntaxe validée (27 fichiers, 0 erreur) — je n'ai pas pu exécuter
`CommissionCalculatorTest` ni confirmer que `PermissionResolverTest`
(Identity) passe désormais, toujours pour la même raison (pas d'accès à
Packagist dans mon bac à sable). À toi de lancer `php artisan test` et de me
dire ce que ça donne.

## Prochaine étape suggérée

Câbler le Listener manquant côté Identity (`ShopCreated` → création
automatique de l'employé Owner), ou avancer sur **Catalog**/**Sales**
maintenant que Shop existe.
