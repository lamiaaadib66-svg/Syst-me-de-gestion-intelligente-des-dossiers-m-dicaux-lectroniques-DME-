<?php
require_once 'config.php';
require_once 'database.php';

// Récupérer l'URL et la méthode
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Obtenir les segments de l'URL
$segments = explode('/', trim($uri, '/'));

// Ignorer le répertoire racine si présent
$base_path = 'dme-pro';
if (isset($segments[0]) && $segments[0] === $base_path) {
    array_shift($segments);
}

// Route API
$endpoint = $segments[0] ?? '';
$param1 = $segments[1] ?? '';
$param2 = $segments[2] ?? '';

// Gérer les différentes routes
switch(true) {
    case $endpoint === '' && $method === 'GET':
        require_once 'index.html';
        break;
        
    case $endpoint === 'stats' && $method === 'GET':
        getStats();
        break;
        
    case $endpoint === 'patients' && $method === 'GET':
        getPatients($param1);
        break;
        
    case $endpoint === 'patients' && $method === 'POST':
        createPatient();
        break;
        
    case $endpoint === 'patients' && $method === 'PUT':
        updatePatient($param1);
        break;
        
    case $endpoint === 'patients' && $method === 'DELETE':
        deletePatient($param1);
        break;
        
    case $endpoint === 'rendezvous' && $method === 'GET':
        getRendezVous();
        break;
        
    case $endpoint === 'consultations' && $method === 'GET':
        getConsultations();
        break;
        
    case $endpoint === 'prescriptions' && $method === 'GET':
        getPrescriptions();
        break;
        
    case $endpoint === 'alertes' && $method === 'GET':
        getAlertes();
        break;
        
    case $endpoint === 'medecins' && $method === 'GET':
        getMedecins();
        break;
        
    case $endpoint === 'specialites' && $method === 'GET':
        getSpecialites();
        break;
        
    default:
        handle_error('Endpoint non trouvé', 404);
}

// ==================== FONCTIONS API ====================

/**
 * Récupérer les statistiques pour le tableau de bord
 */
