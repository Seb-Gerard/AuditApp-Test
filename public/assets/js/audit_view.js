/**
 * Audit View - JavaScript functions for audit details page
 * Application MVC pattern - View component
 */

// Variables globales
const videoStreams = {};

// Pour éviter les erreurs avec bootstrap
const bootstrap = window.bootstrap || {};

/**
 * Initialisation au chargement du document
 */
document.addEventListener("DOMContentLoaded", function () {
  // Ajouter un style pour les champs désactivés
  const style = document.createElement("style");
  style.innerHTML = `
    .disabled-field {
      background-color: #e9ecef !important;
      cursor: not-allowed;
      opacity: 0.7;
    }
    
    /* Style pour formulaires non modifiables (audit terminé) */
    form[disabled] {
      opacity: 0.8;
      pointer-events: none;
    }
    form[disabled] input, 
    form[disabled] textarea, 
    form[disabled] select, 
    form[disabled] button {
      pointer-events: none;
      background-color: #e9ecef;
      opacity: 0.7;
    }
  `;
  document.head.appendChild(style);

  // Vérifier si l'audit est terminé
  const badgeElement = document.querySelector(".badge.bg-success");
  const isAuditTermine =
    badgeElement !== null &&
    badgeElement.textContent &&
    badgeElement.textContent.trim() === "Terminé";

  // Gérer la soumission du formulaire d'évaluation
  initEvaluationForms(isAuditTermine);

  // Gérer la soumission des formulaires de documents
  initDocumentForms(isAuditTermine);

  // Initialiser les webcams pour chaque point de vigilance
  const pointsIds = document.querySelectorAll("[data-point-id]");
  pointsIds.forEach((element) => {
    const pointId = element.getAttribute("data-point-id");
    if (pointId) {
      initWebcam(parseInt(pointId));
    }
  });

  // Remplacer tous les aria-hidden par inert pour éviter les problèmes d'accessibilité
  initModalAccessibility();
});

/**
 * Initialise les formulaires d'évaluation
 */
