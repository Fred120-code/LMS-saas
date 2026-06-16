# EduLearn — Learning Management System (LMS)

EduLearn est une plateforme Web moderne de gestion de l'apprentissage (LMS - Learning Management System) développée en **PHP (Vanilla)**, **MySQL**, **CSS personnalisé** et **JavaScript (AJAX)**.

Cette plateforme permet de gérer des parcours de formation comprenant des modules, des cours, des leçons et des évaluations (quizz), avec un système de suivi de progression et de délivrance de certificats.

---

## Structure des dossiers

Le projet est structuré de manière modulaire selon l'arborescence ci-dessous :

```text
LMS/
├── index.php                      # Page de connexion unifiée
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
│   │   └── style.css              # Feuille de style principale avec variables CSS
│   └── js/
│       └── app.js                 # Logique JS (Gestion AJAX, modales, recherche)
│
├── admin/                         # Espace Administration (Accès réservé 'admin')
│   ├── dashboard.php              # Tableau de bord des statistiques globales
│   ├── modules.php                # Gestion (CRUD) des modules de cours
│   ├── students.php               # Gestion (CRUD) des étudiants
│   └── teachers.php               # Gestion (CRUD) des enseignants
│
├── teacher/                       # Espace Enseignant (Accès réservé 'teacher')
│   ├── dashboard.php              # Tableau de bord enseignant
│   ├── courses.php                # Liste des cours créés par l'enseignant
│   ├── create_course.php          # Ajout/édition de cours
│   └── results.php                # Suivi des résultats des étudiants
│
├── student/                       # Espace Étudiant (Accès réservé 'student')
│   ├── dashboard.php              # Tableau de bord étudiant avec progression
│   ├── modules.php                # Navigation dans les modules de formation
│   ├── courses.php                # Affichage des cours d'un module
│   ├── lesson.php                 # Visionneuse de leçon (vidéo, pdf, quiz)
│   ├── my_results.php             # Historique et visualisation des scores
│   └── certificates.php           # Téléchargement des certificats obtenus
│
├── api/                           # Endpoints AJAX de traitement
│   ├── quiz_submit.php            # Soumission et correction automatique des quiz
│   └── progress_update.php        # Mise à jour de la progression de l'étudiant
│
└── uploads/                       # Documents de cours et médias
    ├── pdfs/                      # Fichiers PDF de cours
    ├── videos/                    # Fichiers Vidéo de cours
    └── certificates/              # Certificats générés
```

---

## Instructions d'installation et de configuration

### 1. Prérequis
- Un serveur web local (comme **XAMPP**, **WampServer**, **MAMP** ou Docker) avec PHP 7.4+ et MySQL.

### 2. Configuration de la Base de Données
1. Démarrez votre serveur MySQL.
2. Créez une base de données nommée `lms_db` et importez le fichier `database.sql`.
   - *Via le terminal :*
     ```bash
     mysql -u root -p -e "CREATE DATABASE lms_db;"
     mysql -u root -p lms_db < database.sql
     ```
   - *Via phpMyAdmin :* Créez la base de données `lms_db` et importez-y le fichier SQL.

### 3. Édition du fichier `includes/config.php`
Ouvrez le fichier [config.php](file:///c:/xampp/htdocs/lms/includes/config.php) et adaptez les constantes selon vos identifiants locaux :

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'votre_utilisateur'); // Généralement 'root' sous XAMPP
define('DB_PASS', 'votre_mot_de_passe'); // Généralement '' sous XAMPP
define('DB_NAME', 'lms_db');
define('BASE_URL', 'http://localhost/lms'); // URL d'accès locale (sans slash final)
```

---

## Comptes de Démo (Créés par database.sql)

Le mot de passe pour tous les comptes par défaut est : **`Password123!`**

| Rôle | Adresse E-mail | Mot de passe | Description |
| :--- | :--- | :--- | :--- |
| **Administrateur** | `admin@lms.com` | `Password123!` | Accès complet à la gestion des utilisateurs et des modules. |
| **Enseignant** | `sophie@lms.com` | `Password123!` | Accès à la gestion du contenu pédagogique (cours, leçons, quiz). |
| **Étudiant** | `julien@lms.com` | `Password123!` | Suivi des cours, passage des quiz et obtention des certificats. |
| **Étudiant** | `fatima@lms.com` | `Password123!` | Compte de test étudiant secondaire. |

---

## Aperçu des Fonctionnalités Implémentées

- **Index Intelligent** : Connexion unifiée avec routage dynamique automatique selon le rôle (`admin`, `teacher`, `student`).
- **Sécurité et Permissions** : Validation stricte des sessions et droits d'accès à l'aide de middleware en PHP (`includes/auth.php`).
- **Suivi en temps réel** : Mise à jour instantanée de la progression de l'étudiant via AJAX lors du passage des leçons et des quiz.
- **Délivrance de Certificats** : Génération de certificats à télécharger au format PDF lorsque l'étudiant réussit un module de cours.
- **Interface Premium** :
  - Design moderne, responsive et fluide (variables CSS personnalisées, polices Google Fonts, transitions fluides).
  - Bouton de **Déconnexion Premium** : Un bouton au style soigné avec animation SVG au survol et confirmation sécurisée par modale dynamique pour éviter toute sortie accidentelle.
