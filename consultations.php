
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

// Fonction pour échapper les données HTML
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Gestion des opérations CRUD
$action = $_GET['action'] ?? '';
$consultation_id = $_GET['id'] ?? 0;
$message = '';
$success = false;

// Traitement des formulaires POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    
    // Récupération des données du formulaire
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $medecin_id = (int)($_POST['medecin_id'] ?? 0);
    $date_consultation = $_POST['date_consultation'] ?? date('Y-m-d H:i:s');
    $motif = trim($_POST['motif'] ?? '');
    $diagnostic = trim($_POST['diagnostic'] ?? '');
    $traitement = trim($_POST['traitement'] ?? '');
    $examens = trim($_POST['examens'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $statut = $_POST['statut'] ?? 'en_cours';
    $rdv_id = !empty($_POST['rdv_id']) ? (int)$_POST['rdv_id'] : null;
    
    // Validation
    $errors = [];
    if ($patient_id <= 0) $errors[] = "Patient requis";
    if ($medecin_id <= 0) $errors[] = "Médecin requis";
    if (empty($motif)) $errors[] = "Motif requis";
    
    if (empty($errors)) {
        if (isset($_POST['consultation_id']) && !empty($_POST['consultation_id'])) {
            // Mise à jour de la consultation existante
            $consultation_id = (int)$_POST['consultation_id'];
            $stmt = $conn->prepare("UPDATE consultations SET 
                patient_id = ?, medecin_id = ?, date_consultation = ?, 
                motif = ?, diagnostic = ?, traitement = ?, examens = ?, 
                notes = ?, statut = ?, rdv_id = ? 
                WHERE id = ?");
            $stmt->bind_param("iisssssssii", 
                $patient_id, $medecin_id, $date_consultation,
                $motif, $diagnostic, $traitement, $examens, 
                $notes, $statut, $rdv_id, $consultation_id);
            
            if ($stmt->execute()) {
                $message = "Consultation mise à jour avec succès";
                $success = true;
            } else {
                $message = "Erreur lors de la mise à jour: " . $conn->error;
            }
            $stmt->close();
        } else {
            // Création d'une nouvelle consultation
            $stmt = $conn->prepare("INSERT INTO consultations 
                (patient_id, medecin_id, date_consultation, 
                 motif, diagnostic, traitement, examens, notes, statut, rdv_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssssssi", 
                $patient_id, $medecin_id, $date_consultation,
                $motif, $diagnostic, $traitement, $examens, 
                $notes, $statut, $rdv_id);
            
            if ($stmt->execute()) {
                $message = "Consultation créée avec succès";
                $success = true;
                $consultation_id = $conn->insert_id;
                
                // Si c'est lié à un rendez-vous, mettre à jour le statut du RDV
                if ($rdv_id) {
                    $updateRdv = $conn->prepare("UPDATE rendezvous SET statut = 'termine' WHERE id = ?");
                    $updateRdv->bind_param("i", $rdv_id);
                    $updateRdv->execute();
                    $updateRdv->close();
                }
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
        header("Location: consultations.php?message=" . urlencode($message) . "&success=1");
        exit();
    }
}

// Suppression d'une consultation
if ($action === 'delete' && $consultation_id > 0) {
    $conn = connectDB();
    
    // Vérifier si la consultation existe
    $stmt = $conn->prepare("SELECT * FROM consultations WHERE id = ?");
    $stmt->bind_param("i", $consultation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Supprimer la consultation
        $deleteStmt = $conn->prepare("DELETE FROM consultations WHERE id = ?");
        $deleteStmt->bind_param("i", $consultation_id);
        
        if ($deleteStmt->execute()) {
            $message = "Consultation supprimée avec succès";
            $success = true;
        } else {
            $message = "Erreur lors de la suppression: " . $conn->error;
        }
        $deleteStmt->close();
    } else {
        $message = "Consultation non trouvée";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: consultations.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

// Terminer une consultation
if ($action === 'complete' && $consultation_id > 0) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("UPDATE consultations SET statut = 'termine' WHERE id = ?");
    $stmt->bind_param("i", $consultation_id);
    
    if ($stmt->execute()) {
        $message = "Consultation marquée comme terminée";
        $success = true;
    } else {
        $message = "Erreur: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: consultations.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

// Récupération des données pour l'affichage
$conn = connectDB();

// Récupérer les consultations pour affichage (simulation - à adapter avec votre base)
$consultations = [];

// Exemple de données statiques (à remplacer par une requête SQL)
$consultations = [
    [
        'id' => 1,
        'date_consultation' => '2025-12-09 10:45:00',
        'patient_prenom' => 'Léa',
        'patient_nom' => 'Martin',
        'medecin_nom' => 'Alice Dupont',
        'motif' => 'Maux de gorge, fièvre',
        'diagnostic' => 'Angine streptococcique',
        'statut' => 'termine',
        'age' => 35
    ],
    [
        'id' => 2,
        'date_consultation' => '2025-12-08 15:20:00',
        'patient_prenom' => 'Karim',
        'patient_nom' => 'Benali',
        'medecin_nom' => 'Karim Benali',
        'motif' => 'Bilan croissance + vaccins',
        'diagnostic' => 'Développement normal',
        'statut' => 'termine',
        'age' => 2
    ],
    [
        'id' => 3,
        'date_consultation' => '2025-12-07 11:30:00',
        'patient_prenom' => 'Sarah',
        'patient_nom' => 'Martin',
        'medecin_nom' => 'Sarah Martin',
        'motif' => 'Dermatite séborrhéique cuir chevelu',
        'diagnostic' => 'Dermatite séborrhéique modérée',
        'statut' => 'termine',
        'age' => 33
    ],
    [
        'id' => 4,
        'date_consultation' => '2025-12-06 09:00:00',
        'patient_prenom' => 'Pierre',
        'patient_nom' => 'Dubois',
        'medecin_nom' => 'Alice Dupont',
        'motif' => 'Contrôle hypertension',
        'diagnostic' => 'Hypertension artérielle stade 2',
        'statut' => 'termine',
        'age' => 68
    ],
    [
        'id' => 5,
        'date_consultation' => '2025-12-05 14:00:00',
        'patient_prenom' => 'Sophie',
        'patient_nom' => 'Lambert',
        'medecin_nom' => 'Nadia Khalil',
        'motif' => 'Suivi 6ème mois grossesse',
        'diagnostic' => 'Grossesse évolutive normale',
        'statut' => 'termine',
        'age' => 28
    ],
    [
        'id' => 6,
        'date_consultation' => '2025-12-04 16:30:00',
        'patient_prenom' => 'Jean',
        'patient_nom' => 'Bernard',
        'medecin_nom' => 'Paul Leroy',
        'motif' => 'Céphalées persistantes',
        'diagnostic' => 'Migraine sans aura',
        'statut' => 'en_cours',
        'age' => 52
    ]
];

// Récupérer les patients pour les select
$patients = $conn->query("SELECT id, nom, prenom FROM patients ORDER BY nom, prenom")->fetch_all(MYSQLI_ASSOC);

// Récupérer les médecins pour les select
$medecins = $conn->query("SELECT id, nom, prenom, specialite FROM medecins WHERE statut = 'actif' ORDER BY nom, prenom")->fetch_all(MYSQLI_ASSOC);

// Récupérer les rendez-vous pour les select
$rendezvous = $conn->query("SELECT r.id, r.date_rdv, p.nom as patient_nom, p.prenom as patient_prenom 
                           FROM rendezvous r 
                           LEFT JOIN patients p ON r.patient_id = p.id
                           ORDER BY r.date_rdv DESC")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Consultations</title>
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
      max-width: 800px;
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
    
    textarea.form-control {
      min-height: 100px;
      resize: vertical;
    }
    
    .status.en-cours {
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: var(--warning);
      border: 1px solid rgba(217, 119, 6, 0.2);
    }
    
    .status.en-cours::before {
      background: var(--warning);
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
        <a href="patients.php" class="nav-btn">
          <i class="fas fa-user-injured nav-icon"></i> Patients
          <span class="notification-badge">3</span>
        </a>
        <a href="rendezvous.php" class="nav-btn">
          <i class="fas fa-calendar-check nav-icon"></i> Rendez-vous
          <span class="notification-badge">5</span>
        </a>
        <a href="consultations.php" class="nav-btn active">
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
      <section id="consultations-view" class="view">
        <div class="page-header">
          <div>
            <h1 class="page-title">Consultations</h1>
            <p class="page-subtitle">Historique des consultations récentes et suivi médical</p>
          </div>
          <div style="display: flex; gap: 1rem;">
            <button class="btn btn-primary" onclick="openConsultationForm()">
              <i class="fas fa-file-medical"></i> Nouvelle consultation
            </button>
            <button class="btn" style="background: linear-gradient(135deg, var(--info), #0284c7);">
              <i class="fas fa-filter"></i> Filtrer
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
        
        <!-- Contenu des consultations... -->
        <div class="table-container">
    <div class="table-header">
      <div style="font-size: 1.1rem; font-weight: 600;">
        <i class="fas fa-history"></i> Consultations récentes (42 ce mois)
      </div>
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Rechercher une consultation...">
      </div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Date/Heure</th>
          <th>Patient</th>
          <th>Médecin</th>
          <th>Motif</th>
          <th>Diagnostic</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($consultations as $consult): ?>
        <tr>
          <td>
            <strong><?php echo date('d/m/Y', strtotime($consult['date_consultation'])); ?></strong><br>
            <small><?php echo date('H:i', strtotime($consult['date_consultation'])); ?></small>
          </td>
          <td>
            <div style="display: flex; align-items: center; gap: 10px;">
              <div class="patient-avatar" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); width: 40px; height: 40px; font-size: 1rem;">
                <?php echo strtoupper(substr($consult['patient_prenom'], 0, 1) . substr($consult['patient_nom'], 0, 1)); ?>
              </div>
              <div>
                <div style="font-weight: 600;"><?php echo escape($consult['patient_prenom'] . ' ' . $consult['patient_nom']); ?></div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo $consult['age']; ?> ans</div>
              </div>
            </div>
          </td>
          <td>Dr. <?php echo escape($consult['medecin_nom']); ?></td>
          <td><?php echo escape($consult['motif']); ?></td>
          <td><?php echo escape($consult['diagnostic']); ?></td>
          <td>
            <?php if ($consult['statut'] == 'termine'): ?>
              <span class="status terminee">Terminée</span>
            <?php else: ?>
              <span class="status en-cours">En cours</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="table-actions">
              <button class="action-btn btn-view" onclick="viewConsultation(<?php echo $consult['id']; ?>)" title="Voir compte-rendu complet">
                <i class="fas fa-file-medical-alt"></i> Compte-rendu
              </button>
              <button class="action-btn btn-edit" onclick="editConsultation(<?php echo $consult['id']; ?>)" title="Modifier la consultation">
                <i class="fas fa-edit"></i> Éditer
              </button>
              <button class="action-btn" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));" title="Voir prescriptions associées">
                <i class="fas fa-prescription"></i> Ordonnance
              </button>
              <?php if ($consult['statut'] == 'en_cours'): ?>
                <button class="action-btn btn-confirm" onclick="completeConsultation(<?php echo $consult['id']; ?>)" title="Terminer la consultation">
                  <i class="fas fa-check-circle"></i> Terminer
                </button>
              <?php endif; ?>
              <button class="action-btn btn-delete" onclick="deleteConsultation(<?php echo $consult['id']; ?>)" title="Supprimer la consultation">
                <i class="fas fa-trash"></i> Suppr.
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Consultation en cours -->
  <div class="table-container" style="margin-top: 2rem;">
    <div class="table-header">
      <div style="font-size: 1.1rem; font-weight: 600;">
        <i class="fas fa-hourglass-half"></i> Consultations en cours de rédaction
      </div>
      <button class="btn" style="background: linear-gradient(135deg, var(--warning), #b45309);">
        <i class="fas fa-clock"></i> En attente (3)
      </button>
    </div>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Patient</th>
          <th>Médecin</th>
          <th>Progression</th>
          <th>Dernière modif.</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>10/12/2025</strong></td>
          <td>Marc Leclerc</td>
          <td>Dr. Alice Dupont</td>
          <td>
            <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
              <div style="background: linear-gradient(90deg, var(--warning), #b45309); width: 60%; height: 100%;"></div>
            </div>
            <small>60% complété</small>
          </td>
          <td>11:50</td>
          <td>
            <div class="table-actions">
              <button class="action-btn btn-edit" onclick="editConsultation(7)" title="Continuer la rédaction">
                <i class="fas fa-pen"></i> Continuer
              </button>
              <button class="action-btn" style="background: linear-gradient(135deg, var(--success), #047857);" onclick="completeConsultation(7)" title="Finaliser la consultation">
                <i class="fas fa-check-circle"></i> Finaliser
              </button>
              <button class="action-btn btn-delete" onclick="deleteConsultation(7)" title="Supprimer le brouillon">
                <i class="fas fa-trash"></i> Brouillon
              </button>
            </div>
          </td>
        </tr>
        <tr>
          <td><strong>09/12/2025</strong></td>
          <td>Marie Curie</td>
          <td>Dr. Sarah Martin</td>
          <td>
            <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
              <div style="background: linear-gradient(90deg, var(--warning), #b45309); width: 30%; height: 100%;"></div>
            </div>
            <small>30% complété</small>
          </td>
          <td>16:20</td>
          <td>
            <div class="table-actions">
              <button class="action-btn btn-edit" onclick="editConsultation(8)" title="Continuer la rédaction">
                <i class="fas fa-pen"></i> Continuer
              </button>
              <button class="action-btn" style="background: linear-gradient(135deg, var(--info), #0284c7);" title="Dupliquer la consultation">
                <i class="fas fa-copy"></i> Dupliquer
              </button>
              <button class="action-btn btn-delete" onclick="deleteConsultation(8)" title="Supprimer le brouillon">
                <i class="fas fa-trash"></i> Brouillon
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
      </section>
    </main>
  </div>
  
  <!-- Modal pour formulaire consultation -->
  <div id="consultationFormModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('consultationFormModal')">&times;</span>
      <h2 id="formTitle">Nouvelle Consultation</h2>
      <form method="POST" action="consultations.php" id="consultationForm">
        <input type="hidden" name="consultation_id" id="consultation_id" value="">
        
        <div class="form-group">
          <label class="form-label">Patient *</label>
          <select class="form-control" name="patient_id" id="patient_id" required>
            <option value="">Sélectionner un patient</option>
            <?php foreach ($patients as $patient): ?>
              <option value="<?php echo $patient['id']; ?>">
                <?php echo escape($patient['prenom'] . ' ' . $patient['nom']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Médecin *</label>
          <select class="form-control" name="medecin_id" id="medecin_id" required>
            <option value="">Sélectionner un médecin</option>
            <?php foreach ($medecins as $medecin): ?>
              <option value="<?php echo $medecin['id']; ?>">
                Dr. <?php echo escape($medecin['prenom'] . ' ' . $medecin['nom']); ?> - <?php echo escape($medecin['specialite']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Rendez-vous associé (optionnel)</label>
          <select class="form-control" name="rdv_id" id="rdv_id">
            <option value="">Aucun rendez-vous associé</option>
            <?php foreach ($rendezvous as $rdv): ?>
              <option value="<?php echo $rdv['id']; ?>">
                RDV #<?php echo $rdv['id']; ?> - <?php echo escape($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']); ?> 
                (<?php echo date('d/m/Y H:i', strtotime($rdv['date_rdv'])); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Date de consultation *</label>
          <input type="datetime-local" class="form-control" name="date_consultation" id="date_consultation" 
                 value="<?php echo date('Y-m-d\TH:i'); ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Motif de consultation *</label>
          <textarea class="form-control" name="motif" id="motif" rows="3" required 
                    placeholder="Décrivez le motif de la consultation..."></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Diagnostic (optionnel)</label>
          <textarea class="form-control" name="diagnostic" id="diagnostic" rows="3" 
                    placeholder="Diagnostic établi..."></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Traitement prescrit (optionnel)</label>
          <textarea class="form-control" name="traitement" id="traitement" rows="3" 
                    placeholder="Traitement recommandé..."></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Examens complémentaires (optionnel)</label>
          <textarea class="form-control" name="examens" id="examens" rows="2" 
                    placeholder="Examens à réaliser..."></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Notes supplémentaires (optionnel)</label>
          <textarea class="form-control" name="notes" id="notes" rows="3" 
                    placeholder="Notes cliniques, observations..."></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select class="form-control" name="statut" id="statut">
            <option value="en_cours">En cours</option>
            <option value="termine">Terminée</option>
          </select>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
          </button>
          <button type="button" class="btn" onclick="closeModal('consultationFormModal')" 
                  style="background: linear-gradient(135deg, var(--warning), #b45309);">
            <i class="fas fa-times"></i> Annuler
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <script>
    // Fonctions JavaScript pour la gestion des consultations
    function openConsultationForm(consultationId = null) {
      if (consultationId) {
        // Charger les données de la consultation pour édition via AJAX
        fetch('get_consultation.php?id=' + consultationId)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const consultation = data.consultation;
              document.getElementById('consultation_id').value = consultation.id;
              document.getElementById('patient_id').value = consultation.patient_id;
              document.getElementById('medecin_id').value = consultation.medecin_id;
              document.getElementById('rdv_id').value = consultation.rdv_id || '';
              
              // Formater la date pour datetime-local
              const dateTime = new Date(consultation.date_consultation);
              const formattedDate = dateTime.toISOString().slice(0, 16);
              document.getElementById('date_consultation').value = formattedDate;
              
              document.getElementById('motif').value = consultation.motif || '';
              document.getElementById('diagnostic').value = consultation.diagnostic || '';
              document.getElementById('traitement').value = consultation.traitement || '';
              document.getElementById('examens').value = consultation.examens || '';
              document.getElementById('notes').value = consultation.notes || '';
              document.getElementById('statut').value = consultation.statut || 'en_cours';
              document.getElementById('formTitle').textContent = 'Modifier Consultation';
            }
          })
          .catch(error => {
            alert('Erreur lors du chargement des données de la consultation');
            console.error(error);
          });
      } else {
        // Réinitialiser le formulaire pour une nouvelle consultation
        document.getElementById('consultationForm').reset();
        document.getElementById('consultation_id').value = '';
        document.getElementById('date_consultation').value = new Date().toISOString().slice(0, 16);
        document.getElementById('statut').value = 'en_cours';
        document.getElementById('formTitle').textContent = 'Nouvelle Consultation';
      }
      openModal('consultationFormModal');
    }
    
    function viewConsultation(consultationId) {
      window.location.href = 'consultation_details.php?id=' + consultationId;
    }
    
    function editConsultation(consultationId) {
      openConsultationForm(consultationId);
    }
    
    function deleteConsultation(consultationId) {
      if (confirm('Êtes-vous sûr de vouloir supprimer cette consultation ? Cette action est irréversible.')) {
        window.location.href = 'consultations.php?action=delete&id=' + consultationId;
      }
    }
    
    function completeConsultation(consultationId) {
      if (confirm('Êtes-vous sûr de vouloir marquer cette consultation comme terminée ?')) {
        window.location.href = 'consultations.php?action=complete&id=' + consultationId;
      }
    }
    
    function openModal(modalId) {
      document.getElementById(modalId).style.display = 'block';
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
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
<?php
// Fin du fichier PHP
?>