function initEvaluationForms(isAuditTermine) {
  const forms = document.querySelectorAll(".evaluation-form");

  forms.forEach((form) => {
    // Ajouter un gestionnaire pour le checkbox "Audité" afin de suivre son changement d'état
    const nonAuditeCheckbox = form.querySelector('[name="non_audite"]');
    if (nonAuditeCheckbox) {
      // Fonction pour mettre à jour l'état des champs du formulaire
      const updateFieldsStatus = (isChecked) => {
        console.log(
          "Checkbox Audité changée : " + (isChecked ? "cochée" : "décochée")
        );

        // Identifier tous les champs du formulaire sauf le commentaire/justification et mesure réglementaire
        const formFields = form.querySelectorAll(
          'input:not([name="non_audite"]):not([name="mesure_reglementaire"]), textarea:not([name="justification"]), select'
        );
        const resultatRadios = form.querySelectorAll('input[name="resultat"]');
        // Référence au champ mesure_reglementaire pour s'assurer qu'il reste accessible
        const mesureReglementaire = form.querySelector(
          '[name="mesure_reglementaire"]'
        );

        // Activer/désactiver les champs
        formFields.forEach((field) => {
          // Conversion en types spécifiques qui ont la propriété disabled
          if (
            field instanceof HTMLInputElement ||
            field instanceof HTMLTextAreaElement ||
            field instanceof HTMLSelectElement
          ) {
            field.disabled = !isChecked || isAuditTermine;
          }
        });

        // Appliquer un style visuel pour indiquer que les champs sont désactivés
        if (!isChecked || isAuditTermine) {
          formFields.forEach((field) => {
            field.classList.add("disabled-field");
          });
          // Désactiver les radios également
          resultatRadios.forEach((radio) => {
            if (radio instanceof HTMLInputElement) {
              radio.disabled = true;
              if (radio.parentElement) {
                radio.parentElement.classList.add("text-muted");
              }
            }
          });
        } else {
          formFields.forEach((field) => {
            field.classList.remove("disabled-field");
          });
          // Réactiver les radios
          resultatRadios.forEach((radio) => {
            if (radio instanceof HTMLInputElement) {
              radio.disabled = false;
              if (radio.parentElement) {
                radio.parentElement.classList.remove("text-muted");
              }
            }
          });
        }
      };

      // Appliquer l'état initial au chargement de la page
      if (nonAuditeCheckbox && nonAuditeCheckbox instanceof HTMLInputElement) {
        updateFieldsStatus(nonAuditeCheckbox.checked);

        // Ajouter l'écouteur d'événement pour les changements
        nonAuditeCheckbox.addEventListener("change", function () {
          if (this instanceof HTMLInputElement) {
            updateFieldsStatus(this.checked);
          }
        });
      }
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const auditId = this.getAttribute("data-audit-id");
      const pointId = this.getAttribute("data-point-id");

      // Vérifier explicitement l'état des cases à cocher et les ajouter avec la bonne valeur
      const mesureReglementaireChecked =
        form.querySelector('[name="mesure_reglementaire"]')?.checked || false;
      const nonAuditeChecked =
        form.querySelector('[name="non_audite"]')?.checked || false;

      // Supprimer les valeurs existantes pour éviter les doublons
      if (formData.has("mesure_reglementaire")) {
        formData.delete("mesure_reglementaire");
      }
      if (formData.has("non_audite")) {
        formData.delete("non_audite");
      }

      // Ajouter les valeurs correctes
      formData.append(
        "mesure_reglementaire",
        mesureReglementaireChecked ? "1" : "0"
      );
      formData.append("non_audite", nonAuditeChecked ? "1" : "0");

      console.log(
        "Valeur envoyée pour non_audite: " + formData.get("non_audite")
      );

      // Afficher un indicateur de chargement
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
      submitBtn.disabled = true;

      // Envoyer les données au serveur
      fetch("index.php?action=audits&method=evaluerPoint", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Erreur réseau");
          }
          return response.json();
        })
        .then((data) => {
          console.log("Réponse du serveur:", data);

          if (data.success) {
            // Afficher un message de succès
            const alert = document.createElement("div");
            alert.className = "alert alert-success mt-3";
            alert.textContent = data.message;
            form.appendChild(alert);

            // Forcer le rechargement de la page après 1 seconde pour montrer la mise à jour
            setTimeout(() => {
              window.location.reload();
            }, 1000);
          } else {
            // Afficher un message d'erreur
            const alert = document.createElement("div");
            alert.className = "alert alert-danger mt-3";
            alert.textContent = data.message || "Une erreur est survenue";
            form.appendChild(alert);

            // Supprimer l'alerte après 3 secondes
            setTimeout(() => {
              alert.remove();
            }, 3000);
          }
        })
        .catch((error) => {
          console.error("Erreur:", error);

          // Afficher un message d'erreur
          const alert = document.createElement("div");
          alert.className = "alert alert-danger mt-3";
          alert.textContent =
            "Une erreur est survenue lors de la communication avec le serveur";
          form.appendChild(alert);

          // Supprimer l'alerte après 3 secondes
          setTimeout(() => {
            alert.remove();
          }, 3000);
        })
        .finally(() => {
          // Restaurer le bouton
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
    });
  });
}

/**
 * Initialise les formulaires d'ajout de document
 */
function initDocumentForms(isAuditTermine) {
  const documentForms = document.querySelectorAll(".document-form");
  documentForms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Téléchargement...';
      submitBtn.disabled = true;

      fetch("index.php?action=audits&method=ajouterDocument", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Fermer la modal
            const modalId =
              "documentModal-" + formData.get("point_vigilance_id");
            const modal = document.getElementById(modalId);
            const bsModal = bootstrap.Modal.getInstance(modal);
            bsModal.hide();

            // Rafraîchir la page pour afficher le nouveau document
            window.location.reload();
          } else {
            // Afficher un message d'erreur
            alert(
              data.message || "Une erreur est survenue lors du téléchargement"
            );
          }
        })
        .catch((error) => {
          console.error("Erreur:", error);
          alert(
            "Une erreur est survenue lors de la communication avec le serveur"
          );
        })
        .finally(() => {
          // Restaurer le bouton
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
    });
  });
}

/**
 * Initialise l'accessibilité des modales
 */
function initModalAccessibility() {
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("hide.bs.modal", function () {
      // Supprimer aria-hidden qui cause des problèmes
      setTimeout(() => {
        if (this.getAttribute("aria-hidden") === "true") {
          this.removeAttribute("aria-hidden");
          // Utiliser inert à la place (plus moderne et sans conflit de focus)
          this.setAttribute("inert", "");
        }
      }, 0);
    });

    modal.addEventListener("show.bs.modal", function () {
      // Supprimer inert lors de l'affichage
      if (this.hasAttribute("inert")) {
        this.removeAttribute("inert");
      }
    });
  });
}

/**
 * Confirmer la suppression d'un audit
 * @param {number} id - L'ID de l'audit à supprimer
 */
function confirmDelete(id) {
  if (
    confirm(
      "Êtes-vous sûr de vouloir supprimer cet audit ? Cette action est irréversible."
    )
  ) {
    window.location.href = "index.php?action=audits&method=delete&id=" + id;
  }
}

