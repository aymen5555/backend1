# ⚠️ DANGER — NE JAMAIS EXÉCUTER CES COMMANDES SANS BACKUP

## Commandes destructrices interdites en développement

```bash
php artisan migrate:fresh         # ❌ SUPPRIME TOUTES LES TABLES ET TOUTES LES DONNÉES
php artisan migrate:fresh --seed  # ❌ Idem + reseed (écrase les données manuelles)
php artisan migrate:refresh       # ❌ Rollback + re-run de toutes les migrations (perte données)
php artisan db:wipe               # ❌ Supprime toutes les tables sans avertissement
```

## Pourquoi c'est critique ici

Ce projet utilise une **base de données de développement persistante** qui contient :
- Des complexes, terrains, gérants créés manuellement
- Des réservations, paiements, stocks en données de test réelles
- Des bons d'entrée/sortie, fournisseurs internes liés
- Des notifications et abonnements actifs

**Un `migrate:fresh` a probablement causé en juin 2026 :**
1. La perte de 5 complexes (Padel House, Tennis Club, Sassi, La Marsa, La Soukra)
2. La perte de la colonne `complexe_id` sur `fournisseurs_internes` (migration jamais recréée)

Ces pertes ont nécessité plusieurs heures de récréation manuelle des données.

## Procédure correcte si une migration doit être modifiée

```bash
# ✅ Ajouter une migration incrémentale pour modifier une table existante
php artisan make:migration add_column_xxx_to_table_yyy

# ✅ Appliquer uniquement les nouvelles migrations
php artisan migrate

# ✅ Rollback uniquement le dernier batch si besoin de correction
php artisan migrate:rollback
```

## Avant tout backup obligatoire

```bash
# MySQL — exporter la base avant toute opération risquée
mysqldump -u root -p laravel > backup_$(date +%Y%m%d_%H%M%S).sql

# Ou via artisan (package spatie/laravel-backup recommandé)
php artisan backup:run
```

---
> Ce fichier a été créé après investigation d'une régression en date du 28/06/2026.
> Ne le supprimer pas.
