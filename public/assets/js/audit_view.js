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
  console.log("Initialisation de", forms.length, "formulaires d'évaluation");

  forms.forEach((form, index) => {
    // Récupérer les checkboxes
    const nonAuditeCheckbox = form.querySelector('[name="non_audite"]');
    const mesureReglementaireCheckbox = form.querySelector(
      '[name="mesure_reglementaire"]'
    );

    // Empêcher la soumission automatique au clic sur les checkboxes
    if (mesureReglementaireCheckbox) {
      // Remplacer l'écouteur d'événement existant par un qui empêche complètement la soumission
      mesureReglementaireCheckbox.removeEventListener("click", function (e) {});
      mesureReglementaireCheckbox.addEventListener("click", function (e) {
        // Empêcher simplement la propagation de l'événement
        e.stopPropagation();
        // Ne pas utiliser preventDefault() qui empêcherait le changement d'état
        console.log("Clic sur mesure_reglementaire, état:", this.checked);
      });
    }

    if (nonAuditeCheckbox) {
      // Remplacer l'écouteur d'événement existant par un qui empêche complètement la soumission
      nonAuditeCheckbox.removeEventListener("click", function (e) {});
      nonAuditeCheckbox.addEventListener("click", function (e) {
        // Empêcher simplement la propagation de l'événement
        e.stopPropagation();
        // Ne pas utiliser preventDefault() qui empêcherait le changement d'état
        console.log("Clic sur non_audite, état:", this.checked);
      });

      // Fonction pour mettre à jour l'état des champs du formulaire
      const updateFieldsStatus = (isChecked) => {
        console.log("updateFieldsStatus appelé avec isChecked=", isChecked);

        // Sélectionner les éléments avec vérification de type
        const textInputs = Array.from(
          form.querySelectorAll('input[type="text"], input[type="number"]')
        ).filter((el) => el instanceof HTMLInputElement);

        const textareas = Array.from(form.querySelectorAll("textarea")).filter(
          (el) => el instanceof HTMLTextAreaElement
        );

        const selects = Array.from(form.querySelectorAll("select")).filter(
          (el) => el instanceof HTMLSelectElement
        );

        const radios = Array.from(
          form.querySelectorAll('input[type="radio"]')
        ).filter((el) => el instanceof HTMLInputElement);

        // Ne jamais désactiver les checkboxes pour qu'elles restent indépendantes l'une de l'autre
        // Les checkboxes "Mesure réglementaire" et "Audité" doivent toujours être actives

        // Gérer les champs de texte, les textareas et les selects
        [...textInputs, ...textareas, ...selects].forEach((field) => {
          field.disabled = !isChecked || isAuditTermine;
          if (!isChecked || isAuditTermine) {
            field.classList.add("disabled-field");
          } else {
            field.classList.remove("disabled-field");
          }
        });

        // Gérer séparément les boutons radio
        radios.forEach((radio) => {
          radio.disabled = !isChecked || isAuditTermine;
          if (radio.parentElement) {
            if (!isChecked || isAuditTermine) {
              radio.parentElement.classList.add("text-muted");
            } else {
              radio.parentElement.classList.remove("text-muted");
            }
          }
        });
      };

      // Appliquer l'état initial au chargement de la page
      if (nonAuditeCheckbox instanceof HTMLInputElement) {
        updateFieldsStatus(nonAuditeCheckbox.checked);

        // Ajouter l'écouteur d'événement pour les changements
        nonAuditeCheckbox.addEventListener("change", function () {
          if (this instanceof HTMLInputElement) {
            updateFieldsStatus(this.checked);
          }
        });
      }
    }

    // S'assurer que les champs cachés sont bien présents et avec des valeurs
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      console.log("Soumission du formulaire");

      // Récupérer les identifiants depuis les attributs data du formulaire
      const auditId = this.getAttribute("data-audit-id");
      const pointId = this.getAttribute("data-point-id");

      if (!auditId || !pointId) {
        console.error(
          "ERREUR: Attributs data-audit-id ou data-point-id manquants"
        );
        alert(
          "Erreur: Identifiants manquants. Veuillez recharger la page et réessayer."
        );
        return;
      }

      console.log(`IDs récupérés: audit=${auditId}, point=${pointId}`);

      // Créer un FormData pour l'envoi
      const formData = new FormData();
      formData.append("audit_id", auditId);
      formData.append("point_vigilance_id", pointId);

      // Récupérer l'état des checkboxes
      const mesureReglementaireEl = this.querySelector(
        '[name="mesure_reglementaire"]'
      );
      const nonAuditeEl = this.querySelector('[name="non_audite"]');

      // Ajouter les valeurs des checkboxes, avec vérification explicite
      if (mesureReglementaireEl instanceof HTMLInputElement) {
        const mesureValue = mesureReglementaireEl.checked ? "1" : "0";
        formData.append("mesure_reglementaire", mesureValue);
        console.log(
          `mesure_reglementaire=${mesureValue} (${
            mesureReglementaireEl.checked ? "coché" : "non coché"
          })`
        );
      } else {
        formData.append("mesure_reglementaire", "0");
        console.log("mesure_reglementaire=0 (élément non trouvé)");
      }

      if (nonAuditeEl instanceof HTMLInputElement) {
        const nonAuditeValue = nonAuditeEl.checked ? "1" : "0";
        formData.append("non_audite", nonAuditeValue);
        console.log(
          `non_audite=${nonAuditeValue} (${
            nonAuditeEl.checked ? "coché" : "non coché"
          })`
        );
      } else {
        formData.append("non_audite", "0");
        console.log("non_audite=0 (élément non trouvé)");
      }

      // Récupérer les valeurs des autres champs
      const fieldsToCollect = [
        { name: "mode_preuve", isRequired: false },
        { name: "resultat", isRequired: false },
        { name: "justification", isRequired: false },
        { name: "plan_action_numero", isRequired: false },
        { name: "plan_action_priorite", isRequired: false },
        { name: "plan_action_description", isRequired: false },
      ];

      fieldsToCollect.forEach((field) => {
        const fieldElement = this.querySelector(`[name="${field.name}"]`);
        if (fieldElement) {
          let value = "";

          // Traitement spécial pour les radios
          if (field.name === "resultat") {
            const selectedRadio = this.querySelector(
              `input[name="${field.name}"]:checked`
            );
            value = selectedRadio ? selectedRadio.value : "";
          } else {
            value = fieldElement.value || "";
          }

          formData.append(field.name, value);
          console.log(`${field.name}=${value}`);
        } else if (field.isRequired) {
          console.error(`Champ requis ${field.name} manquant`);
          return;
        }
      });

      // Afficher un indicateur de chargement
      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
        submitBtn.disabled = true;

        // Ajouter un message de chargement
        const loadingMessage = document.createElement("div");
        loadingMessage.className = "alert alert-info mt-3";
        loadingMessage.innerHTML = "Envoi des données en cours...";
        this.appendChild(loadingMessage);

        // Envoyer les données au serveur
        fetch("index.php?action=audits&method=evaluerPoint", {
          method: "POST",
          body: formData,
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
        })
          .then((response) => {
            console.log("Réponse du serveur:", response.status);
            console.log("Headers:", [...response.headers.entries()]);
            console.log("Response type:", response.type);
            console.log("Response URL:", response.url);

            return response.text().then((text) => {
              console.log("Texte brut de la réponse:", text);
              if (!text) {
                throw new Error("Réponse vide du serveur");
              }
              try {
                // Essayer de parser le texte en JSON
                return JSON.parse(text);
              } catch (e) {
                console.error("Réponse non-JSON reçue:", text);
                throw new Error("Réponse invalide: " + text.substring(0, 100));
              }
            });
          })
          .then((data) => {
            console.log("Données reçues:", data);
            loadingMessage.remove();

            if (data.success) {
              // Afficher un message de succès
              const alert = document.createElement("div");
              alert.className = "alert alert-success mt-3";
              alert.textContent = data.message || "Enregistrement réussi";
              form.appendChild(alert);

              // Forcer le rechargement de la page après 1 seconde
              setTimeout(() => {
                window.location.reload();
              }, 1000);
            } else {
              // Afficher un message d'erreur
              const alert = document.createElement("div");
              alert.className = "alert alert-danger mt-3";
              alert.textContent = data.message || "Une erreur est survenue";
              form.appendChild(alert);
            }
          })
          .catch((error) => {
            console.error("Erreur:", error);
            loadingMessage.remove();

            // Afficher un message d'erreur
            const alert = document.createElement("div");
            alert.className = "alert alert-danger mt-3";
            alert.textContent =
              "Une erreur est survenue lors de la communication avec le serveur: " +
              error.message;
            form.appendChild(alert);
          })
          .finally(() => {
            // Restaurer le bouton
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
          });
      }
    });
  });
}

