# WandaMarket V2 — Schéma de base de données

**Moteur** : postgre 18· **Tables** : 66 · **Clés étrangères** : 106
Schéma validé par import réel sur un serveur MariaDB (création + insertions test réussies).

---

## 1. Principes de conception

- **Multi-tenant par colonne** : chaque table métier porte un `shop_id`. Pas de base séparée par boutique (trop lourd à maintenir à grande échelle) — l'isolation se fait au niveau applicatif (scope automatique dans chaque requête Eloquent via un Global Scope Laravel sur `shop_id`).
- **Un seul compte `users`** pour tous les rôles (super admin, vendeur, employé, livreur, client). Le rôle et les permissions d'un utilisateur *dans une boutique donnée* sont définis par la table pivot `shop_employees`, pas par une colonne `role` sur `users`. Un même utilisateur peut donc être client d'une boutique et employé d'une autre.
- **UUID public + id interne** : chaque table exposée via l'API a un `id` BIGINT auto-increment (perf des jointures/index) **et** un `uuid` (jamais l'id interne n'est exposé dans les URLs de l'API `/api/v1/...`).
- **Snapshots sur les commandes** : `order_items` fige `product_name_snapshot`, `variant_label_snapshot`, `sku_snapshot`, `unit_price`. Si un produit est renommé ou son prix change après coup, l'historique des commandes reste exact.
- **Stock = source de vérité par mouvement** : `stocks` donne la quantité *actuelle* (rapide à lire), mais chaque changement passe aussi par `stock_movements` (journal append-only, jamais modifié ni supprimé) — traçabilité totale exigée par le cahier des charges (entrées, sorties, transferts, retours, pertes, casses, corrections, inventaires).
- **Soft deletes** (`deleted_at`) sur les tables où l'historique compte (users, shops, products, orders indirectement via statut, warehouses, suppliers…) pour ne jamais perdre l'historique commercial/comptable.
- **JSON pour le variable, colonnes pour ce qui est interrogé/filtré** : ex. `shop_settings.opening_hours` en JSON (structure libre), mais `stocks.quantity` et `orders.status` restent des colonnes indexables.

---

## 2. Organisation des fichiers SQL

| Fichier | Contenu |
|---|---|
| `01_platform_users.sql` | Plans d'abonnement, utilisateurs, rôles/permissions, boutiques, paramètres boutique, employés, journal d'activité |
| `02_catalog_suppliers.sql` | Catégories, marques, produits, variantes, attributs (couleur/taille), fournisseurs, commandes fournisseurs |
| `03_inventory.sql` | Entrepôts, stocks, mouvements de stock, transferts, inventaires physiques, alertes |
| `04_orders_payments_delivery.sql` | Clients par boutique, adresses, panier, commandes, paiements, remboursements, livraisons, coursiers |
| `05_marketing_messaging_accounting.sql` | Coupons, promotions, flash sales, produits sponsorisés, campagnes, messagerie, avis, notifications, comptabilité simplifiée |
| `wandamarket_v2_schema.sql` | **Fichier unique fusionné** — c'est celui à utiliser pour l'import ou comme base des migrations Laravel |

---

## 3. Tour des modules

### Plateforme & utilisateurs
`subscription_plans → shops → shop_subscriptions` gère le cycle d'abonnement SaaS de chaque boutique (essentiel puisque le produit est vendu par abonnement). `commission_rules` permet une commission par défaut **et** des surcharges par boutique ou par catégorie.

`roles` / `permissions` / `role_permissions` forment un RBAC générique. Comme demandé dans le cahier des charges ("permissions entièrement personnalisables"), `shop_employee_permissions` permet d'accorder ou retirer une permission précise à un employé donné, en plus de celles héritées de son rôle.

### Catalogue produits
Le point le plus délicat du cahier des charges est la gestion des variantes (T-shirt × couleur × taille). Le modèle retenu :

