
-- 1. CREATE & SELECT DATABASE
DROP DATABASE IF EXISTS mentor_portal;
CREATE DATABASE mentor_portal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE mentor_portal;

-- 2. USERS

CREATE TABLE users (
    id              INT           NOT NULL AUTO_INCREMENT,
    full_name       VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL,
    password        VARCHAR(255)  NOT NULL,
    role            ENUM('admin','mentor','mentee') NOT NULL DEFAULT 'mentee',
    profile_picture VARCHAR(255)  DEFAULT NULL,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    is_online       TINYINT(1)    NOT NULL DEFAULT 0,
    last_seen       DATETIME      DEFAULT NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_users_email (email),
    KEY     idx_users_role    (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. MENTOR-MENTEE ASSIGNMENTS  (pairings)

CREATE TABLE assignments (
    id          INT        NOT NULL AUTO_INCREMENT,
    mentor_id   INT        NOT NULL,
    mentee_id   INT        NOT NULL,
    assigned_by INT        NOT NULL,
    assigned_at TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    notes       TEXT       DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pair (mentor_id, mentee_id),
    KEY idx_asgn_mentor (mentor_id),
    KEY idx_asgn_mentee (mentee_id),
    CONSTRAINT fk_asgn_mentor FOREIGN KEY (mentor_id)   REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_asgn_mentee FOREIGN KEY (mentee_id)   REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_asgn_by     FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 4. CONVERSATIONS  (one per assignment)

CREATE TABLE conversations (
    id            INT       NOT NULL AUTO_INCREMENT,
    assignment_id INT       NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_conv_asgn (assignment_id),
    CONSTRAINT fk_conv_asgn FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 5. MESSAGES  (text + file attachments)

CREATE TABLE messages (
    id              INT          NOT NULL AUTO_INCREMENT,
    conversation_id INT          NOT NULL,
    sender_id       INT          NOT NULL,
    message_text    TEXT         DEFAULT NULL,
    message_type    ENUM('text','file') NOT NULL DEFAULT 'text',
    attachment_path VARCHAR(255) DEFAULT NULL,
    attachment_name VARCHAR(255) DEFAULT NULL,
    attachment_size INT          DEFAULT NULL,
    attachment_type VARCHAR(100) DEFAULT NULL,
    is_read         TINYINT(1)   NOT NULL DEFAULT 0,
    sent_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_msg_conv   (conversation_id),
    KEY idx_msg_sender (sender_id),
    CONSTRAINT fk_msg_conv   FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id)       REFERENCES users(id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 6. TASKS 

CREATE TABLE tasks (
    id            INT          NOT NULL AUTO_INCREMENT,
    assignment_id INT          NOT NULL,
    mentor_id     INT          NOT NULL,
    mentee_id     INT          NOT NULL,
    title         VARCHAR(255) NOT NULL,
    description   TEXT         DEFAULT NULL,
    due_date      DATETIME     DEFAULT NULL,
    points        INT          DEFAULT NULL,
    status        ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tasks_mentor (mentor_id),
    KEY idx_tasks_mentee (mentee_id),
    KEY idx_tasks_asgn   (assignment_id),
    CONSTRAINT fk_task_asgn   FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_task_mentor FOREIGN KEY (mentor_id)     REFERENCES users(id)       ON DELETE CASCADE,
    CONSTRAINT fk_task_mentee FOREIGN KEY (mentee_id)     REFERENCES users(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TASK ATTACHMENTS  (resource files added by mentor)

CREATE TABLE task_attachments (
    id          INT          NOT NULL AUTO_INCREMENT,
    task_id     INT          NOT NULL,
    file_name   VARCHAR(255) NOT NULL,
    file_path   VARCHAR(255) NOT NULL,
    file_size   INT          DEFAULT NULL,
    file_type   VARCHAR(100) DEFAULT NULL,
    uploaded_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tattach_task (task_id),
    CONSTRAINT fk_tattach_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TASK SUBMISSIONS  (mentee responses and uploads)

CREATE TABLE task_submissions (
    id              INT          NOT NULL AUTO_INCREMENT,
    task_id         INT          NOT NULL,
    mentee_id       INT          NOT NULL,
    submission_text TEXT         DEFAULT NULL,
    file_name       VARCHAR(255) DEFAULT NULL,
    file_path       VARCHAR(255) DEFAULT NULL,
    file_size       INT          DEFAULT NULL,
    file_type       VARCHAR(100) DEFAULT NULL,
    grade           INT          DEFAULT NULL,
    feedback        TEXT         DEFAULT NULL,
    status          ENUM('not_submitted','submitted','graded','returned') NOT NULL DEFAULT 'not_submitted',
    submitted_at    TIMESTAMP    NULL DEFAULT NULL,
    graded_at       TIMESTAMP    NULL DEFAULT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_submission (task_id, mentee_id),
    KEY idx_tsub_task   (task_id),
    KEY idx_tsub_mentee (mentee_id),
    CONSTRAINT fk_tsub_task   FOREIGN KEY (task_id)   REFERENCES tasks(id)  ON DELETE CASCADE,
    CONSTRAINT fk_tsub_mentee FOREIGN KEY (mentee_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 9. ACTIVITY LOG

CREATE TABLE activity_log (
    id         INT          NOT NULL AUTO_INCREMENT,
    user_id    INT          NOT NULL,
    action     VARCHAR(255) NOT NULL,
    details    TEXT         DEFAULT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_actlog_user (user_id),
    KEY idx_actlog_time (created_at),
    CONSTRAINT fk_actlog_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. PASSWORD RESET TOKENS

CREATE TABLE password_resets (
    id         INT          NOT NULL AUTO_INCREMENT,
    email      VARCHAR(150) NOT NULL,
    token      VARCHAR(255) NOT NULL,
    expires_at DATETIME     NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pwreset_email (email),
    KEY idx_pwreset_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 11. NOTIFICATIONS SEEN TRACKING

CREATE TABLE notifications_seen (
    id         INT          NOT NULL AUTO_INCREMENT,
    user_id    INT          NOT NULL,
    ref_type   VARCHAR(30)  NOT NULL,  -- 'task', 'grade', 'submission'
    ref_id     INT          NOT NULL,  -- task_id or task_submission.id
    seen_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_seen (user_id, ref_type, ref_id),
    KEY idx_seen_user (user_id),
    CONSTRAINT fk_seen_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
--  SAMPLE DATA
--  All passwords = "password"
--  bcrypt hash of "password":
--  $2y$10$73B.rxT3jRL.0o3I9.IqkONQE/KFzSrveWvY8malSKXI2AasiyywW
-- ================================================================

-- ----------------------------------------------------------------
-- Users
-- ----------------------------------------------------------------
INSERT INTO users (id, full_name, email, password, role) VALUES
(1, 'System Admin',        'admin@mentorportal.com',    '$2y$10$73B.rxT3jRL.0o3I9.IqkONQE/KFzSrveWvY8malSKXI2AasiyywW', 'admin'),
(2, 'Dr. Sarah Johnson',   'sarah.j@mentorportal.com',  '$2y$10$73B.rxT3jRL.0o3I9.IqkONQE/KFzSrveWvY8malSKXI2AasiyywW', 'mentor'),
(3, 'Prof. Michael Chen',  'michael.c@mentorportal.com','$2y$10$73B.rxT3jRL.0o3I9.IqkONQE/KFzSrveWvY8malSKXI2AasiyywW', 'mentor'),
(4, 'Dr. Emily Rodriguez', 'emily.r@mentorportal.com',  '$2y$10$73B.rxT3jRL.0o3I9.IqkONQE/KFzSrveWvY8malSKXI2AasiyywW', 'mentor'),
(5, 'Alex Thompson',       'alex.t@mentorportal.com',   '$2y$10$73B.rxT3jRL.0o3I9.IqkONQE/KFzSrveWvY8malSKXI2AasiyywW', 'mentee'),
(6, 'Jamie Wilson',        'jamie.w@mentorportal.com',  '$2y$10$73B.rxT3jRL.0o3I9.IqkONQE/KFzSrveWvY8malSKXI2AasiyywW', 'mentee'),
(7, 'Sam Parker',          'sam.p@mentorportal.com',    '$2y$10$73B.rxT3jRL.0o3I9.IqkONQE/KFzSrveWvY8malSKXI2AasiyywW', 'mentee'),
(8, 'Chris Martinez',      'chris.m@mentorportal.com',  '$2y$10$73B.rxT3jRL.0o3I9.IqkONQE/KFzSrveWvY8malSKXI2AasiyywW', 'mentee');

-- ----------------------------------------------------------------
-- Assignments
-- ----------------------------------------------------------------
INSERT INTO assignments (id, mentor_id, mentee_id, assigned_by) VALUES
(1, 2, 5, 1),
(2, 2, 6, 1),
(3, 3, 7, 1),
(4, 4, 8, 1);

-- ----------------------------------------------------------------
-- Conversations
-- ----------------------------------------------------------------
INSERT INTO conversations (id, assignment_id) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4);

-- ----------------------------------------------------------------
-- Messages
-- ----------------------------------------------------------------
INSERT INTO messages (conversation_id, sender_id, message_text, message_type, is_read) VALUES
(1, 2, 'Hello Alex! Welcome to our mentoring program. I am looking forward to working with you this semester.',        'text', 1),
(1, 5, 'Thank you Dr. Johnson! I am really excited to get started. What should I focus on first?',                     'text', 1),
(1, 2, 'Great attitude! Let us begin with a research overview. I will assign your first task shortly.',                'text', 1),
(1, 5, 'Sounds perfect. I will be ready!',                                                                              'text', 0),
(2, 3, 'Hi Jamie, I have reviewed your research proposal. Very promising work, a few revisions needed.',               'text', 1),
(2, 6, 'Thank you Professor Chen! Could you point out the areas that need improvement?',                               'text', 1),
(2, 3, 'Mainly the methodology section. I have created a task with detailed feedback. Please check your Tasks page.',  'text', 0),
(3, 3, 'Welcome Sam! I am Prof. Chen and I will be your mentor. Let us schedule our first meeting soon.',              'text', 1),
(3, 7, 'Hello Professor! I am available any weekday afternoon.',                                                        'text', 0),
(4, 4, 'Hi Chris! I am Dr. Rodriguez. I have assigned your first task, please review it when you get a chance.',      'text', 1),
(4, 8, 'Thank you Dr. Rodriguez! I will check it right away.',                                                          'text', 1);

-- ----------------------------------------------------------------
-- Tasks
-- ----------------------------------------------------------------
INSERT INTO tasks (id, assignment_id, mentor_id, mentee_id, title, description, due_date, points, status) VALUES

(1, 1, 2, 5,
 'Introduction to Research Methods',
 'Read the attached overview and write a 500-word summary covering:\n1. The key research methodologies discussed\n2. Which methodology suits your project best and why\n3. Any questions you have after reading\n\nSubmit as a Word document or plain text.',
 DATE_ADD(NOW(), INTERVAL 7 DAY), 100, 'published'),

(2, 1, 2, 5,
 'Literature Review – First Draft',
 'Conduct a literature review on your chosen research topic. Your submission should include:\n- Minimum 8 academic sources\n- APA or MLA citation format\n- 800 to 1000 words\n- A brief critique of each source\n\nThis is a graded assignment. Quality of sources matters.',
 DATE_ADD(NOW(), INTERVAL 14 DAY), 150, 'published'),

(3, 2, 3, 6,
 'Methodology Section Revision',
 'Please revise your research proposal methodology section based on our discussion.\n\nKey areas to address:\n- Clarify your data collection approach\n- Justify your sample size selection\n- Address potential biases\n- Add a timeline for data collection\n\nTarget length: 600 to 800 words.',
 DATE_ADD(NOW(), INTERVAL 5 DAY), 120, 'published'),

(4, 3, 3, 7,
 'Research Topic Proposal',
 'Submit a 300 to 400 word research topic proposal that includes:\n1. Your proposed research question\n2. Why this topic is relevant and interesting\n3. A brief outline of how you plan to approach it\n4. Any initial resources you have found\n\nThis will form the foundation of your semester project.',
 DATE_ADD(NOW(), INTERVAL 10 DAY), 80, 'published'),

(5, 4, 4, 8,
 'Academic Goal Setting',
 'Complete the following goal-setting exercise:\n1. List your top 3 academic goals for this semester\n2. For each goal describe:\n   a) Why it matters to you\n   b) What steps you will take\n   c) How you will measure success\n3. Identify one challenge you anticipate and how you plan to overcome it\n\nThis helps me tailor our sessions to your needs.',
 DATE_ADD(NOW(), INTERVAL 3 DAY), 50, 'published'),

(6, 4, 4, 8,
 'Weekly Progress Report – Week 1',
 'Submit your first weekly progress report covering:\n- What you worked on this week\n- What you accomplished\n- What challenges did you face\n- What is your plan for next week\n\nKeep it concise, bullet points are fine. This is a recurring assignment.',
 DATE_ADD(NOW(), INTERVAL 7 DAY), 30, 'published');

-- ----------------------------------------------------------------
-- Task Submissions
-- ----------------------------------------------------------------
INSERT INTO task_submissions (task_id, mentee_id, submission_text, status, submitted_at, grade, feedback, graded_at) VALUES

(1, 5,
 'After reading the overview I found that qualitative and quantitative methods each have distinct advantages. For my project on student learning outcomes I believe a mixed-methods approach would be most suitable because it allows me to gather both measurable data and personal insights from participants. My main question is how large the sample size should be for statistical significance.',
 'graded',
 DATE_SUB(NOW(), INTERVAL 2 DAY),
 88,
 'Excellent summary Alex! Your reasoning for choosing a mixed-methods approach is sound. For sample size we will cover that in our next session, generally 30 or more participants for quantitative validity. Well done!',
 DATE_SUB(NOW(), INTERVAL 1 DAY)),

(2, 5,
 'I have compiled 9 sources for my literature review on student engagement in online learning environments. The sources span 2018 to 2024 and cover both theoretical frameworks and empirical studies.',
 'submitted',
 DATE_SUB(NOW(), INTERVAL 1 DAY),
 NULL, NULL, NULL),

(3, 6, NULL, 'not_submitted', NULL, NULL, NULL, NULL),

(4, 7,
 'Proposed Research Question: How does peer collaboration in project-based learning affect academic performance among undergraduate students?\n\nThis topic is relevant because collaborative learning is increasingly being adopted in universities yet its effectiveness varies significantly across disciplines.',
 'submitted',
 DATE_SUB(NOW(), INTERVAL 3 HOUR),
 NULL, NULL, NULL),

(5, 8,
 'Goal 1: Improve my academic writing skills\n- Why: My essays lack structure\n- Steps: Practice weekly writing exercises, attend the writing centre\n- Measure: Feedback scores improve each submission\n\nGoal 2: Complete all tasks on time\n- Why: Time management is my weakness\n- Steps: Use a planner, set reminders\n- Measure: Zero late submissions this semester\n\nGoal 3: Develop a strong research proposal\n- Why: Foundation for final year project\n- Steps: Weekly meetings with Dr. Rodriguez, read 2 papers per week\n- Measure: Proposal approved by week 8',
 'graded',
 DATE_SUB(NOW(), INTERVAL 4 DAY),
 47,
 'Wonderful reflection Chris! Your goals are specific and measurable. I especially like how you have identified time management as a challenge and have concrete steps to address it. Let us track these goals in our weekly check-ins.',
 DATE_SUB(NOW(), INTERVAL 3 DAY)),

(6, 8, NULL, 'not_submitted', NULL, NULL, NULL, NULL);

-- ----------------------------------------------------------------
-- Activity Log
-- ----------------------------------------------------------------
INSERT INTO activity_log (user_id, action, details) VALUES
(1, 'Assignment Created', 'Assigned Dr. Sarah Johnson to Alex Thompson'),
(1, 'Assignment Created', 'Assigned Dr. Sarah Johnson to Jamie Wilson'),
(1, 'Assignment Created', 'Assigned Prof. Michael Chen to Sam Parker'),
(1, 'Assignment Created', 'Assigned Dr. Emily Rodriguez to Chris Martinez'),
(2, 'Task Created',       'Created task: Introduction to Research Methods for Alex Thompson'),
(2, 'Task Created',       'Created task: Literature Review First Draft for Alex Thompson'),
(3, 'Task Created',       'Created task: Methodology Section Revision for Jamie Wilson'),
(3, 'Task Created',       'Created task: Research Topic Proposal for Sam Parker'),
(4, 'Task Created',       'Created task: Academic Goal Setting for Chris Martinez'),
(4, 'Task Created',       'Created task: Weekly Progress Report Week 1 for Chris Martinez'),
(5, 'Task Submitted',     'Submitted task: Introduction to Research Methods'),
(5, 'Task Submitted',     'Submitted task: Literature Review First Draft'),
(8, 'Task Submitted',     'Submitted task: Academic Goal Setting'),
(7, 'Task Submitted',     'Submitted task: Research Topic Proposal'),
(2, 'Login',              'User logged in'),
(3, 'Login',              'User logged in'),
(5, 'Login',              'User logged in'),
(8, 'Login',              'User logged in');

-- ================================================================
--  LOGIN CREDENTIALS QUICK REFERENCE
-- ================================================================
--
--  Role    | Email                         | Password
--  --------+-------------------------------+---------
--  Admin   | admin@mentorportal.com        | password
--  Mentor  | sarah.j@mentorportal.com      | password
--  Mentor  | michael.c@mentorportal.com    | password
--  Mentor  | emily.r@mentorportal.com      | password
--  Mentee  | alex.t@mentorportal.com       | password
--  Mentee  | jamie.w@mentorportal.com      | password
--  Mentee  | sam.p@mentorportal.com        | password
--  Mentee  | chris.m@mentorportal.com      | password