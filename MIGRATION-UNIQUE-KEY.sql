-- ============================================
-- MIGRATION : Ajout de utm_content à la clé unique
-- ============================================
-- Date : 2025-11-07
-- Plugin : UTM Tracker
-- 
-- Cette migration modifie la contrainte unique de wp_utm_campaigns
-- pour inclure utm_content, permettant ainsi plusieurs campagnes
-- avec le même source/medium/campaign mais un content différent.
--
-- AVANT : unique_utm (utm_source, utm_medium, utm_campaign)
-- APRÈS : unique_utm (utm_source, utm_medium, utm_campaign, utm_content)
--
-- ⚠️ ATTENTION : Cette migration va échouer si vous avez déjà
-- des campagnes en conflit (même source/medium/campaign mais
-- content différent). Résolvez ces conflits avant de migrer.
-- ============================================

-- Étape 1 : Supprimer l'ancienne contrainte unique
ALTER TABLE `wp_utm_campaigns` 
DROP INDEX `unique_utm`;

-- Étape 2 : Créer la nouvelle contrainte unique incluant utm_content
ALTER TABLE `wp_utm_campaigns` 
ADD UNIQUE KEY `unique_utm` (`utm_source`, `utm_medium`, `utm_campaign`, `utm_content`);

-- ✅ MIGRATION TERMINÉE
-- Vous pouvez maintenant créer plusieurs campagnes avec :
-- - linkedin / post / jpro / annonce
-- - linkedin / post / jpro / postlendemain
-- - linkedin / post / jpro / relance
-- etc.


