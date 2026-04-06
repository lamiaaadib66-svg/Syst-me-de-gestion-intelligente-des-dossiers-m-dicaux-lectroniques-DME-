
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

// Vérifier si l'ID de la consultation est fourni
$consultation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($consultation_id <= 0) {
    header("Location: consultations.php?message=Consultation+non+trouvée&success=0");
    exit();
}

// Récupérer les données de la consultation
$conn = connectDB();

// Données de test (à remplacer par une requête SQL)
$consultation = [
    'id' => $consultation_id,
    'date_consultation' => '2025-12-09 10:45:00',
    'motif' => 'Maux de gorge, fièvre',
    'diagnostic' => 'Angine streptococcique confirmée par test rapide. Température à 38.5°C, gorge très inflammée avec présence d\'exsudats.',
    'traitement' => 'Amoxicilline 500mg : 1 comprimé 3x/jour pendant 7 jours
Paracétamol 1000mg : en cas de fièvre ou douleur
Repos recommandé pendant 48h
Boissons chaudes et gargarismes à l\'eau salée',
    'examens' => 'Test rapide streptococcique : POSITIF
Prise de température : 38.5°C
Examen ORL : Pharynx inflammé, amygdales augmentées de volume avec exsudats',
    'notes' => 'Patient à surveiller pour éventuelle réaction allergique. Contrôle dans 48h si fièvre persiste. Déclaration maladie professionnelle possible.',
    'statut' => 'termine',
    'created_at' => '2025-12-09 11:30:00',
    'patient_id' => 1,
    'patient_nom' => 'Martin',
    'patient_prenom' => 'Léa',
    'patient_tel' => '07 98 76 54 32',
    'patient_email' => 'lea.martin@email.com',
    'date_naissance' => '1990-05-15',
    'genre' => 'F',
    'groupe_sanguin' => 'A+',
    'allergies' => 'Pénicilline (réaction cutanée légère)',
    'antecedents' => 'Angines récurrentes (3-4 par an)
Amygdalectomie à l\'âge de 12 ans
Asthme léger contrôlé',
    'adresse' => '12 Rue de la Santé, 75000 Paris',
    'medecin_id' => 1,
    'medecin_nom' => 'Dupont',
    'medecin_prenom' => 'Alice',
    'medecin_specialite' => 'Cardiologie',
    'medecin_tel' => '01 23 45 67 89',
    'medecin_email' => 'alice.dupont@dmepro.fr',
    'medecin_experience' => 18,
    'rdv_id' => 1,
    'date_rdv' => '2025-12-09 10:30:00',
    'rdv_motif' => 'Consultation urgence - Maux de gorge',
    'rdv_salle' => 'Salle 3'
];