```
products (produit "parent", has_variants = true/false)
  └─ product_variants (1 ligne par combinaison réelle : prix, stock, SKU, code-barres propres)
        └─ product_variant_attribute_values (liaison variante ↔ valeurs d'attributs)
attribute_types (ex: "Couleur", "Taille" — définis par boutique)
  └─ attribute_values (ex: "Noir", "M")
```

Si un produit n'a pas de variantes, `has_variants = false` et `products.base_price`/`sku` suffisent — mais en interne, on crée quand même **une** ligne `product_variants` "par défaut" pour que tout le reste du système (stock, commandes, panier) référence toujours `product_variant_id` de façon uniforme, sans branche conditionnelle partout dans le code.

### Stock
Modèle à deux niveaux : `stocks` (état courant, rapide) + `stock_movements` (journal complet, append-only). Tous les cas du cahier des charges sont couverts par l'enum `type` de `stock_movements` : entrée, sortie, transfert (in/out), retour, inventaire, perte, casse, correction. `stock_transfers` et `stock_inventories` structurent les opérations qui génèrent plusieurs mouvements liés. `stock_alerts` matérialise les alertes automatiques (stock bas, critique, rupture, expiration).

### Commandes
Cycle complet couvert : `carts/cart_items → orders/order_items → order_status_histories`. Le statut de commande (`orders.status`) suit précisément le flux du cahier des charges (panier → validation → paiement → préparation → expédition → livraison → terminée), avec `cancelled/refunded/failed` en plus pour la robustesse métier.

### Paiements & livraisons
`payment_methods` est configurable par boutique (Orange Money, MTN MoMo, carte, paiement à la livraison…) et extensible sans migration — ajouter une passerelle = une ligne + une config JSON. `deliveries` porte le tracking (statuts, preuve de livraison photo/signature) et `delivery_tracking_points` permet un suivi GPS en temps réel du livreur.

### Comptabilité simplifiée
Plutôt qu'un grand-livre comptable complet (hors périmètre "simplifié"), le modèle retenu est :
- `daily_shop_summaries` : agrégats précalculés par jour (CA brut, remises, commissions, remboursements, coût des marchandises, profit estimé) — alimentés par un job planifié Laravel, pour que les dashboards restent rapides même avec des milliers de commandes.
- `shop_expenses` : dépenses saisies manuellement (loyer, salaires…) pour compléter le calcul de bénéfice réel.

### Marketing, messagerie, avis
Modules classiques mais complets : coupons avec limites d'usage globales/par client, promotions par produit/catégorie, flash sales avec quantité limitée, produits sponsorisés avec compteur d'impressions/clics, campagnes multi-canal (email/SMS/WhatsApp/push) avec suivi par destinataire. La messagerie (`conversations`/`messages`) est pensée pour être diffusée en temps réel via Laravel Reverb (WebSockets) — `is_read` et `last_read_at` par participant permettent les indicateurs de non-lu classiques.

---

## 4. Ce que ce schéma ne couvre pas (volontairement)

- **Les migrations Laravel elles-mêmes** : ce fichier SQL est la référence à partir de laquelle générer les migrations (`php artisan make:migration`), pas un remplacement. Je peux les générer ensuite si utile.
- **Les tables techniques Laravel** (`sessions`, `jobs`, `failed_jobs`, `personal_access_tokens` de Sanctum, `cache`) : elles sont créées automatiquement par les packages/commandes Laravel standard, inutile de les dupliquer ici.
- **Un moteur de recherche dédié** (Elasticsearch/Meilisearch) : un index `FULLTEXT` MySQL basique est prévu sur `products` pour démarrer, mais à grande échelle un moteur de recherche externe sera préférable.

---

## 5. Prochaines étapes suggérées

1. Générer les migrations Laravel + modèles Eloquent (relations, casts, scopes multi-tenant) à partir de ce schéma.
2. Définir les Form Requests et Policies pour chaque module (sécurité par rôle).
3. Concevoir les endpoints `/api/v1/...` module par module (je peux faire une proposition d'architecture API si tu veux avancer sur ce point ensuite).
