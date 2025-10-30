-- ==============================================================================
-- EXEMPLES DE CAMPAGNES UTM - UTM Tracker Plugin
-- ==============================================================================
-- Version: 1.0.0
-- Date: 2025-10-30
--
-- Ce fichier contient des exemples de campagnes UTM prêtes à l'emploi.
-- Adaptez les noms, sources, mediums et tags selon vos besoins.
--
-- IMPORTANT : Remplacez 'wp_' par votre préfixe de base de données si différent.
-- ==============================================================================
-- ------------------------------------------------------------------------------
-- CAMPAGNES GOOGLE ADS
-- ------------------------------------------------------------------------------
-- Campagne Google Ads - Coaching Professionnel
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        utm_content,
        utm_term,
        user_tags,
        status
    )
VALUES (
        'Google Ads - Coaching Q1 2025',
        'google',
        'cpc',
        'coaching_q1_2025',
        'ad_text_v1',
        'coach professionnel',
        '["lead_google", "coaching", "q1_2025", "paid"]',
        'active'
    );
-- Campagne Google Ads - Reflexivo (Outil IA)
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        utm_content,
        utm_term,
        user_tags,
        status
    )
VALUES (
        'Google Ads - Reflexivo Promo',
        'google',
        'cpc',
        'reflexivo_promo_2025',
        'banner_interactive',
        'outil reflexion coaching',
        '["lead_google", "reflexivo", "paid", "tool_interest"]',
        'active'
    );
-- Campagne Google Organic (SEO)
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'Google Organic - SEO',
        'google',
        'organic',
        'seo_coaching',
        '["lead_google", "organic", "seo"]',
        'active'
    );
-- ------------------------------------------------------------------------------
-- CAMPAGNES FACEBOOK / META
-- ------------------------------------------------------------------------------
-- Campagne Facebook Ads - Lead Generation
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        utm_content,
        user_tags,
        status
    )
VALUES (
        'Facebook Ads - Lead Generation',
        'facebook',
        'paid',
        'lead_gen_2025',
        'carousel_ad',
        '["lead_facebook", "paid", "premium", "carousel"]',
        'active'
    );
-- Campagne Facebook Organic
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'Facebook Organic - Post Share',
        'facebook',
        'social',
        'post_share',
        '["lead_facebook", "organic", "social_share"]',
        'active'
    );
-- Campagne Instagram Stories
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        utm_content,
        user_tags,
        status
    )
VALUES (
        'Instagram Stories - Reflexivo Demo',
        'instagram',
        'stories',
        'reflexivo_demo',
        'story_swipe_up',
        '["lead_instagram", "reflexivo", "mobile", "stories"]',
        'active'
    );
-- ------------------------------------------------------------------------------
-- CAMPAGNES LINKEDIN
-- ------------------------------------------------------------------------------
-- Campagne LinkedIn Ads
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'LinkedIn Ads - Coachs Professionnels',
        'linkedin',
        'cpc',
        'coach_pros_2025',
        '["lead_linkedin", "b2b", "paid", "professional"]',
        'active'
    );
-- Campagne LinkedIn Organic
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'LinkedIn Organic - Article Share',
        'linkedin',
        'social',
        'article_share',
        '["lead_linkedin", "organic", "content"]',
        'active'
    );
-- ------------------------------------------------------------------------------
-- CAMPAGNES EMAIL MARKETING
-- ------------------------------------------------------------------------------
-- Newsletter Mensuelle
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        utm_content,
        user_tags,
        status
    )
VALUES (
        'Email - Newsletter Mensuelle',
        'email',
        'newsletter',
        'monthly_digest',
        'cta_button',
        '["subscriber", "newsletter", "engaged"]',
        'active'
    );
-- Email de Bienvenue
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'Email - Welcome Series',
        'email',
        'automation',
        'welcome_series',
        '["new_subscriber", "welcome", "automation"]',
        'active'
    );
-- Email de Re-Engagement
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'Email - Re-Engagement Campaign',
        'email',
        'reengagement',
        'winback_q1',
        '["inactive_user", "reengagement", "winback"]',
        'active'
    );
-- ------------------------------------------------------------------------------
-- CAMPAGNES AFFILIÉS / PARTENAIRES
-- ------------------------------------------------------------------------------
-- Programme d'Affiliation
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'Affiliation - Partenaire 1',
        'affiliate',
        'partner',
        'partner_1_promo',
        '["affiliate", "partner_1", "referral"]',
        'active'
    );
