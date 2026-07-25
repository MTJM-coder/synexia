-- ============================================================================
-- WANDAMARKET V2 - SCHEMA DE BASE DE DONNEES (MySQL 8+)
-- Fichier 1/6 : Plateforme, Boutiques, Utilisateurs, Roles & Permissions
-- ============================================================================
-- Convention : toutes les tables utilisent un id BIGINT UNSIGNED auto-increment
-- + un uuid public (pour ne jamais exposer les id internes via l'API)
-- Toutes les dates : created_at, updated_at, deleted_at (soft delete) en UTC
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- PLANS D'ABONNEMENT (niveau plateforme)
-- ----------------------------------------------------------------------------
CREATE TABLE subscription_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency VARCHAR(3) NOT NULL DEFAULT 'XAF',
    billing_period ENUM('monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
    max_products INT UNSIGNED NULL,              -- NULL = illimite
    max_employees INT UNSIGNED NULL,
    max_warehouses INT UNSIGNED NULL,
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0, -- % pris par la marketplace
    features JSON NULL,                           -- liste des fonctionnalites incluses
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- UTILISATEURS (table unique pour TOUS les roles : admin, vendeur, employe, livreur, client)
-- ----------------------------------------------------------------------------
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NULL UNIQUE,
    phone VARCHAR(30) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar_path VARCHAR(255) NULL,
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    two_factor_secret TEXT NULL,
    is_super_admin BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('active','suspended','banned','pending') NOT NULL DEFAULT 'pending',
    locale VARCHAR(10) NOT NULL DEFAULT 'fr',
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    fcm_token VARCHAR(255) NULL,                 -- pour notifications push Firebase
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_users_status (status),
    INDEX idx_users_email (email),
    INDEX idx_users_phone (phone)
) ENGINE=InnoDB;

-- Historique des connexions (audit)
CREATE TABLE login_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    device VARCHAR(100) NULL,
    location VARCHAR(150) NULL,
    status ENUM('success','failed') NOT NULL DEFAULT 'success',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_hist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_login_hist_user (user_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- ROLES & PERMISSIONS (systeme generique, style spatie/permission, mais custom)
-- ----------------------------------------------------------------------------
-- guard_scope : 'platform' (super_admin, admin_marketplace) ou 'shop' (owner, manager, employe, livreur)
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    guard_scope ENUM('platform','shop') NOT NULL,
    shop_id BIGINT UNSIGNED NULL,                 -- NULL = role systeme global, sinon role custom cree par une boutique
    is_system BOOLEAN NOT NULL DEFAULT TRUE,       -- roles predefinis non supprimables
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_slug_shop (slug, shop_id)
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,             -- ex: orders.manage, stock.view, cashier.use
    module VARCHAR(80) NOT NULL,                   -- ex: orders, stock, products, customers
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- BOUTIQUES
-- ----------------------------------------------------------------------------
CREATE TABLE shops (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    owner_id BIGINT UNSIGNED NOT NULL,             -- users.id (proprietaire)
    subscription_plan_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    slogan VARCHAR(255) NULL,
    description TEXT NULL,
    logo_path VARCHAR(255) NULL,
    banner_path VARCHAR(255) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    website VARCHAR(255) NULL,
    social_links JSON NULL,                        -- {facebook, instagram, tiktok, ...}
    address_line VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    region VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Cameroun',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    status ENUM('pending','active','suspended','closed') NOT NULL DEFAULT 'pending',
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_shop_owner FOREIGN KEY (owner_id) REFERENCES users(id),
    CONSTRAINT fk_shop_plan FOREIGN KEY (subscription_plan_id) REFERENCES subscription_plans(id),
    INDEX idx_shops_status (status),
    INDEX idx_shops_owner (owner_id)
) ENGINE=InnoDB;

-- Abonnement actif / historique de facturation d'une boutique
CREATE TABLE shop_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    subscription_plan_id BIGINT UNSIGNED NOT NULL,
    starts_at DATE NOT NULL,
    ends_at DATE NOT NULL,
    status ENUM('active','expired','cancelled','trial') NOT NULL DEFAULT 'active',
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_shopsub_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_shopsub_plan FOREIGN KEY (subscription_plan_id) REFERENCES subscription_plans(id),
    INDEX idx_shopsub_shop (shop_id),
    INDEX idx_shopsub_status (status)
) ENGINE=InnoDB;

-- Regles de commission (peut surcharger celle du plan, par categorie par ex.)
CREATE TABLE commission_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NULL,                  -- NULL = regle globale par defaut
    category_id BIGINT UNSIGNED NULL,               -- s'applique a une categorie precise
    rate DECIMAL(5,2) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comm_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Parametres detailles d'une boutique (cle/valeur pour rester extensible)
CREATE TABLE shop_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'XAF',
    language VARCHAR(10) NOT NULL DEFAULT 'fr',
    timezone VARCHAR(60) NOT NULL DEFAULT 'Africa/Douala',
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 19.25,   -- TVA par defaut
    tax_inclusive BOOLEAN NOT NULL DEFAULT TRUE,
    opening_hours JSON NULL,                        -- {lundi:{open,close}, ...}
    allow_pickup BOOLEAN NOT NULL DEFAULT TRUE,
    allow_delivery BOOLEAN NOT NULL DEFAULT TRUE,
    delivery_radius_km DECIMAL(6,2) NULL,
    email_config JSON NULL,                         -- smtp custom eventuel
    sms_config JSON NULL,
    whatsapp_config JSON NULL,
    notification_preferences JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shop_settings (shop_id),
    CONSTRAINT fk_shopsettings_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- EMPLOYES DE BOUTIQUE (pivot user <-> shop avec role + permissions custom)
-- ----------------------------------------------------------------------------
CREATE TABLE shop_employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,               -- manager, employe, livreur...
    job_title VARCHAR(100) NULL,                    -- ex: "Caissier", "Preparateur"
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    hired_at DATE NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_shop_employee (shop_id, user_id),
    CONSTRAINT fk_se_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_se_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_se_role FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_se_shop (shop_id)
) ENGINE=InnoDB;

