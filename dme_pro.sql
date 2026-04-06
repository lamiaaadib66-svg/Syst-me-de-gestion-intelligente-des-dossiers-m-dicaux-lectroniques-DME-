-- Création de la base de données
CREATE DATABASE IF NOT EXISTS dme_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dme_pro;

-- Table des patients
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    matricule VARCHAR(20) UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    genre ENUM('M', 'F') NOT NULL,
    telephone VARCHAR(20),
    email VARCHAR(100),
    adresse TEXT,
    groupe_sanguin VARCHAR(5),
    allergies TEXT,
    antecedents TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des médecins
CREATE TABLE medecins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    matricule VARCHAR(20) UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    specialite VARCHAR(100),
    telephone VARCHAR(20),
    email VARCHAR(100),
    photo VARCHAR(255),
    diplome TEXT,
    experience INT,
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des rendez-vous
CREATE TABLE rendezvous (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    medecin_id INT,
    date_rdv DATETIME NOT NULL,
    duree INT DEFAULT 30, -- en minutes
    motif VARCHAR(255),
    salle VARCHAR(50),
    statut ENUM('planifie', 'confirme', 'annule', 'termine') DEFAULT 'planifie',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id) REFERENCES medecins(id) ON DELETE SET NULL
);

-- Table des consultations
CREATE TABLE consultations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    rdv_id INT,
    date_consultation DATETIME NOT NULL,
    motif VARCHAR(255),
    diagnostic TEXT,
    traitement TEXT,
    examens TEXT,
    notes TEXT,
    statut ENUM('en_cours', 'termine') DEFAULT 'en_cours',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id) REFERENCES medecins(id) ON DELETE CASCADE,
    FOREIGN KEY (rdv_id) REFERENCES rendezvous(id) ON DELETE SET NULL
);

-- Table des prescriptions
CREATE TABLE prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    consultation_id INT,
    medicament VARCHAR(255) NOT NULL,
    dosage VARCHAR(100),
    frequence VARCHAR(100),
    duree VARCHAR(50),
    date_debut DATE NOT NULL,
    date_fin DATE,
    statut ENUM('en_cours', 'termine', 'suspendu') DEFAULT 'en_cours',
    instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id) REFERENCES medecins(id) ON DELETE CASCADE,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE SET NULL
);

-- Table des alertes
CREATE TABLE alertes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    patient_id INT,
    priorite ENUM('haute', 'moyenne', 'basse') DEFAULT 'moyenne',
    date_alerte DATE NOT NULL,
    traite BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Table des spécialités
CREATE TABLE specialites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    nb_medecins INT DEFAULT 0
);

-- Insertion des données de test
-- Patients
INSERT INTO patients (matricule, nom, prenom, date_naissance, genre, telephone, groupe_sanguin) VALUES
('PAT001', 'Martin', 'Léa', '1990-05-15', 'F', '07 98 76 54 32', 'A+'),
('PAT002', 'Benali', 'Karim', '1985-09-20', 'M', '06 61 23 45 67', 'O+'),
('PAT003', 'Martin', 'Sarah', '1992-03-12', 'F', '06 54 32 10 98', 'B-'),
('PAT004', 'Dubois', 'Pierre', '1957-07-05', 'M', '07 77 88 99 00', 'AB+'),
('PAT005', 'Lambert', 'Sophie', '1997-04-25', 'F', '06 33 44 55 66', 'O+'),
('PAT006', 'Leclerc', 'Marc', '1980-11-30', 'M', '07 11 22 33 44', 'A-');

-- Médecins
INSERT INTO medecins (matricule, nom, prenom, specialite, telephone, experience) VALUES
('MED001', 'Dupont', 'Alice', 'Cardiologie', '01 23 45 67 89', 18),
('MED002', 'Benali', 'Karim', 'Pédiatrie', '01 34 56 78 90', 12),
('MED003', 'Martin', 'Sarah', 'Dermatologie', '01 45 67 89 01', 15),
('MED004', 'El Amrani', 'Mohammed', 'Radiologie', '01 56 78 90 12', 10),
('MED005', 'Khalil', 'Nadia', 'Pneumologie', '01 67 89 01 23', 8),
('MED006', 'Leroy', 'Paul', 'Neurologie', '01 78 90 12 34', 14);

