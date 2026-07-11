

-- Table : users
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100)  NOT NULL,
    prenom      VARCHAR(100)  NOT NULL,
    email       VARCHAR(191)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
    avatar      VARCHAR(255)  DEFAULT NULL,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table : modules
CREATE TABLE modules (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(255)  NOT NULL,
    description TEXT,
    created_by  INT           NOT NULL,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table : courses

CREATE TABLE courses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    module_id   INT           NOT NULL,
    teacher_id  INT           NOT NULL,
    titre       VARCHAR(255)  NOT NULL,
    description TEXT,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id)  REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB;


-- Table : lessons

CREATE TABLE lessons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    course_id   INT           NOT NULL,
    titre       VARCHAR(255)  NOT NULL,
    type        ENUM('pdf','video') NOT NULL,
    fichier     VARCHAR(500)  NOT NULL,
    ordre       INT           DEFAULT 1,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- Table : quizzes 

CREATE TABLE quizzes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id   INT           NOT NULL UNIQUE,
    titre       VARCHAR(255)  NOT NULL,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- Table : quiz_questions

CREATE TABLE quiz_questions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id         INT           NOT NULL,
    question        TEXT          NOT NULL,
    option_a        VARCHAR(500)  NOT NULL,
    option_b        VARCHAR(500)  NOT NULL,
    option_c        VARCHAR(500)  NOT NULL,
    option_d        VARCHAR(500)  NOT NULL,
    bonne_reponse   ENUM('A','B','C','D') NOT NULL,
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- Table : results

CREATE TABLE results (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT           NOT NULL,
    quiz_id     INT           NOT NULL,
    lesson_id   INT           NOT NULL,
    score       INT           NOT NULL DEFAULT 0,
    total       INT           NOT NULL DEFAULT 0,
    pourcentage DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    passed      TINYINT(1)    NOT NULL DEFAULT 0,
    taken_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_result (student_id, quiz_id),
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (quiz_id)    REFERENCES quizzes(id)  ON DELETE CASCADE,
    FOREIGN KEY (lesson_id)  REFERENCES lessons(id)  ON DELETE CASCADE
) ENGINE=InnoDB;


-- Table : progress

CREATE TABLE progress (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT           NOT NULL,
    module_id       INT           NOT NULL,
    lessons_done    INT           DEFAULT 0,
    lessons_total   INT           DEFAULT 0,
    pourcentage     DECIMAL(5,2)  DEFAULT 0.00,
    updated_at      DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_progress (student_id, module_id),
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (module_id)  REFERENCES modules(id)  ON DELETE CASCADE
) ENGINE=InnoDB;


-- Table : certificates

CREATE TABLE certificates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT           NOT NULL,
    module_id       INT           NOT NULL,
    fichier         VARCHAR(500)  DEFAULT NULL,
    delivered_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_cert (student_id, module_id),
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (module_id)  REFERENCES modules(id)  ON DELETE CASCADE
) ENGINE=InnoDB;


-- Table : student_lessons

CREATE TABLE student_lessons (
    student_id  INT NOT NULL,
    lesson_id   INT NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, lesson_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;
