[file name]: rdv_details.php
[file content begin]
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

// Vérifier si l'ID du rendez-vous est fourni
$rdv_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rdv_id <= 0) {
    header("Location: rendezvous.php?message=Rendez-vous+non+trouvé&success=0");
    exit();
}

// Récupérer les données du rendez-vous
$conn = connectDB();

$query = "SELECT r.*, 
                 p.id as patient_id, p.nom as patient_nom, p.prenom as patient_prenom, 
                 p.date_naissance, p.genre, p.telephone as patient_tel, p.email as patient_email,
                 p.groupe_sanguin, p.allergies, p.antecedents,
                 m.id as medecin_id, m.nom as medecin_nom, m.prenom as medecin_prenom, 
                 m.specialite as medecin_specialite, m.telephone as medecin_tel,
                 m.email as medecin_email, m.experience as medecin_experience
          FROM rendezvous r
          LEFT JOIN patients p ON r.patient_id = p.id
          LEFT JOIN medecins m ON r.medecin_id = m.id
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $rdv_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: rendezvous.php?message=Rendez-vous+non+trouvé&success=0");
    exit();
}

$rdv = $result->fetch_assoc();
$stmt->close();

// Calculer l'âge du patient
function calculateAge($birthDate) {
    if (empty($birthDate)) return 0;
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

$patient_age = calculateAge($rdv['date_naissance']);

// Récupérer les consultations associées à ce rendez-vous
$consultations_query = "SELECT c.* FROM consultations c WHERE c.rdv_id = ?";
$stmt = $conn->prepare($consultations_query);
$stmt->bind_param("i", $rdv_id);
$stmt->execute();
$consultations_result = $stmt->get_result();
$consultations = $consultations_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Récupérer les prescriptions associées au patient
$prescriptions_query = "SELECT pr.* FROM prescriptions pr WHERE pr.patient_id = ? AND pr.statut = 'en_cours'";
$stmt = $conn->prepare($prescriptions_query);
$stmt->bind_param("i", $rdv['patient_id']);
$stmt->execute();
$prescriptions_result = $stmt->get_result();
$prescriptions = $prescriptions_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Texte des statuts
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

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Détails du Rendez-vous</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .detail-container {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-radius: var(--radius);
      padding: 2.5rem;
      border: 1px solid var(--border);
      box-shadow: var(--shadow-md);
      margin-bottom: 2rem;
    }
    
    .detail-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 2px solid var(--border);
    }
    
    .detail-title {
      font-size: 2rem;
      font-weight: 900;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
    }
    
    .detail-subtitle {
      color: var(--text-secondary);
      font-size: 1.1rem;
    }
    
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin-bottom: 2rem;
    }
    
    .info-card {
      background: var(--bg-secondary);
      padding: 1.5rem;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
    }
    
    .info-card h3 {
      font-size: 1.2rem;
      margin-bottom: 1rem;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .info-row {
      margin-bottom: 0.8rem;
      display: flex;
      justify-content: space-between;
      border-bottom: 1px dashed var(--border);
      padding-bottom: 0.5rem;
    }
    
    .info-label {
      font-weight: 600;
      color: var(--text-secondary);
      min-width: 120px;
    }
    
    .info-value {
      color: var(--text-primary);
      text-align: right;
      flex: 1;
    }
    
    .actions-container {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
      flex-wrap: wrap;
    }
    
    .badge {
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 700;
      display: inline-block;
    }
    
    .badge-info {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: var(--primary);
    }
    
    .badge-success {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      color: var(--success);
    }
    
    .badge-warning {
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: var(--warning);
    }
    
    .badge-danger {
      background: linear-gradient(135deg, #fee2e2, #fecaca);
      color: var(--danger);
    }
    
    .patient-avatar-lg {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 2rem;
      margin-bottom: 1rem;
      box-shadow: var(--shadow-lg);
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    }
    
    .medecin-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 1.5rem;
      background: linear-gradient(135deg, var(--secondary), var(--success));
      box-shadow: var(--shadow-md);
    }
    
    .notes-box {
      background: var(--bg-secondary);
      padding: 1.5rem;
      border-radius: var(--radius-sm);
      margin-top: 1.5rem;
      border-left: 4px solid var(--primary);
    }
    
    .notes-box h4 {
      margin-bottom: 0.5rem;
      color: var(--text-primary);
    }
    
    .consultation-history {
      margin-top: 3rem;
    }
    
    .history-item {
      background: var(--bg-secondary);
      padding: 1.5rem;
      border-radius: var(--radius-sm);
      margin-bottom: 1rem;
      border-left: 4px solid var(--info);
    }
    
    .history-date {
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.5rem;
    }
    
    .history-diagnostic {
      color: var(--text-secondary);
      font-size: 0.95rem;
    }
    
    .empty-state {
      text-align: center;
      padding: 3rem;
      color: var(--text-secondary);
    }
    
    .empty-state i {
      font-size: 3rem;
      margin-bottom: 1rem;
      display: block;
    }
    
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
      margin-bottom: 2rem;
      padding: 0.5rem 1rem;
      border-radius: var(--radius-sm);
      background: rgba(30, 58, 138, 0.1);
    }
    
    .back-link:hover {
      background: rgba(30, 58, 138, 0.2);
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="header-content">
      <h1><i class="fas fa-stethoscope"></i> DME Pro</h1>
      <p>Système Intelligent de Dossiers Médicaux Électroniques - Détails du rendez-vous</p>
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
        <a href="rendezvous.php" class="nav-btn active">
          <i class="fas fa-calendar-check nav-icon"></i> Rendez-vous
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
      <section id="rdv-details-view" class="view">
        <a href="rendezvous.php" class="back-link">
          <i class="fas fa-arrow-left"></i> Retour aux rendez-vous
        </a>
        
        <div class="detail-container">
          <div class="detail-header">
            <div>
              <h1 class="detail-title">Rendez-vous #<?php echo $rdv['id']; ?></h1>
              <p class="detail-subtitle">
                <span class="badge <?php echo $statusClass[$rdv['statut']]; ?>">
                  <?php echo $statusText[$rdv['statut']]; ?>
                </span>
                • <?php echo formatDate($rdv['date_rdv'], 'l d F Y à H:i'); ?>
              </p>
            </div>
            <div class="patient-avatar-lg">
              <?php echo strtoupper(substr($rdv['patient_prenom'], 0, 1) . substr($rdv['patient_nom'], 0, 1)); ?>
            </div>
          </div>
          
          <div class="info-grid">
            <!-- Informations du patient -->
            <div class="info-card">
              <h3><i class="fas fa-user-injured"></i> Patient</h3>
              <div class="info-row">
                <span class="info-label">Nom complet:</span>
                <span class="info-value"><?php echo escape($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Âge:</span>
                <span class="info-value"><?php echo $patient_age; ?> ans</span>
              </div>
              <div class="info-row">
                <span class="info-label">Genre:</span>
                <span class="info-value"><?php echo $rdv['genre'] == 'F' ? 'Femme' : 'Homme'; ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Téléphone:</span>
                <span class="info-value"><?php echo escape($rdv['patient_tel'] ?: 'Non renseigné'); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Groupe sanguin:</span>
                <span class="info-value">
                  <?php if ($rdv['groupe_sanguin']): ?>
                    <span class="badge badge-danger"><?php echo escape($rdv['groupe_sanguin']); ?></span>
                  <?php else: ?>
                    Inconnu
                  <?php endif; ?>
                </span>
              </div>
              <?php if ($rdv['allergies']): ?>
              <div class="info-row">
                <span class="info-label">Allergies:</span>
                <span class="info-value"><?php echo escape($rdv['allergies']); ?></span>
              </div>
              <?php endif; ?>
              <div class="actions-container" style="margin-top: 1rem;">
                <a href="patients.php?action=edit&id=<?php echo $rdv['patient_id']; ?>" class="action-btn btn-edit">
                  <i class="fas fa-edit"></i> Modifier patient
                </a>
                <a href="patients.php?action=view&id=<?php echo $rdv['patient_id']; ?>" class="action-btn btn-view">
                  <i class="fas fa-file-medical"></i> Dossier complet
                </a>
              </div>
            </div>
            
            <!-- Informations du médecin -->
            <div class="info-card">
              <h3><i class="fas fa-user-md"></i> Médecin</h3>
              <div class="info-row">
                <span class="info-label">Nom:</span>
                <span class="info-value">Dr. <?php echo escape($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Spécialité:</span>
                <span class="info-value"><?php echo escape($rdv['medecin_specialite'] ?: 'Non spécifiée'); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Expérience:</span>
                <span class="info-value"><?php echo $rdv['medecin_experience']; ?> ans</span>
              </div>
              <div class="info-row">
                <span class="info-label">Contact:</span>
                <span class="info-value"><?php echo escape($rdv['medecin_tel'] ?: 'Non renseigné'); ?></span>
              </div>
              <div class="medecin-avatar" style="margin-top: 1rem;">
                <?php echo strtoupper(substr($rdv['medecin_prenom'], 0, 1) . substr($rdv['medecin_nom'], 0, 1)); ?>
              </div>
            </div>
            
            <!-- Informations du rendez-vous -->
            <div class="info-card">
              <h3><i class="fas fa-calendar-check"></i> Rendez-vous</h3>
              <div class="info-row">
                <span class="info-label">Date et heure:</span>
                <span class="info-value"><?php echo formatDate($rdv['date_rdv']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Durée:</span>
                <span class="info-value"><?php echo $rdv['duree']; ?> minutes</span>
              </div>
              <div class="info-row">
                <span class="info-label">Salle:</span>
                <span class="info-value">
                  <?php if ($rdv['salle']): ?>
                    <span class="badge badge-info"><?php echo escape($rdv['salle']); ?></span>
                  <?php else: ?>
                    Non spécifiée
                  <?php endif; ?>
                </span>
              </div>
              <div class="info-row">
                <span class="info-label">Motif:</span>
                <span class="info-value"><?php echo escape($rdv['motif'] ?: 'Non spécifié'); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Statut:</span>
                <span class="info-value">
                  <span class="status <?php echo $statusClass[$rdv['statut']]; ?>">
                    <?php echo $statusText[$rdv['statut']]; ?>
                  </span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-label">Créé le:</span>
                <span class="info-value"><?php echo formatDate($rdv['created_at']); ?></span>
              </div>
            </div>
          </div>
          
          <!-- Notes -->
          <?php if ($rdv['notes']): ?>
          <div class="notes-box">
            <h4><i class="fas fa-sticky-note"></i> Notes du rendez-vous</h4>
            <p><?php echo nl2br(escape($rdv['notes'])); ?></p>
          </div>
          <?php endif; ?>
          
          <!-- Actions -->
          <div class="actions-container">
            <?php if ($rdv['statut'] == 'planifie'): ?>
              <a href="rendezvous.php?action=confirm&id=<?php echo $rdv['id']; ?>" class="btn btn-confirm">
                <i class="fas fa-check"></i> Confirmer le RDV
              </a>
            <?php elseif ($rdv['statut'] == 'confirme'): ?>
              <a href="consultations.php?action=new&rdv_id=<?php echo $rdv['id']; ?>" class="btn btn-primary">
                <i class="fas fa-stethoscope"></i> Démarrer consultation
              </a>
              <a href="rendezvous.php?action=complete&id=<?php echo $rdv['id']; ?>" class="btn" style="background: linear-gradient(135deg, var(--success), #047857);">
                <i class="fas fa-check-circle"></i> Marquer comme terminé
              </a>
            <?php endif; ?>
            
            <?php if ($rdv['statut'] != 'annule' && $rdv['statut'] != 'termine'): ?>
              <a href="rendezvous.php?action=cancel&id=<?php echo $rdv['id']; ?>" class="btn btn-delete">
                <i class="fas fa-times"></i> Annuler le RDV
              </a>
            <?php endif; ?>
            
            <a href="rendezvous.php?action=edit&id=<?php echo $rdv['id']; ?>" class="btn btn-edit">
              <i class="fas fa-edit"></i> Modifier
            </a>
            
            <a href="rendezvous.php?action=delete&id=<?php echo $rdv['id']; ?>" 
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?')"
               class="btn btn-delete">
              <i class="fas fa-trash"></i> Supprimer
            </a>
            
            <button class="btn" style="background: linear-gradient(135deg, var(--info), #0284c7);" onclick="window.print()">
              <i class="fas fa-print"></i> Imprimer
            </button>
          </div>
        </div>
        
        <!-- Prescriptions actives du patient -->
        <?php if (!empty($prescriptions)): ?>
        <div class="detail-container">
          <h3 style="margin-bottom: 1.5rem; color: var(--text-primary);">
            <i class="fas fa-pills"></i> Prescriptions actives du patient
          </h3>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
            <?php foreach ($prescriptions as $prescription): ?>
            <div class="info-card">
              <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                  <strong><?php echo escape($prescription['medicament']); ?></strong><br>
                  <small><?php echo escape($prescription['dosage']); ?> - <?php echo escape($prescription['frequence']); ?></small>
                </div>
                <span class="badge badge-success">En cours</span>
              </div>
              <div class="info-row">
                <span class="info-label">Début:</span>
                <span class="info-value"><?php echo formatDate($prescription['date_debut'], 'd/m/Y'); ?></span>
              </div>
              <?php if ($prescription['date_fin']): ?>
              <div class="info-row">
                <span class="info-label">Fin:</span>
                <span class="info-value"><?php echo formatDate($prescription['date_fin'], 'd/m/Y'); ?></span>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- Historique des consultations -->
        <?php if (!empty($consultations)): ?>
        <div class="detail-container consultation-history">
          <h3 style="margin-bottom: 1.5rem; color: var(--text-primary);">
            <i class="fas fa-history"></i> Historique des consultations liées
          </h3>
          <?php foreach ($consultations as $consultation): ?>
          <div class="history-item">
            <div class="history-date">
              <?php echo formatDate($consultation['date_consultation']); ?>
              <?php if ($consultation['statut'] == 'termine'): ?>
                <span class="badge badge-success" style="margin-left: 1rem; padding: 4px 12px;">Terminée</span>
              <?php else: ?>
                <span class="badge badge-warning" style="margin-left: 1rem; padding: 4px 12px;">En cours</span>
              <?php endif; ?>
            </div>
            <div class="history-diagnostic">
              <?php if ($consultation['diagnostic']): ?>
                <strong>Diagnostic:</strong> <?php echo escape($consultation['diagnostic']); ?><br>
              <?php endif; ?>
              <?php if ($consultation['traitement']): ?>
                <strong>Traitement:</strong> <?php echo escape($consultation['traitement']); ?>
              <?php endif; ?>
            </div>
            <div style="margin-top: 1rem;">
              <a href="consultations.php?action=view&id=<?php echo $consultation['id']; ?>" class="action-btn btn-view" style="padding: 8px 16px;">
                <i class="fas fa-eye"></i> Voir consultation
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="detail-container">
          <div class="empty-state">
            <i class="fas fa-stethoscope"></i>
            <h4>Aucune consultation liée à ce rendez-vous</h4>
            <p>Créez une consultation à partir de ce rendez-vous pour commencer le suivi médical.</p>
            <?php if ($rdv['statut'] == 'confirme'): ?>
            <a href="consultations.php?action=new&rdv_id=<?php echo $rdv['id']; ?>" class="btn btn-primary" style="margin-top: 1rem;">
              <i class="fas fa-plus"></i> Créer une consultation
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- Antécédents médicaux -->
        <?php if ($rdv['antecedents']): ?>
        <div class="detail-container">
          <h3 style="margin-bottom: 1.5rem; color: var(--text-primary);">
            <i class="fas fa-file-medical-alt"></i> Antécédents médicaux
          </h3>
          <div class="notes-box">
            <p><?php echo nl2br(escape($rdv['antecedents'])); ?></p>
          </div>
        </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
  
  <script src="script.js"></script>
  <script>
    // Initialiser le menu responsive
    document.addEventListener('DOMContentLoaded', function() {
      const menuToggle = document.getElementById('menuToggle');
      const sidebar = document.getElementById('sidebar');
      
      if (window.innerWidth < 1200) {
        menuToggle.style.display = 'block';
      }
      
      menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
      });
      
      window.addEventListener('resize', () => {
        if (window.innerWidth < 1200) {
          menuToggle.style.display = 'block';
        } else {
          menuToggle.style.display = 'none';
          sidebar.classList.remove('open');
        }
      });
    });
  </script>
</body>
</html>
<?php
// Fin du fichier PHP
?>
[file content end]