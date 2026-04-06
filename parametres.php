<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DME Pro - Paramètres</title>
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
        <a href="equipe.html" class="nav-btn">
          <i class="fas fa-users nav-icon"></i> Équipe médicale
        </a>
        <a href="parametres.html" class="nav-btn active">
          <i class="fas fa-cog nav-icon"></i> Paramètres
        </a>
      </nav>
    </aside>

    <main class="main">
      <section id="parametres-view" class="view">
        <div class="page-header">
          <div>
            <h1 class="page-title">Paramètres</h1>
            <p class="page-subtitle">Configuration du système et préférences utilisateur</p>
          </div>
        </div>
        
        <!-- Formulaire des paramètres... -->
        <div class="table-container">
          <div class="table-header">
            <div style="font-size: 1.1rem; font-weight: 600;">
              <i class="fas fa-sliders-h"></i> Paramètres généraux
            </div>
          </div>
          <div style="padding: 2.5rem;">
            <div class="form-group">
              <label class="form-label">Nom de l'établissement</label>
              <input type="text" class="form-control" value="Centre Médical DME Pro">
            </div>
            <div class="form-group">
              <label class="form-label">Adresse</label>
              <input type="text" class="form-control" value="123 Avenue de la Santé, 75000 Paris">
            </div>
            <div class="form-group">
              <label class="form-label">Téléphone</label>
              <input type="text" class="form-control" value="01 23 45 67 89">
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" value="contact@dmepro.fr">
            </div>
            <div class="form-group">
              <label class="form-label">Thème de l'interface</label>
              <select class="form-control">
                <option>Clair (par défaut)</option>
                <option>Sombre</option>
                <option>Auto</option>
              </select>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
              <button class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer les modifications
              </button>
              <button class="btn" style="background: linear-gradient(135deg, var(--warning), #b45309);">
                <i class="fas fa-undo"></i> Réinitialiser
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
  
  <script src="script.js"></script>
</body>
</html>