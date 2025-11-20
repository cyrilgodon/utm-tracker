# 🚨 CRITICAL BUG FIX - UTM Tracker

**Date :** 20 Novembre 2024  
**Priorité :** 🔴 **P0 - CRITIQUE**  
**Impact :** Cause des ERR_CONNECTION_RESET sur tout le site

---

## 🔴 PROBLÈME CRITIQUE

### **Session PHP jamais fermée**

**Symptômes :**
- ❌ Pages blanches aléatoires
- ❌ ERR_CONNECTION_RESET intermittent
- ❌ Timeouts sur pages admin
- ❌ Aucune erreur dans debug.log

**Cause Racine :**
```php
// Fichier : utm-tracker.php
public function start_session() {
    if ( ! session_id() && ! headers_sent() ) {
        session_start(); // ✅ Ouvre la session
        // ❌ JAMAIS DE session_write_close() !
    }
}
```

**Impact :**
1. `session_start()` VERROUILLE le fichier de session
2. Toutes les autres requêtes du même utilisateur sont BLOQUÉES
3. WordPress fait des loopback requests (API REST vers lui-même)
4. La 2e requête attend la 1ère (deadlock)
5. Timeout après 30s → ERR_CONNECTION_RESET

---

## ⚡ SOLUTION

### **Ajouter session_write_close() après utilisation**

```php
// ❌ AVANT (BUGUÉ)
public function start_session() {
    if ( ! session_id() && ! headers_sent() ) {
        session_start();
    }
}

public function save_utm_to_session( $utm_data ) {
    $_SESSION['utm_data'] = $utm_data;
    // ❌ Session reste ouverte !
}
```

```php
// ✅ APRÈS (CORRIGÉ)
public function start_session() {
    if ( ! session_id() && ! headers_sent() ) {
        session_start();
    }
}

public function save_utm_to_session( $utm_data ) {
    $_SESSION['utm_data'] = $utm_data;
    
    // ✅ CRITIQUE : Fermer immédiatement la session
    session_write_close();
}

// ✅ Alternative : Fermer au hook shutdown
public function close_session_on_shutdown() {
    if ( session_id() ) {
        session_write_close();
    }
}

// Dans __construct() :
add_action( 'shutdown', array( $this, 'close_session_on_shutdown' ), 1 );
```

---

## 🎯 IMPLÉMENTATION RECOMMANDÉE

### **Méthode 1 : session_write_close() immédiat** ⭐ (MEILLEUR)

```php
/**
 * Enregistrer les paramètres UTM en session
 *
 * @param array $utm_data Données UTM
 * @since 1.0.0
 */
public function save_utm_to_session( $utm_data ) {
    // Démarrer session si pas déjà fait
    if ( ! session_id() ) {
        $this->start_session();
    }
    
    // Enregistrer les données
    $_SESSION['utm_data'] = $utm_data;
    $_SESSION['utm_timestamp'] = time();
    
    // ✅ CRITIQUE : Fermer immédiatement
    // Permet aux autres requêtes de continuer
    session_write_close();
    
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[UTM Tracker] Session fermée après enregistrement UTM' );
    }
}

/**
 * Récupérer les paramètres UTM depuis la session
 *
 * @return array|null Données UTM ou null
 * @since 1.0.0
 */
public function get_utm_from_session() {
    // Réouvrir temporairement si besoin
    if ( ! session_id() ) {
        $this->start_session();
    }
    
    $utm_data = isset( $_SESSION['utm_data'] ) ? $_SESSION['utm_data'] : null;
    
    // Fermer immédiatement après lecture
    session_write_close();
    
    return $utm_data;
}
```

**Avantages :**
- ✅ Session ouverte seulement le temps nécessaire (quelques ms)
- ✅ Libère immédiatement le verrou
- ✅ Pas d'impact sur les loopback requests

---

### **Méthode 2 : Hook shutdown** (Alternative)

