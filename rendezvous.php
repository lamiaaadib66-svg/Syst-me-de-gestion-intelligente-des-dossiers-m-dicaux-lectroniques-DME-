
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
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

// Gestion des opérations CRUD
$action = $_GET['action'] ?? '';
$rdv_id = $_GET['id'] ?? 0;
$message = '';
$success = false;

// Traitement des formulaires POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    
    // Récupération des données du formulaire
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $medecin_id = (int)($_POST['medecin_id'] ?? 0);
    $date_rdv = $_POST['date_rdv'] ?? '';
    $heure_rdv = $_POST['heure_rdv'] ?? '';
    $duree = (int)($_POST['duree'] ?? 30);
    $motif = trim($_POST['motif'] ?? '');
    $salle = trim($_POST['salle'] ?? '');
    $statut = $_POST['statut'] ?? 'planifie';
    $notes = trim($_POST['notes'] ?? '');
    
    // Combiner date et heure
    $datetime_rdv = $date_rdv . ' ' . $heure_rdv;
    
    // Validation
    $errors = [];
    if ($patient_id <= 0) $errors[] = "Patient requis";
    if ($medecin_id <= 0) $errors[] = "Médecin requis";
    if (empty($datetime_rdv) || $datetime_rdv == ' ') $errors[] = "Date et heure requises";
    if (empty($motif)) $errors[] = "Motif requis";
    
    if (empty($errors)) {
        if (isset($_POST['rdv_id']) && !empty($_POST['rdv_id'])) {
            // Mise à jour du rendez-vous existant
            $rdv_id = (int)$_POST['rdv_id'];
            $stmt = $conn->prepare("UPDATE rendezvous SET 
                patient_id = ?, medecin_id = ?, date_rdv = ?, duree = ?, 
                motif = ?, salle = ?, statut = ?, notes = ? 
                WHERE id = ?");
            $stmt->bind_param("iisissssi", 
                $patient_id, $medecin_id, $datetime_rdv, $duree,
                $motif, $salle, $statut, $notes, $rdv_id);
            
            if ($stmt->execute()) {
                $message = "Rendez-vous mis à jour avec succès";
                $success = true;
            } else {
                $message = "Erreur lors de la mise à jour: " . $conn->error;
            }
            $stmt->close();
        } else {
            // Création d'un nouveau rendez-vous
            $stmt = $conn->prepare("INSERT INTO rendezvous 
                (patient_id, medecin_id, date_rdv, duree, motif, salle, statut, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisissss", 
                $patient_id, $medecin_id, $datetime_rdv, $duree,
                $motif, $salle, $statut, $notes);
            
            if ($stmt->execute()) {
                $message = "Rendez-vous créé avec succès";
                $success = true;
                $rdv_id = $conn->insert_id;
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
        header("Location: rendezvous.php?message=" . urlencode($message) . "&success=1");
        exit();
    }
}

// Suppression d'un rendez-vous
if ($action === 'delete' && $rdv_id > 0) {
    $conn = connectDB();
    
    // Vérifier si le rendez-vous existe
    $stmt = $conn->prepare("SELECT * FROM rendezvous WHERE id = ?");
    $stmt->bind_param("i", $rdv_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Supprimer le rendez-vous
        $deleteStmt = $conn->prepare("DELETE FROM rendezvous WHERE id = ?");
        $deleteStmt->bind_param("i", $rdv_id);
        
        if ($deleteStmt->execute()) {
            $message = "Rendez-vous supprimé avec succès";
            $success = true;
        } else {
            $message = "Erreur lors de la suppression: " . $conn->error;
        }
        $deleteStmt->close();
    } else {
        $message = "Rendez-vous non trouvé";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: rendezvous.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

// Confirmer un rendez-vous
if ($action === 'confirm' && $rdv_id > 0) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("UPDATE rendezvous SET statut = 'confirme' WHERE id = ?");
    $stmt->bind_param("i", $rdv_id);
    
    if ($stmt->execute()) {
        $message = "Rendez-vous confirmé avec succès";
        $success = true;
    } else {
        $message = "Erreur lors de la confirmation: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: rendezvous.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

// Annuler un rendez-vous
if ($action === 'cancel' && $rdv_id > 0) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("UPDATE rendezvous SET statut = 'annule' WHERE id = ?");
    $stmt->bind_param("i", $rdv_id);
    
    if ($stmt->execute()) {
        $message = "Rendez-vous annulé avec succès";
        $success = true;
    } else {
        $message = "Erreur lors de l'annulation: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: rendezvous.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

// Terminer un rendez-vous
if ($action === 'complete' && $rdv_id > 0) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("UPDATE rendezvous SET statut = 'termine' WHERE id = ?");
    $stmt->bind_param("i", $rdv_id);
    
    if ($stmt->execute()) {
        $message = "Rendez-vous marqué comme terminé";
        $success = true;
    } else {
        $message = "Erreur: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: rendezvous.php?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0"));
    exit();
}

// Récupération des données pour l'affichage
$conn = connectDB();

// Récupérer les rendez-vous pour affichage
$sql = "SELECT r.*, 
               p.nom as patient_nom, p.prenom as patient_prenom,
               m.nom as medecin_nom, m.prenom as medecin_prenom, m.specialite as medecin_specialite
        FROM rendezvous r 
        LEFT JOIN patients p ON r.patient_id = p.id
        LEFT JOIN medecins m ON r.medecin_id = m.id
        ORDER BY r.date_rdv DESC 
        LIMIT 50";

$result = $conn->query($sql);
$rendezvous = $result->fetch_all(MYSQLI_ASSOC);

// Récupérer les patients pour les select
$patients = $conn->query("SELECT id, nom, prenom FROM patients ORDER BY nom, prenom")->fetch_all(MYSQLI_ASSOC);

// Récupérer les médecins pour les select
$medecins = $conn->query("SELECT id, nom, prenom, specialite FROM medecins WHERE statut = 'actif' ORDER BY nom, prenom")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Rendez-vous</title>
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
          <span class="notification-badge"><?php echo count($patients); ?></span>
        </a>
        <a href="rendezvous.php" class="nav-btn active">
          <i class="fas fa-calendar-check nav-icon"></i> Rendez-vous
          <span class="notification-badge"><?php echo count($rendezvous); ?></span>
        </a>
        <a href="consultations.php" class="nav-btn">
          <i class="fas fa-stethoscope nav-icon"></i> Consultations
        </a>
        <a href="prescriptions.php" class="nav-btn">
          <i class="fas fa-pills nav-icon"></i> Prescriptions
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
      <section id="rendezvous-view" class="view">
        <div class="page-header">
          <div>
            <h1 class="page-title">Rendez-vous</h1>
            <p class="page-subtitle">Agenda médical complet et gestion des rendez-vous</p>
          </div>
          <div style="display: flex; gap: 1rem;">
            <button class="btn btn-primary" onclick="openRdvForm()">
              <i class="fas fa-calendar-plus"></i> Nouveau RDV
            </button>
            <button class="btn" style="background: linear-gradient(135deg, var(--info), #0284c7);">
              <i class="fas fa-print"></i> Imprimer planning
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
        
        <!-- Contenu des rendez-vous... -->
        <div class="table-container">
          <div class="table-header">
            <div style="display: flex; align-items: center; gap: 2rem;">
              <div style="font-size: 1.1rem; font-weight: 600;">
                <i class="fas fa-calendar-week"></i> Semaine du 9-15 Décembre 2025
              </div>
              <div style="display: flex; gap: 0.5rem;">
                <button class="action-btn" style="background: var(--text-secondary);">
                  <i class="fas fa-chevron-left"></i>
                </button>
                <button class="action-btn" style="background: var(--primary);">
                  Aujourd'hui
                </button>
                <button class="action-btn" style="background: var(--text-secondary);">
                  <i class="fas fa-chevron-right"></i>
                </button>
              </div>
            </div>
            <div style="display: flex; gap: 1rem;">
              <select class="form-control" style="width: auto; padding: 0.5rem 1rem;">
                <option>Tous les médecins</option>
                <option>Dr. Alice Dupont</option>
                <option>Dr. Karim Benali</option>
                <option>Dr. Sarah Martin</option>
              </select>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Date/Heure</th>
                <th>Patient</th>
                <th>Médecin</th>
                <th>Motif</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rendezvous as $rdv): ?>
              <tr>
                <td>
                  <strong><?php echo formatDate($rdv['date_rdv'], 'd M H:i'); ?></strong><br>
                  <small><?php echo escape($rdv['salle'] ?: 'Salle ' . rand(1, 5)); ?></small>
                </td>
                <td><?php echo escape($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']); ?></td>
                <td>Dr. <?php echo escape($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']); ?></td>
                <td><?php echo escape($rdv['motif'] ?: 'Consultation générale'); ?></td>
                <td>
                  <?php 
                  $status = $rdv['statut'] ?? 'planifie';
                  $statusText = [
                    'planifie' => 'Planifié',
                    'confirme' => 'Confirmé',
                    'annule' => 'Annulé',
                    'termine' => 'Terminé'
                  ];
                  $statusClass = [
                    'planifie' => 'planifie',
                    'confirme' => 'confirme',
                    'annule' => 'annule',
                    'termine' => 'termine'
                  ];
                  ?>
                  <span class="status <?php echo $statusClass[$status] ?? 'planifie'; ?>">
                    <?php echo $statusText[$status] ?? 'Planifié'; ?>
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <button class="action-btn btn-view" onclick="viewRdv(<?php echo $rdv['id']; ?>)" title="Détails">
                      <i class="fas fa-eye"></i>Détails
                    </button>
                    <button class="action-btn btn-edit" onclick="editRdv(<?php echo $rdv['id']; ?>)" title="Modifier">
                      <i class="fas fa-edit"></i>Modifier
                    </button>
                    <?php if ($status == 'planifie'): ?>
                      <button class="action-btn btn-confirm" onclick="confirmRdv(<?php echo $rdv['id']; ?>)" title="Confirmer">
                        <i class="fas fa-check"></i>Confirmer
                      </button>
                    <?php elseif ($status == 'confirme'): ?>
                      <button class="action-btn" style="background: linear-gradient(135deg, var(--success), #047857);" onclick="completeRdv(<?php echo $rdv['id']; ?>)" title="Terminer">
                        <i class="fas fa-check-circle"></i>Terminer
                      </button>
                    <?php endif; ?>
                    <button class="action-btn btn-delete" onclick="deleteRdv(<?php echo $rdv['id']; ?>)" title="Annuler">
                      <i class="fas fa-times"></i>Annuler
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
  
  <!-- Modal pour formulaire rendez-vous -->
  <div id="rdvFormModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('rdvFormModal')">&times;</span>
      <h2 id="formTitle">Nouveau Rendez-vous</h2>
      <form method="POST" action="rendezvous.php" id="rdvForm">
        <input type="hidden" name="rdv_id" id="rdv_id" value="">
        
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
        
        <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" class="form-control" name="date_rdv" id="date_rdv" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">Heure *</label>
            <input type="time" class="form-control" name="heure_rdv" id="heure_rdv" required>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Durée (minutes)</label>
          <input type="number" class="form-control" name="duree" id="duree" value="30" min="5" max="180">
        </div>
        
        <div class="form-group">
          <label class="form-label">Motif *</label>
          <input type="text" class="form-control" name="motif" id="motif" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Salle</label>
          <input type="text" class="form-control" name="salle" id="salle" placeholder="Ex: Salle 1">
        </div>
        
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select class="form-control" name="statut" id="statut">
            <option value="planifie">Planifié</option>
            <option value="confirme">Confirmé</option>
            <option value="annule">Annulé</option>
            <option value="termine">Terminé</option>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea class="form-control" name="notes" id="notes" rows="3" 
                    placeholder="Notes supplémentaires..."></textarea>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
          </button>
          <button type="button" class="btn" onclick="closeModal('rdvFormModal')" 
                  style="background: linear-gradient(135deg, var(--warning), #b45309);">
            <i class="fas fa-times"></i> Annuler
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <script>
    // Fonctions JavaScript pour la gestion des rendez-vous
    function openRdvForm(rdvId = null) {
      if (rdvId) {
        // Charger les données du rendez-vous pour édition via AJAX
        fetch('get_rdv.php?id=' + rdvId)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const rdv = data.rdv;
              document.getElementById('rdv_id').value = rdv.id;
              document.getElementById('patient_id').value = rdv.patient_id;
              document.getElementById('medecin_id').value = rdv.medecin_id;
              
              // Séparer date et heure
              const dateTime = new Date(rdv.date_rdv);
              document.getElementById('date_rdv').value = dateTime.toISOString().split('T')[0];
              document.getElementById('heure_rdv').value = dateTime.toTimeString().substring(0, 5);
              
              document.getElementById('duree').value = rdv.duree;
              document.getElementById('motif').value = rdv.motif || '';
              document.getElementById('salle').value = rdv.salle || '';
              document.getElementById('statut').value = rdv.statut || 'planifie';
              document.getElementById('notes').value = rdv.notes || '';
              document.getElementById('formTitle').textContent = 'Modifier Rendez-vous';
            }
          })
          .catch(error => {
            alert('Erreur lors du chargement des données du rendez-vous');
            console.error(error);
          });
      } else {
        // Réinitialiser le formulaire pour un nouveau rendez-vous
        document.getElementById('rdvForm').reset();
        document.getElementById('rdv_id').value = '';
        document.getElementById('date_rdv').value = new Date().toISOString().split('T')[0];
        document.getElementById('heure_rdv').value = '09:00';
        document.getElementById('duree').value = '30';
        document.getElementById('statut').value = 'planifie';
        document.getElementById('formTitle').textContent = 'Nouveau Rendez-vous';
      }
      openModal('rdvFormModal');
    }
    
    function viewRdv(rdvId) {
      // Rediriger vers une page de détails ou ouvrir une modal
      window.location.href = 'rdv_details.php?id=' + rdvId;
    }
    
    function editRdv(rdvId) {
      openRdvForm(rdvId);
    }
    
    function confirmRdv(rdvId) {
      if (confirm('Êtes-vous sûr de vouloir confirmer ce rendez-vous ?')) {
        window.location.href = 'rendezvous.php?action=confirm&id=' + rdvId;
      }
    }
    
    function deleteRdv(rdvId) {
      if (confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ? Cette action est irréversible.')) {
        window.location.href = 'rendezvous.php?action=delete&id=' + rdvId;
      }
    }
    
    function completeRdv(rdvId) {
      if (confirm('Êtes-vous sûr de vouloir marquer ce rendez-vous comme terminé ?')) {
        window.location.href = 'rendezvous.php?action=complete&id=' + rdvId;
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
