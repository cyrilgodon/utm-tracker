-- ==============================================================================
-- CRÉATION DES TABLES - UTM Tracker Plugin
-- ==============================================================================
-- Version: 1.0.0
-- Date: 2025-10-30
--
-- Ce script crée les 3 tables nécessaires au plugin UTM Tracker.
-- À utiliser UNIQUEMENT si l'installation automatique a échoué.
--
-- IMPORTANT : Remplacez 'wp_' par votre préfixe de base de données si différent.
-- ==============================================================================
-- ------------------------------------------------------------------------------
-- TABLE 1 : wp_utm_campaigns
-- Configuration des campagnes marketing (UTM → Tags)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_utm_campaigns` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID unique de la campagne',
    `name` VARCHAR(255) NOT NULL COMMENT 'Nom descriptif de la campagne',
    `utm_source` VARCHAR(255) NOT NULL COMMENT 'Source UTM (ex: google, facebook)',
    `utm_medium` VARCHAR(255) NOT NULL COMMENT 'Medium UTM (ex: cpc, organic)',
    `utm_campaign` VARCHAR(255) NOT NULL COMMENT 'Campagne UTM (ex: coaching_q1)',
    `utm_content` VARCHAR(255) DEFAULT NULL COMMENT 'Contenu UTM (optionnel)',
    `utm_term` VARCHAR(255) DEFAULT NULL COMMENT 'Terme UTM (optionnel)',
    `user_tags` TEXT NOT NULL COMMENT 'JSON array des tags à appliquer',
    `status` ENUM('active', 'paused', 'archived') DEFAULT 'active' COMMENT 'Statut de la campagne',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de mise à jour',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_utm` (`utm_source`, `utm_medium`, `utm_campaign`),
    KEY `idx_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Configuration des campagnes UTM';
-- ------------------------------------------------------------------------------
-- TABLE 2 : wp_user_tags
-- Tags appliqués aux utilisateurs (résultat de l'attribution)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_user_tags` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID unique du tag',
    `user_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'ID de l\'utilisateur WordPress',
    `tag_slug` VARCHAR(100) NOT NULL COMMENT 'Slug du tag (ex: lead_google)',
    `campaign_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'ID de la campagne source',
    `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date d\'application du tag',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_tag` (`user_id`, `tag_slug`),
    KEY `idx_user` (`user_id`),
    KEY `idx_tag` (`tag_slug`),
    KEY `idx_campaign` (`campaign_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Tags utilisateur appliqués';
-- ------------------------------------------------------------------------------
-- TABLE 3 : wp_utm_events
-- Historique des événements UTM (tracking)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_utm_events` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID unique de l\'événement',
    `user_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'ID utilisateur (NULL si anonyme)',
    `session_id` VARCHAR(64) DEFAULT NULL COMMENT 'ID de session PHP',
    `campaign_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'ID de la campagne matchée',
    `utm_source` VARCHAR(255) DEFAULT NULL COMMENT 'Source UTM capturée',
    `utm_medium` VARCHAR(255) DEFAULT NULL COMMENT 'Medium UTM capturé',
    `utm_campaign` VARCHAR(255) DEFAULT NULL COMMENT 'Campagne UTM capturée',
    `utm_content` VARCHAR(255) DEFAULT NULL COMMENT 'Contenu UTM capturé',
    `utm_term` VARCHAR(255) DEFAULT NULL COMMENT 'Terme UTM capturé',
    `referrer` TEXT DEFAULT NULL COMMENT 'URL du referrer',
    `landing_page` TEXT DEFAULT NULL COMMENT 'URL de la landing page',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de capture',
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`, `created_at`),
    KEY `idx_session` (`session_id`),
    KEY `idx_campaign` (`campaign_id`, `created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Historique des événements UTM';
-- ==============================================================================
-- VÉRIFICATION DES TABLES
-- ==============================================================================
-- Vérifier que les tables ont été créées
SHOW TABLES LIKE '%utm%';
-- Vérifier la structure de chaque table
DESCRIBE wp_utm_campaigns;
DESCRIBE wp_user_tags;
DESCRIBE wp_utm_events;
-- ==============================================================================
-- REQUÊTES UTILES POST-INSTALLATION
-- ==============================================================================
-- Compter les campagnes
-- SELECT COUNT(*) as total_campaigns FROM wp_utm_campaigns;
-- Compter les tags utilisateur
-- SELECT COUNT(*) as total_tags FROM wp_user_tags;
-- Compter les événements UTM
-- SELECT COUNT(*) as total_events FROM wp_utm_events;
-- Vérifier les indexes
-- SHOW INDEX FROM wp_utm_campaigns;
-- SHOW INDEX FROM wp_user_tags;
-- SHOW INDEX FROM wp_utm_events;
-- ==============================================================================
-- SUPPRESSION DES TABLES (⚠️ DANGER - TOUTES LES DONNÉES SERONT PERDUES)
-- ==============================================================================
-- ⚠️ À utiliser UNIQUEMENT pour désinstallation complète ou réinstallation
-- ⚠️ CETTE OPÉRATION EST IRRÉVERSIBLE
-- DROP TABLE IF EXISTS wp_utm_events;
-- DROP TABLE IF EXISTS wp_user_tags;
-- DROP TABLE IF EXISTS wp_utm_campaigns;
-- ==============================================================================
-- NOTES IMPORTANTES
-- ==============================================================================
-- 1. PRÉFIXE DE BASE DE DONNÉES
--    Si votre WordPress utilise un préfixe différent de 'wp_', remplacez
--    'wp_' par votre préfixe dans toutes les requêtes ci-dessus.
--    Exemple : 'wpxyz_utm_campaigns' au lieu de 'wp_utm_campaigns'
--
-- 2. ENCODAGE
--    Les tables utilisent utf8mb4_unicode_ci pour supporter les caractères
--    Unicode complets (émojis, caractères internationaux, etc.)
--
-- 3. MOTEUR DE BASE DE DONNÉES
--    Les tables utilisent InnoDB pour :
--    - Support des transactions
--    - Meilleure intégrité des données
--    - Performance optimale pour les lectures/écritures concurrentes
--
-- 4. INDEXES
--    Les indexes ont été optimisés pour les requêtes les plus fréquentes :
--    - Recherche de campagnes par UTM (unique_utm)
--    - Recherche de tags par utilisateur (idx_user)
--    - Recherche d'événements par campagne/session (idx_campaign, idx_session)
--
-- 5. FOREIGN KEYS
--    Les foreign keys ne sont pas créées pour éviter les problèmes avec
--    la suppression d'utilisateurs WordPress. La gestion de l'intégrité
--    est faite au niveau de l'application.
--
-- ==============================================================================