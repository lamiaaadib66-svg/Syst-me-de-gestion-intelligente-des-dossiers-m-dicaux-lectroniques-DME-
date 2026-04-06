
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

// Vérifier si l'ID de la prescription est fourni
$prescription_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($prescription_id <= 0) {
    header("Location: prescriptions.php?message=Prescription+non+trouvée&success=0");
    exit();
}

// Connexion à la base de données
$conn = connectDB();

// Récupérer les données de la prescription
$stmt = $conn->prepare("
    SELECT 
        p.*,
        pat.id as patient_id,
        pat.nom as patient_nom,
        pat.prenom as patient_prenom,
        pat.telephone as patient_tel,
        pat.email as patient_email,
        pat.date_naissance,
        pat.genre,
        pat.groupe_sanguin,
        pat.allergies,
        pat.antecedents,
        pat.adresse,
        m.id as medecin_id,
        m.nom as medecin_nom,
        m.prenom as medecin_prenom,
        m.specialite as medecin_specialite,
        m.telephone as medecin_tel,
        m.email as medecin_email,
        m.experience as medecin_experience,
        c.id as consultation_id,
        c.date_consultation,
        c.motif as consultation_motif,
        c.diagnostic as consultation_diagnostic
    FROM prescriptions p
    LEFT JOIN patients pat ON p.patient_id = pat.id
    LEFT JOIN medecins m ON p.medecin_id = m.id
    LEFT JOIN consultations c ON p.consultation_id = c.id
    WHERE p.id = ?
");

if (!$stmt) {
    die("Erreur de préparation: " . $conn->error);
}

$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: prescriptions.php?message=Prescription+non+trouvée&success=0");
    exit();
}

$prescription = $result->fetch_assoc();
$stmt->close();

