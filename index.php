<?php
// ==================== BACKEND PHP ====================
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dme_pro');

// Connexion à la base de données
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        // Mode silencieux pour ne pas casser l'affichage
        return null;
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Gérer les requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    handleAjaxRequest();
    exit;
}

// Traitement des requêtes AJAX
function handleAjaxRequest() {
    $conn = connectDB();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        return;
    }
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'refresh_stats':
            $response = getStats($conn);
            break;
        case 'search_patient':
            $search = isset($_POST['search']) ? $_POST['search'] : '';
            $response = searchPatients($conn, $search);
            break;
        case 'create_rdv':
            $response = createRendezVous($conn, $_POST);
            break;
        case 'confirm_rdv':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $response = confirmRendezVous($conn, $id);
            break;
        case 'mark_alerte_read':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $response = markAlerteAsRead($conn, $id);
            break;
        default:
            $response['message'] = 'Action non reconnue';
    }
    
    $conn->close();
    header('Content-Type: application/json');
    echo json_encode($response);
}

// Fonctions CRUD
function createRendezVous($conn, $data) {
    // Données par défaut pour la démo
    $patient_id = 1;
    $medecin_id = 1;
    $date_rdv = date('Y-m-d H:i:s', strtotime('+1 day'));
    $duree = 30;
    $motif = isset($data['motif']) ? $conn->real_escape_string($data['motif']) : 'Consultation générale';
    $salle = 'Salle 1';
    $statut = 'planifie';
    
    $sql = "INSERT INTO rendezvous (patient_id, medecin_id, date_rdv, duree, motif, salle, statut) 
            VALUES ($patient_id, $medecin_id, '$date_rdv', $duree, '$motif', '$salle', '$statut')";
    
    if ($conn->query($sql)) {
        return ['success' => true, 'message' => 'Rendez-vous créé avec succès'];
    } else {
        return ['success' => false, 'message' => 'Erreur: ' . $conn->error];
    }
}

function confirmRendezVous($conn, $id) {
    if ($id <= 0) {
        return ['success' => false, 'message' => 'ID invalide'];
    }
    
    $sql = "UPDATE rendezvous SET statut = 'confirme' WHERE id = $id";
    
    if ($conn->query($sql)) {
        return ['success' => true, 'message' => 'Rendez-vous confirmé'];
    } else {
        return ['success' => false, 'message' => 'Erreur: ' . $conn->error];
    }
}

function markAlerteAsRead($conn, $id) {
    if ($id <= 0) {
        return ['success' => false, 'message' => 'ID invalide'];
    }
    
    $sql = "UPDATE alertes SET traite = 1 WHERE id = $id";
    
    if ($conn->query($sql)) {
        return ['success' => true, 'message' => 'Alerte marquée comme lue'];
    } else {
        return ['success' => false, 'message' => 'Erreur: ' . $conn->error];
    }
}

function searchPatients($conn, $search) {
    if (empty($search)) {
        return ['success' => false, 'message' => 'Terme de recherche vide'];
    }
    
    $search = $conn->real_escape_string($search);
    $sql = "SELECT * FROM patients WHERE nom LIKE '%$search%' OR prenom LIKE '%$search%' LIMIT 10";
    $result = $conn->query($sql);
    
    $patients = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
    }
    
    return ['success' => true, 'patients' => $patients];
}

// Récupérer les statistiques
function getStats($conn) {
    $stats = [
        'patients' => 247,
        'rdv_today' => 4,
        'consultations' => 42,
        'prescriptions' => 156
    ];
    
    // Récupérer les vraies données si la table existe
    $tables = $conn->query("SHOW TABLES LIKE 'patients'");
    if ($tables->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as total FROM patients");
        if ($result) $stats['patients'] = $result->fetch_assoc()['total'];
    }
    
    return ['success' => true, 'stats' => $stats];
}

// Fonctions utilitaires pour l'affichage
function getPatientInitials($prenom, $nom) {
    $initials = '';
    if (!empty($prenom)) $initials .= strtoupper(substr($prenom, 0, 1));
    if (!empty($nom)) $initials .= strtoupper(substr($nom, 0, 1));
    return $initials;
}

