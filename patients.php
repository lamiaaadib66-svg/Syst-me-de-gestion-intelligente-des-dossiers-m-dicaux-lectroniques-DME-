<?php
// ==================== CONFIGURATION ET FONCTIONS ====================
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dme_pro');

// Fonction de connexion à la base de données
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Fonction pour calculer l'âge
function calculateAge($birthDate) {
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

// Fonction pour générer une couleur d'avatar
function getAvatarColor($id) {
    $colors = [
        'linear-gradient(135deg, #3b82f6, #1d4ed8)',
        'linear-gradient(135deg, #10b981, #059669)',
        'linear-gradient(135deg, #f59e0b, #d97706)',
        'linear-gradient(135deg, #8b5cf6, #7c3aed)',
        'linear-gradient(135deg, #ec4899, #db2777)',
        'linear-gradient(135deg, #06b6d4, #0891b2)'
    ];
    return $colors[$id % count($colors)];
}

// Fonction pour échapper les données HTML
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Gestion des opérations CRUD
$action = $_GET['action'] ?? '';
$patient_id = $_GET['id'] ?? 0;
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    
    // Récupération des données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $groupe_sanguin = $_POST['groupe_sanguin'] ?? '';
    $allergies = trim($_POST['allergies'] ?? '');
    $antecedents = trim($_POST['antecedents'] ?? '');
    $matricule = $_POST['matricule'] ?? '';
    
    // Validation
    $errors = [];
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($prenom)) $errors[] = "Le prénom est requis";
    if (empty($date_naissance)) $errors[] = "La date de naissance est requise";
    if (empty($genre) || !in_array($genre, ['M', 'F'])) $errors[] = "Le genre est invalide";
    
    if (empty($errors)) {
        if (isset($_POST['patient_id']) && !empty($_POST['patient_id'])) {
            // Mise à jour du patient existant
            $patient_id = (int)$_POST['patient_id'];
            $stmt = $conn->prepare("UPDATE patients SET 
                nom = ?, prenom = ?, date_naissance = ?, genre = ?, 
                telephone = ?, email = ?, adresse = ?, groupe_sanguin = ?, 
                allergies = ?, antecedents = ? 
                WHERE id = ?");
            $stmt->bind_param("ssssssssssi", 
                $nom, $prenom, $date_naissance, $genre,
                $telephone, $email, $adresse, $groupe_sanguin,
                $allergies, $antecedents, $patient_id);
            
            if ($stmt->execute()) {
                $message = "Patient mis à jour avec succès";
                $success = true;
            } else {
                $message = "Erreur lors de la mise à jour: " . $conn->error;
            }
            $stmt->close();
        } else {
            // Création d'un nouveau patient
            // Générer un matricule unique
            $matricule = 'PAT' . date('Ym') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("INSERT INTO patients 
                (matricule, nom, prenom, date_naissance, genre, telephone, email, adresse, groupe_sanguin, allergies, antecedents) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", 
                $matricule, $nom, $prenom, $date_naissance, $genre,
                $telephone, $email, $adresse, $groupe_sanguin,
                $allergies, $antecedents);
            
            if ($stmt->execute()) {
                $message = "Patient créé avec succès";
                $success = true;
                $patient_id = $conn->insert_id;
            } else {
                $message = "Erreur lors de la création: " . $conn->error;
            }
            $stmt->close();
        }
    } else {
        $message = implode("<br>", $errors);
    }
    
    $conn->close();
    
    // Redirection pour éviter la resoumission du formulaire
    if ($success) {
        header("Location: patients.php?message=" . urlencode($message) . "&success=1");
        exit();
    }
}

