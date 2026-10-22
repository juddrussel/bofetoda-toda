SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS bofetoda
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bofetoda;

CREATE TABLE IF NOT EXISTS barangays (
    barangay_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS colors (
    color_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reject_reasons (
    reject_reason_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reason           VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS officers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    officer_id      VARCHAR(20)  NOT NULL UNIQUE,
    name            VARCHAR(100) NOT NULL,
    initials        VARCHAR(5)   NOT NULL,
    password        VARCHAR(255) NOT NULL,
    role            ENUM('president','secretary') NOT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS drivers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_id       VARCHAR(20)  NOT NULL UNIQUE,
    name            VARCHAR(100) NOT NULL,
    age             TINYINT UNSIGNED,
    birthday        DATE,
    contact_no      VARCHAR(20),
    address         VARCHAR(255),
    barangay_id     INT UNSIGNED NOT NULL,
    body_no         VARCHAR(20)  NOT NULL,
    color_id        INT UNSIGNED NOT NULL,
    renewal_status  ENUM('-','Pending Renewal','Validated Renewal',
                          'Approved Renewal','Rejected Renewal')
                                 NOT NULL DEFAULT '-',
    password        VARCHAR(255) NOT NULL,
    is_archived     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_drivers_barangay FOREIGN KEY (barangay_id)
        REFERENCES barangays(barangay_id) ON UPDATE CASCADE,
    CONSTRAINT fk_drivers_color FOREIGN KEY (color_id)
        REFERENCES colors(color_id) ON UPDATE CASCADE,
    INDEX idx_drivers_barangay (barangay_id),
    INDEX idx_drivers_status (renewal_status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS applications (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    app_no              VARCHAR(20) NOT NULL UNIQUE,
    driver_id           VARCHAR(20) NOT NULL,
    announcement_id     INT NULL DEFAULT NULL,
    name                VARCHAR(100) NOT NULL,
    age                 TINYINT UNSIGNED,
    birthday            DATE,
    contact_no          VARCHAR(20),
    address             VARCHAR(255),
    barangay_id         INT UNSIGNED NOT NULL,
    body_no             VARCHAR(20) NOT NULL,
    color_id            INT UNSIGNED NOT NULL,
    status              ENUM('Pending','Validated','For Approval',
                              'Approved','Rejected') NOT NULL DEFAULT 'Pending',
    submitted_at        DATETIME NOT NULL,
    validated_by        VARCHAR(20) NULL,
    validated_at        DATETIME NULL,
    approved_by         VARCHAR(20) NULL,
    approved_at         DATETIME NULL,
    rejected_by         VARCHAR(20) NULL,
    rejected_at         DATETIME NULL,
    reject_reason_id    INT UNSIGNED NULL,
    reject_note         TEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_app_driver FOREIGN KEY (driver_id)
        REFERENCES drivers(driver_id) ON UPDATE CASCADE,
    CONSTRAINT fk_app_barangay FOREIGN KEY (barangay_id)
        REFERENCES barangays(barangay_id) ON UPDATE CASCADE,
    CONSTRAINT fk_app_color FOREIGN KEY (color_id)
        REFERENCES colors(color_id) ON UPDATE CASCADE,
    CONSTRAINT fk_app_validated_by FOREIGN KEY (validated_by)
        REFERENCES officers(officer_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_app_approved_by FOREIGN KEY (approved_by)
        REFERENCES officers(officer_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_app_rejected_by FOREIGN KEY (rejected_by)
        REFERENCES officers(officer_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_app_reject_reason FOREIGN KEY (reject_reason_id)
        REFERENCES reject_reasons(reject_reason_id) ON UPDATE CASCADE,
    INDEX idx_app_driver (driver_id),
    INDEX idx_app_status (status),
    INDEX idx_app_barangay (barangay_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS application_documents (
    document_id     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    app_no          VARCHAR(20) NOT NULL,
    doc_type        ENUM('license','or_cr','receipt','photo') NOT NULL,
    file_path       VARCHAR(255) NULL,
    is_verified     TINYINT(1)  NOT NULL DEFAULT 0,
    uploaded_at     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_app FOREIGN KEY (app_no)
        REFERENCES applications(app_no) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_app_doctype (app_no, doc_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS renewal_history (
    history_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_id       VARCHAR(20) NOT NULL,
    app_no          VARCHAR(20) NULL,
    renewal_year    YEAR NOT NULL,
    decided_at      DATE NOT NULL,
    status          ENUM('APPROVED','REJECTED') NOT NULL,
    CONSTRAINT fk_rh_driver FOREIGN KEY (driver_id)
        REFERENCES drivers(driver_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_rh_app FOREIGN KEY (app_no)
        REFERENCES applications(app_no) ON UPDATE CASCADE ON DELETE SET NULL,
    UNIQUE KEY uq_rh_app_no (app_no),
    INDEX idx_rh_driver_year (driver_id, renewal_year)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcements (
    announcement_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150) NOT NULL,
    message         TEXT NOT NULL,
    status          ENUM('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN',
    opened_date     DATE NOT NULL,
    deadline_date   DATE NOT NULL,
    created_by      VARCHAR(20) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ann_officer FOREIGN KEY (created_by)
        REFERENCES officers(officer_id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_ann_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    notification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_id       VARCHAR(20) NOT NULL,
    app_no          VARCHAR(20) NULL,
    title           VARCHAR(150) NOT NULL,
    message         TEXT NOT NULL,
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_driver FOREIGN KEY (driver_id)
        REFERENCES drivers(driver_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notif_app FOREIGN KEY (app_no)
        REFERENCES applications(app_no) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_notif_driver (driver_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action          VARCHAR(50) NOT NULL,
    driver_id       VARCHAR(20) NULL,
    officer_id      VARCHAR(20) NULL,
    role            ENUM('president','secretary') NOT NULL,
    logged_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alog_driver FOREIGN KEY (driver_id)
        REFERENCES drivers(driver_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_alog_officer FOREIGN KEY (officer_id)
        REFERENCES officers(officer_id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_alog_driver (driver_id),
    INDEX idx_alog_date (logged_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS access_logs (
    access_log_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    officer_id      VARCHAR(20) NULL,
    action          ENUM('Login','Logout','Invalid') NOT NULL,
    role            VARCHAR(20) NOT NULL,
    ip_address      VARCHAR(45) NULL,
    logged_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_acclog_officer FOREIGN KEY (officer_id)
        REFERENCES officers(officer_id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_acclog_date (logged_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

INSERT IGNORE INTO barangays (name) VALUES
('Antipona'),('Bagumbayan'),('Bambang'),('Batia'),('Biñang 1st'),('Biñang 2nd'),
('Bolacan'),('Bundukan'),('Bunlo'),('Caingin'),('Duhat'),('Igulot'),
('Lolomboy'),('Poblacion'),('Sulucan'),('Taal'),('Tambobong'),('Turo'),('Wakas');

INSERT IGNORE INTO colors (name) VALUES
('Red'),('Yellow'),('Blue'),('Brown'),('Pink'),('Orange'),('Green'),('Maroon'),('White'),('Purple');

INSERT IGNORE INTO reject_reasons (reason) VALUES
('Invalid Documents'),('Missing Requirements'),('Poor Document Quality'),('Record Mismatch');

INSERT IGNORE INTO officers (officer_id, name, initials, password, role) VALUES
('OFF-20240001', 'Avelino Conception', 'AC', 'president123', 'president'),
('OFF-20240002', 'Arturo Ventura',     'AV', 'secretary123', 'secretary');

INSERT IGNORE INTO drivers (driver_id, name, age, birthday, contact_no, address, barangay_id, body_no, color_id, renewal_status, password) VALUES
('DRV-NT0072', 'Bienvenido Roxas', 28, '1999-02-27', '09815908301', '112 Aguinaldo Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0072', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'bienvenido64'),
('DRV-NT0297', 'Ricardo Medina', 32, '1995-04-21', '09909960308', '220 Bonifacio Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0297', (SELECT color_id FROM colors WHERE name='Green'), '-', 'ricardo25'),
('DRV-NT0211', 'Estelita Rivera', 60, '1967-11-06', '09199351819', '16 Quezon Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0211', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'estelita64'),
('DRV-NT0747', 'Alexis Ramirez', 62, '1965-08-12', '09432319487', '225 Luna Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0747', (SELECT color_id FROM colors WHERE name='Maroon'), '-', 'alexis19'),
('DRV-NT0359', 'Rolando Morales', 51, '1976-03-25', '09527601895', '88 Magsaysay Avenue, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0359', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'rolando68'),
('DRV-NT0364', 'Roberto Garcia', 42, '1985-08-23', '09104974650', '241 Del Pilar Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0364', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'roberto73'),
('DRV-NT0141', 'Ricardo Del Rosario', 43, '1984-03-24', '09366712768', '72 Maligaya Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0141', (SELECT color_id FROM colors WHERE name='Green'), '-', 'ricardo45'),
('DRV-NT0005', 'Marcelo Manalo', 49, '1978-04-05', '09122330792', '68 Malvar Street, Antipona, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Antipona'), '0005', (SELECT color_id FROM colors WHERE name='Blue'), '-', 'marcelo57'),
('DRV-BG0064', 'Renato Estrada', 57, '1970-10-21', '09078666617', '163 Aguinaldo Street, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0064', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'renato36'),
('DRV-BG0896', 'Quirino Villanueva', 32, '1995-06-20', '09010928159', '7 Mabini Street, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0896', (SELECT color_id FROM colors WHERE name='Brown'), '-', 'quirino29'),
('DRV-BG0351', 'Elena Manalo', 63, '1964-06-16', '09117777412', '27 Magsaysay Avenue, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0351', (SELECT color_id FROM colors WHERE name='Pink'), '-', 'elena98'),
('DRV-BG0172', 'Maria Jimenez', 26, '2001-04-17', '09528084148', '94 Maligaya Street, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0172', (SELECT color_id FROM colors WHERE name='Orange'), '-', 'maria78'),
('DRV-BG0030', 'Amado Lacson', 57, '1970-06-21', '09393363387', '92 Magsaysay Avenue, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0030', (SELECT color_id FROM colors WHERE name='Red'), '-', 'amado70'),
('DRV-BG0640', 'Milagros Aquino', 63, '1964-06-15', '09551313735', '53 Del Pilar Street, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0640', (SELECT color_id FROM colors WHERE name='Purple'), '-', 'milagros71'),
('DRV-BG0970', 'Cesar Cabrera', 30, '1997-11-04', '09637265167', '103 Magsaysay Avenue, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0970', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'cesar31'),
('DRV-BG0819', 'Renato Reyes', 34, '1993-10-15', '09299752882', '6 Rizal Street, Bagumbayan, Bocaue, Bulacan', (SELECT barangay_id FROM barangays WHERE name='Bagumbayan'), '0819', (SELECT color_id FROM colors WHERE name='Yellow'), '-', 'renato27');