```php
/**
 * Constructeur
 */
public function __construct() {
    // ... hooks existants ...
    
    // ✅ Fermer session au shutdown (priorité 1 = très tôt)
    add_action( 'shutdown', array( $this, 'close_session_on_shutdown' ), 1 );
}

/**
 * Fermer la session PHP au shutdown
 *
 * @since 1.0.1
 */
public function close_session_on_shutdown() {
    if ( session_id() ) {
        session_write_close();
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[UTM Tracker] Session fermée au shutdown' );
        }
    }
}
```

**Avantages :**
- ✅ Simple à implémenter
- ✅ Ferme automatiquement toutes les sessions
- ⚠️ Session reste ouverte plus longtemps (mais libérée avant loopback requests)

---

## 🔍 ALTERNATIVE : NE PAS UTILISER DE SESSIONS

### **Utiliser des Cookies à la place**

```php
/**
 * Enregistrer les paramètres UTM en cookie
 *
 * @param array $utm_data Données UTM
 * @since 2.0.0
 */
public function save_utm_to_cookie( $utm_data ) {
    $cookie_data = array(
        'utm' => $utm_data,
        'timestamp' => time()
    );
    
    // Cookie valide 30 jours
    setcookie(
        'utm_tracker_data',
        base64_encode( json_encode( $cookie_data ) ),
        time() + ( 30 * DAY_IN_SECONDS ),
        '/',
        '',
        is_ssl(),
        true // HttpOnly pour sécurité
    );
    
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[UTM Tracker] UTM enregistré en cookie' );
    }
}

/**
 * Récupérer les paramètres UTM depuis le cookie
 *
 * @return array|null Données UTM ou null
 * @since 2.0.0
 */
public function get_utm_from_cookie() {
    if ( ! isset( $_COOKIE['utm_tracker_data'] ) ) {
        return null;
    }
    
    $decoded = json_decode( base64_decode( $_COOKIE['utm_tracker_data'] ), true );
    
    // Vérifier expiration (30 jours)
    if ( isset( $decoded['timestamp'] ) && ( time() - $decoded['timestamp'] ) > ( 30 * DAY_IN_SECONDS ) ) {
        return null;
    }
    
    return isset( $decoded['utm'] ) ? $decoded['utm'] : null;
}
```

**Avantages :**
- ✅ Pas de sessions PHP du tout
- ✅ Pas de verrous
- ✅ Pas de problèmes de loopback
- ✅ Plus simple et plus performant
- ✅ Conforme RGPD (cookie technique)

---

## 📊 COMPARAISON DES SOLUTIONS

| Solution | Complexité | Performance | Risque | Recommandé |
|----------|------------|-------------|--------|------------|
| **session_write_close() immédiat** | Faible | Excellente | Faible | ⭐⭐⭐ |
| **Hook shutdown** | Très faible | Bonne | Moyen | ⭐⭐ |
| **Cookies** | Moyenne | Excellente | Faible | ⭐⭐⭐⭐ |

---

## ✅ PLAN D'ACTION

### **Court Terme (URGENT)**

```
1. Implémenter session_write_close() immédiat
   Temps : 15 minutes
   Gain : Résout le problème ERR_CONNECTION_RESET
```

### **Moyen Terme (RECOMMANDÉ)**

```
2. Migrer vers des Cookies
   Temps : 1-2 heures
   Gain : Solution plus robuste et performante
```

---

## 🧪 TESTS REQUIS

### **Après Fix**

1. **Test Fonctionnel :**
   - UTM parameters sont bien enregistrés
   - UTM parameters sont bien récupérés
   - Pas de régression

2. **Test Performance :**
   - Aucun ERR_CONNECTION_RESET
   - Pages admin chargent normalement
   - Loopback requests fonctionnent

3. **Test Health Check :**
   - `wp-admin/site-health.php`
   - Vérifier que l'avertissement session PHP a disparu

---

## 📞 CONTACT

**Questions / Implémentation :**
- Référence : `UTM-TRACKER-CRITICAL-001`
- Priorité : 🔴 P0
- Fix requis : URGENT

---

**Dernière Mise à Jour :** 2024-11-20  
**Status :** ⚠️ CRITIQUE - Fix requis avant mise en production