-- Permissions individuelles qui surchargent celles du role (grant/deny ponctuel)
CREATE TABLE shop_employee_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_employee_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    is_granted BOOLEAN NOT NULL DEFAULT TRUE,        -- TRUE=ajoute, FALSE=retire explicitement
    CONSTRAINT fk_sep_employee FOREIGN KEY (shop_employee_id) REFERENCES shop_employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_sep_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_sep (shop_employee_id, permission_id)
) ENGINE=InnoDB;

-- Journal d'activite (audit trail generique, polymorphique)
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,                    -- ex: "order.status_changed"
    subject_type VARCHAR(100) NULL,                  -- ex: "Order", "Product"
    subject_id BIGINT UNSIGNED NULL,
    description VARCHAR(500) NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_shop (shop_id),
    INDEX idx_activity_user (user_id),
    INDEX idx_activity_subject (subject_type, subject_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================================
-- WANDAMARKET V2 - Fichier 2/6 : Catalogue produits & Fournisseurs
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- CATEGORIES (globales, gerees par le Super Admin + sous-categories par boutique)
-- ----------------------------------------------------------------------------
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NULL,                   -- NULL = categorie globale marketplace
    parent_id BIGINT UNSIGNED NULL,                 -- sous-categorie
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    icon_path VARCHAR(255) NULL,
    image_path VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_category_slug_shop (slug, shop_id),
    CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_cat_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    INDEX idx_cat_parent (parent_id)
) ENGINE=InnoDB;

CREATE TABLE brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NULL,                   -- une marque peut etre globale ou propre a une boutique
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    logo_path VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_brand_slug_shop (slug, shop_id),
    CONSTRAINT fk_brand_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- FOURNISSEURS
