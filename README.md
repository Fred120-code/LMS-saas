# 📚 EduLearn — Learning Management System (LMS)

EduLearn est une plateforme Web légère de gestion de l'apprentissage (LMS - Learning Management System) développée en **PHP (Vanilla)**, **MySQL**, **CSS personnalisé** et **JavaScript (AJAX)**.

Cette plateforme permet de gérer des parcours de formation comprenant des modules, des cours, des leçons et des évaluations (quizz), avec un système de suivi de progression et de délivrance de certificats.

---

## 🔍 Analyse de l'architecture du projet

L'analyse du code source montre que le projet est structuré pour fonctionner avec des **rôles utilisateurs distincts** (Admin, Enseignant, Étudiant) et un système d'inclusion modulaire. Cependant, dans son état actuel, **tous les fichiers sont livrés "à plat" à la racine du dossier**. 

### ⚠️ Problème de structure des fichiers
Les inclusions PHP (par ex. `require_once '../includes/config.php'`) et les liens d'assets (par ex. `../assets/css/style.css`) s'attendent à une arborescence de répertoires spécifique. Si les fichiers restent tous dans le dossier racine, **le projet ne fonctionnera pas** et générera des erreurs d'inclusion fatales (PHP Fatal Error).

De plus, l'analyse révèle que certains fichiers de fonctionnalités pour les espaces **Enseignants**, **Étudiants** et les routes **API** sont actuellement manquants dans ce livrable.

---

## 📁 Structure cible des dossiers (Recommandée)

Pour restaurer le fonctionnement du projet, vous devez organiser les fichiers selon l'arborescence ci-dessous. 

