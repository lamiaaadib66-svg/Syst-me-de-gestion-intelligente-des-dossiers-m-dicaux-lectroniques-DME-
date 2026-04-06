class DMEPro {
    constructor() {
        this.baseUrl = 'api.php';
        this.init();
    }

    init() {
        this.bindNavigation();
        this.initResponsive();
        this.loadDashboardData();
        this.initEventListeners();
    }

    // Communication avec l'API
    async fetchData(endpoint, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`${this.baseUrl}/${endpoint}`, options);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.error || 'Erreur serveur');
            }
            
            return result;
        } catch (error) {
            console.error('Erreur API:', error);
            this.showNotification(error.message, 'error');
            throw error;
        }
    }

    // Charger les données du tableau de bord
    async loadDashboardData() {
        try {
            const stats = await this.fetchData('stats');
            this.updateStats(stats.statistiques);
            
            const today = new Date().toISOString().split('T')[0];
            const rdvs = await this.fetchData(`rendezvous?date=${today}`);
            this.updateAgenda(rdvs.rendezvous);
            
            const alertes = await this.fetchData('alertes');
            this.updateAlertes(alertes.alertes);
            
            // Patients nécessitant attention
            const consultations = await this.fetchData('consultations');
            this.updatePatientsUrgents(consultations.consultations);
            
        } catch (error) {
            console.error('Erreur chargement dashboard:', error);
        }
    }

    updateStats(stats) {
        // Mettre à jour les cartes statistiques
        document.querySelectorAll('.stat-card').forEach(card => {
            const type = card.querySelector('.stat-label').textContent.toLowerCase();
            let value = 0;
            
            switch(type) {
                case 'patients':
                    value = stats.patients;
                    break;
                case 'rdv aujourd\'hui':
                    value = stats.rendezvous_du_jour;
                    break;
                case 'consultations':
                    value = stats.consultations_mois;
                    break;
                case 'prescriptions':
                    value = stats.prescriptions_actives;
                    break;
            }
            
            const numberElement = card.querySelector('.stat-number');
            this.animateCounter(numberElement, value);
        });
    }

    animateCounter(element, target) {
        let current = 0;
        const increment = target / 50;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = target;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 30);
    }

    updateAgenda(rdvs) {
        const tbody = document.querySelector('#dashboard-view table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        rdvs.forEach(rdv => {
            const tr = document.createElement('tr');
            
            const time = new Date(rdv.date_rdv).toLocaleTimeString('fr-FR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            const initials = (rdv.patient_prenom.charAt(0) + rdv.patient_nom.charAt(0)).toUpperCase();
            const color = this.getAvatarColor(rdv.id);
            
            tr.innerHTML = `
                <td><strong>${time}</strong></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="patient-avatar" style="background: ${color}; width: 40px; height: 40px; font-size: 1rem;">
                            ${initials}
                        </div>
                        <div>
                            <div style="font-weight: 600;">${rdv.patient_prenom} ${rdv.patient_nom}</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                ${this.calculateAge(rdv.date_naissance)} ans
                            </div>
                        </div>
                    </div>
                </td>
                <td>${rdv.motif}</td>
                <td>${rdv.duree} min</td>
                <td>${rdv.salle}</td>
                <td><span class="status ${rdv.statut}">${this.getStatusText(rdv.statut)}</span></td>
                <td>
                    <div class="table-actions">
                        <button class="action-btn btn-view" onclick="dme.viewPatient(${rdv.patient_id})" title="Voir dossier patient">
                            <i class="fas fa-user"></i> Dossier
                        </button>
                        <button class="action-btn btn-edit" onclick="dme.editRdv(${rdv.id})" title="Modifier RDV">
                            <i class="fas fa-calendar-alt"></i> Modifier
                        </button>
                        <button class="action-btn" style="background: linear-gradient(135deg, var(--success), #047857);" onclick="dme.startConsultation(${rdv.id})" title="Commencer consultation">
                            <i class="fas fa-play"></i> Démarrer
                        </button>
                    </div>
                </td>
            `;
            
            tbody.appendChild(tr);
        });
    }

    updateAlertes(alertes) {
        const tbody = document.querySelectorAll('#dashboard-view table tbody')[1];
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        alertes.forEach(alerte => {
            const tr = document.createElement('tr');
            
            const priorityColor = this.getPriorityColor(alerte.priorite);
            const priorityText = this.getPriorityText(alerte.priorite);
            
            tr.innerHTML = `
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 12px; height: 12px; background: ${priorityColor}; border-radius: 50%;"></div>
                        <span>${alerte.type}</span>
                    </div>
                </td>
                <td>${alerte.description}</td>
                <td>${alerte.patient_prenom} ${alerte.patient_nom}</td>
                <td>${new Date(alerte.date_alerte).toLocaleDateString('fr-FR')}</td>
                <td><span style="color: ${priorityColor}; font-weight: 700;">${priorityText}</span></td>
                <td>
                    <div class="table-actions">
                        <button class="action-btn btn-view" onclick="dme.viewAlerte(${alerte.id})" title="Voir détails">
                            <i class="fas fa-eye"></i> Détails
                        </button>
                        <button class="action-btn btn-edit" onclick="dme.editAlerte(${alerte.id})" title="Traiter">
                            <i class="fas fa-check"></i> Traiter
                        </button>
                        <button class="action-btn" style="background: linear-gradient(135deg, #6b7280, #4b5563);" onclick="dme.markAlerteAsRead(${alerte.id})" title="Marquer comme lue">
                            <i class="fas fa-check"></i> Lue
                        </button>
                    </div>
                </td>
            `;
            
            tbody.appendChild(tr);
        });
    }

    updatePatientsUrgents(consultations) {
        // Filtrer les patients avec diagnostics urgents
        const urgentPatients = consultations.filter(c => 
            c.diagnostic && (
                c.diagnostic.toLowerCase().includes('hypertension') ||
                c.diagnostic.toLowerCase().includes('urgence') ||
                c.diagnostic.toLowerCase().includes('sévère')
            )
        );
        
        const tbody = document.querySelectorAll('#dashboard-view table tbody')[2];
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        urgentPatients.slice(0, 5).forEach(patient => {
            const tr = document.createElement('tr');
            const initials = (patient.patient_prenom?.charAt(0) || '') + (patient.patient_nom?.charAt(0) || '');
            
            tr.innerHTML = `
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="patient-avatar" style="background: linear-gradient(135deg, #ef4444, #dc2626); width: 40px; height: 40px; font-size: 1rem;">
                            ${initials}
                        </div>
                        <div>
                            <div style="font-weight: 600;">${patient.patient_prenom} ${patient.patient_nom}</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">${patient.diagnostic}</div>
                        </div>
                    </div>
                </td>
                <td>${patient.motif}</td>
                <td>${new Date(patient.date_consultation).toLocaleDateString('fr-FR')}</td>
                <td><span style="color: var(--danger); font-weight: 700;">Urgent</span></td>
                <td>
                    <div class="table-actions">
                        <button class="action-btn btn-view" onclick="dme.viewPatient(${patient.patient_id})" title="Voir dossier complet">
                            <i class="fas fa-file-medical"></i> Dossier
                        </button>
                        <button class="action-btn btn-edit" onclick="dme.editConsultation(${patient.id})" title="Modifier traitement">
                            <i class="fas fa-pills"></i> Traitement
                        </button>
                    </div>
                </td>
            `;
            
            tbody.appendChild(tr);
        });
    }

    // Charger la liste des patients
    async loadPatients() {
        try {
            const result = await this.fetchData('patients');
            this.displayPatients(result.patients);
        } catch (error) {
            console.error('Erreur chargement patients:', error);
        }
    }

    displayPatients(patients) {
        const tbody = document.querySelector('#patients-view table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        patients.forEach(patient => {
            const tr = document.createElement('tr');
            const initials = (patient.prenom.charAt(0) + patient.nom.charAt(0)).toUpperCase();
            const color = this.getAvatarColor(patient.id);
            
            tr.innerHTML = `
                <td>
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div class="patient-avatar" style="background: ${color};">
                            ${initials}
                        </div>
                        <div>
                            <div style="font-weight: 700;">${patient.prenom} ${patient.nom}</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                ${patient.genre === 'F' ? 'Femme' : 'Homme'}, ${patient.age} ans
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    ${new Date(patient.date_naissance).toLocaleDateString('fr-FR')}<br>
                    <small>${patient.age} ans</small>
                </td>
                <td>${patient.telephone}</td>
                <td><span style="color: var(--danger); font-weight: 700;">${patient.groupe_sanguin}</span></td>
                <td>
                    <strong>${patient.derniere_consultation ? new Date(patient.derniere_consultation).toLocaleDateString('fr-FR') : 'Jamais'}</strong><br>
                    <small>${patient.derniere_consultation ? new Date(patient.derniere_consultation).toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'}) : ''}</small>
                </td>
                <td>
                    <div class="table-actions">
                        <button class="action-btn btn-view" onclick="dme.viewPatient(${patient.id})" title="Voir dossier">
                            <i class="fas fa-eye"></i> VOIR
                        </button>
                        <button class="action-btn btn-edit" onclick="dme.editPatient(${patient.id})" title="Modifier">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button class="action-btn btn-delete" onclick="dme.deletePatient(${patient.id})" title="Supprimer">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </div>
                </td>
            `;
            
            tbody.appendChild(tr);
        });
    }

    // Méthodes utilitaires
    getAvatarColor(id) {
        const colors = [
            'linear-gradient(135deg, #3b82f6, #1d4ed8)',
            'linear-gradient(135deg, #10b981, #059669)',
            'linear-gradient(135deg, #f59e0b, #d97706)',
            'linear-gradient(135deg, #8b5cf6, #7c3aed)',
            'linear-gradient(135deg, #ec4899, #db2777)',
            'linear-gradient(135deg, #06b6d4, #0891b2)'
        ];
        return colors[id % colors.length];
    }

    getStatusText(status) {
        const statusMap = {
            'planifie': 'Planifié',
            'confirme': 'Confirmé',
            'annule': 'Annulé',
            'termine': 'Terminé',
            'en_cours': 'En cours'
        };
        return statusMap[status] || status;
    }

    getPriorityColor(priority) {
        const colors = {
            'haute': 'var(--danger)',
            'moyenne': 'var(--warning)',
            'basse': 'var(--info)'
        };
        return colors[priority] || 'var(--text-secondary)';
    }

    getPriorityText(priority) {
        const texts = {
            'haute': 'Haute',
            'moyenne': 'Moyenne',
            'basse': 'Basse'
        };
        return texts[priority] || priority;
    }

    calculateAge(dateString) {
        const birthDate = new Date(dateString);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        return age;
    }

    showNotification(message, type = 'info') {
        // Créer une notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            color: white;
            font-weight: 600;
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
            box-shadow: var(--shadow-lg);
        `;
        
        const bgColor = type === 'error' ? 'var(--danger)' : 
                       type === 'success' ? 'var(--success)' : 
                       'var(--info)';
        
        notification.style.background = bgColor;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Gestion des événements
    initEventListeners() {
        // Recherche de patients
        document.querySelectorAll('.search-box input').forEach(input => {
            input.addEventListener('keyup', async (e) => {
                if (e.key === 'Enter') {
                    const searchTerm = e.target.value;
                    try {
                        const result = await this.fetchData(`patients?search=${encodeURIComponent(searchTerm)}`);
                        this.displayPatients(result.patients);
                    } catch (error) {
                        console.error('Erreur recherche:', error);
                    }
                }
            });
        });

        // Bouton nouveau patient
        document.querySelector('#patients-view .btn-primary')?.addEventListener('click', () => {
            this.showPatientForm();
        });

        // Bouton nouveau rendez-vous
        document.querySelector('#dashboard-view .btn-primary')?.addEventListener('click', () => {
            this.showRdvForm();
        });
    }

    // Méthodes pour les actions
    async viewPatient(id) {
        try {
            const patient = await this.fetchData(`patients/${id}`);
            this.showPatientModal(patient);
        } catch (error) {
            console.error('Erreur vue patient:', error);
        }
    }

    async editPatient(id) {
        try {
            const patient = await this.fetchData(`patients/${id}`);
            this.showPatientForm(patient);
        } catch (error) {
            console.error('Erreur édition patient:', error);
        }
    }

    async deletePatient(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce patient ?')) {
            try {
                await this.fetchData(`patients/${id}`, 'DELETE');
                this.showNotification('Patient supprimé avec succès', 'success');
                this.loadPatients();
            } catch (error) {
                this.showNotification('Erreur lors de la suppression', 'error');
            }
        }
    }

    async startConsultation(rdvId) {
        try {
            const rdv = await this.fetchData(`rendezvous/${rdvId}`);
            this.showConsultationForm(rdv);
        } catch (error) {
            console.error('Erreur démarrage consultation:', error);
        }
    }

    async markAlerteAsRead(id) {
        try {
            await this.fetchData(`alertes/${id}`, 'PUT', { traite: true });
            this.showNotification('Alerte marquée comme lue', 'success');
            this.loadDashboardData();
        } catch (error) {
            this.showNotification('Erreur mise à jour alerte', 'error');
        }
    }

    // Modales (simplifiées)
    showPatientModal(patient) {
        const modal = `
            <div class="modal-overlay">
                <div class="modal-content">
                    <h2>Dossier Patient: ${patient.prenom} ${patient.nom}</h2>
                    <div class="patient-info">
                        <p><strong>Matricule:</strong> ${patient.matricule}</p>
                        <p><strong>Âge:</strong> ${this.calculateAge(patient.date_naissance)} ans</p>
                        <p><strong>Téléphone:</strong> ${patient.telephone}</p>
                        <p><strong>Groupe sanguin:</strong> ${patient.groupe_sanguin}</p>
                        <p><strong>Allergies:</strong> ${patient.allergies || 'Aucune connue'}</p>
                        <p><strong>Antécédents:</strong> ${patient.antecedents || 'Aucun'}</p>
                    </div>
                    <button onclick="this.closest('.modal-overlay').remove()">Fermer</button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modal);
    }

    showPatientForm(patient = null) {
        const isEdit = !!patient;
        const form = `
            <div class="modal-overlay">
                <div class="modal-content">
                    <h2>${isEdit ? 'Modifier' : 'Nouveau'} Patient</h2>
                    <form id="patientForm">
                        <input type="hidden" name="id" value="${patient?.id || ''}">
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="nom" value="${patient?.nom || ''}" required>
                        </div>
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="prenom" value="${patient?.prenom || ''}" required>
                        </div>
                        <div class="form-group">
                            <label>Date de naissance *</label>
                            <input type="date" name="date_naissance" value="${patient?.date_naissance || ''}" required>
                        </div>
                        <div class="form-group">
                            <label>Genre</label>
                            <select name="genre">
                                <option value="M" ${patient?.genre === 'M' ? 'selected' : ''}>Masculin</option>
                                <option value="F" ${patient?.genre === 'F' ? 'selected' : ''}>Féminin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="tel" name="telephone" value="${patient?.telephone || ''}">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="${patient?.email || ''}">
                        </div>
                        <div class="form-actions">
                            <button type="submit">${isEdit ? 'Mettre à jour' : 'Créer'}</button>
                            <button type="button" onclick="this.closest('.modal-overlay').remove()">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', form);
        
        // Gestion de la soumission
        document.getElementById('patientForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            try {
                if (isEdit) {
                    await this.fetchData(`patients/${data.id}`, 'PUT', data);
                    this.showNotification('Patient mis à jour', 'success');
                } else {
                    await this.fetchData('patients', 'POST', data);
                    this.showNotification('Patient créé', 'success');
                }
                
                e.target.closest('.modal-overlay').remove();
                this.loadPatients();
            } catch (error) {
                this.showNotification('Erreur', 'error');
            }
        });
    }

    // Navigation et responsive (gardé depuis votre code original)
    bindNavigation() {
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const view = btn.dataset.view;
                
                document.querySelectorAll('.view').forEach(v => {
                    v.style.animation = 'slideUp 0.5s ease-out';
                    v.style.display = 'none';
                });
                
                const targetView = document.getElementById(`${view}-view`);
                targetView.style.display = 'block';
                
                document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Charger les données spécifiques à la vue
                if (view === 'patients') {
                    this.loadPatients();
                }
                
                if (window.innerWidth < 1200) {
                    document.getElementById('sidebar').classList.remove('open');
                }
            });
        });
    }

    initResponsive() {
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
    }
}

// Initialiser l'application
let dme;
document.addEventListener('DOMContentLoaded', () => {
    dme = new DMEPro();
});



///////

// Fonction pour récupérer les données du tableau de bord
async function fetchDashboardData() {
    try {
        const [statsRes, rdvRes, alertesRes] = await Promise.all([
            fetch('api.php/stats'),
            fetch('api.php/rendezvous'),
            fetch('api.php/alertes')
        ]);
        
        const stats = await statsRes.json();
        const rdvs = await rdvRes.json();
        const alertes = await alertesRes.json();
        
        // Mettre à jour l'interface avec les données
        updateDashboardUI(stats, rdvs, alertes);
        
    } catch (error) {
        console.error('Erreur chargement dashboard:', error);
        showNotification('Erreur de chargement des données', 'error');
    }
}

// Fonction pour mettre à jour les statistiques
function updateStats(stats) {
    const statCards = {
        'patients': stats.patients,
        'rdv aujourd\'hui': stats.rendezvous_du_jour,
        'consultations': stats.consultations_mois,
        'prescriptions': stats.prescriptions_actives
    };
    
    Object.entries(statCards).forEach(([type, value]) => {
        const card = document.querySelector(`.stat-card .stat-label:contains("${type}")`).closest('.stat-card');
        if (card) {
            const numberElement = card.querySelector('.stat-number');
            animateCounter(numberElement, value);
        }
    });
}

// Fonction pour animer les compteurs
function animateCounter(element, target) {
    let current = 0;
    const increment = target / 50;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current);
        }
    }, 30);
}

// Fonction pour charger les patients
async function loadPatients() {
    try {
        const response = await fetch('api.php/patients');
        const data = await response.json();
        
        if (data.success) {
            displayPatients(data.data.patients);
        }
    } catch (error) {
        console.error('Erreur chargement patients:', error);
        showNotification('Erreur de chargement des patients', 'error');
    }
}

// Modifier les URL dans les pages HTML
// Dans chaque fichier HTML, changer les liens pour pointer vers l'API :
// Exemple dans patients.html :
// <a href="api.php?page=patients" class="nav-btn active">