function calculateAge($date_naissance) {
    if (empty($date_naissance)) return 'N/A';
    $birthDate = new DateTime($date_naissance);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y;
}

// Données par défaut pour l'affichage (si BD non disponible)
$defaultStats = [
    'patients' => 247,
    'rdv_today' => 4,
    'consultations' => 42,
    'prescriptions' => 156
];

$todayAppointments = [
    [
        'id' => 1,
        'patient_nom' => 'Dubois',
        'patient_prenom' => 'Pierre',
        'date_naissance' => '1957-07-05',
        'date_rdv' => date('Y-m-d') . ' 09:00:00',
        'motif' => 'Bilan annuel + ECG',
        'duree' => 30,
        'salle' => 'Salle 3',
        'statut' => 'confirme'
    ],
    [
        'id' => 2,
        'patient_nom' => 'Lambert',
        'patient_prenom' => 'Sophie',
        'date_naissance' => '1997-04-25',
        'date_rdv' => date('Y-m-d') . ' 10:30:00',
        'motif' => 'Suivi grossesse + échographie',
        'duree' => 45,
        'salle' => 'Salle 1',
        'statut' => 'confirme'
    ],
    [
        'id' => 3,
        'patient_nom' => 'Leclerc',
        'patient_prenom' => 'Marc',
        'date_naissance' => '1980-11-30',
        'date_rdv' => date('Y-m-d') . ' 11:45:00',
        'motif' => 'Consultation urgence - Douleurs abdominales',
        'duree' => 20,
        'salle' => 'Salle Urgences',
        'statut' => 'planifie'
    ],
    [
        'id' => 4,
        'patient_nom' => 'Martin',
        'patient_prenom' => 'Léa',
        'date_naissance' => '1990-05-15',
        'date_rdv' => date('Y-m-d') . ' 14:00:00',
        'motif' => 'Contrôle post-angine',
        'duree' => 15,
        'salle' => 'Salle 2',
        'statut' => 'planifie'
    ]
];

$alertes = [
    [
        'id' => 1,
        'type' => 'Prescription',
        'description' => 'Prescription à renouveler avant le 15/12',
        'patient_nom' => 'Benali',
        'patient_prenom' => 'Karim',
        'date_alerte' => date('Y-m-d'),
        'priorite' => 'haute'
    ],
    [
        'id' => 2,
        'type' => 'Résultats',
        'description' => 'Résultats d\'analyses disponibles',
        'patient_nom' => 'Martin',
        'patient_prenom' => 'Sarah',
        'date_alerte' => date('Y-m-d', strtotime('-1 day')),
        'priorite' => 'moyenne'
    ],
    [
        'id' => 3,
        'type' => 'Rappel',
        'description' => 'Vaccin à effectuer dans 1 mois',
        'patient_nom' => 'Benali',
        'patient_prenom' => 'Enfant',
        'date_alerte' => date('Y-m-d', strtotime('-2 days')),
        'priorite' => 'basse'
    ]
];

$urgentPatients = [
    [
        'patient_id' => 4,
        'patient_nom' => 'Dubois',
        'patient_prenom' => 'Pierre',
        'diagnostic' => 'Hypertension sévère',
        'motif' => 'Tension non contrôlée (180/110)',
        'date_consultation' => date('Y-m-d', strtotime('-4 days')),
        'id' => 101
    ],
    [
        'patient_id' => 3,
        'patient_nom' => 'Martin',
        'patient_prenom' => 'Sarah',
        'diagnostic' => 'Effets secondaires traitement',
        'motif' => 'Effets secondaires isotrétinoïne (sécheresse sévère)',
        'date_consultation' => date('Y-m-d', strtotime('-3 days')),
        'id' => 102
    ]
];

// Essayer de récupérer les données de la BD si disponible
try {
    $conn = connectDB();
    if ($conn) {
        // Récupérer les vraies statistiques
        $result = getStats($conn);
        if ($result['success']) {
            $defaultStats = $result['stats'];
        }
        $conn->close();
    }
} catch (Exception $e) {
    // Continuer avec les données par défaut
}
?>