function getStats() {
    $db = Database::getInstance();
    
    try {
        // Nombre total de patients
        $patients = $db->queryOne("SELECT COUNT(*) as total FROM patients");
        
        // Rendez-vous du jour
        $today = date('Y-m-d');
        $rdv_today = $db->queryOne("
            SELECT COUNT(*) as total 
            FROM rendezvous 
            WHERE DATE(date_rdv) = ? 
            AND statut IN ('planifie', 'confirme')
        ", [$today], "s");
        
        // Consultations ce mois
        $first_day = date('Y-m-01');
        $last_day = date('Y-m-t');
        $consult_mois = $db->queryOne("
            SELECT COUNT(*) as total 
            FROM consultations 
            WHERE DATE(date_consultation) BETWEEN ? AND ?
        ", [$first_day, $last_day], "ss");
        
        // Prescriptions actives
        $prescriptions = $db->queryOne("
            SELECT COUNT(*) as total 
            FROM prescriptions 
            WHERE statut = 'en_cours'
        ");
        
        // Dernières alertes non traitées
        $alertes = $db->query("
            SELECT * FROM alertes 
            WHERE traite = 0 
            ORDER BY FIELD(priorite, 'haute', 'moyenne', 'basse'), date_alerte DESC 
            LIMIT 5
        ");
        
        // Rendez-vous d'aujourd'hui avec infos patient
        $rdvs = $db->query("
            SELECT r.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                   p.date_naissance, m.nom as medecin_nom, m.prenom as medecin_prenom
            FROM rendezvous r
            LEFT JOIN patients p ON r.patient_id = p.id
            LEFT JOIN medecins m ON r.medecin_id = m.id
            WHERE DATE(r.date_rdv) = ?
            ORDER BY r.date_rdv ASC
        ", [$today], "s");
        
        $stats = [
            'statistiques' => [
                'patients' => $patients['total'] ?? 0,
                'rendezvous_du_jour' => $rdv_today['total'] ?? 0,
                'consultations_mois' => $consult_mois['total'] ?? 0,
                'prescriptions_actives' => $prescriptions['total'] ?? 0
            ],
            'rendezvous' => $rdvs,
            'alertes' => $alertes
        ];
        
        json_response($stats);
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}

/**
 * Récupérer les patients
 */
function getPatients($id = null) {
    $db = Database::getInstance();
    
    try {
        if ($id) {
            // Un seul patient
            $patient = $db->queryOne("
                SELECT p.*, 
                       (SELECT MAX(date_consultation) 
                        FROM consultations c 
                        WHERE c.patient_id = p.id) as derniere_consultation,
                       TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) as age
                FROM patients p 
                WHERE p.id = ?
            ", [$id], "i");
            
            if (!$patient) {
                handle_error('Patient non trouvé', 404);
            }
            
            json_response(['patient' => $patient]);
            
        } else {
            // Tous les patients avec recherche
            $search = $_GET['search'] ?? '';
            $params = [];
            $types = "";
            
            $sql = "
                SELECT p.*, 
                       (SELECT MAX(date_consultation) 
                        FROM consultations c 
                        WHERE c.patient_id = p.id) as derniere_consultation,
                       TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) as age
                FROM patients p 
                WHERE 1=1
            ";
            
            if (!empty($search)) {
                $sql .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.matricule LIKE ?)";
                $searchTerm = "%" . $search . "%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                $types .= "sss";
            }
            
            $sql .= " ORDER BY p.nom, p.prenom LIMIT 100";
            
            $patients = $db->query($sql, $params, $types);
            
            json_response(['patients' => $patients]);
        }
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}

/**
 * Créer un nouveau patient
 */
function createPatient() {
    $db = Database::getInstance();
    
    try {
        // Récupérer les données du POST
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            handle_error('Données invalides', 400);
        }
        
        // Validation des champs requis
        $required = ['nom', 'prenom', 'date_naissance', 'genre'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                handle_error("Le champ $field est requis", 400);
            }
        }
        
        // Générer un matricule unique
        $matricule = 'PAT' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        // Insérer le patient
        $result = $db->query("
            INSERT INTO patients (matricule, nom, prenom, date_naissance, genre, 
                                 telephone, email, adresse, groupe_sanguin, allergies, antecedents)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $matricule,
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'],
            $data['genre'],
            $data['telephone'] ?? null,
            $data['email'] ?? null,
            $data['adresse'] ?? null,
            $data['groupe_sanguin'] ?? null,
            $data['allergies'] ?? null,
            $data['antecedents'] ?? null
        ], "sssssssssss");
        
        if ($result['affected_rows'] > 0) {
            $patientId = $result['insert_id'];
            $patient = $db->queryOne("SELECT * FROM patients WHERE id = ?", [$patientId], "i");
            
            json_response(['patient' => $patient], 201, 'Patient créé avec succès');
        } else {
            handle_error('Erreur lors de la création du patient');
        }
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}

/**
 * Mettre à jour un patient
 */
function updatePatient($id) {
    $db = Database::getInstance();
    
    try {
        if (!$id) {
            handle_error('ID patient requis', 400);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            handle_error('Données invalides', 400);
        }
        
        // Construire la requête dynamiquement
        $fields = [];
        $params = [];
        $types = "";
        
        $allowedFields = ['nom', 'prenom', 'date_naissance', 'genre', 'telephone', 
                         'email', 'adresse', 'groupe_sanguin', 'allergies', 'antecedents'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= "s";
            }
        }
        
        if (empty($fields)) {
            handle_error('Aucune donnée à mettre à jour', 400);
        }
        
        // Ajouter l'ID à la fin
        $params[] = $id;
        $types .= "i";
        
        $sql = "UPDATE patients SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $result = $db->query($sql, $params, $types);
        
        if ($result['affected_rows'] > 0) {
            $patient = $db->queryOne("SELECT * FROM patients WHERE id = ?", [$id], "i");
            json_response(['patient' => $patient], 200, 'Patient mis à jour avec succès');
        } else {
            json_response(null, 200, 'Aucune modification effectuée');
        }
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}

/**
 * Supprimer un patient
 */
function deletePatient($id) {
    $db = Database::getInstance();
    
    try {
        if (!$id) {
            handle_error('ID patient requis', 400);
        }
        
        // Vérifier si le patient existe
        $patient = $db->queryOne("SELECT * FROM patients WHERE id = ?", [$id], "i");
        
        if (!$patient) {
            handle_error('Patient non trouvé', 404);
        }
        
        // Supprimer le patient (cascade configurée dans la base de données)
        $result = $db->query("DELETE FROM patients WHERE id = ?", [$id], "i");
        
        if ($result['affected_rows'] > 0) {
            json_response(null, 200, 'Patient supprimé avec succès');
        } else {
            handle_error('Erreur lors de la suppression');
        }
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}

/**
 * Récupérer les rendez-vous
 */
function getRendezVous() {
    $db = Database::getInstance();
    
    try {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $sql = "
            SELECT r.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                   p.date_naissance, m.nom as medecin_nom, m.prenom as medecin_prenom
            FROM rendezvous r
            LEFT JOIN patients p ON r.patient_id = p.id
            LEFT JOIN medecins m ON r.medecin_id = m.id
        ";
        
        $params = [];
        $types = "";
        
        if (!empty($date)) {
            $sql .= " WHERE DATE(r.date_rdv) = ?";
            $params[] = $date;
            $types .= "s";
        }
        
        $sql .= " ORDER BY r.date_rdv ASC";
        
        $rdvs = $db->query($sql, $params, $types);
        
        json_response(['rendezvous' => $rdvs]);
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}

/**
 * Récupérer les consultations
 */
function getConsultations() {
    $db = Database::getInstance();
    
    try {
        $limit = $_GET['limit'] ?? 20;
        $patient_id = $_GET['patient_id'] ?? null;
        
        $sql = "
            SELECT c.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                   m.nom as medecin_nom, m.prenom as medecin_prenom
            FROM consultations c
            LEFT JOIN patients p ON c.patient_id = p.id
            LEFT JOIN medecins m ON c.medecin_id = m.id
        ";
        
        $params = [];
        $types = "";
        
        if ($patient_id) {
            $sql .= " WHERE c.patient_id = ?";
            $params[] = $patient_id;
            $types .= "i";
        }
        
        $sql .= " ORDER BY c.date_consultation DESC LIMIT ?";
        $params[] = (int)$limit;
        $types .= "i";
        
        $consultations = $db->query($sql, $params, $types);
        
        json_response(['consultations' => $consultations]);
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}

/**
 * Récupérer les prescriptions
 */
function getPrescriptions() {
    $db = Database::getInstance();
    
    try {
        $status = $_GET['status'] ?? null;
        
        $sql = "
            SELECT pr.*, p.nom as patient_nom, p.prenom as patient_prenom,
                   m.nom as medecin_nom, m.prenom as medecin_prenom
            FROM prescriptions pr
            LEFT JOIN patients p ON pr.patient_id = p.id
            LEFT JOIN medecins m ON pr.medecin_id = m.id
            WHERE 1=1
        ";
        
        $params = [];
        $types = "";
        
        if ($status) {
            $sql .= " AND pr.statut = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $sql .= " ORDER BY pr.date_debut DESC";
        
        $prescriptions = $db->query($sql, $params, $types);
        
        json_response(['prescriptions' => $prescriptions]);
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}

/**
 * Récupérer les alertes
 */
function getAlertes() {
    $db = Database::getInstance();
    
    try {
        $sql = "
            SELECT a.*, p.nom as patient_nom, p.prenom as patient_prenom
            FROM alertes a
            LEFT JOIN patients p ON a.patient_id = p.id
            WHERE a.traite = 0
            ORDER BY FIELD(a.priorite, 'haute', 'moyenne', 'basse'), a.date_alerte DESC
            LIMIT 10
        ";
        
        $alertes = $db->query($sql);
        
        json_response(['alertes' => $alertes]);
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}

/**
 * Récupérer les médecins
 */
function getMedecins() {
    $db = Database::getInstance();
    
    try {
        $sql = "
            SELECT * FROM medecins 
            WHERE statut = 'actif' 
            ORDER BY nom, prenom
        ";
        
        $medecins = $db->query($sql);
        
        json_response(['medecins' => $medecins]);
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}

/**
 * Récupérer les spécialités
 */
function getSpecialites() {
    $db = Database::getInstance();
    
    try {
        $sql = "SELECT * FROM specialites ORDER BY nom";
        
        $specialites = $db->query($sql);
        
        json_response(['specialites' => $specialites]);
        
    } catch (Exception $e) {
        handle_error($e->getMessage());
    }
}
?>