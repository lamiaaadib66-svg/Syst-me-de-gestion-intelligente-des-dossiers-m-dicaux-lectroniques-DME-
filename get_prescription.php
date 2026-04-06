
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
$prescription_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($prescription_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID prescription invalide']);
    exit();
}

// Connexion à la base de données
$conn = connectDB();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Récupérer la prescription
$query = "SELECT pr.*, 
                 p.nom as patient_nom, p.prenom as patient_prenom,
                 m.nom as medecin_nom, m.prenom as medecin_prenom
          FROM prescriptions pr
          LEFT JOIN patients p ON pr.patient_id = p.id
          LEFT JOIN medecins m ON pr.medecin_id = m.id
          WHERE pr.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $prescription = $result->fetch_assoc();
    echo json_encode(['success' => true, 'prescription' => $prescription]);
} else {
    echo json_encode(['success' => false, 'message' => 'Prescription non trouvée']);
}

$stmt->close();
$conn->close();
?>