-- ----------------------------------------------------------------------------
CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    contact_name VARCHAR(150) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    country VARCHAR(100) NULL,
    payment_terms VARCHAR(255) NULL,                -- ex: "30 jours net"
    notes TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_supplier_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    INDEX idx_supplier_shop (shop_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- PRODUITS
-- ----------------------------------------------------------------------------
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    shop_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    subcategory_id BIGINT UNSIGNED NULL,
    brand_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NULL,               -- fournisseur principal
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(280) NOT NULL,
    description TEXT NULL,
    short_description VARCHAR(500) NULL,
    sku VARCHAR(100) NULL,                          -- SKU produit "parent" (si pas de variantes)
    barcode VARCHAR(100) NULL,
    qr_code_path VARCHAR(255) NULL,
    has_variants BOOLEAN NOT NULL DEFAULT FALSE,
    base_price DECIMAL(12,2) NOT NULL DEFAULT 0,    -- utilise si has_variants = FALSE
    compare_at_price DECIMAL(12,2) NULL,            -- prix barre / avant reduction
    cost_price DECIMAL(12,2) NULL,                  -- prix d'achat, pour calcul de marge
    tax_rate DECIMAL(5,2) NULL,                     -- surcharge la TVA de la boutique si besoin
    weight_grams INT UNSIGNED NULL,
    length_cm DECIMAL(8,2) NULL,
    width_cm DECIMAL(8,2) NULL,
    height_cm DECIMAL(8,2) NULL,
    status ENUM('draft','published','archived','out_of_stock') NOT NULL DEFAULT 'draft',
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    views_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    sold_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    average_rating DECIMAL(3,2) NOT NULL DEFAULT 0,
    reviews_count INT UNSIGNED NOT NULL DEFAULT 0,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_product_slug_shop (slug, shop_id),
    CONSTRAINT fk_product_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_product_subcategory FOREIGN KEY (subcategory_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_product_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    CONSTRAINT fk_product_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    INDEX idx_product_shop_status (shop_id, status),
    INDEX idx_product_category (category_id),
    FULLTEXT INDEX ft_product_search (name, description)
) ENGINE=InnoDB;

-- Attributs de variantes (Couleur, Taille, Materiau...) - definis par boutique
CREATE TABLE attribute_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,                     -- "Couleur", "Taille"
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attrtype_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE attribute_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_type_id BIGINT UNSIGNED NOT NULL,
    value VARCHAR(100) NOT NULL,                    -- "Noir", "M", "XL"
    hex_color VARCHAR(7) NULL,                      -- utile si attribut = couleur
    CONSTRAINT fk_attrval_type FOREIGN KEY (attribute_type_id) REFERENCES attribute_types(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Variantes de produit (chaque combinaison Couleur x Taille = 1 ligne)
CREATE TABLE product_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    product_id BIGINT UNSIGNED NOT NULL,
    sku VARCHAR(100) NULL,
    barcode VARCHAR(100) NULL,
    price DECIMAL(12,2) NOT NULL,
    compare_at_price DECIMAL(12,2) NULL,
    cost_price DECIMAL(12,2) NULL,
    weight_grams INT UNSIGNED NULL,
    image_path VARCHAR(255) NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_variant_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_variant_product (product_id),
    INDEX idx_variant_sku (sku)
) ENGINE=InnoDB;

-- Association variante <-> valeurs d'attributs (ex: variante #12 = Noir + M)
CREATE TABLE product_variant_attribute_values (
    product_variant_id BIGINT UNSIGNED NOT NULL,
    attribute_value_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (product_variant_id, attribute_value_id),
    CONSTRAINT fk_pvav_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    CONSTRAINT fk_pvav_value FOREIGN KEY (attribute_value_id) REFERENCES attribute_values(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pimg_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_videos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pvid_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Commandes passees aux fournisseurs (approvisionnement)
CREATE TABLE supplier_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    shop_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,          -- entrepot destinataire (voir fichier 03)
    reference VARCHAR(50) NOT NULL,
    status ENUM('draft','ordered','partially_received','received','cancelled') NOT NULL DEFAULT 'draft',
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    expected_at DATE NULL,
    received_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,               -- users.id
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_supplier_order_ref (shop_id, reference),
    CONSTRAINT fk_so_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_so_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    INDEX idx_so_shop_status (shop_id, status)
) ENGINE=InnoDB;

CREATE TABLE supplier_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_order_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    quantity_ordered INT UNSIGNED NOT NULL,
    quantity_received INT UNSIGNED NOT NULL DEFAULT 0,
    unit_cost DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_soi_order FOREIGN KEY (supplier_order_id) REFERENCES supplier_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_soi_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

CREATE TABLE supplier_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_order_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method ENUM('cash','mobile_money','bank_transfer','check','other') NOT NULL DEFAULT 'cash',
    paid_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    reference VARCHAR(100) NULL,
    notes VARCHAR(255) NULL,
    CONSTRAINT fk_sp_order FOREIGN KEY (supplier_order_id) REFERENCES supplier_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================================
-- WANDAMARKET V2 - Fichier 3/6 : Entrepots & Gestion du Stock
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE warehouses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,                     -- "Entrepot Douala"
    code VARCHAR(30) NOT NULL,
    address VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_warehouse_code (shop_id, code),
    CONSTRAINT fk_warehouse_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Niveau de stock courant par variante et par entrepot (ligne mise a jour en temps reel)
CREATE TABLE stocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 0,                -- INT signe pour tolerer des corrections negatives temporaires
    reserved_quantity INT UNSIGNED NOT NULL DEFAULT 0, -- reserve pour commandes en cours
    min_stock INT UNSIGNED NOT NULL DEFAULT 0,      -- seuil stock minimum
    critical_stock INT UNSIGNED NOT NULL DEFAULT 0, -- seuil stock critique
    expiry_date DATE NULL,                          -- pour produits perissables
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_stock_warehouse_variant (warehouse_id, product_variant_id),
    CONSTRAINT fk_stock_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX idx_stock_variant (product_variant_id),
    INDEX idx_stock_low (warehouse_id, quantity)
) ENGINE=InnoDB;

-- Journal COMPLET de tous les mouvements de stock (source de verite, append-only)
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    shop_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    type ENUM(
        'in',            -- entree (reception fournisseur)
        'out',            -- sortie (vente)
        'transfer_in',     -- transfert entrant depuis un autre entrepot
        'transfer_out',    -- transfert sortant vers un autre entrepot
        'return',          -- retour client
        'inventory',       -- ajustement suite a inventaire physique
        'loss',            -- perte
        'breakage',        -- casse
        'correction'       -- correction manuelle
    ) NOT NULL,
    quantity INT NOT NULL,                          -- positif ou negatif selon le type
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    reference_type VARCHAR(100) NULL,               -- "Order", "SupplierOrder", "StockTransfer", "Inventory"
    reference_id BIGINT UNSIGNED NULL,
    related_warehouse_id BIGINT UNSIGNED NULL,      -- entrepot source/destination si transfert
    reason VARCHAR(255) NULL,
    performed_by BIGINT UNSIGNED NULL,              -- users.id
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sm_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_sm_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_sm_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    INDEX idx_sm_shop_date (shop_id, created_at),
    INDEX idx_sm_variant (product_variant_id),
    INDEX idx_sm_reference (reference_type, reference_id)
) ENGINE=InnoDB;