<!-- ==================== FRONTEND HTML (MEME CONTENU) ==================== -->
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Tableau de bord</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        <a href="index.php" class="nav-btn active">
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
      <section id="dashboard-view" class="view" style="display: block;">
        <div class="page-header">
          <div>
            <h1 class="page-title">Tableau de bord</h1>
            <p class="page-subtitle">Votre vue d'ensemble médicale en temps réel</p>
          </div>
          <div style="display: flex; gap: 1rem;">
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input type="text" placeholder="Rechercher un patient..." id="searchInput">
            </div>
            <button class="btn btn-primary" id="refreshBtn">
              <i class="fas fa-sync-alt"></i> Actualiser
            </button>
            <button class="btn" style="background: linear-gradient(135deg, var(--info), #0284c7);">
              <i class="fas fa-download"></i> Exporter
            </button>
          </div>
        </div>
        
        <!-- Les cartes statistiques -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon stat-patients">
              <i class="fas fa-user-injured"></i>
            </div>
            <div class="stat-number"><?php echo $defaultStats['patients']; ?></div>
            <div class="stat-label">Patients</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon stat-rdv">
              <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-number"><?php echo $defaultStats['rdv_today']; ?></div>
            <div class="stat-label">RDV aujourd'hui</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon stat-consult">
              <i class="fas fa-stethoscope"></i>
            </div>
            <div class="stat-number"><?php echo $defaultStats['consultations']; ?></div>
            <div class="stat-label">Consultations</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon stat-presc">
              <i class="fas fa-pills"></i>
            </div>
            <div class="stat-number"><?php echo $defaultStats['prescriptions']; ?></div>
            <div class="stat-label">Prescriptions</div>
          </div>
        </div>

        <!-- Agenda du jour -->
        <div class="table-container" style="margin-top: 3rem;">
          <div class="table-header">
            <div style="font-size: 1.1rem; font-weight: 600;">
              <i class="fas fa-calendar-day"></i> Agenda d'aujourd'hui - <?php echo date('d F Y'); ?>
            </div>
            <div style="display: flex; gap: 1rem;">
              <button class="btn btn-primary" id="newRdvBtn">
                <i class="fas fa-plus"></i> Nouveau RDV
              </button>
              <button class="btn" style="background: linear-gradient(135deg, var(--info), #0284c7);">
                <i class="fas fa-print"></i> Imprimer
              </button>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Heure</th>
                <th>Patient</th>
                <th>Motif</th>
                <th>Durée</th>
                <th>Salle</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="agendaTable">
              <?php foreach ($todayAppointments as $rdv): 
                $time = date('H:i', strtotime($rdv['date_rdv']));
                $initials = getPatientInitials($rdv['patient_prenom'], $rdv['patient_nom']);
                $age = calculateAge($rdv['date_naissance']);
              ?>
              <tr data-rdv-id="<?php echo $rdv['id']; ?>">
                <td><strong><?php echo $time; ?></strong></td>
                <td>
                  <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="patient-avatar" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); width: 40px; height: 40px; font-size: 1rem;">
                      <?php echo $initials; ?>
                    </div>
                    <div>
                      <div style="font-weight: 600;"><?php echo $rdv['patient_prenom'] . ' ' . $rdv['patient_nom']; ?></div>
                      <div style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo $age; ?> ans</div>
                    </div>
                  </div>
                </td>
                <td><?php echo $rdv['motif']; ?></td>
                <td><?php echo $rdv['duree']; ?> min</td>
                <td><?php echo $rdv['salle']; ?></td>
                <td><span class="status <?php echo $rdv['statut']; ?>">
                  <?php echo $rdv['statut'] === 'confirme' ? 'Confirmé' : 'Planifié'; ?>
                </span></td>
                <td>
                  <div class="table-actions">
                    <button class="action-btn btn-view" onclick="viewPatient(<?php echo $rdv['patient_prenom'] . ' ' . $rdv['patient_nom']; ?>)">
                      <i class="fas fa-user"></i> Dossier
                    </button>
                    <button class="action-btn btn-edit" onclick="editRdv(<?php echo $rdv['id']; ?>)">
                      <i class="fas fa-calendar-alt"></i> Modifier
                    </button>
                    <?php if ($rdv['statut'] === 'planifie'): ?>
                    <button class="action-btn btn-confirm confirm-rdv" data-rdv-id="<?php echo $rdv['id']; ?>">
                      <i class="fas fa-check"></i> Confirmer
                    </button>
                    <?php else: ?>
                    <button class="action-btn" style="background: linear-gradient(135deg, var(--success), #047857);" onclick="startConsultation(<?php echo $rdv['id']; ?>)">
                      <i class="fas fa-play"></i> Démarrer
                    </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Alertes et notifications -->
        <div class="table-container" style="margin-top: 2rem;">
          <div class="table-header">
            <div style="font-size: 1.1rem; font-weight: 600;">
              <i class="fas fa-bell"></i> Alertes et notifications
            </div>
            <button class="btn" style="background: linear-gradient(135deg, var(--warning), #b45309);">
              <i class="fas fa-filter"></i> Filtrer
            </button>
          </div>
          <table>
            <thead>
              <tr>
                <th>Type</th>
                <th>Description</th>
                <th>Patient concerné</th>
                <th>Date</th>
                <th>Priorité</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="alertesTable">
              <?php foreach ($alertes as $alerte): 
                $dateAlerte = date('d/m/Y', strtotime($alerte['date_alerte']));
                $priorityColor = $alerte['priorite'] === 'haute' ? 'var(--danger)' : 
                                ($alerte['priorite'] === 'moyenne' ? 'var(--warning)' : 'var(--info)');
                $priorityText = $alerte['priorite'] === 'haute' ? 'Haute' : 
                               ($alerte['priorite'] === 'moyenne' ? 'Moyenne' : 'Basse');
              ?>
              <tr data-alerte-id="<?php echo $alerte['id']; ?>">
                <td>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 12px; height: 12px; background: <?php echo $priorityColor; ?>; border-radius: 50%;"></div>
                    <span><?php echo $alerte['type']; ?></span>
                  </div>
                </td>
                <td><?php echo $alerte['description']; ?></td>
                <td><?php echo $alerte['patient_prenom'] . ' ' . $alerte['patient_nom']; ?></td>
                <td><?php echo $dateAlerte; ?></td>
                <td><span style="color: <?php echo $priorityColor; ?>; font-weight: 700;"><?php echo $priorityText; ?></span></td>
                <td>
                  <div class="table-actions">
                    <button class="action-btn btn-view" onclick="viewAlerteDetails(<?php echo $alerte['id']; ?>)">
                      <i class="fas fa-eye"></i> Détails
                    </button>
                    <button class="action-btn btn-edit" onclick="treatAlerte(<?php echo $alerte['id']; ?>)">
                      <i class="fas fa-check"></i> Traiter
                    </button>
                    <button class="action-btn mark-read" data-alerte-id="<?php echo $alerte['id']; ?>" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
                      <i class="fas fa-check"></i> Lue
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Patients urgents -->
        <div class="table-container" style="margin-top: 2rem;">
          <div class="table-header">
            <div style="font-size: 1.1rem; font-weight: 600;">
              <i class="fas fa-exclamation-triangle"></i> Patients nécessitant attention
            </div>
            <button class="btn" style="background: linear-gradient(135deg, var(--danger), #b91c1c);">
              <i class="fas fa-history"></i> Historique
            </button>
          </div>
          <table>
            <thead>
              <tr>
                <th>Patient</th>
                <th>Problème</th>
                <th>Dernière consultation</th>
                <th>Priorité</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($urgentPatients as $patient): 
                $initials = getPatientInitials($patient['patient_prenom'], $patient['patient_nom']);
                $dateConsult = date('d/m/Y', strtotime($patient['date_consultation']));
              ?>
              <tr>
                <td>
                  <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="patient-avatar" style="background: linear-gradient(135deg, #ef4444, #dc2626); width: 40px; height: 40px; font-size: 1rem;">
                      <?php echo $initials; ?>
                    </div>
                    <div>
                      <div style="font-weight: 600;"><?php echo $patient['patient_prenom'] . ' ' . $patient['patient_nom']; ?></div>
                      <div style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo $patient['diagnostic']; ?></div>
                    </div>
                  </div>
                </td>
                <td><?php echo $patient['motif']; ?></td>
                <td><?php echo $dateConsult; ?></td>
                <td><span style="color: var(--danger); font-weight: 700;">Urgent</span></td>
                <td>
                  <div class="table-actions">
                    <button class="action-btn btn-view" onclick="viewPatient(<?php echo $patient['patient_prenom'] . ' ' . $patient['patient_nom']; ?>)">
                      <i class="fas fa-file-medical"></i> Dossier
                    </button>
                    <button class="action-btn btn-edit" onclick="editTreatment(<?php echo $patient['id']; ?>)">
                      <i class="fas fa-pills"></i> Traitement
                    </button>
                    <button class="action-btn" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));" onclick="callPatient('<?php echo $patient['patient_prenom'] . ' ' . $patient['patient_nom']; ?>')">
                      <i class="fas fa-phone"></i> Appeler
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
  
  <!-- Modal pour les actions -->
  <div id="actionModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 20px; max-width: 500px; width: 90%;">
      <div id="modalContent"></div>
      <div style="margin-top: 2rem; text-align: right;">
        <button id="closeModal" style="padding: 10px 20px; background: var(--text-secondary); color: white; border: none; border-radius: 8px; cursor: pointer;">
          Fermer
        </button>
      </div>
    </div>
  </div>

  <script>
  // ==================== FRONTEND JAVASCRIPT ====================

  // Fonctions utilitaires
  function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 20px;
      border-radius: 12px;
      color: white;
      font-weight: 600;
      z-index: 9999;
      animation: slideIn 0.3s ease-out;
      box-shadow: 0 25px 50px rgba(0,0,0,0.15);
      background: ${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#0ea5e9'};
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.style.animation = 'slideOut 0.3s ease-out';
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  function showModal(content) {
    document.getElementById('modalContent').innerHTML = content;
    document.getElementById('actionModal').style.display = 'flex';
  }

  function hideModal() {
    document.getElementById('actionModal').style.display = 'none';
  }

  // Requêtes AJAX
  async function ajaxRequest(action, data = {}) {
    try {
      const formData = new FormData();
      formData.append('action', action);
      for (const key in data) {
        formData.append(key, data[key]);
      }
      
      const response = await fetch('index.php', {
        method: 'POST',
        body: formData
      });
      
      return await response.json();
    } catch (error) {
      console.error('Erreur:', error);
      return { success: false, message: 'Erreur réseau' };
    }
  }

  // Gestion des événements
  document.addEventListener('DOMContentLoaded', function() {
    // Bouton Actualiser
    document.getElementById('refreshBtn').addEventListener('click', function() {
      ajaxRequest('refresh_stats').then(response => {
        if (response.success) {
          // Mettre à jour les statistiques
          const stats = response.stats;
          document.querySelectorAll('.stat-number')[0].textContent = stats.patients;
          document.querySelectorAll('.stat-number')[1].textContent = stats.rdv_today;
          document.querySelectorAll('.stat-number')[2].textContent = stats.consultations;
          document.querySelectorAll('.stat-number')[3].textContent = stats.prescriptions;
          showNotification('Données actualisées', 'success');
        }
      });
    });

    // Recherche patient
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
      if (e.key === 'Enter' && this.value.trim()) {
        ajaxRequest('search_patient', { search: this.value.trim() }).then(response => {
          if (response.success) {
            const patients = response.patients;
            let content = '<h3>Résultats de recherche</h3>';
            if (patients.length === 0) {
              content += '<p>Aucun patient trouvé</p>';
            } else {
              content += '<ul style="list-style: none; padding: 0;">';
              patients.forEach(patient => {
                content += `<li style="padding: 10px; border-bottom: 1px solid #e2e8f0;">
                  <strong>${patient.prenom} ${patient.nom}</strong><br>
                  <small>${patient.date_naissance}</small>
                </li>`;
              });
              content += '</ul>';
            }
            showModal(content);
          }
        });
      }
    });

    // Nouveau RDV
    document.getElementById('newRdvBtn').addEventListener('click', function() {
      showModal(`
        <h3>Nouveau Rendez-vous</h3>
        <form id="newRdvForm">
          <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Patient</label>
            <input type="text" placeholder="Nom du patient" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;" required>
          </div>
          <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Motif</label>
            <input type="text" placeholder="Motif de la consultation" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;" required>
          </div>
          <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Date et heure</label>
            <input type="datetime-local" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;" required>
          </div>
          <button type="submit" style="padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">
            Créer le RDV
          </button>
        </form>
      `);
      
      document.getElementById('newRdvForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        ajaxRequest('create_rdv', { motif: this.querySelector('input[type="text"]').value }).then(response => {
          if (response.success) {
            showNotification(response.message, 'success');
            hideModal();
          } else {
            showNotification(response.message, 'error');
          }
        });
      });
    });

    // Confirmer RDV
    document.querySelectorAll('.confirm-rdv').forEach(btn => {
      btn.addEventListener('click', function() {
        const rdvId = this.getAttribute('data-rdv-id');
        if (confirm('Confirmer ce rendez-vous ?')) {
          ajaxRequest('confirm_rdv', { id: rdvId }).then(response => {
            if (response.success) {
              showNotification(response.message, 'success');
              // Mettre à jour l'affichage
              const row = this.closest('tr');
              const statusCell = row.querySelector('.status');
              statusCell.textContent = 'Confirmé';
              statusCell.className = 'status confirme';
              // Remplacer le bouton Confirmer par Démarrer
              this.outerHTML = '<button class="action-btn" style="background: linear-gradient(135deg, var(--success), #047857);" onclick="startConsultation(' + rdvId + ')"><i class="fas fa-play"></i> Démarrer</button>';
            } else {
              showNotification(response.message, 'error');
            }
          });
        }
      });
    });

    // Marquer alerte comme lue
    document.querySelectorAll('.mark-read').forEach(btn => {
      btn.addEventListener('click', function() {
        const alerteId = this.getAttribute('data-alerte-id');
        ajaxRequest('mark_alerte_read', { id: alerteId }).then(response => {
          if (response.success) {
            showNotification(response.message, 'success');
            // Supprimer la ligne
            this.closest('tr').remove();
          } else {
            showNotification(response.message, 'error');
          }
        });
      });
    });

    // Fermer modal
    document.getElementById('closeModal').addEventListener('click', hideModal);
    
    // Fermer modal en cliquant à l'extérieur
    document.getElementById('actionModal').addEventListener('click', function(e) {
      if (e.target === this) hideModal();
    });

    // Navigation responsive
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

  // Fonctions d'actions (pour compatibilité avec le contenu existant)
  function viewPatient(patientName) {
    showModal(`<h3>Dossier Patient</h3><p>Consultation du dossier de ${patientName}</p><p>Fonctionnalité en développement...</p>`);
  }

  function editRdv(rdvId) {
    showModal(`<h3>Modifier Rendez-vous</h3><p>Modification du RDV #${rdvId}</p><p>Fonctionnalité en développement...</p>`);
  }

  function startConsultation(rdvId) {
    showNotification(`Démarrage de la consultation pour le RDV #${rdvId}`, 'info');
  }

  function viewAlerteDetails(alerteId) {
    showModal(`<h3>Détails de l'alerte</h3><p>Alerte #${alerteId}</p><p>Fonctionnalité en développement...</p>`);
  }

  function treatAlerte(alerteId) {
    showModal(`<h3>Traiter l'alerte</h3><p>Traitement de l'alerte #${alerteId}</p><p>Fonctionnalité en développement...</p>`);
  }

  function editTreatment(patientId) {
    showModal(`<h3>Modifier le traitement</h3><p>Patient #${patientId}</p><p>Fonctionnalité en développement...</p>`);
  }

  function callPatient(patientName) {
    showModal(`<h3>Appeler le patient</h3><p>Appel de ${patientName}</p><p>Fonctionnalité en développement...</p>`);
  }

  // Ajouter les animations CSS
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }
  `;
  document.head.appendChild(style);
  </script>
</body>
</html>