*(Les fichiers marqués d'un ⚠️ sont appelés par le code mais sont absents du dossier actuel).*

```text
LMS/
├── index.php                      # Page de connexion
├── logout.php                     # Script de déconnexion
├── database.sql                   # Script SQL d'initialisation de la BDD
├── README.md                      # Ce fichier de documentation
│
├── includes/                      # Scripts communs d'inclusion
│   ├── config.php                 # Configuration de la BDD et constantes
│   ├── auth.php                   # Fonctions de session, rôles et sécurité
│   ├── sidebar_admin.php          # Barre latérale pour l'administrateur
│   ├── sidebar_student.php        # Barre latérale pour l'étudiant
│   └── sidebar_teacher.php        # Barre latérale pour l'enseignant
│
├── assets/                        # Fichiers statiques
│   ├── css/
│   │   └── style.css              # Feuille de style principale (22 KB)
│   └── js/
│       └── app.js                 # Logique JS (Gestion AJAX des formulaires, quiz, etc.)
│
├── admin/                         # Espace Administration (Accès réservé 'admin')
│   ├── dashboard.php              # Tableau de bord des statistiques globales
│   ├── modules.php                # Gestion (CRUD) des modules de cours
│   ├── students.php               # Gestion (CRUD) des étudiants
│   └── teachers.php               # Gestion (CRUD) des enseignants
│
├── teacher/                       # Espace Enseignant (Accès réservé 'teacher')
│   ├── dashboard.php ⚠️            # Tableau de bord enseignant (à créer)
│   ├── courses.php ⚠️              # Liste des cours (à créer)
│   ├── create_course.php ⚠️        # Ajout/édition de cours (à créer)
│   └── results.php ⚠️              # Suivi des résultats des étudiants (à créer)
│
├── student/                       # Espace Étudiant (Accès réservé 'student')
│   ├── dashboard.php ⚠️            # Tableau de bord étudiant (à créer)
│   ├── modules.php ⚠️              # Navigation dans les modules (à créer)
│   ├── my_results.php ⚠️           # Visualisation des scores (à créer)
│   └── certificates.php ⚠️         # Téléchargement des certificats (à créer)
│
├── api/                           # Endpoints AJAX de traitement
│   ├── quiz_submit.php ⚠️          # Soumission et correction automatique des quiz (à créer)
│   └── progress_update.php ⚠️      # Mise à jour de la progression de l'étudiant (à créer)
│
└── uploads/                       # Dossier contenant les fichiers téléchargés (à créer)
    ├── pdfs/
    ├── videos/
    └── certificates/
```

---

## 🛠️ Instructions d'installation et de configuration

### 1. Organisation automatique des fichiers existants
Vous pouvez utiliser ce script PowerShell pour créer les dossiers nécessaires et déplacer automatiquement les fichiers existants à leur place :

```powershell
# Exécuter dans le répertoire racine LMS/
New-Item -ItemType Directory -Force -Path "includes", "assets/css", "assets/js", "admin", "uploads/pdfs", "uploads/videos", "uploads/certificates"

# Déplacement des fichiers
Move-Item -Path "auth.php", "config.php", "sidebar_admin.php", "sidebar_student.php", "sidebar_teacher.php" -Destination "includes/" -Force
Move-Item -Path "style.css" -Destination "assets/css/" -Force
Move-Item -Path "app.js" -Destination "assets/js/" -Force
Move-Item -Path "dashboard.php", "modules.php", "students.php", "teachers.php" -Destination "admin/" -Force
```

### 2. Configuration de la Base de Données
1. Démarrez votre serveur MySQL (via **XAMPP**, **WampServer**, **MAMP** ou Docker).
2. Créez la base de données en important le fichier `database.sql`. Ce fichier configurera automatiquement la structure des tables (`users`, `modules`, `courses`, `lessons`, `quizzes`, `quiz_questions`, `results`, `progress`, `certificates`) et insérera des données de test.
   - *Via terminal :*
     ```bash
     mysql -u root -p < database.sql
     ```
   - *Ou via phpMyAdmin :* Créez une base nommée `lms_db` et importez le fichier.

### 3. Édition du fichier `includes/config.php`
Ouvrez le fichier `includes/config.php` (après déplacement) et adaptez les constantes de connexion selon vos identifiants locaux :

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'votre_utilisateur'); // Généralement 'root' sous XAMPP
define('DB_PASS', 'votre_mot_de_passe'); // Généralement '' sous XAMPP
define('DB_NAME', 'lms_db');
define('BASE_URL', 'http://localhost/LMS'); // URL d'accès locale
```

---

## 🔑 Identifiants des Comptes de Démo (Semés par database.sql)

Le mot de passe pour tous les comptes par défaut est : **`Password123!`**

| Rôle | Adresse E-mail | Mot de passe | Description |
| :--- | :--- | :--- | :--- |
| **Administrateur** | `admin@lms.com` | `Password123!` | Accès complet à la gestion des utilisateurs et des modules. |
| **Enseignant** | `sophie@lms.com` | `Password123!` | Accès à la gestion du contenu pédagogique (cours, leçons, quiz). |
| **Étudiant** | `julien@lms.com` | `Password123!` | Suivi des cours, passage des quiz et obtention des certificats. |
| **Étudiant** | `fatima@lms.com` | `Password123!` | Compte de test étudiant secondaire. |

---

## 🖥️ Fonctionnalités du Code Source Existant

- **`index.php`** : Page de connexion unifiée avec détection et redirection automatique selon le rôle (`admin`, `teacher`, `student`).
- **`includes/auth.php`** : Gestion sécurisée des sessions, vérification des rôles (`requireRole($role)`) et protection contre les injections XSS (`sanitize()`).
- **`admin/dashboard.php`** : Vue d'ensemble avec statistiques clés (nombre de modules, cours, enseignants, étudiants) et listes des derniers inscrits.
- **`admin/modules.php`** : CRUD complet des modules (titre, description) avec un moteur de recherche dynamique côté client en JS.
- **`admin/teachers.php`** & **`students.php`** : Outils de gestion administrative pour ajouter ou supprimer des enseignants et des étudiants.
- **`assets/js/app.js`** :
  - Animations de progression de cours.
  - Système de notification sous forme de toasts flottants.
  - Gestion des popups (modales) interactives pour les ajouts de données.
  - Moteur de recherche de tableau côté client (`tableSearch()`).
  - Gestion AJAX pour la soumission des quiz et la mise à jour de la progression (prêt à être lié aux scripts API).
- **`assets/css/style.css`** : Charte graphique moderne, soignée, responsive et structurée (variables CSS personnalisées, design de tableaux élégant, boutons avec micro-animations, etc.).
