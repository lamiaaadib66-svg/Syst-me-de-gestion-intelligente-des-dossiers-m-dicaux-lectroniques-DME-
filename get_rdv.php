
<?php
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dme_pro');

// Fonction de connexion
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        return null;
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Vérifier si l'ID est fourni
$rdv_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rdv_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID rendez-vous invalide']);
    exit();
}

// Connexion à la base de données
$conn = connectDB();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Récupérer le rendez-vous avec les informations patient/médecin
$query = "SELECT r.*, 
                 p.nom as patient_nom, p.prenom as patient_prenom,
                 m.nom as medecin_nom, m.prenom as medecin_prenom, m.specialite as medecin_specialite
          FROM rendezvous r
          LEFT JOIN patients p ON r.patient_id = p.id
          LEFT JOIN medecins m ON r.medecin_id = m.id
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $rdv_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $rdv = $result->fetch_assoc();
    echo json_encode(['success' => true, 'rdv' => $rdv]);
} else {
    echo json_encode(['success' => false, 'message' => 'Rendez-vous non trouvé']);
}

$stmt->close();
$conn->close();
?>