// Suppression d'un patient
if ($action === 'delete' && $patient_id > 0) {
    $conn = connectDB();
    
    // Vérifier si le patient existe
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Supprimer le patient
        $deleteStmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
        $deleteStmt->bind_param("i", $patient_id);
        
        if ($deleteStmt->execute()) {
            $message = "Patient supprimé avec succès";
            $success = true;
        } else {
            $message = "Erreur lors de la suppression: " . $conn->error;
        }
        $deleteStmt->close();
    } else {
        $message = "Patient non trouvé";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: patients.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

// Récupération des patients pour l'affichage
$conn = connectDB();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres et recherche
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

$whereClause = "1=1";
$params = [];
$param_types = "";

if (!empty($search)) {
    $search_term = "%{$search}%";
    $whereClause .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.matricule LIKE ? OR p.telephone LIKE ?)";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $param_types .= "ssss";
}

switch ($filter) {
    case 'recent':
        $whereClause .= " AND p.id IN (
            SELECT DISTINCT patient_id 
            FROM consultations 
            WHERE date_consultation >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )";
        break;
    case 'age_high':
        $whereClause .= " AND TIMESTAMPDIFF(YEAR, p.date_naissance, CURDATE()) > 60";
        break;
    case 'age_low':
        $whereClause .= " AND TIMESTAMPDIFF(YEAR, p.date_naissance, CURDATE()) < 18";
        break;
    case 'femmes':
        $whereClause .= " AND p.genre = 'F'";
        break;
    case 'hommes':
        $whereClause .= " AND p.genre = 'M'";
        break;
}

// Compter le total des patients
$countQuery = "SELECT COUNT(*) as total FROM patients p WHERE $whereClause";
$countStmt = $conn->prepare($countQuery);