/**
 * Initialise les formulaires d'ajout de document
 */
function initDocumentForms(isAuditTermine) {
  const documentForms = document.querySelectorAll(".document-form");

  // Corriger les problèmes d'attributs aria-hidden sur les modals
  document.querySelectorAll(".modal").forEach((modal) => {
    // Remplacer aria-hidden par inert quand la modal est cachée
    if (modal.getAttribute("aria-hidden") === "true") {
      modal.removeAttribute("aria-hidden");
      modal.setAttribute("inert", "");
    }

    // S'assurer que la modal utilise inert au lieu de aria-hidden
    modal.addEventListener("hide.bs.modal", function () {
      // Utiliser un délai pour permettre à Bootstrap de terminer sa fermeture
      setTimeout(() => {
        if (this.getAttribute("aria-hidden") === "true") {
          this.removeAttribute("aria-hidden");
          this.setAttribute("inert", "");
        }
      }, 50);
    });

    // Retirer inert lors de l'affichage
    modal.addEventListener("show.bs.modal", function () {
      if (this.hasAttribute("inert")) {
        this.removeAttribute("inert");
      }
    });
  });

  documentForms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      console.log("Soumission du formulaire de document");

      // Vérifier si les champs requis sont présents
      const auditIdField = this.querySelector('input[name="audit_id"]');
      const pointVigilanceIdField = this.querySelector(
        'input[name="point_vigilance_id"]'
      );

      if (
        !auditIdField ||
        !pointVigilanceIdField ||
        !auditIdField.value ||
        !pointVigilanceIdField.value
      ) {
        alert(
          "Erreur: Paramètres manquants (audit_id, point_vigilance_id). Veuillez réessayer."
        );
        return;
      }

      // Créer un nouveau FormData pour plus de contrôle
      const formData = new FormData();

      // Ajouter explicitement les identifiants
      formData.append("audit_id", auditIdField.value);
      formData.append("point_vigilance_id", pointVigilanceIdField.value);
      formData.append("type", "document");

      // Ajouter le fichier
      const fileInput = this.querySelector('input[type="file"]');
      if (fileInput && fileInput.files && fileInput.files[0]) {
        formData.append("document", fileInput.files[0]);
        console.log("Fichier ajouté:", fileInput.files[0].name);
      } else {
        alert("Veuillez sélectionner un fichier à télécharger");
        return;
      }

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Téléchargement...';
      submitBtn.disabled = true;

      fetch("index.php?action=audits&method=ajouterDocument", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => {
          console.log("Status:", response.status);
          console.log("Headers:", [...response.headers.entries()]);

          if (!response.ok) {
            throw new Error("Erreur réseau: " + response.status);
          }

          // Vérifier le type de contenu retourné
          const contentType = response.headers.get("content-type");
          if (contentType && contentType.includes("text/html")) {
            // Le serveur a renvoyé du HTML au lieu de JSON
            return response.text().then((html) => {
              console.error(
                "Le serveur a renvoyé du HTML au lieu de JSON:",
                html.substring(0, 500)
              );
              throw new Error(
                "Réponse invalide du serveur (HTML au lieu de JSON)"
              );
            });
          }

          return response.text().then((text) => {
            console.log("Texte de la réponse:", text);

            if (!text) {
              throw new Error("Réponse vide");
            }

            try {
              return JSON.parse(text);
            } catch (e) {
              console.error("Texte non JSON reçu:", text.substring(0, 500));
              throw new Error("La réponse n'est pas au format JSON");
            }
          });
        })
        .then((data) => {
          if (data.success) {
            console.log("Document ajouté avec succès");

            // Fermer la modal de manière sécurisée
            const modalId =
              "documentModal-" + formData.get("point_vigilance_id");
            const modal = document.getElementById(modalId);

            if (modal) {
              // Utiliser notre fonction closeModal pour une fermeture cohérente
              closeModal(modal);
            }

            // Rafraîchir la page avec un paramètre pour éviter le cache
            setTimeout(() => {
              window.location.href =
                window.location.href.split("?")[0] +
                `?action=audits&method=view&id=${
                  auditIdField.value
                }&nocache=${Date.now()}`;
            }, 300);
          } else {
            // Afficher un message d'erreur
            alert(
              data.message || "Une erreur est survenue lors du téléchargement"
            );
          }
        })
        .catch((error) => {
          console.error("Erreur lors du téléchargement:", error);
          alert(
            "Une erreur est survenue lors de la communication avec le serveur: " +
              error.message
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
      // Supprimer aria-hidden qui cause des problèmes d'accessibilité
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
  try {
    // 1. Fermer l'offcanvas sans dépendre de bootstrap
    const offcanvas = document.querySelector(".offcanvas.show");
    if (offcanvas) {
      // Fermeture manuelle directe
      offcanvas.classList.remove("show");
      if (offcanvas instanceof HTMLElement) {
        offcanvas.style.display = "none";
      }

      // Supprimer aria-modal et ajouter aria-hidden
      offcanvas.removeAttribute("aria-modal");
      offcanvas.setAttribute("aria-hidden", "true");

      // Réactiver le défilement
      document.body.classList.remove("offcanvas-open");
      document.body.style.removeProperty("overflow");
      document.body.style.removeProperty("padding-right");
    }

    // 2. Afficher la modal manuellement sans dépendre de bootstrap
    const modalElement = document.getElementById("imageModal-" + pointId);
    if (!modalElement) {
      console.error("Élément modal non trouvé:", "imageModal-" + pointId);
      return;
    }

    // Affichage direct de la modal
    modalElement.classList.add("show");
    if (modalElement instanceof HTMLElement) {
      modalElement.style.display = "block";
    }

    // Configurer l'accessibilité
    modalElement.setAttribute("aria-modal", "true");
    modalElement.removeAttribute("aria-hidden");

    // Ajouter un backdrop
    const backdrop = document.createElement("div");
    backdrop.className = "modal-backdrop fade show";
    document.body.appendChild(backdrop);

    // Bloquer le défilement du body
    document.body.classList.add("modal-open");

    // Ajouter gestionnaire de fermeture pour les boutons
    const closeButtons = modalElement.querySelectorAll(
      '[data-bs-dismiss="modal"]'
    );
    closeButtons.forEach((button) => {
      // Supprime les gestionnaires existants pour éviter les doublons
      const oldClone = button.cloneNode(true);
      if (button.parentNode) {
        button.parentNode.replaceChild(oldClone, button);
      }

      // Ajoute un nouveau gestionnaire de fermeture
      oldClone.addEventListener("click", function () {
        // Fermeture manuelle
        modalElement.classList.remove("show");
        if (modalElement instanceof HTMLElement) {
          modalElement.style.display = "none";
        }

        // Supprimer le backdrop
        const backdrops = document.querySelectorAll(".modal-backdrop");
        backdrops.forEach((backdrop) => backdrop.remove());

        // Réactiver le défilement
        document.body.classList.remove("modal-open");
      });
    });
  } catch (error) {
    console.error("Erreur lors de l'affichage de la modal d'image:", error);
  }
}

/**
 * Afficher la modal pour voir une photo
 * @param {string} photoUrl - L'URL de la photo à afficher
 * @param {string} photoName - Le nom de la photo
 */
function showPhotoModal(photoUrl, photoName) {
  try {
    // Mettre à jour la source de l'image
    const modalPhoto = document.getElementById("modal-photo");
    const modalLabel = document.getElementById("viewPhotoModalLabel");
    const modalElement = document.getElementById("viewPhotoModal");

    if (!modalElement) {
      console.error("Élément modal non trouvé: viewPhotoModal");
      return;
    }

    if (modalPhoto instanceof HTMLImageElement) {
      modalPhoto.src = photoUrl;
    }

    if (modalLabel) {
      modalLabel.textContent = photoName;
    }

    // Afficher la modal directement
    modalElement.classList.add("show");
    if (modalElement instanceof HTMLElement) {
      modalElement.style.display = "block";
    }

    // Configurer l'accessibilité
    modalElement.setAttribute("aria-modal", "true");
    modalElement.removeAttribute("aria-hidden");

    // Ajouter un backdrop
    const backdrop = document.createElement("div");
    backdrop.className = "modal-backdrop fade show";
    document.body.appendChild(backdrop);

    // Bloquer le défilement du body
    document.body.classList.add("modal-open");

    // Ajouter gestionnaire de fermeture pour les boutons
    const closeButtons = modalElement.querySelectorAll(
      '[data-bs-dismiss="modal"]'
    );
    closeButtons.forEach((button) => {
      // Supprime les gestionnaires existants pour éviter les doublons
      const oldClone = button.cloneNode(true);
      if (button.parentNode) {
        button.parentNode.replaceChild(oldClone, button);
      }

      // Ajoute un nouveau gestionnaire de fermeture
      oldClone.addEventListener("click", function () {
        // Fermeture manuelle
        modalElement.classList.remove("show");
        if (modalElement instanceof HTMLElement) {
          modalElement.style.display = "none";
        }

        // Supprimer le backdrop
        const backdrops = document.querySelectorAll(".modal-backdrop");
        backdrops.forEach((backdrop) => backdrop.remove());

        // Réactiver le défilement
        document.body.classList.remove("modal-open");
      });
    });
  } catch (error) {
    console.error("Erreur lors de l'affichage de la modal photo:", error);
  }
}

/**
 * Supprimer un document ou une photo
 * @param {number} documentId - L'ID du document à supprimer
 */
function supprimerDocument(documentId) {
  if (confirm("Êtes-vous sûr de vouloir supprimer ce document/cette photo ?")) {
    try {
      console.log("Tentative de suppression du document ID:", documentId);

      // Préparer les données à envoyer
      const formData = new FormData();
      formData.append("id", String(documentId));

      // Afficher un indicateur de chargement
      const loadingElement = document.createElement("div");
      loadingElement.className =
        "loading-indicator position-fixed top-50 start-50 translate-middle bg-light p-3 rounded shadow";
      loadingElement.innerHTML =
        '<div class="spinner-border text-primary" role="status"></div><div class="mt-2">Suppression en cours...</div>';
      document.body.appendChild(loadingElement);

      // Effectuer la demande de suppression
      fetch("index.php?action=audits&method=supprimerDocument", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => {
          console.log("Réponse de suppression - Status:", response.status);
          console.log("Réponse de suppression - Headers:", [
            ...response.headers.entries(),
          ]);

          if (!response.ok) {
            throw new Error(
              `Erreur réseau: ${response.status} ${response.statusText}`
            );
          }

          // Vérifier le type de contenu retourné
          const contentType = response.headers.get("content-type");
          console.log("Type de contenu de la réponse:", contentType);

          if (!contentType || !contentType.includes("application/json")) {
            // Le serveur a renvoyé un format non-JSON
            return response.text().then((text) => {
              console.error(
                "Format de réponse invalide (non JSON):",
                text.substring(0, 500)
              );
              throw new Error("Format de réponse invalide");
            });
          }

          return response.text().then((text) => {
            console.log("Texte de la réponse:", text);

            if (!text) {
              throw new Error("Réponse vide du serveur");
            }

            try {
              return JSON.parse(text);
            } catch (e) {
              console.error(
                "Échec du parsing JSON:",
                e,
                "Texte reçu:",
                text.substring(0, 500)
              );
              throw new Error("La réponse n'est pas au format JSON valide");
            }
          });
        })
        .then((data) => {
          if (data.success) {
            // Suppression réussie - recharger la page avec un timestamp pour éviter le cache
            console.log("Suppression réussie, rechargement de la page");

            // Récupérer l'ID de l'audit
            const auditIdElement = document.querySelector(
              'input[name="audit_id"]'
            );
            const auditId = auditIdElement ? auditIdElement.value : null;

            if (!auditId) {
              console.error("Impossible de récupérer l'ID de l'audit");
              throw new Error("ID d'audit non trouvé");
            }

            setTimeout(() => {
              window.location.href = `index.php?action=audits&method=view&id=${auditId}&nocache=${Date.now()}`;
            }, 500);
          } else {
            // Afficher le message d'erreur du serveur
            console.error("Le serveur a renvoyé une erreur:", data.message);
            alert(
              data.message || "Une erreur est survenue lors de la suppression"
            );
          }
        })
        .catch((error) => {
          console.error("Erreur complète lors de la suppression:", error);
          alert(
            "Une erreur est survenue lors de la suppression: " + error.message
          );
        })
        .finally(() => {
          // Supprimer l'indicateur de chargement
          if (loadingElement && loadingElement.parentNode) {
            loadingElement.parentNode.removeChild(loadingElement);
          }
        });
    } catch (error) {
      console.error("Erreur critique lors de la suppression:", error);
      alert("Une erreur critique est survenue: " + error.message);
    }
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
    const cameraContainer = document.getElementById(
      "camera-container-" + pointId
    );
    const capturedContainer = document.getElementById(
      "captured-photo-container-" + pointId
    );
    const captureBtn = document.getElementById("capture-btn-" + pointId);
    const saveBtn = document.getElementById("save-btn-" + pointId);
    const retakeBtn = document.getElementById("retake-btn-" + pointId);

    // Vérifier que les éléments existent avant de modifier leurs propriétés
    if (cameraContainer instanceof HTMLElement) {
      cameraContainer.style.display = "block";
    }

    if (capturedContainer instanceof HTMLElement) {
      capturedContainer.style.display = "none";
    }

    if (captureBtn instanceof HTMLElement) {
      captureBtn.style.display = "inline-block";
    }

    if (saveBtn instanceof HTMLElement) {
      saveBtn.style.display = "none";
    }

    if (retakeBtn instanceof HTMLElement) {
      retakeBtn.style.display = "none";
    }
  });

  // Gérer l'affichage complet de la modal
  modal.addEventListener("shown.bs.modal", function () {
    const video = document.getElementById("video-" + pointId);
    if (!video || !(video instanceof HTMLVideoElement)) {
      console.error("Élément vidéo non trouvé ou de type incorrect");
      return;
    }

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
  try {
    const video = document.getElementById("video-" + pointId);
    const canvas = document.getElementById("canvas-" + pointId);

    if (!video || !canvas) {
      console.error("Erreur: Video ou canvas non trouvé");
      alert("Erreur: Impossible de capturer la photo - éléments manquants");
      return;
    }

    // S'assurer que nous avons des éléments du bon type
    const videoElement = video instanceof HTMLVideoElement ? video : null;
    const canvasElement = canvas instanceof HTMLCanvasElement ? canvas : null;

    if (!videoElement || !canvasElement) {
      console.error("Erreur: Les éléments ne sont pas du bon type");
      alert(
        "Erreur: Impossible de capturer la photo - type d'éléments incorrect"
      );
      return;
    }

    // Configurer le canvas à la taille de la vidéo
    if (videoElement.videoWidth && videoElement.videoHeight) {
      canvasElement.width = videoElement.videoWidth;
      canvasElement.height = videoElement.videoHeight;
    } else {
      // Fallback si les dimensions de la vidéo ne sont pas disponibles
      canvasElement.width = videoElement.offsetWidth || 640;
      canvasElement.height = videoElement.offsetHeight || 480;
    }

    // Dessiner l'image vidéo sur le canvas
    const context = canvasElement.getContext("2d");
    if (!context) {
      console.error("Erreur: Impossible d'obtenir le contexte 2D du canvas");
      alert(
        "Erreur: Impossible de capturer la photo - problème avec le canvas"
      );
      return;
    }

    context.drawImage(
      videoElement,
      0,
      0,
      canvasElement.width,
      canvasElement.height
    );

    // Afficher le canvas et les boutons d'action
    const cameraContainer = document.getElementById(
      "camera-container-" + pointId
    );
    const capturedContainer = document.getElementById(
      "captured-photo-container-" + pointId
    );
    const captureBtn = document.getElementById("capture-btn-" + pointId);
    const saveBtn = document.getElementById("save-btn-" + pointId);
    const retakeBtn = document.getElementById("retake-btn-" + pointId);

    if (cameraContainer instanceof HTMLElement)
      cameraContainer.style.display = "none";
    if (capturedContainer instanceof HTMLElement)
      capturedContainer.style.display = "block";
    if (captureBtn instanceof HTMLElement) captureBtn.style.display = "none";
    if (saveBtn instanceof HTMLElement) saveBtn.style.display = "inline-block";
    if (retakeBtn instanceof HTMLElement)
      retakeBtn.style.display = "inline-block";
  } catch (error) {
    console.error("Erreur lors de la capture de la photo:", error);
    alert("Une erreur est survenue lors de la capture de la photo");
  }
}

/**
 * Reprendre une photo
 * @param {number} pointId - L'ID du point de vigilance
 */
function retakePhoto(pointId) {
  try {
    // Afficher à nouveau la vidéo et masquer le canvas
    const cameraContainer = document.getElementById(
      "camera-container-" + pointId
    );
    const capturedContainer = document.getElementById(
      "captured-photo-container-" + pointId
    );
    const captureBtn = document.getElementById("capture-btn-" + pointId);
    const saveBtn = document.getElementById("save-btn-" + pointId);
    const retakeBtn = document.getElementById("retake-btn-" + pointId);

    if (cameraContainer instanceof HTMLElement)
      cameraContainer.style.display = "block";
    if (capturedContainer instanceof HTMLElement)
      capturedContainer.style.display = "none";
    if (captureBtn instanceof HTMLElement)
      captureBtn.style.display = "inline-block";
    if (saveBtn instanceof HTMLElement) saveBtn.style.display = "none";
    if (retakeBtn instanceof HTMLElement) retakeBtn.style.display = "none";
  } catch (error) {
    console.error("Erreur lors de la reprise de photo:", error);
    alert("Une erreur est survenue lors de la reprise de la photo");
  }
}

/**
 * Enregistrer une photo
 * @param {number} auditId - L'ID de l'audit
 * @param {number} pointId - L'ID du point de vigilance
 */
function savePhoto(auditId, pointId) {
  const canvas = document.getElementById("canvas-" + pointId);
  if (!canvas) {
    alert("Erreur: Canvas non trouvé");
    return;
  }

  // S'assurer que nous avons un élément canvas
  if (!(canvas instanceof HTMLCanvasElement)) {
    alert("Erreur: L'élément trouvé n'est pas un canvas");
    return;
  }

  try {
    const imageData = canvas.toDataURL("image/jpeg");

    // Vérifier que l'image a bien le format attendu
    if (!imageData.startsWith("data:image/jpeg;base64,")) {
      alert("Erreur: format d'image incorrect");
      return;
    }

    // Préparer les données à envoyer
    const formData = new FormData();
    formData.append("audit_id", String(auditId));
    formData.append("point_vigilance_id", String(pointId));
    formData.append("image_base64", imageData);

    // Désactiver les boutons pendant l'envoi
    const saveBtn = document.getElementById("save-btn-" + pointId);
    const retakeBtn = document.getElementById("retake-btn-" + pointId);

    // Vérifier que les boutons sont bien des éléments HTML
    if (saveBtn instanceof HTMLButtonElement) {
      saveBtn.disabled = true;
      saveBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi en cours...';
    }

    if (retakeBtn instanceof HTMLButtonElement) {
      retakeBtn.disabled = true;
    }

    // Arrêter le flux vidéo avant d'envoyer la photo
    if (videoStreams[pointId]) {
      videoStreams[pointId].getTracks().forEach((track) => track.stop());
      videoStreams[pointId] = null;
    }

    // Envoyer l'image au serveur
    fetch("index.php?action=audits&method=prendrePhoto", {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => {
        console.log("Status:", response.status);
        console.log("Headers:", [...response.headers.entries()]);

        if (!response.ok) {
          throw new Error("Erreur réseau: " + response.status);
        }

        // Vérifier le type de contenu retourné
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("text/html")) {
          // Le serveur a renvoyé du HTML au lieu de JSON
          return response.text().then((html) => {
            console.error(
              "Le serveur a renvoyé du HTML au lieu de JSON:",
              html.substring(0, 500)
            );
            throw new Error(
              "Réponse invalide du serveur (HTML au lieu de JSON)"
            );
          });
        }

        return response.text().then((text) => {
          if (!text) {
            throw new Error("Réponse vide");
          }

          try {
            return JSON.parse(text);
          } catch (e) {
            console.error("Texte non JSON reçu:", text.substring(0, 500));
            throw new Error("La réponse n'est pas au format JSON");
          }
        });
      })
      .then((data) => {
        if (data.success) {
          // Méthode simplifiée pour fermer la modal sans causer de boucles infinies
          const modal = document.getElementById("photoModal-" + pointId);
          if (modal) {
            try {
              // Tenter de nettoyer le focusTrap avant de fermer manuellement la modal
              if (window.bootstrap && window.bootstrap.Modal) {
                // Tenter d'utiliser l'API bootstrap si disponible
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                  bsModal.hide();
                }
              } else {
                // Méthode manuelle si bootstrap n'est pas disponible
                modal.style.display = "none";
                modal.classList.remove("show");

                // Supprimer les backdrops
                document
                  .querySelectorAll(".modal-backdrop")
                  .forEach((el) => el.remove());

                // Réinitialiser le body
                document.body.classList.remove("modal-open");
                document.body.style.paddingRight = "";
              }
            } catch (modalError) {
              console.log(
                "Erreur lors de la fermeture de la modal:",
                modalError
              );
            }
          }

          // Rafraîchir la page avec un timestamp pour éviter le cache
          setTimeout(() => {
            window.location.href =
              window.location.href.split("?")[0] +
              `?action=audits&method=view&id=${auditId}&nocache=${Date.now()}`;
          }, 500);
        } else {
          alert(
            data.message ||
              "Une erreur est survenue lors de l'enregistrement de la photo"
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
        // Réactiver les boutons
        if (saveBtn instanceof HTMLButtonElement) {
          saveBtn.disabled = false;
          saveBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
        }

        if (retakeBtn instanceof HTMLButtonElement) {
          retakeBtn.disabled = false;
        }
      });
  } catch (error) {
    console.error("Erreur lors de la capture de la photo:", error);
    alert("Une erreur est survenue lors de la capture de la photo");
  }
}

/**
 * Affiche une modal de manière universelle (fonctionne avec ou sans Bootstrap)
 * @param {HTMLElement} modalElement - L'élément modal à afficher
 */
function openModal(modalElement) {
  if (!modalElement) return;

  // Supprimer l'attribut inert s'il existe
  if (modalElement.hasAttribute("inert")) {
    modalElement.removeAttribute("inert");
  }

  try {
    // Vérification plus rigoureuse de l'existence de bootstrap et de ses méthodes
    if (
      typeof bootstrap !== "undefined" &&
      bootstrap !== null &&
      typeof bootstrap.Modal !== "undefined" &&
      bootstrap.Modal !== null
    ) {
      // Essayer de récupérer l'instance en toute sécurité
      let bsModal = null;
      try {
        if (typeof bootstrap.Modal.getInstance === "function") {
          bsModal = bootstrap.Modal.getInstance(modalElement);
        }
      } catch (instanceError) {
        console.log(
          "Information: Erreur lors de la récupération de l'instance de modal"
        );
      }

      // Si aucune instance n'existe, en créer une nouvelle
      if (!bsModal) {
        try {
          bsModal = new bootstrap.Modal(modalElement);
        } catch (createError) {
          console.log(
            "Information: Impossible de créer une nouvelle instance de modal"
          );
          // Si échec de création, on passera à la méthode manuelle
          throw createError;
        }
      }

      // Afficher la modal si l'instance existe
      if (bsModal) {
        bsModal.show();
        return; // Si tout s'est bien passé, on retourne
      }
    }

    // Si nous arrivons ici, c'est que la méthode Bootstrap n'a pas fonctionné
    // Méthode manuelle sans Bootstrap
    modalElement.style.display = "block";
    modalElement.classList.add("show");
    modalElement.setAttribute("aria-modal", "true");
    modalElement.removeAttribute("aria-hidden");

    // Ajouter un backdrop manuel si nécessaire
    const backdrop = document.createElement("div");
    backdrop.className = "modal-backdrop fade show";
    document.body.appendChild(backdrop);

    // Ajouter la classe au body pour empêcher le défilement
    document.body.classList.add("modal-open");

    // Gérer la fermeture de la modal
    const closeButtons = modalElement.querySelectorAll(
      '[data-bs-dismiss="modal"]'
    );
    closeButtons.forEach((button) => {
      button.addEventListener("click", function () {
        closeModal(modalElement);
      });
    });
  } catch (error) {
    console.log("Ouverture manuelle de la modal suite à une erreur:", error);
    // Fallback en cas d'erreur
    modalElement.style.display = "block";
  }
}

/**
 * Ferme une modale de manière cohérente en utilisant Bootstrap si disponible ou manuellement
 * @param {HTMLElement} modal Élément modal à fermer
 */
function closeModal(modal) {
  if (!modal) return;

  // Essayer d'utiliser Bootstrap si disponible
  if (window.bootstrap && window.bootstrap.Modal) {
    try {
      const bsModal = window.bootstrap.Modal.getInstance(modal);
      if (bsModal) {
        bsModal.hide();
      }
    } catch (error) {
      // Fallback en cas d'erreur avec l'API Bootstrap
      closeModalManually(modal);
    }
  } else {
    // Fermeture manuelle si Bootstrap n'est pas disponible
    closeModalManually(modal);
  }
}

/**
 * Implémentation manuelle de fermeture de modale
 * @param {HTMLElement} modal Élément modal à fermer
 */
function closeModalManually(modal) {
  // Cacher la modale
  modal.style.display = "none";
  modal.classList.remove("show");

  // Gérer l'accessibilité sans utiliser aria-hidden qui cause des problèmes
  modal.removeAttribute("aria-modal");
  modal.setAttribute("inert", "");

  // Supprimer les backdrops
  document.querySelectorAll(".modal-backdrop").forEach((el) => el.remove());

  // Réinitialiser le body
  document.body.classList.remove("modal-open");
  document.body.style.paddingRight = "";
}