-- ------------------------------------------------------------------------------
-- CAMPAGNES RETARGETING
-- ------------------------------------------------------------------------------
-- Retargeting Google Display
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'Retargeting - Google Display',
        'google',
        'display',
        'retargeting_q1',
        '["retargeting", "display", "remarketing"]',
        'active'
    );
-- Retargeting Facebook
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'Retargeting - Facebook Pixel',
        'facebook',
        'retargeting',
        'pixel_remarketing',
        '["retargeting", "facebook_pixel", "hot_lead"]',
        'active'
    );
-- ------------------------------------------------------------------------------
-- CAMPAGNES WEBINAIRES / ÉVÉNEMENTS
-- ------------------------------------------------------------------------------
-- Webinaire Gratuit
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'Webinaire - Coaching & IA',
        'webinar',
        'registration',
        'coaching_ia_webinar',
        '["webinar", "coaching", "ia", "education"]',
        'active'
    );
-- Événement LinkedIn Live
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'LinkedIn Live - Reflexivo Demo',
        'linkedin',
        'live',
        'reflexivo_live_demo',
        '["linkedin_live", "demo", "reflexivo", "live_event"]',
        'active'
    );
-- ------------------------------------------------------------------------------
-- CAMPAGNES QR CODE (Événements Physiques)
-- ------------------------------------------------------------------------------
-- Salon Professionnel
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'QR Code - Salon du Coaching 2025',
        'qrcode',
        'event',
        'salon_coaching_2025',
        '["qrcode", "event", "offline", "salon"]',
        'active'
    );
-- ------------------------------------------------------------------------------
-- CAMPAGNES TEST / DÉMO
-- ------------------------------------------------------------------------------
-- Campagne de Test
INSERT INTO wp_utm_campaigns (
        name,
        utm_source,
        utm_medium,
        utm_campaign,
        user_tags,
        status
    )
VALUES (
        'TEST - Campagne de Démonstration',
        'test',
        'demo',
        'test_campaign',
        '["test", "demo"]',
        'paused'
    );
-- ==============================================================================
-- REQUÊTES UTILES POUR GÉRER LES CAMPAGNES
-- ==============================================================================
-- Lister toutes les campagnes actives
-- SELECT * FROM wp_utm_campaigns WHERE status = 'active' ORDER BY created_at DESC;
-- Compter les campagnes par statut
-- SELECT status, COUNT(*) as count FROM wp_utm_campaigns GROUP BY status;
-- Trouver une campagne spécifique
-- SELECT * FROM wp_utm_campaigns WHERE utm_source = 'google' AND utm_medium = 'cpc' AND utm_campaign = 'coaching_q1_2025';
-- Mettre en pause une campagne
-- UPDATE wp_utm_campaigns SET status = 'paused' WHERE id = 1;
-- Archiver une campagne
-- UPDATE wp_utm_campaigns SET status = 'archived' WHERE id = 1;
-- Réactiver une campagne
-- UPDATE wp_utm_campaigns SET status = 'active' WHERE id = 1;
-- Supprimer une campagne
-- DELETE FROM wp_utm_campaigns WHERE id = 1;
-- Modifier les tags d'une campagne
-- UPDATE wp_utm_campaigns SET user_tags = '["nouveau_tag", "autre_tag"]' WHERE id = 1;
-- ==============================================================================
-- NOTES D'UTILISATION
-- ==============================================================================
-- 1. NOMMAGE DES CAMPAGNES
--    - Utilisez des noms descriptifs et cohérents
--    - Incluez la source et la période (ex: "Google Ads Q1 2025")
--
-- 2. PARAMÈTRES UTM
--    - utm_source : Toujours en lowercase (google, facebook, email, etc.)
--    - utm_medium : Type de trafic (cpc, organic, paid, social, etc.)
--    - utm_campaign : Identifiant unique de la campagne
--    - utm_content : Variante de l'annonce (optionnel)
--    - utm_term : Mots-clés (optionnel)
--
-- 3. TAGS UTILISATEUR
--    - Format JSON : ["tag1", "tag2", "tag3"]
--    - Utilisez des slugs cohérents (snake_case recommandé)
--    - Évitez les espaces et caractères spéciaux
--    - Exemples : lead_google, coaching, premium, q1_2025
--
-- 4. STATUTS
--    - 'active' : Campagne en cours, matching actif
--    - 'paused' : Campagne en pause, pas de matching
--    - 'archived' : Campagne terminée, pour historique seulement
--
-- 5. BONNES PRATIQUES
--    - Créez une campagne de test avant de lancer en production
--    - Documentez vos tags dans un fichier séparé
--    - Utilisez des conventions de nommage cohérentes
--    - Archivez les campagnes terminées plutôt que de les supprimer
--
-- ==============================================================================