-- Transferts entre entrepots (regroupe plusieurs lignes de stock_movements lies)
CREATE TABLE stock_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    shop_id BIGINT UNSIGNED NOT NULL,
    from_warehouse_id BIGINT UNSIGNED NOT NULL,
    to_warehouse_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','in_transit','completed','cancelled') NOT NULL DEFAULT 'pending',
    requested_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    CONSTRAINT fk_st_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_st_from FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_st_to FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB;

CREATE TABLE stock_transfer_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_transfer_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    CONSTRAINT fk_sti_transfer FOREIGN KEY (stock_transfer_id) REFERENCES stock_transfers(id) ON DELETE CASCADE,
    CONSTRAINT fk_sti_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

-- Inventaires physiques (comptage periodique)
CREATE TABLE stock_inventories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    shop_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    status ENUM('in_progress','completed','cancelled') NOT NULL DEFAULT 'in_progress',
    started_by BIGINT UNSIGNED NULL,
    started_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    CONSTRAINT fk_si_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_si_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB;

CREATE TABLE stock_inventory_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_inventory_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    expected_quantity INT NOT NULL,
    counted_quantity INT NOT NULL,
    difference INT NOT NULL,                        -- counted - expected
    CONSTRAINT fk_sii_inventory FOREIGN KEY (stock_inventory_id) REFERENCES stock_inventories(id) ON DELETE CASCADE,
    CONSTRAINT fk_sii_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

-- Alertes automatiques (stock bas, rupture, expiration proche)
CREATE TABLE stock_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    type ENUM('low_stock','critical_stock','out_of_stock','expiring_soon','expired') NOT NULL,
    is_resolved BOOLEAN NOT NULL DEFAULT FALSE,
    triggered_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    CONSTRAINT fk_sa_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_sa_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_sa_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    INDEX idx_sa_unresolved (shop_id, is_resolved)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================================