// Récupérer l'historique des modifications de la prescription
$history = [];
try {
    // Vérifier si la table existe
    $table_check = $conn->query("SHOW TABLES LIKE 'prescription_history'");
    if ($table_check->num_rows > 0) {
        // La table existe, récupérer l'historique
        $history_stmt = $conn->prepare("
            SELECT * FROM prescription_history 
            WHERE prescription_id = ? 
            ORDER BY created_at DESC
        ");
        $history_stmt->bind_param("i", $prescription_id);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        $history = $history_result->fetch_all(MYSQLI_ASSOC);
        $history_stmt->close();
    }
} catch (Exception $e) {
    // La table n'existe pas, on continue avec un historique vide
    error_log("Table prescription_history non trouvée: " . $e->getMessage());
    $history = [];
}

// Calculer l'âge du patient
function calculateAge($birthDate) {
    if (empty($birthDate)) return 0;
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

$patient_age = calculateAge($prescription['date_naissance']);

// Texte des statuts
$statusText = [
    'en_cours' => 'En cours',
    'termine' => 'Terminée',
    'suspendu' => 'Suspendue'
];

$statusClass = [
    'en_cours' => 'confirme',
    'termine' => 'termine',
    'suspendu' => 'suspendu'
];

// Informations sur le médicament (exemple - pourrait être récupéré depuis une table)
$medicament_info = [
    'classe_therapeutique' => 'Antibiotique - Bêta-lactamine',
    'forme_pharmaceutique' => 'Comprimé',
    'laboratoire' => 'Laboratoires PharmaPlus',
    'voie_administration' => 'Orale',
    'conservation' => 'À température ambiante (15-25°C)',
    'dci' => 'Amoxicilline trihydrate',
    'presentation' => 'Boîte de 21 comprimés',
    'remboursement' => '65% - Classe A',
    'contre_indications' => 'Allergie aux pénicillines, insuffisance rénale sévère',
    'effets_secondaires' => 'Diarrhée, nausées, éruption cutanée',
    'interactions' => 'Anticoagulants, contraceptifs oraux'
];

// Calculer le nombre de jours restants
$jours_restants = 0;
if ($prescription['date_fin']) {
    $date_fin = new DateTime($prescription['date_fin']);
    $today = new DateTime();
    if ($date_fin > $today) {
        $jours_restants = $today->diff($date_fin)->days;
    }
}

// Calculer la progression
$progress_percentage = 0;
if ($prescription['date_debut'] && $prescription['date_fin']) {
    $debut = new DateTime($prescription['date_debut']);
    $fin = new DateTime($prescription['date_fin']);
    $aujourdhui = new DateTime();
    
    $total_jours = $debut->diff($fin)->days;
    $jours_ecoules = $debut->diff($aujourdhui)->days;
    
    if ($total_jours > 0) {
        $progress_percentage = min(100, max(0, ($jours_ecoules / $total_jours) * 100));
    }
}

$conn->close();
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Détails de la Prescription #<?php echo $prescription['id']; ?></title>
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
    
    .medicament-header {
      background: linear-gradient(135deg, var(--primary-light), rgba(199, 210, 254, 0.3));
      padding: 2rem;
      border-radius: var(--radius-sm);
      margin-bottom: 2rem;
      text-align: center;
      border: 2px solid var(--primary);
    }
    
    .medicament-name {
      font-size: 2.5rem;
      font-weight: 900;
      color: var(--primary);
      margin-bottom: 0.5rem;
    }
    
    .medicament-type {
      color: var(--text-secondary);
      font-size: 1.1rem;
      margin-bottom: 1.5rem;
    }
    
    .posologie-box {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      padding: 1.5rem;
      border-radius: var(--radius-sm);
      border: 2px solid var(--success);
      margin-bottom: 2rem;
    }
    
    .posologie-title {
      color: var(--success);
      font-weight: 700;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .posologie-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
    }
    
    .posologie-item {
      text-align: center;
    }
    
    .posologie-value {
      font-size: 1.8rem;
      font-weight: 900;
      color: var(--text-primary);
      margin-bottom: 0.5rem;
    }
    
    .posologie-label {
      color: var(--text-secondary);
      font-size: 0.9rem;
      font-weight: 600;
    }
    
    .instructions-box {
      background: var(--bg-secondary);
      padding: 1.5rem;
      border-radius: var(--radius-sm);
      margin-top: 1.5rem;
      border-left: 4px solid var(--warning);
    }
    
    .instructions-box h4 {
      margin-bottom: 0.5rem;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .content-box {
      background: white;
      padding: 1.5rem;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      margin-top: 1rem;
      white-space: pre-wrap;
      line-height: 1.6;
    }
    
    .medicament-info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
      margin-top: 1.5rem;
    }
    
    .info-tag {
      background: var(--bg-secondary);
      padding: 0.8rem 1rem;
      border-radius: var(--radius-sm);
      border-left: 3px solid var(--info);
    }
    
    .info-tag-label {
      font-weight: 600;
      color: var(--text-secondary);
      font-size: 0.9rem;
      margin-bottom: 0.3rem;
    }
    
    .info-tag-value {
      color: var(--text-primary);
      font-weight: 500;
    }
    
    .timeline {
      margin-top: 2rem;
      position: relative;
    }
    
    .timeline::before {
      content: '';
      position: absolute;
      left: 50%;
      top: 0;
      bottom: 0;
      width: 2px;
      background: var(--border);
      transform: translateX(-50%);
    }
    
    .timeline-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      position: relative;
    }
    
    .timeline-date {
      width: 45%;
      text-align: right;
      padding-right: 2rem;
    }
    
    .timeline-content {
      width: 45%;
      padding-left: 2rem;
    }
    
    .timeline-dot {
      position: absolute;
      left: 50%;
      top: 50%;
      width: 16px;
      height: 16px;
      background: var(--primary);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      border: 3px solid white;
      box-shadow: 0 0 0 3px var(--primary-light);
    }
    
    .empty-state {
      text-align: center;
      padding: 2rem;
      color: var(--text-secondary);
    }
    
    .empty-state i {
      font-size: 2rem;
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
    
    .status.suspendu {
      background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
      color: #6b7280;
      border: 1px solid rgba(107, 114, 128, 0.2);
    }
    
    .status.suspendu::before {
      background: #6b7280;
    }
    
    .badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
      display: inline-block;
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
    
    .badge-info {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: var(--primary);
    }
    
    .section-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin: 2rem 0 1rem;
      color: var(--text-primary);
      padding-bottom: 0.5rem;
      border-bottom: 2px solid var(--border);
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
    
    .progress-container {
      margin-top: 1.5rem;
    }
    
    .progress-bar {
      height: 10px;
      background: var(--border);
      border-radius: 5px;
      overflow: hidden;
      margin-bottom: 0.5rem;
    }
    
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--success), var(--secondary));
      width: <?php echo $progress_percentage; ?>%;
      transition: width 0.5s ease;
    }
    
    .progress-text {
      display: flex;
      justify-content: space-between;
      font-size: 0.9rem;
      color: var(--text-secondary);
    }
    
    .history-item {
      background: var(--bg-secondary);
      padding: 1rem;
      border-radius: var(--radius-sm);
      margin-bottom: 1rem;
      border-left: 3px solid var(--info);
    }
    
    .history-date {
      font-weight: 600;
      color: var(--text-secondary);
      font-size: 0.9rem;
    }
    
    .history-action {
      color: var(--text-primary);
      margin: 0.5rem 0;
    }
    
    .history-user {
      color: var(--text-secondary);
      font-size: 0.85rem;
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="header-content">
      <h1><i class="fas fa-stethoscope"></i> DME Pro</h1>
      <p>Système Intelligent de Dossiers Médicaux Électroniques - Détails de la prescription</p>
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
      <section id="prescription-details-view" class="view">
        <a href="prescriptions.php" class="back-link">
          <i class="fas fa-arrow-left"></i> Retour aux prescriptions
        </a>
        
        <div class="detail-container">
          <div class="detail-header">
            <div>
              <h1 class="detail-title">Prescription #<?php echo $prescription['id']; ?></h1>
              <p class="detail-subtitle">
                <span class="status <?php echo $statusClass[$prescription['statut']]; ?>">
                  <?php echo $statusText[$prescription['statut']]; ?>
                </span>
                • Émise le <?php echo formatDate($prescription['created_at'], 'd/m/Y à H:i'); ?>
                <?php if ($prescription['consultation_id']): ?>
                  • <a href="consultation_details.php?id=<?php echo $prescription['consultation_id']; ?>" style="color: var(--primary);">
                    Consultation #<?php echo $prescription['consultation_id']; ?>
                  </a>
                <?php endif; ?>
              </p>
            </div>
            <div class="patient-avatar-lg">
              <?php echo strtoupper(substr($prescription['patient_prenom'], 0, 1) . substr($prescription['patient_nom'], 0, 1)); ?>
            </div>
          </div>
          
          <!-- En-tête du médicament -->
          <div class="medicament-header">
            <div class="medicament-name"><?php echo escape($prescription['medicament']); ?></div>
            <div class="medicament-type"><?php echo $medicament_info['classe_therapeutique']; ?></div>
            
            <div class="posologie-grid">
              <div class="posologie-item">
                <div class="posologie-value"><?php echo escape($prescription['dosage']); ?></div>
                <div class="posologie-label">Dosage</div>
              </div>
              <div class="posologie-item">
                <div class="posologie-value"><?php echo escape($prescription['frequence']); ?></div>
                <div class="posologie-label">Fréquence</div>
              </div>
              <div class="posologie-item">
                <div class="posologie-value"><?php echo escape($prescription['duree']); ?></div>
                <div class="posologie-label">Durée</div>
              </div>
            </div>
          </div>
          
          <!-- Progression du traitement -->
          <?php if ($prescription['statut'] == 'en_cours' && $prescription['date_fin']): ?>
          <div class="progress-container">
            <div class="progress-bar">
              <div class="progress-fill"></div>
            </div>
            <div class="progress-text">
              <span>Début: <?php echo formatDate($prescription['date_debut']); ?></span>
              <span>
                <?php if ($jours_restants > 0): ?>
                  <?php echo $jours_restants; ?> jours restants
                <?php else: ?>
                  Traitement terminé
                <?php endif; ?>
              </span>
              <span>Fin: <?php echo formatDate($prescription['date_fin']); ?></span>
            </div>
          </div>
          <?php endif; ?>
          
          <div class="info-grid">
            <!-- Informations du patient -->
            <div class="info-card">
              <h3><i class="fas fa-user-injured"></i> Patient</h3>
              <div class="info-row">
                <span class="info-label">Nom complet:</span>
                <span class="info-value"><?php echo escape($prescription['patient_prenom'] . ' ' . $prescription['patient_nom']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Âge:</span>
                <span class="info-value"><?php echo $patient_age; ?> ans</span>
              </div>
              <div class="info-row">
                <span class="info-label">Genre:</span>
                <span class="info-value"><?php echo $prescription['genre'] == 'F' ? 'Femme' : 'Homme'; ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Téléphone:</span>
                <span class="info-value"><?php echo escape($prescription['patient_tel']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Allergies:</span>
                <span class="info-value">
                  <span class="badge badge-danger"><?php echo escape($prescription['allergies']); ?></span>
                </span>
              </div>
              <div class="actions-container" style="margin-top: 1rem;">
                <a href="patients.php?action=view&id=<?php echo $prescription['patient_id']; ?>" class="action-btn btn-view">
                  <i class="fas fa-file-medical"></i> Dossier patient
                </a>
              </div>
            </div>
            
            <!-- Informations du médecin -->
            <div class="info-card">
              <h3><i class="fas fa-user-md"></i> Médecin prescripteur</h3>
              <div class="info-row">
                <span class="info-label">Nom:</span>
                <span class="info-value">Dr. <?php echo escape($prescription['medecin_prenom'] . ' ' . $prescription['medecin_nom']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Spécialité:</span>
                <span class="info-value"><?php echo escape($prescription['medecin_specialite']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Expérience:</span>
                <span class="info-value"><?php echo $prescription['medecin_experience']; ?> ans</span>
              </div>
              <div class="info-row">
                <span class="info-label">Contact:</span>
                <span class="info-value"><?php echo escape($prescription['medecin_tel']); ?></span>
              </div>
              <div class="medecin-avatar" style="margin-top: 1rem;">
                <?php echo strtoupper(substr($prescription['medecin_prenom'], 0, 1) . substr($prescription['medecin_nom'], 0, 1)); ?>
              </div>
            </div>
            
            <!-- Informations de la prescription -->
            <div class="info-card">
              <h3><i class="fas fa-prescription"></i> Prescription</h3>
              <div class="info-row">
                <span class="info-label">Date de début:</span>
                <span class="info-value"><?php echo formatDate($prescription['date_debut']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Date de fin:</span>
                <span class="info-value"><?php echo formatDate($prescription['date_fin']); ?></span>
              </div>
              <?php if ($prescription['consultation_id']): ?>
              <div class="info-row">
                <span class="info-label">Consultation:</span>
                <span class="info-value">
                  <a href="consultation_details.php?id=<?php echo $prescription['consultation_id']; ?>" style="color: var(--primary);">
                    #<?php echo $prescription['consultation_id']; ?>
                  </a>
                </span>
              </div>
              <?php endif; ?>
              <div class="info-row">
                <span class="info-label">Statut:</span>
                <span class="info-value">
                  <span class="status <?php echo $statusClass[$prescription['statut']]; ?>">
                    <?php echo $statusText[$prescription['statut']]; ?>
                  </span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-label">Créée le:</span>
                <span class="info-value"><?php echo formatDate($prescription['created_at']); ?></span>
              </div>
            </div>
          </div>
          
          <!-- Instructions -->
          <?php if ($prescription['instructions']): ?>
          <div class="instructions-box">
            <h4><i class="fas fa-info-circle"></i> Instructions particulières</h4>
            <div class="content-box">
              <?php echo nl2br(escape($prescription['instructions'])); ?>
            </div>
          </div>
          <?php endif; ?>
          
          <!-- Actions -->
          <div class="actions-container">
            <?php if ($prescription['statut'] == 'en_cours'): ?>
              <a href="prescriptions.php?action=complete&id=<?php echo $prescription['id']; ?>" class="btn btn-confirm">
                <i class="fas fa-check-circle"></i> Marquer comme terminée
              </a>
              <a href="prescriptions.php?action=suspend&id=<?php echo $prescription['id']; ?>" class="btn" style="background: linear-gradient(135deg, var(--warning), #b45309);">
                <i class="fas fa-pause"></i> Suspendre
              </a>
            <?php elseif ($prescription['statut'] == 'suspendu'): ?>
              <a href="prescriptions.php?action=edit&id=<?php echo $prescription['id']; ?>" class="btn btn-edit">
                <i class="fas fa-play"></i> Reprendre
              </a>
            <?php endif; ?>
            
            <a href="prescriptions.php?action=edit&id=<?php echo $prescription['id']; ?>" class="btn btn-edit">
              <i class="fas fa-edit"></i> Modifier
            </a>
            
            <a href="prescriptions.php?action=delete&id=<?php echo $prescription['id']; ?>" 
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette prescription ?')"
               class="btn btn-delete">
              <i class="fas fa-trash"></i> Supprimer
            </a>
            
            <button class="btn" style="background: linear-gradient(135deg, var(--info), #0284c7);" onclick="window.print()">
              <i class="fas fa-print"></i> Imprimer
            </button>
            
            <button class="btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);" onclick="downloadPDF()">
              <i class="fas fa-download"></i> Télécharger PDF
            </button>
            
            <button class="btn" style="background: linear-gradient(135deg, var(--success), #047857);" onclick="sendReminder()">
              <i class="fas fa-bell"></i> Rappel patient
            </button>
          </div>
        </div>
        
        <!-- Informations sur le médicament -->
        <div class="detail-container">
          <h2 class="section-title"><i class="fas fa-pills"></i> Informations sur le médicament</h2>
          <div class="medicament-info-grid">
            <div class="info-tag">
              <div class="info-tag-label">Dénomination Commune Internationale</div>
              <div class="info-tag-value"><?php echo $medicament_info['dci']; ?></div>
            </div>
            <div class="info-tag">
              <div class="info-tag-label">Forme pharmaceutique</div>
              <div class="info-tag-value"><?php echo $medicament_info['forme_pharmaceutique']; ?></div>
            </div>
            <div class="info-tag">
              <div class="info-tag-label">Laboratoire</div>
              <div class="info-tag-value"><?php echo $medicament_info['laboratoire']; ?></div>
            </div>
            <div class="info-tag">
              <div class="info-tag-label">Voie d'administration</div>
              <div class="info-tag-value"><?php echo $medicament_info['voie_administration']; ?></div>
            </div>
            <div class="info-tag">
              <div class="info-tag-label">Conditions de conservation</div>
              <div class="info-tag-value"><?php echo $medicament_info['conservation']; ?></div>
            </div>
            <div class="info-tag">
              <div class="info-tag-label">Présentation</div>
              <div class="info-tag-value"><?php echo $medicament_info['presentation']; ?></div>
            </div>
            <div class="info-tag">
              <div class="info-tag-label">Taux de remboursement</div>
              <div class="info-tag-value"><?php echo $medicament_info['remboursement']; ?></div>
            </div>
          </div>
          
          <div style="margin-top: 2rem;">
            <h3 style="color: var(--text-primary); margin-bottom: 1rem;">
              <i class="fas fa-exclamation-triangle"></i> Précautions d'emploi
            </h3>
            <div class="content-box">
              <strong>Contre-indications:</strong> <?php echo $medicament_info['contre_indications']; ?><br><br>
              <strong>Effets secondaires courants:</strong> <?php echo $medicament_info['effets_secondaires']; ?><br><br>
              <strong>Interactions médicamenteuses:</strong> <?php echo $medicament_info['interactions']; ?>
            </div>
          </div>
        </div>
        
        <!-- Consultation associée -->
        <?php if ($prescription['consultation_id']): ?>
        <div class="detail-container">
          <h2 class="section-title"><i class="fas fa-stethoscope"></i> Consultation associée</h2>
          <div class="info-card">
            <div class="info-row">
              <span class="info-label">Date consultation:</span>
              <span class="info-value"><?php echo formatDate($prescription['date_consultation'], 'd/m/Y H:i'); ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Motif:</span>
              <span class="info-value"><?php echo escape($prescription['consultation_motif']); ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Diagnostic:</span>
              <span class="info-value"><?php echo escape($prescription['consultation_diagnostic']); ?></span>
            </div>
            <div style="margin-top: 1rem;">
              <a href="consultation_details.php?id=<?php echo $prescription['consultation_id']; ?>" class="action-btn btn-view">
                <i class="fas fa-eye"></i> Voir la consultation
              </a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- Historique du traitement -->
        <div class="detail-container">
          <h2 class="section-title"><i class="fas fa-history"></i> Historique des modifications</h2>
          
          <?php if (!empty($history)): ?>
            <?php foreach ($history as $event): ?>
              <div class="history-item">
                <div class="history-date">
                  <i class="fas fa-clock"></i> <?php echo formatDate($event['created_at'], 'd/m/Y H:i'); ?>
                </div>
                <div class="history-action">
                  <?php echo escape($event['action']); ?>
                </div>
                <div class="history-user">
                  Par <?php echo escape($event['user_name']); ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="empty-state">
              <i class="fas fa-history"></i>
              <p>Aucune modification enregistrée</p>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Chronologie du traitement -->
        <div class="detail-container">
          <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Chronologie du traitement</h2>
          <div class="timeline">
            <div class="timeline-item">
              <div class="timeline-date">
                <strong><?php echo formatDate($prescription['date_debut']); ?></strong><br>
                <small>10:00</small>
              </div>
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <strong>Début du traitement</strong><br>
                <small>Prescription émise par Dr. <?php echo $prescription['medecin_prenom']; ?></small>
              </div>
            </div>
            
            <?php if ($prescription['date_fin']): ?>
            <div class="timeline-item">
              <div class="timeline-date">
                <strong><?php echo formatDate($prescription['date_fin']); ?></strong><br>
                <small>Fin du traitement</small>
              </div>
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <strong>Fin du traitement prévue</strong><br>
                <small>Date de fin de la prescription</small>
              </div>
            </div>
            <?php endif; ?>
            
            <?php if ($prescription['statut'] == 'termine'): ?>
            <div class="timeline-item">
              <div class="timeline-date">
                <strong>Terminé</strong><br>
                <small>Traitement achevé</small>
              </div>
              <div class="timeline-dot" style="background: var(--success);"></div>
              <div class="timeline-content">
                <strong>Traitement terminé</strong><br>
                <small>Prescription marquée comme complétée</small>
              </div>
            </div>
            <?php elseif ($prescription['statut'] == 'suspendu'): ?>
            <div class="timeline-item">
              <div class="timeline-date">
                <strong>Suspendu</strong><br>
                <small>Traitement interrompu</small>
              </div>
              <div class="timeline-dot" style="background: var(--warning);"></div>
              <div class="timeline-content">
                <strong>Traitement suspendu</strong><br>
                <small>Prescription temporairement arrêtée</small>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </main>
  </div>
  
  <script>
    // Fonctions JavaScript pour les actions
    function downloadPDF() {
      alert('Génération du PDF en cours pour la prescription #<?php echo $prescription["id"]; ?>');
      // window.open('generate_prescription_pdf.php?id=<?php echo $prescription["id"]; ?>', '_blank');
    }
    
    function sendReminder() {
      if (confirm('Envoyer un rappel au patient <?php echo $prescription["patient_prenom"]; ?> ?')) {
        // Simulation d'envoi
        alert('Rappel envoyé à <?php echo $prescription["patient_email"]; ?>');
        // window.location.href = 'send_reminder.php?id=<?php echo $prescription["id"]; ?>';
      }
    }
    
    // Initialiser le menu mobile
    document.addEventListener('DOMContentLoaded', function() {
      const menuToggle = document.getElementById('menuToggle');
      const sidebar = document.getElementById('sidebar');
      
      if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
          sidebar.classList.toggle('mobile-show');
        });
      }
      
      // Mettre à jour la largeur de la barre de progression
      const progressFill = document.querySelector('.progress-fill');
      if (progressFill) {
        setTimeout(() => {
          progressFill.style.width = '<?php echo $progress_percentage; ?>%';
        }, 100);
      }
    });
    
    // Impression optimisée
    function printPrescription() {
      const printContent = document.querySelector('.detail-container').outerHTML;
      const originalContent = document.body.innerHTML;
      
      document.body.innerHTML = printContent;
      window.print();
      document.body.innerHTML = originalContent;
      location.reload();
    }
  </script>
  
  <script src="script.js"></script>
</body>
</html>
<?php
// Fin du fichier PHP
?>