-- Spécialités
INSERT INTO specialites (nom, description, icon, nb_medecins) VALUES
('Cardiologie', 'Électrocardiogrammes, échographies Doppler, coronarographies, holter tensionnel.', 'fa-heart-pulse', 3),
('Pneumologie', 'Asthme, BPCO, spirométrie, bronchoscopies, tests d''effort respiratoire.', 'fa-lungs', 2),
('Dermatologie', 'Acné, psoriasis, dermatoscopie, laser, cryothérapie, chirurgie cutanée.', 'fa-hand-holding-medical', 2),
('Neurologie', 'Migraines, épilepsie, IRM cérébrale, électroencéphalogramme, EMG.', 'fa-brain', 2),
('Pédiatrie', 'Vaccins, bilans croissance, ORL pédiatrique, développement psychomoteur.', 'fa-baby', 4),
('Radiologie', 'Scanner, IRM, échographie 3D, mammographie, ostéodensitométrie.', 'fa-x-ray', 3);

-- Rendez-vous
INSERT INTO rendezvous (patient_id, medecin_id, date_rdv, duree, motif, salle, statut) VALUES
(1, 1, '2025-12-10 09:00:00', 30, 'Bilan annuel + ECG', 'Salle 3', 'confirme'),
(5, 2, '2025-12-10 10:30:00', 45, 'Suivi grossesse + échographie', 'Salle 1', 'confirme'),
(6, 1, '2025-12-10 11:45:00', 20, 'Consultation urgence - Douleurs abdominales', 'Salle Urgences', 'planifie'),
(1, 1, '2025-12-10 14:00:00', 15, 'Contrôle post-angine', 'Salle 2', 'planifie');

-- Consultations
INSERT INTO consultations (patient_id, medecin_id, date_consultation, motif, diagnostic, statut) VALUES
(1, 1, '2025-12-09 10:45:00', 'Maux de gorge, fièvre', 'Angine streptococcique', 'termine'),
(2, 2, '2025-12-08 15:20:00', 'Bilan croissance + vaccins', 'Développement normal', 'termine'),
(3, 3, '2025-12-07 11:30:00', 'Dermatite séborrhéique cuir chevelu', 'Dermatite séborrhéique modérée', 'termine'),
(4, 1, '2025-12-06 09:00:00', 'Contrôle hypertension', 'Hypertension artérielle stade 2', 'termine'),
(5, 5, '2025-12-05 14:00:00', 'Suivi 6ème mois grossesse', 'Grossesse évolutive normale', 'termine'),
(6, 6, '2025-12-04 16:30:00', 'Céphalées persistantes', 'Migraine sans aura', 'en_cours');

-- Prescriptions
INSERT INTO prescriptions (patient_id, medecin_id, medicament, dosage, frequence, date_debut, date_fin, statut) VALUES
(1, 1, 'Amoxicilline 500mg', '1 comprimé', '3x/jour', '2025-12-09', '2025-12-16', 'en_cours'),
(2, 2, 'Paracétamol 500mg', '1 comprimé', 'Si besoin', '2025-12-08', '2026-01-07', 'en_cours'),
(3, 3, 'Isotrétinoïne 20mg', '1 gélule', '1x/jour', '2025-12-07', '2026-03-06', 'en_cours'),
(4, 1, 'Amlodipine 5mg', '1 comprimé', '1x/jour', '2025-11-01', '2026-02-01', 'termine'),
(5, 5, 'Levothyrox 75μg', '1 comprimé', '1x/jour', '2025-10-01', '2026-04-01', 'en_cours');

-- Alertes
INSERT INTO alertes (type, description, patient_id, priorite, date_alerte) VALUES
('Prescription', 'Prescription à renouveler avant le 15/12', 2, 'haute', '2025-12-10'),
('Résultats', 'Résultats d''analyses disponibles', 3, 'moyenne', '2025-12-09'),
('Rappel', 'Vaccin à effectuer dans 1 mois', 2, 'basse', '2025-12-08');

-- Création des index pour optimiser les performances
CREATE INDEX idx_rdv_date ON rendezvous(date_rdv);
CREATE INDEX idx_rdv_statut ON rendezvous(statut);
CREATE INDEX idx_consult_date ON consultations(date_consultation);
CREATE INDEX idx_patient_nom ON patients(nom, prenom);