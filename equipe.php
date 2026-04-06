<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Équipe Médicale</title>
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
        <a href="specialites.html" class="nav-btn">
          <i class="fas fa-clinic-medical nav-icon"></i> Spécialités
        </a>
        <a href="equipe.html" class="nav-btn active">
          <i class="fas fa-users nav-icon"></i> Équipe médicale
        </a>
        <a href="parametres.html" class="nav-btn">
          <i class="fas fa-cog nav-icon"></i> Paramètres
        </a>
      </nav>
    </aside>

    <main class="main">
      <section id="equipe-view" class="view">
        <div class="page-header">
          <div>
            <h1 class="page-title">Équipe Médicale</h1>
            <p class="page-subtitle">Nos 12 spécialistes expérimentés à votre service</p>
          </div>
        </div>
        
        <!-- Grille de l'équipe... -->
        <div class="equipe-grid">
          <div class="docteur-card">
            <div class="docteur-photo" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
              <i class="fas fa-user-md"></i>
            </div>
            <h3>Dr. Alice Dupont</h3>
            <div class="docteur-badge">Cardiologue Chef</div>
            <p>18 ans d'expérience - Spécialiste en cardiologie interventionnelle</p>
            <div style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
              <i class="fas fa-graduation-cap"></i> Diplômée de l'Université Paris VI
            </div>
          </div>
          <div class="docteur-card">
            <div class="docteur-photo" style="background: linear-gradient(135deg, #10b981, #059669);">
              <i class="fas fa-user-md"></i>
            </div>
            <h3>Dr. Karim Benali</h3>
            <div class="docteur-badge">Pédiatre Senior</div>
            <p>12 ans d'expérience - Expert en néonatalogie et développement enfant</p>
            <div style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
              <i class="fas fa-graduation-cap"></i> Diplômé de l'Université Lyon 1
            </div>
          </div>
          <div class="docteur-card">
            <div class="docteur-photo" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
              <i class="fas fa-user-md"></i>
            </div>
            <h3>Dr. Sarah Martin</h3>
            <div class="docteur-badge">Dermatologue</div>
            <p>15 ans d'expérience en dermatologie esthétique et oncologique</p>
            <div style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
              <i class="fas fa-graduation-cap"></i> Diplômée de l'Université Bordeaux
            </div>
          </div>
          <div class="docteur-card">
            <div class="docteur-photo" style="background: linear-gradient(135deg, #ec4899, #db2777);">
              <i class="fas fa-user-md"></i>
            </div>
            <h3>Dr. Mohammed El Amrani</h3>
            <div class="docteur-badge">Radiologue</div>
            <p>Expert en IRM et scanner multicouches - 10 ans d'expérience</p>
            <div style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
              <i class="fas fa-graduation-cap"></i> Diplômé de l'Université Strasbourg
            </div>
          </div>
          <div class="docteur-card">
            <div class="docteur-photo" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
              <i class="fas fa-user-md"></i>
            </div>
            <h3>Dr. Nadia Khalil</h3>
            <div class="docteur-badge">Pneumologue</div>
            <p>Spécialiste des maladies respiratoires et allergiques - 8 ans</p>
            <div style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
              <i class="fas fa-graduation-cap"></i> Diplômée de l'Université Montpellier
            </div>
          </div>
          <div class="docteur-card">
            <div class="docteur-photo" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
              <i class="fas fa-user-md"></i>
            </div>
            <h3>Dr. Paul Leroy</h3>
            <div class="docteur-badge">Neurologue</div>
            <p>Troubles du sommeil et épilepsie adulte - 14 ans d'expérience</p>
            <div style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
              <i class="fas fa-graduation-cap"></i> Diplômé de l'Université Lille
            </div>
          </div>
        </div>
  
  <script src="script.js"></script>
</body>
</html>