-- WANDAMARKET V2 - Fichier 4/6 : Clients, Panier, Commandes, Paiements, Livraisons
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- CLIENTS (relation user <-> boutique : historique/fidelite propres a chaque boutique)
-- ----------------------------------------------------------------------------
CREATE TABLE customer_addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(50) NULL,                         -- "Maison", "Bureau"
    recipient_name VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    address_line VARCHAR(255) NOT NULL,
    city VARCHAR(100) NULL,
    region VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Cameroun',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_caddr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Profil client specifique a une boutique (points fidelite, notes internes, statut VIP...)
CREATE TABLE shop_customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    loyalty_points INT UNSIGNED NOT NULL DEFAULT 0,
    total_orders INT UNSIGNED NOT NULL DEFAULT 0,
    total_spent DECIMAL(14,2) NOT NULL DEFAULT 0,
    internal_notes TEXT NULL,                       -- notes visibles seulement par la boutique
    is_blocked BOOLEAN NOT NULL DEFAULT FALSE,
    first_order_at TIMESTAMP NULL,
    last_order_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shop_customer (shop_id, user_id),
    CONSTRAINT fk_sc_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_sc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favorite (user_id, product_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- PANIER
-- ----------------------------------------------------------------------------
CREATE TABLE carts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NULL,                   -- NULL si panier invite (session_token)
    shop_id BIGINT UNSIGNED NOT NULL,               -- un panier est scope a une boutique
    session_token VARCHAR(100) NULL,
    status ENUM('active','converted','abandoned') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    INDEX idx_cart_user (user_id),
    INDEX idx_cart_session (session_token)
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL,               -- capture du prix au moment de l'ajout
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_item (cart_id, product_variant_id),
    CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    CONSTRAINT fk_ci_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- COMMANDES
-- ----------------------------------------------------------------------------
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    order_number VARCHAR(30) NOT NULL,
    shop_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,           -- users.id
    warehouse_id BIGINT UNSIGNED NULL,               -- entrepot utilise pour honorer la commande
    delivery_address_id BIGINT UNSIGNED NULL,
    fulfillment_type ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
    status ENUM(
        'cart','pending_payment','confirmed','preparing',
        'shipped','out_for_delivery','delivered','completed',
        'cancelled','refunded','failed'
    ) NOT NULL DEFAULT 'pending_payment',
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    delivery_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
    commission_amount DECIMAL(14,2) NOT NULL DEFAULT 0, -- part de la marketplace
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    currency VARCHAR(3) NOT NULL DEFAULT 'XAF',
    coupon_id BIGINT UNSIGNED NULL,
    customer_note VARCHAR(500) NULL,
    internal_note VARCHAR(500) NULL,
    placed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancel_reason VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_order_number (shop_id, order_number),
    CONSTRAINT fk_order_shop FOREIGN KEY (shop_id) REFERENCES shops(id),
    CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_order_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_order_address FOREIGN KEY (delivery_address_id) REFERENCES customer_addresses(id),
    INDEX idx_order_shop_status (shop_id, status),
    INDEX idx_order_customer (customer_id),
    INDEX idx_order_placed (placed_at)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    product_name_snapshot VARCHAR(255) NOT NULL,     -- fige le nom au moment de l'achat
    variant_label_snapshot VARCHAR(150) NULL,        -- ex: "Noir / M"
    sku_snapshot VARCHAR(100) NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(14,2) NOT NULL,
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

-- Historique des statuts (chaque changement trace, avec qui l'a fait)
CREATE TABLE order_status_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(30) NULL,
    to_status VARCHAR(30) NOT NULL,
    changed_by BIGINT UNSIGNED NULL,                 -- users.id (NULL si automatique/systeme)
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_osh_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- PAIEMENTS
-- ----------------------------------------------------------------------------
CREATE TABLE payment_methods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NULL,                    -- NULL = disponible globalement, sinon active par boutique
    code VARCHAR(50) NOT NULL,                       -- 'orange_money','mtn_momo','card','cod'
    name VARCHAR(100) NOT NULL,
    provider VARCHAR(100) NULL,                      -- passerelle utilisee
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    config JSON NULL,                                -- cles API, marchand id, etc.
    UNIQUE KEY uq_payment_method (shop_id, code)
) ENGINE=InnoDB;

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    order_id BIGINT UNSIGNED NOT NULL,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'XAF',
    status ENUM('pending','processing','paid','failed','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
    provider_reference VARCHAR(150) NULL,            -- id de transaction cote passerelle
    payer_phone VARCHAR(30) NULL,
    paid_at TIMESTAMP NULL,
    failed_reason VARCHAR(255) NULL,
    raw_response JSON NULL,                          -- reponse brute de la passerelle (debug/audit)
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id),
    INDEX idx_payment_order (order_id),
    INDEX idx_payment_status (status)
) ENGINE=InnoDB;