/**
 * Afficher la modal d'image d'un point de vigilance
 * @param {number} pointId - L'ID du point de vigilance
 */
function showImageModal(pointId) {
  // Fermer l'offcanvas
  const offcanvas = document.querySelector(".offcanvas.show");
  if (offcanvas) {
    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
    if (bsOffcanvas) {
      bsOffcanvas.hide();
    }
  }

  // Afficher la modal
  const modal = new bootstrap.Modal(
    document.getElementById("imageModal-" + pointId)
  );
  modal.show();
}

/**
 * Afficher la modal pour voir une photo
 * @param {string} photoUrl - L'URL de la photo à afficher
 * @param {string} photoName - Le nom de la photo
 */
function showPhotoModal(photoUrl, photoName) {
  // Mettre à jour la source de l'image
  const modalPhoto = document.getElementById("modal-photo");
  const modalLabel = document.getElementById("viewPhotoModalLabel");

  if (modalPhoto instanceof HTMLImageElement) {
    modalPhoto.src = photoUrl;
  }

  if (modalLabel) {
    modalLabel.textContent = photoName;
  }

  // Afficher la modal
  const modalElement = document.getElementById("viewPhotoModal");
  if (modalElement && typeof bootstrap.Modal !== "undefined") {
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
  }
}

/**
 * Supprimer un document ou une photo
 * @param {number} documentId - L'ID du document à supprimer
 */
function supprimerDocument(documentId) {
  if (confirm("Êtes-vous sûr de vouloir supprimer ce document/cette photo ?")) {
    const formData = new FormData();
    formData.append("document_id", documentId);

    fetch("index.php?action=audits&method=supprimerDocument", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Supprimer l'élément du DOM
          const photoContainer = document.getElementById(
            "photo-container-" + documentId
          );
          if (photoContainer) {
            photoContainer.remove();
          }

          const documentContainer = document.getElementById(
            "document-container-" + documentId
          );
          if (documentContainer) {
            documentContainer.remove();
          }
        } else {
          alert(
            data.message || "Une erreur est survenue lors de la suppression"
          );
        }
      })
      .catch((error) => {
        console.error("Erreur:", error);
        alert(
          "Une erreur est survenue lors de la communication avec le serveur"
        );
      });
  }
}

/**
 * Initialiser la webcam pour un point de vigilance
 * @param {number} pointId - L'ID du point de vigilance
 */
function initWebcam(pointId) {
  // Fermer tout flux vidéo existant pour ce point
  if (videoStreams[pointId]) {
    videoStreams[pointId].getTracks().forEach((track) => track.stop());
    videoStreams[pointId] = null;
  }

  const modal = document.getElementById("photoModal-" + pointId);
  if (!modal) return;

  // Configurer la modal pour utiliser l'attribut inert au lieu de aria-hidden
  modal.setAttribute("data-bs-config", "inert");

  // Gérer l'affichage de la modal
  modal.addEventListener("show.bs.modal", function () {
    // Réinitialiser la vue par défaut (vidéo visible, canvas caché)
    document.getElementById("camera-container-" + pointId).style.display =
      "block";
    document.getElementById(
      "captured-photo-container-" + pointId
    ).style.display = "none";
    document.getElementById("capture-btn-" + pointId).style.display =
      "inline-block";
    document.getElementById("save-btn-" + pointId).style.display = "none";
    document.getElementById("retake-btn-" + pointId).style.display = "none";
  });

  // Gérer l'affichage complet de la modal
  modal.addEventListener("shown.bs.modal", function () {
    const video = document.getElementById("video-" + pointId);

    // Demander l'accès à la webcam
    navigator.mediaDevices
      .getUserMedia({ video: true, audio: false })
      .then((stream) => {
        videoStreams[pointId] = stream;
        video.srcObject = stream;
      })
      .catch((err) => {
        console.error("Erreur lors de l'accès à la webcam:", err);
        alert(
          "Impossible d'accéder à la webcam. Veuillez vérifier que vous avez accordé les permissions nécessaires."
        );
      });
  });

  // Arrêter le flux vidéo lorsque la modal est cachée
  modal.addEventListener("hidden.bs.modal", function () {
    if (videoStreams[pointId]) {
      videoStreams[pointId].getTracks().forEach((track) => track.stop());
      videoStreams[pointId] = null;
    }
  });
}

/**
 * Capturer une photo depuis la webcam
 * @param {number} pointId - L'ID du point de vigilance
 */
