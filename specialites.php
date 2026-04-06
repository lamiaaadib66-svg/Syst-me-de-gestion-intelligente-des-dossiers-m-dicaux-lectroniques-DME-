<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Spécialités Médicales</title>
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
        <a href="index.html" class="nav-btn">
          <i class="fas fa-tachometer-alt nav-icon"></i> Tableau de bord
        </a>
        <a href="patients.html" class="nav-btn">
          <i class="fas fa-user-injured nav-icon"></i> Patients
          <span class="notification-badge">3</span>
        </a>
        <a href="rendezvous.html" class="nav-btn">
          <i class="fas fa-calendar-check nav-icon"></i> Rendez-vous
          <span class="notification-badge">5</span>
        </a>
        <a href="consultations.html" class="nav-btn">
          <i class="fas fa-stethoscope nav-icon"></i> Consultations
        </a>
        <a href="prescriptions.html" class="nav-btn">
          <i class="fas fa-pills nav-icon"></i> Prescriptions
          <span class="notification-badge">2</span>
        </a>
        <a href="specialites.html" class="nav-btn active">
          <i class="fas fa-clinic-medical nav-icon"></i> Spécialités
        </a>
        <a href="equipe.html" class="nav-btn">
          <i class="fas fa-users nav-icon"></i> Équipe médicale
        </a>
        <a href="parametres.html" class="nav-btn">
          <i class="fas fa-cog nav-icon"></i> Paramètres
        </a>
      </nav>
    </aside>

    <main class="main">
      <section id="specialites-view" class="view">
        <div class="page-header">
          <div>
            <h1 class="page-title">Spécialités Médicales</h1>
            <p class="page-subtitle">Nos domaines d'excellence médicale</p>
          </div>
        </div>
        
        <!-- Grille des spécialités... -->
       <div class="specialites-grid">
          <div class="specialite-card">
            <div class="specialite-icon specialite-icon1">
              <i class="fas fa-heart-pulse"></i>
            </div>
            <h2>Cardiologie</h2>
            <p>Électrocardiogrammes, échographies Doppler, coronarographies, holter tensionnel.</p>
            <div style="margin-top: 1.5rem; color: var(--text-secondary); font-size: 0.9rem;">
              <i class="fas fa-user-md"></i> 3 spécialistes
            </div>
          </div>
          <div class="specialite-card">
            <div class="specialite-icon specialite-icon2">
              <i class="fas fa-lungs"></i>
            </div>
            <h2>Pneumologie</h2>
            <p>Asthme, BPCO, spirométrie, bronchoscopies, tests d'effort respiratoire.</p>
            <div style="margin-top: 1.5rem; color: var(--text-secondary); font-size: 0.9rem;">
              <i class="fas fa-user-md"></i> 2 spécialistes
            </div>
          </div>
          <div class="specialite-card">
            <div class="specialite-icon specialite-icon3">
              <i class="fas fa-hand-holding-medical"></i>
            </div>
            <h2>Dermatologie</h2>
            <p>Acné, psoriasis, dermatoscopie, laser, cryothérapie, chirurgie cutanée.</p>
            <div style="margin-top: 1.5rem; color: var(--text-secondary); font-size: 0.9rem;">
              <i class="fas fa-user-md"></i> 2 spécialistes
            </div>
          </div>
          <div class="specialite-card">
            <div class="specialite-icon specialite-icon4">
              <i class="fas fa-brain"></i>
            </div>
            <h2>Neurologie</h2>
            <p>Migraines, épilepsie, IRM cérébrale, électroencéphalogramme, EMG.</p>
            <div style="margin-top: 1.5rem; color: var(--text-secondary); font-size: 0.9rem;">
              <i class="fas fa-user-md"></i> 2 spécialistes
            </div>
          </div>
          <div class="specialite-card">
            <div class="specialite-icon specialite-icon5">
              <i class="fas fa-baby"></i>
            </div>
            <h2>Pédiatrie</h2>
            <p>Vaccins, bilans croissance, ORL pédiatrique, développement psychomoteur.</p>
            <div style="margin-top: 1.5rem; color: var(--text-secondary); font-size: 0.9rem;">
              <i class="fas fa-user-md"></i> 4 spécialistes
            </div>
          </div>
          <div class="specialite-card">
            <div class="specialite-icon specialite-icon6">
              <i class="fas fa-x-ray"></i>
            </div>
            <h2>Radiologie</h2>
            <p>Scanner, IRM, échographie 3D, mammographie, ostéodensitométrie.</p>
            <div style="margin-top: 1.5rem; color: var(--text-secondary); font-size: 0.9rem;">
              <i class="fas fa-user-md"></i> 3 spécialistes
            </div>
          </div>
        </div>
  
  <script src="script.js"></script>
</body>
</html>