CREATE TABLE refunds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    reason VARCHAR(255) NULL,
    status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
    processed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_refund_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- LIVRAISONS
-- ----------------------------------------------------------------------------
CREATE TABLE delivery_zones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,                      -- "Douala - Akwa"
    city VARCHAR(100) NULL,
    polygon JSON NULL,                                -- coordonnees geographiques de la zone
    base_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    fee_per_km DECIMAL(10,2) NOT NULL DEFAULT 0,
    estimated_time_minutes INT UNSIGNED NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_dz_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE couriers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,                -- users.id avec role Livreur
    vehicle_type ENUM('moto','velo','voiture','a_pied') NOT NULL DEFAULT 'moto',
    vehicle_plate VARCHAR(30) NULL,
    is_available BOOLEAN NOT NULL DEFAULT TRUE,
    current_latitude DECIMAL(10,7) NULL,
    current_longitude DECIMAL(10,7) NULL,
    rating DECIMAL(3,2) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_courier_user (shop_id, user_id),
    CONSTRAINT fk_courier_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_courier_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    order_id BIGINT UNSIGNED NOT NULL,
    courier_id BIGINT UNSIGNED NULL,
    delivery_zone_id BIGINT UNSIGNED NULL,
    status ENUM('pending','assigned','picked_up','in_transit','delivered','failed','returned') NOT NULL DEFAULT 'pending',
    fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    scheduled_at TIMESTAMP NULL,
    picked_up_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    proof_photo_path VARCHAR(255) NULL,              -- preuve de livraison (photo/signature)
    proof_signature_path VARCHAR(255) NULL,
    recipient_note VARCHAR(255) NULL,
    failure_reason VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_delivery_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_delivery_courier FOREIGN KEY (courier_id) REFERENCES couriers(id),
    CONSTRAINT fk_delivery_zone FOREIGN KEY (delivery_zone_id) REFERENCES delivery_zones(id),
    INDEX idx_delivery_status (status),
    INDEX idx_delivery_courier (courier_id)
) ENGINE=InnoDB;

