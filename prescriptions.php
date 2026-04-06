
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

// Fonction pour formater la date
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

// Gestion des opérations CRUD
$action = $_GET['action'] ?? '';
$prescription_id = $_GET['id'] ?? 0;
$message = '';
$success = false;

// Traitement des formulaires POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    
    // Récupération des données du formulaire
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $medecin_id = (int)($_POST['medecin_id'] ?? 0);
    $consultation_id = !empty($_POST['consultation_id']) ? (int)$_POST['consultation_id'] : null;
    $medicament = trim($_POST['medicament'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $frequence = trim($_POST['frequence'] ?? '');
    $duree = trim($_POST['duree'] ?? '');
    $date_debut = $_POST['date_debut'] ?? date('Y-m-d');
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;
    $instructions = trim($_POST['instructions'] ?? '');
    $statut = $_POST['statut'] ?? 'en_cours';
    
    // Validation
    $errors = [];
    if ($patient_id <= 0) $errors[] = "Patient requis";
    if ($medecin_id <= 0) $errors[] = "Médecin requis";
    if (empty($medicament)) $errors[] = "Médicament requis";
    if (empty($date_debut)) $errors[] = "Date de début requise";
    
    if (empty($errors)) {
        if (isset($_POST['prescription_id']) && !empty($_POST['prescription_id'])) {
            // Mise à jour de la prescription existante
            $prescription_id = (int)$_POST['prescription_id'];
            $stmt = $conn->prepare("UPDATE prescriptions SET 
                patient_id = ?, medecin_id = ?, consultation_id = ?, 
                medicament = ?, dosage = ?, frequence = ?, duree = ?,
                date_debut = ?, date_fin = ?, instructions = ?, statut = ? 
                WHERE id = ?");
            $stmt->bind_param("iiissssssssi", 
                $patient_id, $medecin_id, $consultation_id,
                $medicament, $dosage, $frequence, $duree,
                $date_debut, $date_fin, $instructions, $statut, $prescription_id);
            
            if ($stmt->execute()) {
                $message = "Prescription mise à jour avec succès";
                $success = true;
            } else {
                $message = "Erreur lors de la mise à jour: " . $conn->error;
            }
            $stmt->close();
        } else {
            // Création d'une nouvelle prescription
            $stmt = $conn->prepare("INSERT INTO prescriptions 
                (patient_id, medecin_id, consultation_id, 
                 medicament, dosage, frequence, duree, 
                 date_debut, date_fin, instructions, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiissssssss", 
                $patient_id, $medecin_id, $consultation_id,
                $medicament, $dosage, $frequence, $duree,
                $date_debut, $date_fin, $instructions, $statut);
            
            if ($stmt->execute()) {
                $message = "Prescription créée avec succès";
                $success = true;
                $prescription_id = $conn->insert_id;
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
        header("Location: prescriptions.php?message=" . urlencode($message) . "&success=1");
        exit();
    }
}

// Suppression d'une prescription
if ($action === 'delete' && $prescription_id > 0) {
    $conn = connectDB();
    
    // Vérifier si la prescription existe
    $stmt = $conn->prepare("SELECT * FROM prescriptions WHERE id = ?");
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Supprimer la prescription
        $deleteStmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ?");
        $deleteStmt->bind_param("i", $prescription_id);
        
        if ($deleteStmt->execute()) {
            $message = "Prescription supprimée avec succès";
            $success = true;
        } else {
            $message = "Erreur lors de la suppression: " . $conn->error;
        }
        $deleteStmt->close();
    } else {
        $message = "Prescription non trouvée";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: prescriptions.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

// Terminer une prescription
if ($action === 'complete' && $prescription_id > 0) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("UPDATE prescriptions SET statut = 'termine' WHERE id = ?");
    $stmt->bind_param("i", $prescription_id);
    
    if ($stmt->execute()) {
        $message = "Prescription marquée comme terminée";
        $success = true;
    } else {
        $message = "Erreur: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: prescriptions.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

// Suspendre une prescription
if ($action === 'suspend' && $prescription_id > 0) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("UPDATE prescriptions SET statut = 'suspendu' WHERE id = ?");
    $stmt->bind_param("i", $prescription_id);
    
    if ($stmt->execute()) {
        $message = "Prescription suspendue";
        $success = true;
    } else {
        $message = "Erreur: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: prescriptions.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

// Récupération des données pour l'affichage
$conn = connectDB();

// Récupérer les prescriptions pour affichage (exemple de données)
$prescriptions = [
    [
        'id' => 1,
        'patient_nom' => 'Léa Martin',
        'medicament' => 'Amoxicilline 500mg',
        'dosage' => '1 comprimé',
        'frequence' => '3x/jour',
        'date_debut' => '2025-12-09',
        'date_fin' => '2025-12-16',
        'statut' => 'en_cours',
        'type' => 'Antibiotique - Classe A'
    ],
    [
        'id' => 2,
        'patient_nom' => 'Karim Benali',
        'medicament' => 'Paracétamol 500mg',
        'dosage' => '1 comprimé',
        'frequence' => 'Si besoin',
        'date_debut' => '2025-12-08',
        'date_fin' => '2026-01-07',
        'statut' => 'en_cours',
        'type' => 'Antalgique - Classe B'
    ],
    [
        'id' => 3,
        'patient_nom' => 'Sarah Martin',
        'medicament' => 'Isotrétinoïne 20mg',
        'dosage' => '1 gélule',
        'frequence' => '1x/jour',
        'date_debut' => '2025-12-07',
        'date_fin' => '2026-03-06',
        'statut' => 'en_cours',
        'type' => 'Rétinoïde - Classe X'
    ],
    [
        'id' => 4,
        'patient_nom' => 'Pierre Dubois',
        'medicament' => 'Amlodipine 5mg',
        'dosage' => '1 comprimé',
        'frequence' => '1x/jour',
        'date_debut' => '2025-11-01',
        'date_fin' => '2026-02-01',
        'statut' => 'termine',
        'type' => 'Antihypertenseur - Classe C'
    ],
    [
        'id' => 5,
        'patient_nom' => 'Sophie Lambert',
        'medicament' => 'Levothyrox 75μg',
        'dosage' => '1 comprimé',
        'frequence' => '1x/jour',
        'date_debut' => '2025-10-01',
        'date_fin' => '2026-04-01',
        'statut' => 'en_cours',
        'type' => 'Hormone thyroïdienne'
    ]
];

// Récupérer les patients pour les select
$patients = $conn->query("SELECT id, nom, prenom FROM patients ORDER BY nom, prenom")->fetch_all(MYSQLI_ASSOC);

// Récupérer les médecins pour les select
$medecins = $conn->query("SELECT id, nom, prenom, specialite FROM medecins WHERE statut = 'actif' ORDER BY nom, prenom")->fetch_all(MYSQLI_ASSOC);

// Récupérer les consultations pour les select
$consultations_list = $conn->query("SELECT c.id, c.date_consultation, p.nom as patient_nom, p.prenom as patient_prenom 
                                  FROM consultations c 
                                  LEFT JOIN patients p ON c.patient_id = p.id
                                  ORDER BY c.date_consultation DESC")->fetch_all(MYSQLI_ASSOC);

// Si création depuis une consultation
$consultation_id_param = $_GET['consultation_id'] ?? 0;
if ($consultation_id_param > 0) {
    // Récupérer les infos de la consultation pour pré-remplir le formulaire
    $stmt = $conn->prepare("SELECT c.*, p.id as patient_id, m.id as medecin_id 
                           FROM consultations c
                           LEFT JOIN patients p ON c.patient_id = p.id
                           LEFT JOIN medecins m ON c.medecin_id = m.id
                           WHERE c.id = ?");
    $stmt->bind_param("i", $consultation_id_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $consult_info = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Prescriptions</title>
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
      min-height: 80px;
      resize: vertical;
    }
    
    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    
    .status.suspendu {
      background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
      color: #6b7280;
      border: 1px solid rgba(107, 114, 128, 0.2);
    }
    
    .status.suspendu::before {
      background: #6b7280;
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
        </a>
        <a href="rendezvous.php" class="nav-btn">
          <i class="fas fa-calendar-check nav-icon"></i> Rendez-vous
        </a>
        <a href="consultations.php" class="nav-btn">
          <i class="fas fa-stethoscope nav-icon"></i> Consultations
        </a>
        <a href="prescriptions.php" class="nav-btn active">
          <i class="fas fa-pills nav-icon"></i> Prescriptions
          <span class="notification-badge"><?php echo count(array_filter($prescriptions, function($p) { return $p['statut'] == 'en_cours'; })); ?></span>
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
      <section id="prescriptions-view" class="view">
        <div class="page-header">
          <div>
            <h1 class="page-title">Prescriptions</h1>
            <p class="page-subtitle">Gestion des ordonnances médicales et suivi des traitements</p>
          </div>
          <button class="btn btn-primary" onclick="openPrescriptionForm()">
            <i class="fas fa-prescription-bottle-medical"></i> Nouvelle prescription
          </button>
        </div>
        
        <!-- Messages d'erreur/succès -->
        <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-<?php echo (isset($_GET['success']) && $_GET['success'] == 1) ? 'success' : 'error'; ?>">
          <i class="fas <?php echo (isset($_GET['success']) && $_GET['success'] == 1) ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
          <?php echo escape($_GET['message']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Contenu des prescriptions... -->
        <div class="table-container">
          <div class="table-header">
            <div style="font-size: 1.1rem; font-weight: 600;">
              Prescriptions actives (<?php echo count(array_filter($prescriptions, function($p) { return $p['statut'] == 'en_cours'; })); ?> en cours)
            </div>
            <div style="display: flex; gap: 1rem;">
              <select class="form-control" style="width: auto; padding: 0.5rem 1rem;">
                <option>Tous les statuts</option>
                <option>En cours</option>
                <option>À renouveler</option>
                <option>Terminée</option>
                <option>Suspendue</option>
              </select>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Patient</th>
                <th>Médicament</th>
                <th>Dosage</th>
                <th>Fréquence</th>
                <th>Début</th>
                <th>Fin</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($prescriptions as $prescription): ?>
              <tr>
                <td><?php echo escape($prescription['patient_nom']); ?></td>
                <td>
                  <strong><?php echo escape($prescription['medicament']); ?></strong><br>
                  <small><?php echo escape($prescription['type']); ?></small>
                </td>
                <td><?php echo escape($prescription['dosage']); ?></td>
                <td><?php echo escape($prescription['frequence']); ?></td>
                <td><?php echo formatDate($prescription['date_debut']); ?></td>
                <td><?php echo $prescription['date_fin'] ? formatDate($prescription['date_fin']) : '--'; ?></td>
                <td>
                  <?php if ($prescription['statut'] == 'en_cours'): ?>
                    <span class="status confirme">En cours</span>
                  <?php elseif ($prescription['statut'] == 'termine'): ?>
                    <span class="status terminee">Terminée</span>
                  <?php elseif ($prescription['statut'] == 'suspendu'): ?>
                    <span class="status suspendu">Suspendue</span>
                  <?php else: ?>
                    <span class="status planifie">À renouveler</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="table-actions">
                    <button class="action-btn btn-view" onclick="viewPrescription(<?php echo $prescription['id']; ?>)" title="Voir l'ordonnance complète">
                      <i class="fas fa-eye"></i> Voir
                    </button>
                    <button class="action-btn btn-edit" onclick="editPrescription(<?php echo $prescription['id']; ?>)" title="Modifier la prescription">
                      <i class="fas fa-edit"></i> Éditer
                    </button>
                    <?php if ($prescription['statut'] == 'en_cours'): ?>
                      <button class="action-btn btn-confirm" onclick="completePrescription(<?php echo $prescription['id']; ?>)" title="Marquer comme terminée">
                        <i class="fas fa-check-circle"></i> Terminer
                      </button>
                      <button class="action-btn" style="background: linear-gradient(135deg, var(--warning), #b45309);" 
                              onclick="suspendPrescription(<?php echo $prescription['id']; ?>)" title="Suspendre le traitement">
                        <i class="fas fa-pause"></i> Suspendre
                      </button>
                    <?php endif; ?>
                    <button class="action-btn btn-delete" onclick="deletePrescription(<?php echo $prescription['id']; ?>)" title="Supprimer la prescription">
                      <i class="fas fa-trash"></i> Suppr.
                    </button>
                    <button class="action-btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);" 
                            onclick="downloadPDF(<?php echo $prescription['id']; ?>)" title="Télécharger PDF">
                      <i class="fas fa-download"></i> PDF
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
  
  <!-- Modal pour formulaire prescription -->
  <div id="prescriptionFormModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('prescriptionFormModal')">&times;</span>
      <h2 id="formTitle">Nouvelle Prescription</h2>
      <form method="POST" action="prescriptions.php" id="prescriptionForm">
        <input type="hidden" name="prescription_id" id="prescription_id" value="">
        
        <div class="form-group">
          <label class="form-label">Patient *</label>
          <select class="form-control" name="patient_id" id="patient_id" required onchange="updatePatientInfo()">
            <option value="">Sélectionner un patient</option>
            <?php foreach ($patients as $patient): ?>
              <option value="<?php echo $patient['id']; ?>">
                <?php echo escape($patient['prenom'] . ' ' . $patient['nom']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Médecin prescripteur *</label>
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
          <label class="form-label">Consultation associée (optionnel)</label>
          <select class="form-control" name="consultation_id" id="consultation_id">
            <option value="">Aucune consultation associée</option>
            <?php foreach ($consultations_list as $consult): ?>
              <option value="<?php echo $consult['id']; ?>">
                Consultation #<?php echo $consult['id']; ?> - <?php echo escape($consult['patient_prenom'] . ' ' . $consult['patient_nom']); ?> 
                (<?php echo formatDate($consult['date_consultation'], 'd/m/Y'); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Médicament *</label>
          <input type="text" class="form-control" name="medicament" id="medicament" required 
                 placeholder="Ex: Amoxicilline 500mg">
        </div>
        
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Dosage *</label>
            <input type="text" class="form-control" name="dosage" id="dosage" required 
                   placeholder="Ex: 1 comprimé, 10ml, 2 gélules">
          </div>
          
          <div class="form-group">
            <label class="form-label">Fréquence *</label>
            <input type="text" class="form-control" name="frequence" id="frequence" required 
                   placeholder="Ex: 3x/jour, 1x/semaine, si besoin">
          </div>
        </div>
        
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Durée du traitement</label>
            <input type="text" class="form-control" name="duree" id="duree" 
                   placeholder="Ex: 7 jours, 1 mois, 3 cycles">
          </div>
          
          <div class="form-group">
            <label class="form-label">Date de début *</label>
            <input type="date" class="form-control" name="date_debut" id="date_debut" 
                   value="<?php echo date('Y-m-d'); ?>" required>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Date de fin (optionnel)</label>
          <input type="date" class="form-control" name="date_fin" id="date_fin">
        </div>
        
        <div class="form-group">
          <label class="form-label">Instructions (optionnel)</label>
          <textarea class="form-control" name="instructions" id="instructions" rows="3" 
                    placeholder="Instructions particulières..."></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select class="form-control" name="statut" id="statut">
            <option value="en_cours">En cours</option>
            <option value="termine">Terminée</option>
            <option value="suspendu">Suspendue</option>
          </select>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
          </button>
          <button type="button" class="btn" onclick="closeModal('prescriptionFormModal')" 
                  style="background: linear-gradient(135deg, var(--warning), #b45309);">
            <i class="fas fa-times"></i> Annuler
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <script>
    // Fonctions JavaScript pour la gestion des prescriptions
    function openPrescriptionForm(prescriptionId = null) {
      if (prescriptionId) {
        // Charger les données de la prescription pour édition via AJAX
        fetch('get_prescription.php?id=' + prescriptionId)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const prescription = data.prescription;
              document.getElementById('prescription_id').value = prescription.id;
              document.getElementById('patient_id').value = prescription.patient_id;
              document.getElementById('medecin_id').value = prescription.medecin_id;
              document.getElementById('consultation_id').value = prescription.consultation_id || '';
              document.getElementById('medicament').value = prescription.medicament || '';
              document.getElementById('dosage').value = prescription.dosage || '';
              document.getElementById('frequence').value = prescription.frequence || '';
              document.getElementById('duree').value = prescription.duree || '';
              document.getElementById('date_debut').value = prescription.date_debut || '<?php echo date("Y-m-d"); ?>';
              document.getElementById('date_fin').value = prescription.date_fin || '';
              document.getElementById('instructions').value = prescription.instructions || '';
              document.getElementById('statut').value = prescription.statut || 'en_cours';
              document.getElementById('formTitle').textContent = 'Modifier Prescription';
            }
          })
          .catch(error => {
            alert('Erreur lors du chargement des données de la prescription');
            console.error(error);
          });
      } else {
        // Réinitialiser le formulaire pour une nouvelle prescription
        document.getElementById('prescriptionForm').reset();
        document.getElementById('prescription_id').value = '';
        document.getElementById('date_debut').value = '<?php echo date("Y-m-d"); ?>';
        document.getElementById('statut').value = 'en_cours';
        document.getElementById('formTitle').textContent = 'Nouvelle Prescription';
        
        // Si création depuis une consultation, pré-remplir
        <?php if (isset($consultation_id_param) && $consultation_id_param > 0 && isset($consult_info)): ?>
          document.getElementById('patient_id').value = <?php echo $consult_info['patient_id']; ?>;
          document.getElementById('medecin_id').value = <?php echo $consult_info['medecin_id']; ?>;
          document.getElementById('consultation_id').value = <?php echo $consultation_id_param; ?>;
        <?php endif; ?>
      }
      openModal('prescriptionFormModal');
    }
    
    function viewPrescription(prescriptionId) {
      window.location.href = 'prescription_details.php?id=' + prescriptionId;
    }
    
    function editPrescription(prescriptionId) {
      openPrescriptionForm(prescriptionId);
    }
    
    function deletePrescription(prescriptionId) {
      if (confirm('Êtes-vous sûr de vouloir supprimer cette prescription ? Cette action est irréversible.')) {
        window.location.href = 'prescriptions.php?action=delete&id=' + prescriptionId;
      }
    }
    
    function completePrescription(prescriptionId) {
      if (confirm('Êtes-vous sûr de vouloir marquer cette prescription comme terminée ?')) {
        window.location.href = 'prescriptions.php?action=complete&id=' + prescriptionId;
      }
    }
    
    function suspendPrescription(prescriptionId) {
      if (confirm('Êtes-vous sûr de vouloir suspendre cette prescription ?')) {
        window.location.href = 'prescriptions.php?action=suspend&id=' + prescriptionId;
      }
    }
    
    function downloadPDF(prescriptionId) {
      alert('Génération du PDF en cours pour la prescription #' + prescriptionId);
      // Ici, vous pourriez rediriger vers un script qui génère le PDF
      // window.open('generate_pdf.php?id=' + prescriptionId, '_blank');
    }
    
    function openModal(modalId) {
      document.getElementById(modalId).style.display = 'block';
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }
    
    // Mettre à jour les informations du patient
    function updatePatientInfo() {
      const patientId = document.getElementById('patient_id').value;
      if (patientId) {
        // Pourrait charger les informations du patient via AJAX
        // Ex: allergies, autres prescriptions en cours, etc.
      }
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
    
    // Initialiser le formulaire si on est en mode édition
    <?php if ($action === 'edit' && $prescription_id > 0): ?>
      document.addEventListener('DOMContentLoaded', function() {
        openPrescriptionForm(<?php echo $prescription_id; ?>);
      });
    <?php elseif (isset($consultation_id_param) && $consultation_id_param > 0): ?>
      document.addEventListener('DOMContentLoaded', function() {
        openPrescriptionForm();
      });
    <?php endif; ?>
    
    // Auto-complétion des médicaments (exemple simple)
    const medicaments = [
      'Amoxicilline 500mg', 'Paracétamol 500mg', 'Ibuprofène 400mg',
      'Levothyrox 75μg', 'Amlodipine 5mg', 'Metformine 850mg',
      'Atorvastatine 20mg', 'Oméprazole 20mg', 'Losartan 50mg',
      'Insuline glargine', 'Salbutamol spray', 'Fluoxétine 20mg'
    ];
    
    document.getElementById('medicament')?.addEventListener('input', function(e) {
      const input = e.target.value.toLowerCase();
      if (input.length > 2) {
        // Pourrait afficher une liste de suggestions
      }
    });
  </script>
  
  <script src="script.js"></script>
</body>
</html>
<?php
// Fin du fichier PHP
?>