// Calculer l'âge du patient
function calculateAge($birthDate) {
    if (empty($birthDate)) return 0;
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

$patient_age = calculateAge($consultation['date_naissance']);

// Données de prescriptions (exemple)
$prescriptions = [
    [
        'id' => 1,
        'medicament' => 'Amoxicilline 500mg',
        'dosage' => '1 comprimé',
        'frequence' => '3x/jour',
        'date_debut' => '2025-12-09',
        'date_fin' => '2025-12-16',
        'statut' => 'en_cours',
        'instructions' => 'Prendre pendant le repas. Terminer le traitement même si amélioration.'
    ],
    [
        'id' => 2,
        'medicament' => 'Paracétamol 1000mg',
        'dosage' => '1 comprimé',
        'frequence' => 'Si besoin (max 3/jour)',
        'date_debut' => '2025-12-09',
        'date_fin' => '2025-12-12',
        'statut' => 'en_cours',
        'instructions' => 'En cas de fièvre > 38°C ou douleur importante.'
    ]
];

// Historique des consultations du patient (exemple)
$other_consultations = [
    [
        'id' => 2,
        'date_consultation' => '2025-11-15 14:30:00',
        'motif' => 'Bilan annuel cardiologie',
        'diagnostic' => 'Tension normale, ECG sans particularité.'
    ],
    [
        'id' => 3,
        'date_consultation' => '2025-08-22 09:15:00',
        'motif' => 'Suivi asthme',
        'diagnostic' => 'Asthme bien contrôlé, pas besoin d\'ajustement traitement.'
    ]
];

// Texte des statuts
$statusText = [
    'en_cours' => 'En cours',
    'termine' => 'Terminée'
];

$statusClass = [
    'en_cours' => 'en-cours',
    'termine' => 'termine'
];
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Détails de la Consultation</title>
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
    
    .content-box {
      background: white;
      padding: 1.5rem;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      margin-top: 1rem;
      white-space: pre-wrap;
      line-height: 1.6;
    }
    
    .prescription-item {
      background: var(--bg-secondary);
      padding: 1.5rem;
      border-radius: var(--radius-sm);
      margin-bottom: 1rem;
      border-left: 4px solid var(--success);
    }
    
    .history-item {
      background: var(--bg-secondary);
      padding: 1rem;
      border-radius: var(--radius-sm);
      margin-bottom: 0.5rem;
      border-left: 3px solid var(--info);
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
    
    .status.en-cours {
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: var(--warning);
      border: 1px solid rgba(217, 119, 6, 0.2);
    }
    
    .status.en-cours::before {
      background: var(--warning);
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
    
    .section-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin: 2rem 0 1rem;
      color: var(--text-primary);
      padding-bottom: 0.5rem;
      border-bottom: 2px solid var(--border);
    }
    
    .consultation-content {
      margin-top: 2rem;
    }
    
    .content-section {
      margin-bottom: 2rem;
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
  </style>
</head>
<body>
  <header class="header">
    <div class="header-content">
      <h1><i class="fas fa-stethoscope"></i> DME Pro</h1>
      <p>Système Intelligent de Dossiers Médicaux Électroniques - Détails de la consultation</p>
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
        <a href="consultations.php" class="nav-btn active">
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
      <section id="consultation-details-view" class="view">
        <a href="consultations.php" class="back-link">
          <i class="fas fa-arrow-left"></i> Retour aux consultations
        </a>
        
        <div class="detail-container">
          <div class="detail-header">
            <div>
              <h1 class="detail-title">Consultation #<?php echo $consultation['id']; ?></h1>
              <p class="detail-subtitle">
                <span class="status <?php echo $statusClass[$consultation['statut']]; ?>">
                  <?php echo $statusText[$consultation['statut']]; ?>
                </span>
                • <?php echo formatDate($consultation['date_consultation'], 'l d F Y à H:i'); ?>
                <?php if ($consultation['rdv_id']): ?>
                  • <a href="rdv_details.php?id=<?php echo $consultation['rdv_id']; ?>" style="color: var(--primary);">
                    RDV #<?php echo $consultation['rdv_id']; ?>
                  </a>
                <?php endif; ?>
              </p>
            </div>
            <div class="patient-avatar-lg">
              <?php echo strtoupper(substr($consultation['patient_prenom'], 0, 1) . substr($consultation['patient_nom'], 0, 1)); ?>
            </div>
          </div>
          
          <div class="info-grid">
            <!-- Informations du patient -->
            <div class="info-card">
              <h3><i class="fas fa-user-injured"></i> Patient</h3>
              <div class="info-row">
                <span class="info-label">Nom complet:</span>
                <span class="info-value"><?php echo escape($consultation['patient_prenom'] . ' ' . $consultation['patient_nom']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Âge:</span>
                <span class="info-value"><?php echo $patient_age; ?> ans</span>
              </div>
              <div class="info-row">
                <span class="info-label">Genre:</span>
                <span class="info-value"><?php echo $consultation['genre'] == 'F' ? 'Femme' : 'Homme'; ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Téléphone:</span>
                <span class="info-value"><?php echo escape($consultation['patient_tel']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Groupe sanguin:</span>
                <span class="info-value">
                  <span style="color: var(--danger); font-weight: 700;"><?php echo escape($consultation['groupe_sanguin']); ?></span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-label">Allergies:</span>
                <span class="info-value"><?php echo escape($consultation['allergies']); ?></span>
              </div>
              <div class="actions-container" style="margin-top: 1rem;">
                <a href="patients.php?action=edit&id=<?php echo $consultation['patient_id']; ?>" class="action-btn btn-edit">
                  <i class="fas fa-edit"></i> Modifier patient
                </a>
                <a href="patients.php?action=view&id=<?php echo $consultation['patient_id']; ?>" class="action-btn btn-view">
                  <i class="fas fa-file-medical"></i> Dossier complet
                </a>
              </div>
            </div>
            
            <!-- Informations du médecin -->
            <div class="info-card">
              <h3><i class="fas fa-user-md"></i> Médecin</h3>
              <div class="info-row">
                <span class="info-label">Nom:</span>
                <span class="info-value">Dr. <?php echo escape($consultation['medecin_prenom'] . ' ' . $consultation['medecin_nom']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Spécialité:</span>
                <span class="info-value"><?php echo escape($consultation['medecin_specialite']); ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Expérience:</span>
                <span class="info-value"><?php echo $consultation['medecin_experience']; ?> ans</span>
              </div>
              <div class="info-row">
                <span class="info-label">Contact:</span>
                <span class="info-value"><?php echo escape($consultation['medecin_tel']); ?></span>
              </div>
              <div class="medecin-avatar" style="margin-top: 1rem;">
                <?php echo strtoupper(substr($consultation['medecin_prenom'], 0, 1) . substr($consultation['medecin_nom'], 0, 1)); ?>
              </div>
            </div>
            
            <!-- Informations de la consultation -->
            <div class="info-card">
              <h3><i class="fas fa-stethoscope"></i> Consultation</h3>
              <div class="info-row">
                <span class="info-label">Date et heure:</span>
                <span class="info-value"><?php echo formatDate($consultation['date_consultation']); ?></span>
              </div>
              <?php if ($consultation['rdv_id']): ?>
              <div class="info-row">
                <span class="info-label">Rendez-vous:</span>
                <span class="info-value">
                  <a href="rdv_details.php?id=<?php echo $consultation['rdv_id']; ?>" style="color: var(--primary);">
                    RDV #<?php echo $consultation['rdv_id']; ?>
                  </a>
                </span>
              </div>
              <div class="info-row">
                <span class="info-label">Salle:</span>
                <span class="info-value"><?php echo escape($consultation['rdv_salle']); ?></span>
              </div>
              <?php endif; ?>
              <div class="info-row">
                <span class="info-label">Statut:</span>
                <span class="info-value">
                  <span class="status <?php echo $statusClass[$consultation['statut']]; ?>">
                    <?php echo $statusText[$consultation['statut']]; ?>
                  </span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-label">Créée le:</span>
                <span class="info-value"><?php echo formatDate($consultation['created_at']); ?></span>
              </div>
            </div>
          </div>
          
          <!-- Contenu de la consultation -->
          <div class="consultation-content">
            <h2 class="section-title"><i class="fas fa-file-medical-alt"></i> Compte-rendu médical</h2>
            
            <div class="content-section">
              <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-comment-medical"></i> Motif de consultation
              </h3>
              <div class="content-box">
                <?php echo nl2br(escape($consultation['motif'])); ?>
              </div>
            </div>
            
            <div class="content-section">
              <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-diagnoses"></i> Diagnostic
              </h3>
              <div class="content-box">
                <?php echo nl2br(escape($consultation['diagnostic'])); ?>
              </div>
            </div>
            
            <div class="content-section">
              <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-pills"></i> Traitement prescrit
              </h3>
              <div class="content-box">
                <?php echo nl2br(escape($consultation['traitement'])); ?>
              </div>
            </div>
            
            <div class="content-section">
              <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-vial"></i> Examens complémentaires
              </h3>
              <div class="content-box">
                <?php echo nl2br(escape($consultation['examens'])); ?>
              </div>
            </div>
            
            <?php if ($consultation['notes']): ?>
            <div class="content-section">
              <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-sticky-note"></i> Notes cliniques
              </h3>
              <div class="content-box">
                <?php echo nl2br(escape($consultation['notes'])); ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
          
          <!-- Actions -->
          <div class="actions-container">
            <a href="consultations.php?action=edit&id=<?php echo $consultation['id']; ?>" class="btn btn-edit">
              <i class="fas fa-edit"></i> Modifier la consultation
            </a>
            
            <?php if ($consultation['statut'] == 'en_cours'): ?>
              <a href="consultations.php?action=complete&id=<?php echo $consultation['id']; ?>" class="btn btn-confirm">
                <i class="fas fa-check-circle"></i> Terminer la consultation
              </a>
            <?php endif; ?>
            
            <a href="prescriptions.php?consultation_id=<?php echo $consultation['id']; ?>" class="btn" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
              <i class="fas fa-prescription"></i> Gérer les prescriptions
            </a>
            
            <a href="consultations.php?action=delete&id=<?php echo $consultation['id']; ?>" 
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette consultation ?')"
               class="btn btn-delete">
              <i class="fas fa-trash"></i> Supprimer
            </a>
            
            <button class="btn" style="background: linear-gradient(135deg, var(--info), #0284c7);" onclick="window.print()">
              <i class="fas fa-print"></i> Imprimer
            </button>
            
            <button class="btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);" onclick="downloadPDF()">
              <i class="fas fa-download"></i> Télécharger PDF
            </button>
          </div>
        </div>
        
        <!-- Prescriptions associées -->
        <?php if (!empty($prescriptions)): ?>
        <div class="detail-container">
          <h2 class="section-title"><i class="fas fa-pills"></i> Prescriptions associées</h2>
          <?php foreach ($prescriptions as $prescription): ?>
          <div class="prescription-item">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
              <div>
                <strong style="font-size: 1.1rem;"><?php echo escape($prescription['medicament']); ?></strong><br>
                <small><?php echo escape($prescription['dosage']); ?> - <?php echo escape($prescription['frequence']); ?></small>
              </div>
              <span class="badge badge-success">En cours</span>
            </div>
            <div class="info-row">
              <span class="info-label">Période:</span>
              <span class="info-value">
                <?php echo formatDate($prescription['date_debut'], 'd/m/Y'); ?> 
                → <?php echo formatDate($prescription['date_fin'], 'd/m/Y'); ?>
              </span>
            </div>
            <?php if ($prescription['instructions']): ?>
            <div class="info-row">
              <span class="info-label">Instructions:</span>
              <span class="info-value"><?php echo escape($prescription['instructions']); ?></span>
            </div>
            <?php endif; ?>
            <div style="margin-top: 1rem;">
              <a href="prescriptions.php?action=edit&id=<?php echo $prescription['id']; ?>" class="action-btn btn-edit" style="padding: 8px 16px;">
                <i class="fas fa-edit"></i> Modifier
              </a>
              <a href="prescriptions.php?action=delete&id=<?php echo $prescription['id']; ?>" 
                 onclick="return confirm('Supprimer cette prescription ?')"
                 class="action-btn btn-delete" style="padding: 8px 16px;">
                <i class="fas fa-trash"></i> Supprimer
              </a>
            </div>
          </div>
          <?php endforeach; ?>
          
          <div style="margin-top: 1.5rem;">
            <a href="prescriptions.php?consultation_id=<?php echo $consultation['id']; ?>&action=new" class="btn btn-primary">
              <i class="fas fa-plus"></i> Ajouter une nouvelle prescription
            </a>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- Antécédents médicaux du patient -->
        <div class="detail-container">
          <h2 class="section-title"><i class="fas fa-file-medical-alt"></i> Antécédents médicaux du patient</h2>
          <div class="content-box">
            <?php echo nl2br(escape($consultation['antecedents'])); ?>
          </div>
          <div class="info-row" style="margin-top: 1rem;">
            <span class="info-label">Allergies connues:</span>
            <span class="info-value"><?php echo escape($consultation['allergies']); ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Adresse:</span>
            <span class="info-value"><?php echo escape($consultation['adresse']); ?></span>
          </div>
        </div>
        
        <!-- Historique des consultations du patient -->
        <?php if (!empty($other_consultations)): ?>
        <div class="detail-container">
          <h2 class="section-title"><i class="fas fa-history"></i> Historique récent du patient</h2>
          <?php foreach ($other_consultations as $history): ?>
          <div class="history-item">
            <div class="info-row">
              <span class="info-label">Date:</span>
              <span class="info-value"><?php echo formatDate($history['date_consultation']); ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Motif:</span>
              <span class="info-value"><?php echo escape($history['motif']); ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Diagnostic:</span>
              <span class="info-value"><?php echo escape($history['diagnostic']); ?></span>
            </div>
            <div style="margin-top: 0.5rem;">
              <a href="consultation_details.php?id=<?php echo $history['id']; ?>" class="action-btn btn-view" style="padding: 6px 12px;">
                <i class="fas fa-eye"></i> Voir
              </a>
            </div>
          </div>
          <?php endforeach; ?>
          
          <div style="margin-top: 1.5rem;">
            <a href="consultations.php?patient_id=<?php echo $consultation['patient_id']; ?>" class="btn" style="background: linear-gradient(135deg, var(--info), #0284c7);">
              <i class="fas fa-list"></i> Voir tout l'historique
            </a>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- Rendez-vous associé -->
        <?php if ($consultation['rdv_id']): ?>
        <div class="detail-container">
          <h2 class="section-title"><i class="fas fa-calendar-check"></i> Rendez-vous associé</h2>
          <div class="info-card">
            <div class="info-row">
              <span class="info-label">Numéro RDV:</span>
              <span class="info-value">
                <a href="rdv_details.php?id=<?php echo $consultation['rdv_id']; ?>" style="color: var(--primary); font-weight: 700;">
                  #<?php echo $consultation['rdv_id']; ?>
                </a>
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">Date programmée:</span>
              <span class="info-value"><?php echo formatDate($consultation['date_rdv']); ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Motif initial:</span>
              <span class="info-value"><?php echo escape($consultation['rdv_motif']); ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Salle:</span>
              <span class="info-value"><?php echo escape($consultation['rdv_salle']); ?></span>
            </div>
            <div style="margin-top: 1rem;">
              <a href="rdv_details.php?id=<?php echo $consultation['rdv_id']; ?>" class="action-btn btn-view">
                <i class="fas fa-eye"></i> Voir détails du rendez-vous
              </a>
            </div>
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
    
    // Fonction pour télécharger en PDF (simulation)
    function downloadPDF() {
      alert('Fonctionnalité PDF en développement. L\'impression est disponible via le bouton "Imprimer".');
      // Ici, vous pourriez intégrer une librairie comme jsPDF ou faire un appel à un service backend
    }
    
    // Ajouter des raccourcis clavier
    document.addEventListener('keydown', function(event) {
      // Ctrl+P pour imprimer
      if (event.ctrlKey && event.key === 'p') {
        event.preventDefault();
        window.print();
      }
      // Échap pour retourner à la liste
      if (event.key === 'Escape') {
        window.location.href = 'consultations.php';
      }
    });
  </script>
</body>
</html>
<?php
// Fin du fichier PHP
?>
