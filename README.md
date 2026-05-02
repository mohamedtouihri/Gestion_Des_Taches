# 🔐 Gestion des Tâches Sécurisée

> Application web PHP de gestion de tâches personnelles avec intégration complète des mesures de sécurité web.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-Local-FB7A24?style=flat-square&logo=xampp&logoColor=white)
![License](https://img.shields.io/badge/Licence-Académique-blue?style=flat-square)
![Status](https://img.shields.io/badge/Status-Complet-success?style=flat-square)

---

## 📋 Table des matières

- [À propos du projet](#-à-propos-du-projet)
- [Fonctionnalités](#-fonctionnalités)
- [Sécurité](#-sécurité)
- [Structure du projet](#-structure-du-projet)
- [Installation](#-installation)
- [Comptes de test](#-comptes-de-test)
- [Captures d'écran](#-captures-décran)
- [Base de données](#-base-de-données)
- [Technologies utilisées](#-technologies-utilisées)
- [Auteur](#-auteur)

---

## 📖 À propos du projet

Ce projet a été réalisé dans le cadre du module **Sécurité des Applications Web** à l'**ISLAIB (Institut Supérieur des Langues Appliquées et d'Informatique de Béja)**, sous la direction de **M. Ramzi Guesmi** — Année 2025/2026.

L'objectif est de développer une application web fonctionnelle intégrant, **dès la conception**, les mesures de sécurité essentielles contre les vulnérabilités les plus courantes (OWASP Top 10).

---

## ✨ Fonctionnalités

### 👤 Gestion des utilisateurs
- [x] Inscription avec validation complète
- [x] Connexion sécurisée
- [x] Déconnexion avec destruction de session
- [x] Modification du profil et du mot de passe
- [x] Timeout de session (30 minutes d'inactivité)

### 📋 Gestion des tâches
- [x] Ajouter une tâche (titre + description)
- [x] Afficher la liste de ses tâches
- [x] Modifier une tâche
- [x] Supprimer une tâche
- [x] Marquer comme **terminée** / **en cours**

### 🔐 Espace Administrateur
- [x] Tableau de bord avec statistiques globales
- [x] Liste de tous les utilisateurs
- [x] Voir toutes les tâches (tous utilisateurs)
- [x] Activer / Désactiver un compte
- [x] Supprimer un compte utilisateur
- [x] Journal de sécurité (audit log) en temps réel

---

## 🛡️ Sécurité

| Mesure | Implémentation |
|--------|----------------|
| ✅ Hachage des mots de passe | `password_hash()` / `password_verify()` — bcrypt |
| ✅ Injection SQL | Requêtes préparées `prepare()` + `execute()` sur 100% des requêtes |
| ✅ XSS | `htmlspecialchars()` sur toutes les variables affichées |
| ✅ CSRF | Token 64 chars `bin2hex(random_bytes(32))` + `hash_equals()` |
| ✅ Brute-force | Blocage après 5 tentatives, délai 30 secondes |
| ✅ Sessions | `session_regenerate_id(true)` après login + `session_destroy()` |
| ✅ Expiration session | Timeout 30 min d'inactivité |
| ✅ Contrôle d'accès | Vérification `role` et `user_id` côté serveur sur chaque page |
| ✅ Journalisation | Table `journaux` — login, échecs, suppressions, accès admin |
| ✅ Comptes désactivables | Champ `statut = 'inactif'` bloque la connexion |
| ✅ Messages génériques | Pas de révélation si email ou mot de passe est faux |
| ✅ Chemins absolus | `__DIR__` pour tous les `require()` — compatible Windows/Linux |

---

## 📁 Structure du projet

```
gestion-taches-securisee/
│
├── index.php                  # Page d'accueil
│
├── auth/
│   ├── login.php              # Connexion (CSRF + brute-force + redirection par rôle)
│   ├── register.php           # Inscription (validation + hachage)
│   ├── logout.php             # Déconnexion (log + session_destroy)
│   └── profile.php            # Modification profil & mot de passe
│
├── tasks/
│   ├── dashboard.php          # Tableau de bord utilisateur (redirige admin)
│   ├── add.php                # Ajout d'une tâche
│   ├── edit.php               # Modification d'une tâche
│   └── delete.php             # Suppression d'une tâche
│
├── admin/
│   └── users.php              # Panneau admin (stats + users + tâches + logs)
│
├── config/
│   └── db.php                 # PDO + session_start() + CSRF + log_action()
│
├── includes/
│   ├── header.php             # HTML commun + CSS global
│   └── footer.php             # Fermeture HTML
│
├── gestion_taches.sql         # Schéma complet de la base de données
├── fix_admin_password.sql     # Script de réinitialisation du mot de passe admin
└── README.md
```

---

## ⚙️ Installation

### Prérequis
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8+)
- Navigateur web

### Étapes

**1. Cloner le dépôt**
```bash
git clone https://github.com/VOTRE_USERNAME/gestion-taches-securisee.git
```

**2. Déplacer dans le dossier XAMPP**
```bash
# Windows
xcopy gestion-taches-securisee C:\xampp\htdocs\gestion-taches /E /I

# macOS / Linux
cp -r gestion-taches-securisee /opt/lampp/htdocs/gestion-taches
```

**3. Démarrer XAMPP**
- Démarrer **Apache** et **MySQL** depuis le panneau XAMPP

**4. Créer la base de données**
- Ouvrir [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
- Créer une base de données nommée `gestion_taches`
- Aller dans l'onglet **SQL** et importer le fichier `gestion_taches.sql`

```sql
-- Ou directement dans phpMyAdmin > Importer > sélectionner gestion_taches.sql
```

**5. Configurer la connexion** *(si besoin)*

Ouvrir `config/db.php` et modifier les identifiants si différents :
```php
$pdo = new PDO(
    "mysql:host=localhost;dbname=gestion_taches;charset=utf8",
    "root",   // ← votre username MySQL
    ""        // ← votre mot de passe MySQL (vide par défaut sur XAMPP)
);
```

**6. Créer le compte admin** *(important)*

Créer un fichier temporaire `htdocs/gestion-taches/create_admin.php` :
```php
<?php
require('config/db.php');
$hash = password_hash('VotreMotDePasse123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare(
    "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, statut, date_creation)
     VALUES ('Admin', 'ISLAIB', 'admin@islaib.tn', ?, 'admin', 'actif', NOW())"
);
$stmt->execute([$hash]);
echo "✅ Compte admin créé avec succès !";
?>
```
Ouvrir [http://localhost/gestion-taches/create_admin.php](http://localhost/gestion-taches/create_admin.php) **une seule fois**, puis **supprimer ce fichier immédiatement**.

**7. Accéder à l'application**
```
http://localhost/gestion-taches/
```

---

## 👥 Comptes de test

| Rôle | Email | Mot de passe | Accès |
|------|-------|--------------|-------|
| **Admin** | `admin@islaib.tn` | `VotreMotDePasse123` | Panneau d'administration complet |
| **User** | *(créer via register.php)* | *(choisi à l'inscription)* | Tableau de bord personnel |

> ⚠️ **Important :** Ne jamais committer de vrais mots de passe dans le dépôt GitHub.

---

## 📸 Captures d'écran

| Page | Description |
|------|-------------|
| `index.php` | Page d'accueil |
| `auth/login.php` | Formulaire de connexion |
| `auth/register.php` | Formulaire d'inscription |
| `tasks/dashboard.php` | Tableau de bord utilisateur |
| `admin/users.php` | Panneau d'administration |

<p align="center">
  <img src="screenshots/Screenshot (1356).png" width="45%" />
  <img src="screenshots/Screenshot (1357).png" width="45%" />
  <img src="screenshots/Screenshot (1358).png" width="45%" />
  <img src="screenshots/Screenshot (1360).png" width="45%" />
</p>

---

## 🗄️ Base de données

### Schéma simplifié

```
utilisateurs
├── id (PK)
├── nom, prenom
├── email (UNIQUE)
├── mot_de_passe  ← hash bcrypt uniquement
├── role          ← 'user' | 'admin'
├── statut        ← 'actif' | 'inactif'
└── date_creation

taches
├── id (PK)
├── user_id (FK → utilisateurs.id)
├── titre, description
├── etat          ← 'en_cours' | 'terminee'
├── date_creation
└── date_modification

journaux
├── id (PK)
├── utilisateur_id (FK → utilisateurs.id)
├── action        ← LOGIN_SUCCESS | LOGIN_FAILED | DELETE_TASK | ...
├── adresse_ip
├── details
└── date_action
```

---

## 🔧 Technologies utilisées

- **PHP 8** — Backend, logique serveur, sécurité
- **MySQL 8** — Base de données relationnelle
- **PDO** — Interface sécurisée PHP/MySQL
- **HTML5 / CSS3** — Structure et style des pages
- **JavaScript** — Onglets admin, toggle mot de passe
- **XAMPP** — Environnement de développement local
- **phpMyAdmin** — Administration de la base de données

---

## 📚 Concepts de sécurité appliqués

- **OWASP A01** — Broken Access Control → Contrôle d'accès côté serveur
- **OWASP A02** — Cryptographic Failures → `password_hash()` bcrypt
- **OWASP A03** — Injection → Requêtes préparées PDO
- **OWASP A05** — Security Misconfiguration → Messages d'erreur génériques
- **OWASP A07** — Identification Failures → Session sécurisée + brute-force protection
- **CWE-352** — CSRF → Token `bin2hex(random_bytes(32))` + `hash_equals()`
- **CWE-79** — XSS → `htmlspecialchars()` systématique

---

## 👨‍💻 Auteur

**[Votre Prénom NOM]**
- Filière : 1ère MIDS / SEIoT
- Établissement : ISLAIB — Béja
- Année : 2025/2026
- Enseignant : M. Ramzi Guesmi

---

## 📄 Licence

Projet académique réalisé dans le cadre d'une évaluation universitaire.
Usage éducatif uniquement.

---

<div align="center">
  <sub>Réalisé avec ❤️ dans le cadre du module Sécurité des Applications Web — ISLAIB 2026</sub>
</div>
