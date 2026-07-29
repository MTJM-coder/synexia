# Module Identity — finalisation

Ce lot clôt les deux derniers points ouverts listés dans `SYNEXIA_BRIEFING.md` (section 6).

## Ce qui est livré

**Étape B — câblage cross-module**
- `Listeners/CreateOwnerEmployeeOnShopCreated.php` : écoute `Modules\Marketplace\Events\ShopCreated`, crée automatiquement le `ShopEmployee` "Owner" du propriétaire. Si le seeder de rôles n'a pas encore tourné, ne bloque pas la création de boutique — journalise l'erreur via `report()` à la place (comportement défensif volontaire).
- `IdentityServiceProvider.php` mis à jour : enregistre ce Listener sur l'event de Marketplace.
- `Tests/Feature/ShopOwnerAutoAssignmentTest.php` : 2 cas — l'employé Owner est bien créé, et il a bien toutes les permissions.

**Étape C — API (convention proposée)**
- Enveloppe JSON standard Laravel (`JsonResource` → `{"data": ...}`), pagination standard, erreurs de validation au format Laravel par défaut. Aucun format "maison" — cohérent avec le principe "pas de sur-ingénierie" du projet.
- `Http/Requests/` : `StoreShopEmployeeRequest`, `UpdateShopEmployeeRoleRequest`, `SetShopEmployeePermissionRequest`.
- `Http/Resources/ShopEmployeeResource.php`.
- `Http/Controllers/ShopEmployeeController.php` : `index`, `show`, `store` (inviter), `updateRole`, `setPermission` — chaque action vérifie les droits via `ShopEmployeePolicy` avant d'agir.
- `routes.php` rempli : `/api/v1/shops/{shop}/employees/...`.

## ⚠️ Point non résolu, à noter

`shop_employees` n'a pas de colonne `uuid` dans le schéma d'origine —
contrairement à la convention du projet ("jamais l'id interne exposé"). J'ai
exposé `id` dans `ShopEmployeeResource` faute de mieux, avec un commentaire
dans le code. Si ça devient un problème réel (URL partageable, etc.), il
faudra une migration additive pour ajouter la colonne.

## Installation chez toi

1. Copier `Modules/Identity/*` par-dessus l'existant (écrase `IdentityServiceProvider.php` et `routes.php`, ajoute le reste).
2. `composer dump-autoload`
3. Lancer les tests :
   ```powershell
   php artisan test --filter=Identity
   ```
   Je m'attends à ce que tout passe (`PermissionResolverTest` déjà confirmé + les 2 nouveaux cas de `ShopOwnerAutoAssignmentTest`), mais je n'ai pas pu l'exécuter réellement ici — même limite que d'habitude (pas d'accès Packagist dans mon bac à sable). Dis-moi ce que ça donne.
4. Pour tester l'API réellement, il faudra un token Sanctum valide (`auth:sanctum` sur les routes) — pas encore de endpoint de login construit à ce stade du projet.

## Statut d'Identity après ce lot

Les 4 points de la section 6 du briefing sont traités :
- [x] Étape A (vérifications) — dépend de toi, déjà confirmé précédemment
- [x] Étape B (Listener ShopCreated)
- [x] Étape C (conventions API + Http/Controllers)
- [ ] Étape D (supprimer `app/Models/User.php` orphelin) — toujours à faire manuellement, une ligne

Identity peut être considéré **fonctionnellement complet** pour cette phase du projet.
