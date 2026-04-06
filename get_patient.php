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
$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$detail = isset($_GET['detail']) ? (int)$_GET['detail'] : 0;

if ($patient_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID patient invalide']);
    exit();
}

// Connexion à la base de données
$conn = connectDB();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Préparer la requête
if ($detail) {
    $query = "SELECT * FROM patients WHERE id = ?";
} else {
    $query = "SELECT id, matricule, nom, prenom, date_naissance, genre, telephone, email, 
                     adresse, groupe_sanguin, allergies, antecedents 
              FROM patients WHERE id = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $patient = $result->fetch_assoc();
    echo json_encode(['success' => true, 'patient' => $patient]);
} else {
    echo json_encode(['success' => false, 'message' => 'Patient non trouvé']);
}

$stmt->close();
$conn->close();
?>