if (!empty($params)) {
    $countStmt->bind_param($param_types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalPatients = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalPatients / $limit);
$countStmt->close();

// Récupérer les patients
$query = "SELECT p.*, 
                 (SELECT MAX(date_consultation) 
                  FROM consultations c 
                  WHERE c.patient_id = p.id) as derniere_consultation
          FROM patients p 
          WHERE $whereClause 
          ORDER BY p.nom, p.prenom 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$patients = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Récupérer un patient spécifique pour édition
$patient_to_edit = null;
if ($action === 'edit' && $patient_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient_to_edit = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Patients</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Styles supplémentaires pour les modales */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
      background-color: #fff;
      margin: 5% auto;
      padding: 2rem;
      border-radius: var(--radius);
      width: 90%;
      max-width: 700px;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: var(--shadow-lg);
      position: relative;
    }
    
    .close {
      position: absolute;
      right: 1.5rem;
      top: 1rem;
      font-size: 2rem;
      cursor: pointer;
      color: var(--text-secondary);
    }
    
    .close:hover {
      color: var(--text-primary);
    }
    
    .alert {
      padding: 1rem 1.5rem;
      border-radius: var(--radius-sm);
      margin-bottom: 1.5rem;
      font-weight: 600;
    }
    
    .alert-success {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      color: var(--success);
      border: 1px solid rgba(5, 150, 105, 0.2);
    }
    
    .alert-error {
      background: linear-gradient(135deg, #fee2e2, #fecaca);
      color: var(--danger);
      border: 1px solid rgba(220, 38, 38, 0.2);
    }
    
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    .form-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--text-primary);
    }
    
    .form-control {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      font-size: 1rem;
      transition: var(--transition);
    }
    
    .form-control:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }
    
    .patient-detail {
      background: var(--bg-secondary);
      padding: 1.5rem;
      border-radius: var(--radius-sm);
      margin-bottom: 1.5rem;
    }
    
    .patient-detail .row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
      margin-bottom: 1rem;
    }
    
    .patient-detail .label {
      font-weight: 600;
      color: var(--text-secondary);
      margin-bottom: 0.25rem;
    }
    
    .patient-detail .value {
      color: var(--text-primary);
      font-weight: 500;
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="header-content">
      <h1><i class="fas fa-stethoscope"></i> DME Pro</h1>
      <p>Système Intelligent de Dossiers Médicaux Électroniques - Gestion médicale complète et sécurisée</p>
    </div>
  </header>

  <button class="menu-toggle" id="menuToggle" style="display: none;">
    <i class="fas fa-bars"></i>
  </button>

  <div class="app">
    <aside class="sidebar" id="sidebar">
      <div class="logo">
        <div class="logo-icon">
          <i class="fas fa-heartbeat"></i>
        </div>
        <div>
          <div class="logo-text">DME Pro</div>
          <div class="logo-subtext">Système médical intelligent</div>
        </div>
      </div>
      <nav>
        <a href="index.php" class="nav-btn">
          <i class="fas fa-tachometer-alt nav-icon"></i> Tableau de bord
        </a>
        <a href="patients.php" class="nav-btn active">
          <i class="fas fa-user-injured nav-icon"></i> Patients
          <span class="notification-badge"><?php echo count($patients); ?></span>
        </a>
        <a href="rendezvous.php" class="nav-btn">
          <i class="fas fa-calendar-check nav-icon"></i> Rendez-vous
          <span class="notification-badge">5</span>
        </a>
        <a href="consultations.php" class="nav-btn">
          <i class="fas fa-stethoscope nav-icon"></i> Consultations
        </a>
        <a href="prescriptions.php" class="nav-btn">
          <i class="fas fa-pills nav-icon"></i> Prescriptions
          <span class="notification-badge">2</span>
        </a>
        <a href="specialites.php" class="nav-btn">
          <i class="fas fa-clinic-medical nav-icon"></i> Spécialités
        </a>
        <a href="equipe.php" class="nav-btn">
          <i class="fas fa-users nav-icon"></i> Équipe médicale
        </a>
        <a href="parametres.php" class="nav-btn">
          <i class="fas fa-cog nav-icon"></i> Paramètres
        </a>
      </nav>
    </aside>

    <main class="main">
      <section id="patients-view" class="view">
        <div class="page-header">
          <div>
            <h1 class="page-title">Patients</h1>
            <p class="page-subtitle">Gestion complète des dossiers patients</p>
          </div>
          <div style="display: flex; gap: 1rem; align-items: center;">
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
              <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="<?php echo escape($search); ?>" 
                       placeholder="Rechercher un patient...">
              </div>
              <button type="submit" class="action-btn" style="background: var(--primary);">
                <i class="fas fa-search"></i>
              </button>
            </form>
            <button class="btn btn-primary" onclick="openPatientForm()">
              <i class="fas fa-plus"></i> Nouveau patient
            </button>
          </div>
        </div>
        
        <!-- Messages d'erreur/succès -->
        <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-<?php echo (isset($_GET['success']) && $_GET['success'] == 1) ? 'success' : 'error'; ?>">
          <i class="fas <?php echo (isset($_GET['success']) && $_GET['success'] == 1) ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
          <?php echo escape($_GET['message']); ?>
        </div>
        <?php endif; ?>

        <!-- Contenu de la table des patients -->
        <div class="table-container">
          <div class="table-header">
            <div style="font-size: 1.1rem; font-weight: 600;">
              Liste des patients (<?php echo $totalPatients; ?> actifs)
            </div>
            <div style="display: flex; gap: 1rem;">
              <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <?php if (!empty($search)): ?>
                  <input type="hidden" name="search" value="<?php echo escape($search); ?>">
                <?php endif; ?>
                <select name="filter" class="form-control" style="width: auto; padding: 0.5rem 1rem;" onchange="this.form.submit()">
                  <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tous les patients</option>
                  <option value="recent" <?php echo $filter === 'recent' ? 'selected' : ''; ?>>Consultés récemment</option>
                  <option value="femmes" <?php echo $filter === 'femmes' ? 'selected' : ''; ?>>Femmes</option>
                  <option value="hommes" <?php echo $filter === 'hommes' ? 'selected' : ''; ?>>Hommes</option>
                  <option value="age_high" <?php echo $filter === 'age_high' ? 'selected' : ''; ?>>Âge élevé (60+)</option>
                  <option value="age_low" <?php echo $filter === 'age_low' ? 'selected' : ''; ?>>Jeunes patients (-18)</option>
                </select>
              </form>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Nom</th>
                <th>Date Naissance</th>
                <th>Téléphone</th>
                <th>Groupe sanguin</th>
                <th>Dernière consultation</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($patients)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    <i class="fas fa-user-injured" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i>
                    <?php echo empty($search) ? 'Aucun patient trouvé' : 'Aucun patient ne correspond à votre recherche'; ?>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($patients as $patient): 
                  $age = calculateAge($patient['date_naissance']);
                  $lastConsultation = $patient['derniere_consultation'] ? date('d M Y', strtotime($patient['derniere_consultation'])) : null;
                ?>
                <tr>
                  <td>
                    <div style="display: flex; align-items: center; gap: 14px;">
                      <div class="patient-avatar" style="background: <?php echo getAvatarColor($patient['id']); ?>;">
                        <?php echo strtoupper(substr($patient['prenom'], 0, 1) . substr($patient['nom'], 0, 1)); ?>
                      </div>
                      <div>
                        <div style="font-weight: 700;"><?php echo escape($patient['prenom'] . ' ' . $patient['nom']); ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                          <?php echo $patient['genre'] == 'F' ? 'Femme' : 'Homme'; ?>, 
                          <?php echo $age; ?> ans
                          <br><small>Matricule: <?php echo escape($patient['matricule']); ?></small>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php echo date('d/m/Y', strtotime($patient['date_naissance'])); ?><br>
                    <small><?php echo $age; ?> ans</small>
                  </td>
                  <td><?php echo escape($patient['telephone'] ?: 'Non renseigné'); ?></td>
                  <td>
                    <?php if ($patient['groupe_sanguin']): ?>
                      <span style="color: var(--danger); font-weight: 700;"><?php echo escape($patient['groupe_sanguin']); ?></span>
                    <?php else: ?>
                      <span style="color: var(--text-secondary);">Inconnu</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($lastConsultation): ?>
                      <strong><?php echo $lastConsultation; ?></strong>
                      <?php if ($patient['derniere_consultation']): ?>
                        <br><small><?php echo date('H:i', strtotime($patient['derniere_consultation'])); ?></small>
                      <?php endif; ?>
                    <?php else: ?>
                      <span style="color: var(--text-secondary);">Jamais</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="table-actions">
                      <button class="action-btn btn-view" onclick="viewPatient(<?php echo $patient['id']; ?>)" title="Voir dossier">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="action-btn btn-edit" onclick="editPatient(<?php echo $patient['id']; ?>)" title="Modifier">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="action-btn btn-delete" onclick="deletePatient(<?php echo $patient['id']; ?>)" title="Supprimer">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
          
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
          <div style="padding: 20px; text-align: center; border-top: 1px solid var(--border);">
            <?php if ($page > 1): ?>
              <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $filter !== 'all' ? '&filter=' . $filter : ''; ?>" 
                 class="action-btn" style="background: var(--text-secondary);">
                <i class="fas fa-chevron-left"></i> Précédent
              </a>
            <?php endif; ?>
            
            <span style="margin: 0 20px; color: var(--text-secondary);">
              Page <?php echo $page; ?> sur <?php echo $totalPages; ?>
            </span>
            
            <?php if ($page < $totalPages): ?>
              <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $filter !== 'all' ? '&filter=' . $filter : ''; ?>" 
                 class="action-btn" style="background: var(--text-secondary);">
                Suivant <i class="fas fa-chevron-right"></i>
              </a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
  
  <!-- Modal pour voir patient -->
  <div id="viewPatientModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('viewPatientModal')">&times;</span>
      <h2 id="viewPatientTitle"></h2>
      <div id="viewPatientContent"></div>
    </div>
  </div>

  <!-- Modal pour formulaire patient -->
  <div id="patientFormModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('patientFormModal')">&times;</span>
      <h2 id="formTitle">Nouveau Patient</h2>
      <form method="POST" action="patients.php" id="patientForm">
        <input type="hidden" name="patient_id" id="patient_id" value="">
        <input type="hidden" name="matricule" id="matricule" value="">
        
        <div class="form-group">
          <label class="form-label">Nom *</label>
          <input type="text" class="form-control" name="nom" id="nom" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Prénom *</label>
          <input type="text" class="form-control" name="prenom" id="prenom" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Date de naissance *</label>
          <input type="date" class="form-control" name="date_naissance" id="date_naissance" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Genre *</label>
          <select class="form-control" name="genre" id="genre" required>
            <option value="">Sélectionner</option>
            <option value="M">Masculin</option>
            <option value="F">Féminin</option>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input type="tel" class="form-control" name="telephone" id="telephone">
        </div>
        
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" name="email" id="email">
        </div>
        
        <div class="form-group">
          <label class="form-label">Adresse</label>
          <textarea class="form-control" name="adresse" id="adresse" rows="2"></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Groupe sanguin</label>
          <select class="form-control" name="groupe_sanguin" id="groupe_sanguin">
            <option value="">Inconnu</option>
            <option value="A+">A+</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="AB+">AB+</option>
            <option value="AB-">AB-</option>
            <option value="O+">O+</option>
            <option value="O-">O-</option>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Allergies</label>
          <textarea class="form-control" name="allergies" id="allergies" rows="2" 
                    placeholder="Séparer par des virgules..."></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Antécédents médicaux</label>
          <textarea class="form-control" name="antecedents" id="antecedents" rows="3"></textarea>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
          </button>
          <button type="button" class="btn" onclick="closeModal('patientFormModal')" 
                  style="background: linear-gradient(135deg, var(--warning), #b45309);">
            <i class="fas fa-times"></i> Annuler
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <script>
    // Fonctions JavaScript pour la gestion des patients
    function openPatientForm(patientId = null) {
      if (patientId) {
        // Charger les données du patient pour édition
        fetch('get_patient.php?id=' + patientId)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const patient = data.patient;
              document.getElementById('patient_id').value = patient.id;
              document.getElementById('matricule').value = patient.matricule;
              document.getElementById('nom').value = patient.nom;
              document.getElementById('prenom').value = patient.prenom;
              document.getElementById('date_naissance').value = patient.date_naissance;
              document.getElementById('genre').value = patient.genre;
              document.getElementById('telephone').value = patient.telephone || '';
              document.getElementById('email').value = patient.email || '';
              document.getElementById('adresse').value = patient.adresse || '';
              document.getElementById('groupe_sanguin').value = patient.groupe_sanguin || '';
              document.getElementById('allergies').value = patient.allergies || '';
              document.getElementById('antecedents').value = patient.antecedents || '';
              document.getElementById('formTitle').textContent = 'Modifier Patient: ' + patient.prenom + ' ' + patient.nom;
            }
          })
          .catch(error => {
            alert('Erreur lors du chargement des données du patient');
            console.error(error);
          });
      } else {
        // Réinitialiser le formulaire pour un nouveau patient
        document.getElementById('patientForm').reset();
        document.getElementById('patient_id').value = '';
        document.getElementById('formTitle').textContent = 'Nouveau Patient';
        document.getElementById('matricule').value = 'PAT' + new Date().getFullYear().toString().substr(-2) + 
          (new Date().getMonth() + 1).toString().padStart(2, '0') + 
          Math.floor(Math.random() * 1000).toString().padStart(3, '0');
      }
      openModal('patientFormModal');
    }
    
    function viewPatient(patientId) {
      fetch('get_patient.php?id=' + patientId + '&detail=1')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const patient = data.patient;
            const age = calculateAge(patient.date_naissance);
            
            let content = `
              <div class="patient-detail">
                <div class="row">
                  <div>
                    <div class="label">Matricule</div>
                    <div class="value">${patient.matricule}</div>
                  </div>
                  <div>
                    <div class="label">Date de naissance</div>
                    <div class="value">${formatDate(patient.date_naissance)} (${age} ans)</div>
                  </div>
                  <div>
                    <div class="label">Genre</div>
                    <div class="value">${patient.genre === 'F' ? 'Femme' : 'Homme'}</div>
                  </div>
                </div>
                <div class="row">
                  <div>
                    <div class="label">Téléphone</div>
                    <div class="value">${patient.telephone || 'Non renseigné'}</div>
                  </div>
                  <div>
                    <div class="label">Email</div>
                    <div class="value">${patient.email || 'Non renseigné'}</div>
                  </div>
                  <div>
                    <div class="label">Groupe sanguin</div>
                    <div class="value">${patient.groupe_sanguin || 'Inconnu'}</div>
                  </div>
                </div>
            `;
            
            if (patient.adresse) {
              content += `
                <div class="row">
                  <div>
                    <div class="label">Adresse</div>
                    <div class="value">${patient.adresse}</div>
                  </div>
                </div>
              `;
            }
            
            if (patient.allergies) {
              content += `
                <div class="row">
                  <div>
                    <div class="label">Allergies</div>
                    <div class="value">${patient.allergies}</div>
                  </div>
                </div>
              `;
            }
            
            if (patient.antecedents) {
              content += `
                <div class="row">
                  <div>
                    <div class="label">Antécédents médicaux</div>
                    <div class="value">${patient.antecedents}</div>
                  </div>
                </div>
              `;
            }
            
            content += `
                <div class="row">
                  <div>
                    <div class="label">Date d'inscription</div>
                    <div class="value">${formatDate(patient.created_at)}</div>
                  </div>
                  <div>
                    <div class="label">Dernière mise à jour</div>
                    <div class="value">${formatDate(patient.updated_at)}</div>
                  </div>
                </div>
              </div>
              <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button class="action-btn btn-edit" onclick="editPatient(${patient.id}); closeModal('viewPatientModal')">
                  <i class="fas fa-edit"></i> Modifier
                </button>
                <button class="action-btn" style="background: linear-gradient(135deg, var(--info), #0284c7);" 
                        onclick="window.location.href='rendezvous.php?patient_id=${patient.id}'">
                  <i class="fas fa-calendar-check"></i> Voir rendez-vous
                </button>
              </div>
            `;
            
            document.getElementById('viewPatientTitle').textContent = `${patient.prenom} ${patient.nom}`;
            document.getElementById('viewPatientContent').innerHTML = content;
            openModal('viewPatientModal');
          } else {
            alert('Erreur lors du chargement des données du patient');
          }
        })
        .catch(error => {
          alert('Erreur lors du chargement des données du patient');
          console.error(error);
        });
    }
    
    function editPatient(patientId) {
      openPatientForm(patientId);
    }
    
    function deletePatient(patientId) {
      if (confirm('Êtes-vous sûr de vouloir supprimer ce patient ? Cette action est irréversible.')) {
        window.location.href = 'patients.php?action=delete&id=' + patientId;
      }
    }
    
    function openModal(modalId) {
      document.getElementById(modalId).style.display = 'block';
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }
    
    function calculateAge(birthDate) {
      const birth = new Date(birthDate);
      const today = new Date();
      let age = today.getFullYear() - birth.getFullYear();
      const monthDiff = today.getMonth() - birth.getMonth();
      
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
      }
      
      return age;
    }
    
    function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
    
    // Fermer la modal en cliquant en dehors
    window.onclick = function(event) {
      const modals = document.querySelectorAll('.modal');
      modals.forEach(modal => {
        if (event.target == modal) {
          modal.style.display = 'none';
        }
      });
    };
  </script>
  
  <script src="script.js"></script>
</body>
</html>