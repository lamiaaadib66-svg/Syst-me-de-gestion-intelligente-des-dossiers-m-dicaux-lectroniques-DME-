<?php
// Configuration de l'application DME Pro
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dme_pro');
define('BASE_URL', 'http://localhost/dme-pro');

// Configuration CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Fonction pour envoyer une réponse JSON
function json_response($data = null, $status = 200, $message = '') {
    http_response_code($status);
    
    $response = [
        'success' => $status >= 200 && $status < 300,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

// Gestion des erreurs
function handle_error($message, $status = 500) {
    json_response(null, $status, $message);
}

// Vérifier la méthode de la requête
$method = $_SERVER['REQUEST_METHOD'];
?>