-- Suivi GPS en temps reel (points successifs de la course)
CREATE TABLE delivery_tracking_points (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    delivery_id BIGINT UNSIGNED NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    recorded_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dtp_delivery FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE,
    INDEX idx_dtp_delivery (delivery_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================================
-- WANDAMARKET V2 - Fichier 5/6 : Marketing, Messagerie, Avis, Notifications, Comptabilite
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- MARKETING
-- ----------------------------------------------------------------------------
CREATE TABLE coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(50) NOT NULL,
    type ENUM('percentage','fixed_amount','free_delivery') NOT NULL,
    value DECIMAL(12,2) NOT NULL DEFAULT 0,
    min_order_amount DECIMAL(12,2) NULL,
    max_discount_amount DECIMAL(12,2) NULL,
    usage_limit INT UNSIGNED NULL,                   -- NULL = illimite
    usage_limit_per_customer INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_coupon_code (shop_id, code),
    CONSTRAINT fk_coupon_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE coupon_usages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coupon_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    discount_applied DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cu_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    CONSTRAINT fk_cu_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE promotions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    type ENUM('product_discount','category_discount','buy_x_get_y') NOT NULL,
    discount_percentage DECIMAL(5,2) NULL,
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_promo_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE promotion_products (
    promotion_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (promotion_id, product_id),
    CONSTRAINT fk_pp_promo FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE flash_sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fs_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE flash_sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    flash_sale_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    flash_price DECIMAL(12,2) NOT NULL,
    quantity_limit INT UNSIGNED NULL,
    quantity_sold INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_fsi_sale FOREIGN KEY (flash_sale_id) REFERENCES flash_sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_fsi_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

-- Produits sponsorises (mis en avant sur la marketplace, payant)
CREATE TABLE sponsored_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    impressions_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    clicks_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending','active','expired') NOT NULL DEFAULT 'pending',
    CONSTRAINT fk_sponsored_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_sponsored_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Campagnes marketing (Email / SMS / WhatsApp)
CREATE TABLE campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    channel ENUM('email','sms','whatsapp','push') NOT NULL,
    subject VARCHAR(255) NULL,                       -- utilise si channel=email
    content TEXT NOT NULL,
    target_segment ENUM('all_customers','vip','inactive','custom') NOT NULL DEFAULT 'all_customers',
    status ENUM('draft','scheduled','sending','sent','failed') NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    recipients_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE campaign_recipients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','sent','delivered','failed','opened','clicked') NOT NULL DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    CONSTRAINT fk_cr_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_cr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- MESSAGERIE (temps reel via Laravel Reverb / WebSockets)
-- ----------------------------------------------------------------------------
CREATE TABLE conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    type ENUM('customer_shop','shop_admin','support') NOT NULL,
    shop_id BIGINT UNSIGNED NULL,
    order_id BIGINT UNSIGNED NULL,                   -- conversation liee a une commande (optionnel)
    subject VARCHAR(255) NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_conv_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_conv_shop (shop_id)
) ENGINE=InnoDB;

CREATE TABLE conversation_participants (
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    last_read_at TIMESTAMP NULL,
    PRIMARY KEY (conversation_id, user_id),
    CONSTRAINT fk_cp_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_cp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    conversation_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    body TEXT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_type ENUM('image','document','audio','video') NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id),
    INDEX idx_msg_conv (conversation_id, created_at)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- AVIS
-- ----------------------------------------------------------------------------
CREATE TABLE reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NULL,              -- pour verifier achat confirme
    customer_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,                -- 1 a 5
    comment TEXT NULL,
    is_verified_purchase BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    shop_reply TEXT NULL,
    shop_replied_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5),
    INDEX idx_review_product (product_id, status)
) ENGINE=InnoDB;

CREATE TABLE review_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_id BIGINT UNSIGNED NOT NULL,
    reported_by BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rr_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    CONSTRAINT fk_rr_user FOREIGN KEY (reported_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- NOTIFICATIONS (in-app + push FCM)
-- ----------------------------------------------------------------------------
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(100) NOT NULL,                      -- 'order.shipped', 'stock.low', 'message.received'...
    title VARCHAR(255) NOT NULL,
    body VARCHAR(500) NULL,
    data JSON NULL,                                   -- payload additionnel (order_id, etc.)
    channel ENUM('in_app','push','email','sms') NOT NULL DEFAULT 'in_app',
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notif_user_unread (user_id, read_at)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- COMPTABILITE SIMPLIFIEE
-- ----------------------------------------------------------------------------
-- Agregat quotidien pre-calcule par boutique (alimente par un job planifie, pour dashboards rapides)
CREATE TABLE daily_shop_summaries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    summary_date DATE NOT NULL,
    orders_count INT UNSIGNED NOT NULL DEFAULT 0,
    gross_revenue DECIMAL(14,2) NOT NULL DEFAULT 0,
    discounts_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    commission_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    refunds_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    net_revenue DECIMAL(14,2) NOT NULL DEFAULT 0,      -- gross - discounts - commission - refunds
    cost_of_goods DECIMAL(14,2) NOT NULL DEFAULT 0,     -- base sur cost_price
    estimated_profit DECIMAL(14,2) NOT NULL DEFAULT 0,
    new_customers_count INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_daily_summary (shop_id, summary_date),
    CONSTRAINT fk_dss_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Depenses manuelles saisies par la boutique (loyer, salaires, factures...)
CREATE TABLE shop_expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    category VARCHAR(100) NOT NULL,                   -- "Loyer", "Salaires", "Electricite"...
    label VARCHAR(255) NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    expense_date DATE NOT NULL,
    receipt_path VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expense_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    INDEX idx_expense_shop_date (shop_id, expense_date)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