function capturePhoto(pointId) {
  const video = document.getElementById("video-" + pointId);
  const canvas = document.getElementById("canvas-" + pointId);

  // Configurer le canvas à la taille de la vidéo
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;

  // Dessiner l'image vidéo sur le canvas
  const context = canvas.getContext("2d");
  context.drawImage(video, 0, 0, canvas.width, canvas.height);

  // Afficher le canvas et les boutons d'action
  document.getElementById("camera-container-" + pointId).style.display = "none";
  document.getElementById("captured-photo-container-" + pointId).style.display =
    "block";
  document.getElementById("capture-btn-" + pointId).style.display = "none";
  document.getElementById("save-btn-" + pointId).style.display = "inline-block";
  document.getElementById("retake-btn-" + pointId).style.display =
    "inline-block";
}

/**
 * Reprendre une photo
 * @param {number} pointId - L'ID du point de vigilance
 */
function retakePhoto(pointId) {
  // Afficher à nouveau la vidéo et masquer le canvas
  document.getElementById("camera-container-" + pointId).style.display =
    "block";
  document.getElementById("captured-photo-container-" + pointId).style.display =
    "none";
  document.getElementById("capture-btn-" + pointId).style.display =
    "inline-block";
  document.getElementById("save-btn-" + pointId).style.display = "none";
  document.getElementById("retake-btn-" + pointId).style.display = "none";
}

/**
 * Enregistrer une photo
 * @param {number} auditId - L'ID de l'audit
 * @param {number} pointId - L'ID du point de vigilance
 */
function savePhoto(auditId, pointId) {
  const canvas = document.getElementById("canvas-" + pointId);
  const imageData = canvas.toDataURL("image/jpeg");

  console.log(
    "Préparation de l'image pour l'envoi. Taille de la chaîne: " +
      imageData.length
  );

  // Vérifier que l'image a bien le format attendu
  if (!imageData.startsWith("data:image/jpeg;base64,")) {
    console.error(
      "Format d'image incorrect:",
      imageData.substring(0, 50) + "..."
    );
    alert("Erreur: format d'image incorrect");
    return;
  }

  // Préparer les données à envoyer
  const formData = new FormData();
  formData.append("audit_id", auditId);
  formData.append("point_vigilance_id", pointId);
  formData.append("image_base64", imageData);

  console.log("Données prêtes à être envoyées:", {
    audit_id: auditId,
    point_vigilance_id: pointId,
    image_base64_length: imageData.length,
  });

  // Désactiver les boutons pendant l'envoi
  document.getElementById("save-btn-" + pointId).disabled = true;
  document.getElementById("retake-btn-" + pointId).disabled = true;
  document.getElementById("save-btn-" + pointId).innerHTML =
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi en cours...';

  // Arrêter le flux vidéo avant d'envoyer la photo
  if (videoStreams[pointId]) {
    videoStreams[pointId].getTracks().forEach((track) => track.stop());
    videoStreams[pointId] = null;
  }

  console.log(
    "Envoi de la requête à index.php?action=audits&method=prendrePhoto"
  );

  // Envoyer l'image au serveur
  fetch("index.php?action=audits&method=prendrePhoto", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      console.log("Réponse reçue avec statut:", response.status);
      return response.json();
    })
    .then((data) => {
      console.log("Données reçues:", data);
      if (data.success) {
        console.log(
          "Photo enregistrée avec succès, ID:",
          data.photo ? data.photo.id : "inconnu"
        );

        // Fermer la modal
        const modal = document.getElementById("photoModal-" + pointId);
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
          bsModal.hide();
        } else {
          console.warn("Impossible de trouver l'instance bootstrap modal");
          modal.style.display = "none";
          // Supprimer manuellement le backdrop si nécessaire
          const backdrops = document.querySelectorAll(".modal-backdrop");
          backdrops.forEach((backdrop) => backdrop.remove());
        }

        // Attendre que le traitement soit terminé avant de rafraîchir la page
        setTimeout(() => {
          window.location.reload();
        }, 300);
      } else {
        console.error("Erreur retournée par le serveur:", data.message);
        alert(
          data.message ||
            "Une erreur est survenue lors de l'enregistrement de la photo"
        );
      }
    })
    .catch((error) => {
      console.error("Erreur lors de la communication:", error);
      alert("Une erreur est survenue lors de la communication avec le serveur");
    })
    .finally(() => {
      // Réactiver les boutons
      document.getElementById("save-btn-" + pointId).disabled = false;
      document.getElementById("retake-btn-" + pointId).disabled = false;
      document.getElementById("save-btn-" + pointId).innerHTML =
        '<i class="fas fa-save"></i> Enregistrer';
    });
}
