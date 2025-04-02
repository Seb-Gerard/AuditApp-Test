/**
 * AuditManager - Module de gestion des audits et des points de vigilance
 *
 * Ce module centralise toutes les fonctionnalités liées à la création et gestion des audits:
 * - Sélection des catégories et sous-catégories
 * - Gestion des points de vigilance
 * - Génération des champs cachés pour le formulaire
 * - Validation du formulaire
 */

// Fonction pour confirmer la suppression d'un audit
function confirmDelete(id) {
  if (
    confirm(
      "Êtes-vous sûr de vouloir supprimer cet audit ? Cette action est irréversible."
    )
  ) {
    window.location.href = "index.php?action=audits&method=delete&id=" + id;
  }
}

// S'assurer que le module est défini globalement
(function (global) {
  "use strict";

  // Vérifier si le module existe déjà
  if (global.AuditManager) {
    return global.AuditManager;
  }

  // Définition du module
  const AuditManager = {
    // Données stockées
    selectedPoints: [], // Points de vigilance sélectionnés
    selectedCategories: new Set(), // Catégories sélectionnées
    selectedSousCategories: new Set(), // Sous-catégories sélectionnées
    allPoints: [], // Tous les points disponibles
    isInitialized: false, // Flag pour vérifier si l'initialisation a déjà été faite

    /**
     * Initialiser manuellement le module (peut être appelé depuis la page HTML)
     * Cette méthode est placée en premier pour assurer qu'elle est définie avant tout usage
     */
    manualInit: function () {
      try {
        // Vérifier si le module est déjà initialisé
        if (this.isInitialized) {
          return;
        }

        // Initialiser directement si le document est prêt
        if (document.readyState !== "loading") {
          this.init();
          return;
        }

        // Sinon attendre que le document soit chargé
        document.addEventListener("DOMContentLoaded", () => {
          this.init();
        });
      } catch (error) {
        // Silencieux en cas d'erreur
      }
    },

    /**
     * Initialisation du module
     */
    init: function () {
      // Éviter les initialisations multiples
      if (this.isInitialized) {
        return;
      }

      // Vérifier la présence du formulaire
      let auditForm = document.getElementById("audit-form");
      if (!auditForm) {
        auditForm = document.querySelector(
          'form[action="index.php?action=audits&method=create"]'
        );
        if (auditForm) {
          auditForm.id = "audit-form";
        } else {
          return; // Sortir si le formulaire n'est pas trouvé
        }
      }

      // Réinitialiser les données
      this.selectedCategories.clear();
      this.selectedSousCategories.clear();
      this.selectedPoints = [];

      // Initialiser les composants
      this.initDropdownChecklists();
      this.attachCategorieEvents();
      this.initFormValidation();
      this.updateSelectedPointsList();

      // Marquer comme initialisé
      this.isInitialized = true;
    },

    /**
     * Initialisation des listes déroulantes
     */
    initDropdownChecklists: function () {
      const dropdownButtons = document.querySelectorAll(
        ".dropdown-check-button"
      );
      if (dropdownButtons.length === 0) return;

      // Fonction pour gérer les clics en dehors des dropdowns
      const documentClickHandler = (event) => {
        if (!event || !event.target) return;
        try {
          const target = event.target;
          if (
            target instanceof HTMLElement &&
            !target.closest(".dropdown-check-list")
          ) {
            document
              .querySelectorAll(".dropdown-check-items")
              .forEach((list) => {
                if (list && list.classList) list.classList.remove("show");
              });
          }
        } catch (error) {}
      };

      // Supprimer les anciens gestionnaires d'événements
      document.removeEventListener("click", documentClickHandler, true);

      // Nettoyer les anciens gestionnaires sur les boutons
      dropdownButtons.forEach((button) => {
        if (button && button instanceof HTMLElement && button.parentNode) {
          const newButton = button.cloneNode(true);
          button.parentNode.replaceChild(newButton, button);
        }
      });

      // Réattacher les gestionnaires d'événements
      document.querySelectorAll(".dropdown-check-button").forEach((button) => {
        if (button && button instanceof HTMLElement) {
          const nextElement = button.nextElementSibling;
          if (!nextElement) return;

          button.addEventListener("click", function (event) {
            if (!event || !event.target) return;

            const itemsList = this.nextElementSibling;
            if (!itemsList) return;

            try {
              itemsList.classList.toggle("show");
              document
                .querySelectorAll(".dropdown-check-items")
                .forEach((list) => {
                  if (list && list !== itemsList && list.classList) {
                    list.classList.remove("show");
                  }
                });
            } catch (error) {}

            event.stopPropagation();
            event.preventDefault();
          });

          // Propriété pour suivre l'état
          button._hasClickEvent = true;
        }
      });

      // Attacher le gestionnaire au document
      document.addEventListener("click", documentClickHandler, true);
    },

    /**
     * Attacher les événements aux checkboxes des catégories
     */
    attachCategorieEvents: function () {
      const self = this;
      document.querySelectorAll(".categorie-checkbox").forEach((checkbox) => {
        checkbox.addEventListener("change", function () {
          this.checked
            ? self.selectedCategories.add(this.value)
            : self.selectedCategories.delete(this.value);
          self.updateCategoriesText();
          self.loadSousCategories();
        });
      });
    },

    /**
     * Mettre à jour le texte du dropdown des catégories
     */
    updateCategoriesText: function () {
      const checkedCategories = document.querySelectorAll(
        ".categorie-checkbox:checked"
      );
      const textSpan = document.getElementById("categories_text");
      if (!textSpan) return;

      if (checkedCategories.length === 0) {
        textSpan.textContent = "Sélectionnez les catégories";
      } else if (checkedCategories.length === 1) {
        const label = checkedCategories[0].nextElementSibling;
        textSpan.textContent =
          label && label.textContent
            ? label.textContent.trim()
            : "1 catégorie sélectionnée";
      } else {
        textSpan.textContent = `${checkedCategories.length} catégories sélectionnées`;
      }
    },

    /**
     * Attacher les événements aux checkboxes des sous-catégories
     */
    attachSousCategorieEvents: function () {
      const self = this;
      document
        .querySelectorAll(".sous-categorie-checkbox")
        .forEach((checkbox) => {
          checkbox.addEventListener("change", function () {
            this.checked
              ? self.selectedSousCategories.add(this.value)
              : self.selectedSousCategories.delete(this.value);
            self.updateSousCategoriesText();
            self.loadPointsVigilance();
          });
        });
    },

    /**
     * Mettre à jour le texte du dropdown des sous-catégories
     */
    updateSousCategoriesText: function () {
      const checkedSousCategories = document.querySelectorAll(
        ".sous-categorie-checkbox:checked"
      );
      const textSpan = document.getElementById("sous_categories_text");
      if (!textSpan) return;

      if (checkedSousCategories.length === 0) {
        textSpan.textContent = "Sélectionnez les sous-catégories";
      } else if (
        checkedSousCategories.length === 1 &&
        checkedSousCategories[0].nextElementSibling
      ) {
        const label = checkedSousCategories[0].nextElementSibling;
        textSpan.textContent =
          label && label.textContent
            ? label.textContent.trim()
            : "1 sous-catégorie sélectionnée";
      } else {
        textSpan.textContent = `${checkedSousCategories.length} sous-catégories sélectionnées`;
      }
    },

    /**
     * Charger les sous-catégories en fonction des catégories sélectionnées
     */
    loadSousCategories: function () {
      const self = this;
      const categorieIds = Array.from(
        document.querySelectorAll(".categorie-checkbox:checked")
      ).map((cb) => cb.value);
      const sousCategContainer = document.getElementById(
        "sous_categories_container"
      );
      const textSpan = document.getElementById("sous_categories_text");

      if (!sousCategContainer || !textSpan) return;

      // Si aucune catégorie n'est sélectionnée
      if (categorieIds.length === 0) {
        sousCategContainer.innerHTML = "";
        textSpan.textContent =
          "Veuillez d'abord sélectionner au moins une catégorie";
        self.selectedSousCategories.clear();
        self.loadPointsVigilance();
        return;
      }

      // Construire l'URL avec tous les IDs de catégories sélectionnées
      const queryParams = categorieIds
        .map((id) => `categorie_id[]=${id}`)
        .join("&");

      fetch(`index.php?action=audits&method=getSousCategories&${queryParams}`)
        .then((response) => response.json())
        .then((data) => {
          if (data.length === 0) {
            sousCategContainer.innerHTML =
              '<div class="text-muted p-2">Aucune sous-catégorie disponible pour les catégories sélectionnées</div>';
            textSpan.textContent = "Aucune sous-catégorie disponible";
            self.selectedSousCategories.clear();
            self.loadPointsVigilance();
            return;
          }

          let html = "";
          data.forEach((sousCateg) => {
            html += `
              <div class="form-check">
                <input class="form-check-input sous-categorie-checkbox" type="checkbox" 
                       name="sous_categories[]" value="${sousCateg.id}" 
                       id="sous-categorie-${sousCateg.id}" 
                       data-categorie-id="${sousCateg.categorie_id}">
                <label class="form-check-label" for="sous-categorie-${sousCateg.id}">
                  ${sousCateg.nom}
                </label>
              </div>
            `;
          });

          sousCategContainer.innerHTML = html;
          textSpan.textContent = "Sélectionnez les sous-catégories";

          // Réappliquer les sélections précédentes
          document
            .querySelectorAll(".sous-categorie-checkbox")
            .forEach((checkbox) => {
              if (self.selectedSousCategories.has(checkbox.value)) {
                checkbox.checked = true;
              }
            });

          // Vérifier les sous-catégories qui ont disparu
          const availableSousCategoriesIds = new Set(
            data.map((item) => item.id.toString())
          );
          let hasChanged = false;

          self.selectedSousCategories.forEach((id) => {
            if (!availableSousCategoriesIds.has(id)) {
              self.selectedSousCategories.delete(id);
              hasChanged = true;
            }
          });

          // Si la sélection a changé, recharger les points
          if (hasChanged) self.loadPointsVigilance();

          // Attacher les événements et mettre à jour le texte
          self.attachSousCategorieEvents();
          self.updateSousCategoriesText();
        })
        .catch((error) => {
          sousCategContainer.innerHTML =
            '<div class="text-danger p-2">Erreur lors du chargement des sous-catégories</div>';
          textSpan.textContent = "Erreur de chargement";
        });
    },

    /**
     * Charger les points de vigilance en fonction des sous-catégories sélectionnées
     */
    loadPointsVigilance: function () {
      const sousCategorieIds = Array.from(this.selectedSousCategories);
      const container = document.getElementById("points_vigilance_container");

      if (!container) return;

      // Message d'attente
      container.innerHTML =
        '<p class="text-info">Chargement des points de vigilance...</p>';

      // Si aucune sous-catégorie n'est sélectionnée
      if (sousCategorieIds.length === 0) {
        container.innerHTML =
          "<p>Veuillez sélectionner des sous-catégories pour voir les points de vigilance disponibles</p>";
        this.allPoints = [];
        this.updateAvailablePoints();
        return;
      }

      // Construire l'URL
      const queryParams = sousCategorieIds
        .map((id) => `sous_categorie_id[]=${id}`)
        .join("&");

      fetch(`index.php?action=audits&method=getPointsVigilance&${queryParams}`)
        .then((response) => {
          if (!response.ok)
            throw new Error(`Erreur réseau: ${response.status}`);
          return response.json();
        })
        .then((data) => {
          this.allPoints = data;
          this.updateAvailablePoints();
        })
        .catch((error) => {
          if (container) {
            container.innerHTML = `<p class="text-danger">Erreur lors du chargement des points de vigilance: ${error.message}</p>`;
          }
          this.allPoints = [];
          this.updateAvailablePoints();
        });
    },

    /**
     * Mettre à jour la liste des points de vigilance disponibles
     */
    updateAvailablePoints: function () {
      const container = document.getElementById("points_vigilance_container");
      if (!container) return;

      // Obtenir les IDs des points déjà sélectionnés
      const selectedIds = this.selectedPoints.map((point) =>
        parseInt(point.id, 10)
      );

      // Filtrer les points disponibles
      const availablePoints = this.allPoints.filter(
        (point) => !selectedIds.includes(parseInt(point.id, 10))
      );

      // Générer le HTML
      let html = "";

      if (availablePoints.length === 0) {
        html =
          this.allPoints.length === 0
            ? "<p>Aucun point de vigilance disponible. Veuillez sélectionner au moins une sous-catégorie.</p>"
            : "<p>Tous les points de vigilance disponibles ont été sélectionnés.</p>";
      } else {
        html = `
          <table class="table table-bordered">
            <thead>
              <tr>
                <th class="col-1">Action</th>
                <th class="col-2">Catégorie</th>
                <th class="col-2">Sous-catégorie</th>
                <th class="col-7">Point de vigilance</th>
              </tr>
            </thead>
            <tbody>
        `;

        availablePoints.forEach((point) => {
          // Extraire les IDs avec valeurs par défaut si manquants
          const pointId = parseInt(point.id || 0, 10);
          if (pointId === 0) return;

          // Gérer les différentes structures possibles
          let categorieId = 0;
          if (point.categorie_id !== undefined) {
            categorieId = parseInt(point.categorie_id || 0, 10);
          } else if (point.categorie && point.categorie.id) {
            categorieId = parseInt(point.categorie.id || 0, 10);
          }

          const sousCategorieId = parseInt(point.sous_categorie_id || 0, 10);

          // Adapter pour les noms
          const categorieNom =
            point.categorie_nom ||
            (point.categorie ? point.categorie.nom : "-");
          const sousCategorieNom =
            point.sous_categorie_nom ||
            (point.sous_categorie ? point.sous_categorie.nom : "-");

          html += `
            <tr id="point-${pointId}">
              <td class="col-1 text-center align-middle">
                <button type="button" class="btn btn-sm btn-success add-point" 
                        onclick="AuditManager.addPointVigilance(${pointId}, ${
            categorieId || 0
          }, ${sousCategorieId || 0}, '${(categorieNom || "-").replace(
            /'/g,
            "\\'"
          )}', '${(sousCategorieNom || "-").replace(/'/g, "\\'")}', '${(
            point.nom || ""
          ).replace(/'/g, "\\'")}')">
                  <i class="fas fa-plus"></i>
                </button>
              </td>
              <td class="col-2 align-middle">${categorieNom || "-"}</td>
              <td class="col-2 align-middle">${sousCategorieNom || "-"}</td>
              <td class="col-7 align-middle">${point.nom || ""}</td>
            </tr>
          `;
        });

        html += `
            </tbody>
          </table>
        `;
      }

      // Mettre à jour le conteneur
      container.innerHTML = html;
    },

    /**
     * Ajouter un point de vigilance à la liste des points sélectionnés
     */
    addPointVigilance: function (
      pointId,
      categorieId,
      sousCategorieId,
      categorieNom,
      sousCategorieNom,
      pointNom
    ) {
      try {
        // Conversion et validation des IDs
        pointId = parseInt(pointId, 10);
        categorieId = parseInt(categorieId || 0, 10);
        sousCategorieId = parseInt(sousCategorieId || 0, 10);

        if (isNaN(pointId) || pointId <= 0) return;

        // Vérifier si déjà sélectionné
        if (this.selectedPoints.some((p) => parseInt(p.id, 10) === pointId)) {
          alert("Ce point de vigilance a déjà été sélectionné");
          return;
        }

        // Valeurs par défaut pour les noms
        categorieNom = categorieNom || "-";
        sousCategorieNom = sousCategorieNom || "-";
        pointNom = pointNom || "";

        // Ajouter à la liste
        this.selectedPoints.push({
          id: pointId,
          categorie_id: categorieId,
          sous_categorie_id: sousCategorieId,
          categorie_nom: categorieNom,
          sous_categorie_nom: sousCategorieNom,
          nom: pointNom,
        });

        // Créer les champs cachés
        let form = document.getElementById("audit-form");
        if (!form) {
          form = document.querySelector('form[action*="audits&method=create"]');
          if (form) form.id = "audit-form";
        }

        if (form) {
          // Conteneur pour les champs cachés
          let hiddenFieldsContainer = document.getElementById(
            "hidden_fields_container"
          );
          if (!hiddenFieldsContainer) {
            hiddenFieldsContainer = document.createElement("div");
            hiddenFieldsContainer.id = "hidden_fields_container";
            form.appendChild(hiddenFieldsContainer);
          }

          const index = this.selectedPoints.length - 1;

          // Champ pour l'ID du point
          const hiddenFieldId = document.createElement("input");
          hiddenFieldId.type = "hidden";
          hiddenFieldId.name = `points[${index}][point_vigilance_id]`;
          hiddenFieldId.value = pointId;
          hiddenFieldsContainer.appendChild(hiddenFieldId);

          // Champ pour l'ID de la catégorie
          const hiddenFieldCat = document.createElement("input");
          hiddenFieldCat.type = "hidden";
          hiddenFieldCat.name = `points[${index}][categorie_id]`;
          hiddenFieldCat.value = categorieId;
          hiddenFieldsContainer.appendChild(hiddenFieldCat);

          // Champ pour l'ID de la sous-catégorie
          const hiddenFieldSousCat = document.createElement("input");
          hiddenFieldSousCat.type = "hidden";
          hiddenFieldSousCat.name = `points[${index}][sous_categorie_id]`;
          hiddenFieldSousCat.value = sousCategorieId;
          hiddenFieldsContainer.appendChild(hiddenFieldSousCat);
        }

        // Mettre à jour l'interface
        this.updateSelectedPointsList();
        this.updateAvailablePoints();
      } catch (error) {}
    },

    /**
     * Supprimer un point de vigilance de la sélection
     */
    removePointVigilance: function (pointId) {
      pointId = parseInt(pointId, 10);
      const index = this.selectedPoints.findIndex(
        (p) => parseInt(p.id, 10) === pointId
      );
      if (index === -1) return;

      // Supprimer de la liste
      this.selectedPoints.splice(index, 1);

      // Supprimer les champs cachés
      const form = document.getElementById("audit-form");
      if (form) {
        const hiddenFields = form.querySelectorAll(
          `input[name^="points["][name$="[point_vigilance_id]"][value="${pointId}"]`
        );

        hiddenFields.forEach((field) => {
          const match = field.name.match(/points\[(\d+)\]/);
          if (match && match[1]) {
            const fieldIndex = match[1];
            const fieldsToRemove = form.querySelectorAll(
              `input[name^="points[${fieldIndex}]"]`
            );
            fieldsToRemove.forEach((f) => f.remove());
          }
        });
      }

      // Mettre à jour l'interface
      setTimeout(() => {
        this.updateSelectedPointsList();
        this.updateAvailablePoints();
      }, 50);
    },

    /**
     * Mettre à jour la liste des points de vigilance sélectionnés
     */
    updateSelectedPointsList: function () {
      try {
        // Récupérer ou créer les éléments nécessaires
        let container = document.getElementById("selected_points_list");
        let section = document.getElementById("selected_points_section");

        // Créer les éléments s'ils n'existent pas
        if (!section || !container) {
          this.createPointsListStructure();
          container = document.getElementById("selected_points_list");
          section = document.getElementById("selected_points_section");

          // Si toujours pas trouvés après tentative de création
          if (!container || !section) return;
        }

        // Vider le conteneur
        container.textContent = "";

        // Si aucun point n'est sélectionné
        if (this.selectedPoints.length === 0) {
          const tr = document.createElement("tr");
          const td = document.createElement("td");
          td.colSpan = 5; // Ajusté pour 5 colonnes
          td.className = "text-center";
          td.textContent = "Aucun point sélectionné";
          tr.appendChild(td);
          container.appendChild(tr);

          section.style.display = "none";
          return;
        }

        // Afficher la section
        section.style.display = "block";

        // Organiser les points par sous-catégorie
        const pointsBySubCategory = {};

        this.selectedPoints.forEach((point, index) => {
          const scId = point.sous_categorie_id;
          if (!pointsBySubCategory[scId]) {
            pointsBySubCategory[scId] = {
              id: scId,
              nom: point.sous_categorie_nom,
              categorie_nom: point.categorie_nom,
              points: [],
            };
          }
          pointsBySubCategory[scId].points.push({ ...point, index });
        });

        // Générer les lignes pour chaque sous-catégorie
        Object.values(pointsBySubCategory).forEach((sc, scIndex) => {
          // Créer une rangée pour le titre de la sous-catégorie
          const scHeaderRow = document.createElement("tr");
          scHeaderRow.className = "table-secondary subcategory-header";

          const scHeaderCell = document.createElement("td");
          scHeaderCell.colSpan = 5;
          scHeaderCell.innerHTML = `<strong>${sc.categorie_nom} / ${sc.nom}</strong>`;
          scHeaderRow.appendChild(scHeaderCell);
          container.appendChild(scHeaderRow);

          // Générer les rangées pour chaque point de cette sous-catégorie
          sc.points.forEach((point, pointIndex) => {
            const tr = document.createElement("tr");
            tr.draggable = true;
            tr.setAttribute("data-point-id", point.id);
            tr.setAttribute("data-index", point.index);
            tr.setAttribute("data-sous-categorie-id", sc.id);

            // Ajouter les gestionnaires d'événements pour le drag and drop
            tr.addEventListener("dragstart", this.handleDragStart.bind(this));
            tr.addEventListener("dragover", this.handleDragOver.bind(this));
            tr.addEventListener("dragenter", this.handleDragEnter.bind(this));
            tr.addEventListener("dragleave", this.handleDragLeave.bind(this));
            tr.addEventListener("drop", this.handleDrop.bind(this));
            tr.addEventListener("dragend", this.handleDragEnd.bind(this));

            // Cellule Action avec uniquement la poignée de glisser-déposer
            const tdAction = document.createElement("td");
            tdAction.className = "col-1 text-center align-middle";

            // Poignée pour le drag and drop
            const dragHandle = document.createElement("span");
            dragHandle.className = "drag-handle";
            dragHandle.innerHTML =
              '<i class="fas fa-grip-vertical text-muted" style="cursor: move;"></i>';
            tdAction.appendChild(dragHandle);
            tr.appendChild(tdAction);

            // Cellule Catégorie
            const tdCat = document.createElement("td");
            tdCat.className = "col-2 align-middle";
            tdCat.textContent = point.categorie_nom || "-";
            tr.appendChild(tdCat);

            // Cellule Sous-catégorie
            const tdSousCat = document.createElement("td");
            tdSousCat.className = "col-2 align-middle";
            tdSousCat.textContent = point.sous_categorie_nom || "-";
            tr.appendChild(tdSousCat);

            // Cellule Point de vigilance
            const tdNom = document.createElement("td");
            tdNom.className = "col-6 align-middle";
            tdNom.textContent = point.nom || "";
            tr.appendChild(tdNom);

            // Nouvelle cellule pour le bouton de suppression
            const tdDelete = document.createElement("td");
            tdDelete.className = "col-1 text-center align-middle";

            // Bouton de suppression
            const button = document.createElement("button");
            button.type = "button";
            button.className = "btn btn-sm btn-danger remove-point";
            button.title = "Supprimer ce point";
            button.onclick = () => this.removePointVigilance(point.id);

            const icon = document.createElement("i");
            icon.className = "fas fa-trash";
            button.appendChild(icon);

            tdDelete.appendChild(button);
            tr.appendChild(tdDelete);

            // Ajouter au conteneur
            container.appendChild(tr);
          });
        });
      } catch (error) {
        console.error(
          "Erreur lors de la mise à jour des points sélectionnés:",
          error
        );
      }
    },

    /**
     * Créer la structure HTML pour la liste des points sélectionnés
     */
    createPointsListStructure: function () {
      try {
        // Chercher un conteneur parent
        let parentContainer = document.querySelector(".col-12");
        if (!parentContainer) {
          parentContainer =
            document.querySelector(".container .row") ||
            document.querySelector(".container") ||
            document.querySelector("form") ||
            document.body;
        }

        // Créer la section
        let section = document.createElement("div");
        section.id = "selected_points_section";
        section.style.display = "none";

        // Ajouter le titre
        const title = document.createElement("h4");
        title.textContent = "Points de vigilance sélectionnés";
        section.appendChild(title);

        // Ajouter un texte d'aide pour expliquer le réordonnement
        const helpText = document.createElement("p");
        helpText.className = "text-muted small";
        helpText.innerHTML =
          '<i class="fas fa-info-circle"></i> Vous pouvez réordonner les points en faisant glisser les lignes à l\'aide de l\'icône <i class="fas fa-grip-vertical"></i>.';
        section.appendChild(helpText);

        // Créer le conteneur des points
        const pointsContainer = document.createElement("div");
        pointsContainer.id = "selected_points_container";

        // Créer la table
        const table = document.createElement("table");
        table.className = "table table-bordered";

        // Créer l'en-tête
        const thead = document.createElement("thead");
        thead.innerHTML = `
          <tr>
            <th class="col-1">Action</th>
            <th class="col-2">Catégorie</th>
            <th class="col-2">Sous-catégorie</th>
            <th class="col-6">Point de vigilance</th>
            <th class="col-1">Supprimer</th>
          </tr>
        `;
        table.appendChild(thead);

        // Créer le corps
        const tbody = document.createElement("tbody");
        tbody.id = "selected_points_list";
        tbody.innerHTML =
          "<tr><td colspan='5' class='text-center'>Aucun point sélectionné</td></tr>";
        table.appendChild(tbody);

        // Assembler
        pointsContainer.appendChild(table);
        section.appendChild(pointsContainer);
        parentContainer.appendChild(section);
      } catch (error) {}
    },

    /**
     * Initialiser la validation du formulaire
     */
    initFormValidation: function () {
      const form = document.getElementById("audit-form");
      if (!form) return;

      form.addEventListener("submit", (event) => {
        // Vérifier si des points sont sélectionnés
        if (this.selectedPoints.length === 0) {
          // Demander confirmation
          if (
            !confirm(
              "ATTENTION: Aucun point de vigilance sélectionné! Voulez-vous continuer sans points?"
            )
          ) {
            event.preventDefault();
            return;
          }

          // Ajouter un champ caché pour indiquer la confirmation
          const noPointsField = document.createElement("input");
          noPointsField.type = "hidden";
          noPointsField.name = "no_points_confirmed";
          noPointsField.value = "1";
          form.appendChild(noPointsField);
        }
      });
    },

    /**
     * Gestionnaire d'événement pour le début du glisser-déposer
     */
    handleDragStart: function (e) {
      e.dataTransfer.effectAllowed = "move";
      e.dataTransfer.setData("text/plain", e.target.getAttribute("data-index"));
      e.dataTransfer.setData(
        "sous-categorie-id",
        e.target.getAttribute("data-sous-categorie-id")
      );
      e.target.classList.add("dragging");
    },

    /**
     * Gestionnaire d'événement pour le survol pendant le glisser-déposer
     */
    handleDragOver: function (e) {
      if (e.preventDefault) {
        e.preventDefault();
      }
      e.dataTransfer.dropEffect = "move";
      return false;
    },

    /**
     * Gestionnaire d'événement pour l'entrée dans une zone pendant le glisser-déposer
     */
    handleDragEnter: function (e) {
      const row = e.target.closest("tr");
      if (row && !row.classList.contains("subcategory-header")) {
        row.classList.add("drag-over");
      }
    },

    /**
     * Gestionnaire d'événement pour la sortie d'une zone pendant le glisser-déposer
     */
    handleDragLeave: function (e) {
      const row = e.target.closest("tr");
      if (row) {
        row.classList.remove("drag-over");
      }
    },

    /**
     * Gestionnaire d'événement pour le dépôt
     */
    handleDrop: function (e) {
      e.stopPropagation();
      e.preventDefault();

      // Récupérer l'élément sur lequel on dépose
      const dropTargetRow = e.target.closest("tr");
      if (
        !dropTargetRow ||
        dropTargetRow.classList.contains("subcategory-header")
      )
        return false;

      // Récupérer les indices
      const draggedIndex = parseInt(e.dataTransfer.getData("text/plain"), 10);
      const targetIndex = parseInt(
        dropTargetRow.getAttribute("data-index"),
        10
      );
      const targetSousCategorieId = dropTargetRow.getAttribute(
        "data-sous-categorie-id"
      );
      const draggedSousCategorieId =
        e.dataTransfer.getData("sous-categorie-id");

      if (
        isNaN(draggedIndex) ||
        isNaN(targetIndex) ||
        draggedIndex === targetIndex
      ) {
        return false;
      }

      // Vérifier si les éléments sont dans la même sous-catégorie
      if (draggedSousCategorieId !== targetSousCategorieId) {
        alert(
          "Vous ne pouvez déplacer un point que dans la même sous-catégorie."
        );
        return false;
      }

      // Réorganiser le tableau des points sélectionnés
      const draggedPoint = this.selectedPoints[draggedIndex];
      const newPoints = [...this.selectedPoints];

      // Supprimer l'élément dragué
      newPoints.splice(draggedIndex, 1);

      // Recalculer l'index cible après suppression
      const newTargetIndex =
        targetIndex > draggedIndex ? targetIndex - 1 : targetIndex;

      // Insérer l'élément à sa nouvelle position
      newPoints.splice(newTargetIndex, 0, draggedPoint);

      // Mettre à jour la liste complète
      this.selectedPoints = newPoints;

      // Mettre à jour l'affichage et les champs cachés
      this.updateHiddenFields();
      this.updateSelectedPointsList();

      return false;
    },

    /**
     * Gestionnaire d'événement pour la fin du glisser-déposer
     */
    handleDragEnd: function (e) {
      // Supprimer les classes de style
      const rows = document.querySelectorAll("#selected_points_list tr");
      rows.forEach((row) => {
        row.classList.remove("dragging");
        row.classList.remove("drag-over");
      });
    },

    /**
     * Mettre à jour les champs cachés du formulaire après réordonnancement
     */
    updateHiddenFields: function () {
      const hiddenFieldsContainer = document.getElementById(
        "hidden_fields_container"
      );
      if (!hiddenFieldsContainer) return;

      // Supprimer tous les champs existants
      hiddenFieldsContainer.innerHTML = "";

      // Recréer les champs dans le nouvel ordre
      this.selectedPoints.forEach((point, index) => {
        // Champ pour l'ID du point
        const hiddenFieldId = document.createElement("input");
        hiddenFieldId.type = "hidden";
        hiddenFieldId.name = `points[${index}][point_vigilance_id]`;
        hiddenFieldId.value = point.id;
        hiddenFieldsContainer.appendChild(hiddenFieldId);

        // Champ pour l'ID de la catégorie
        const hiddenFieldCat = document.createElement("input");
        hiddenFieldCat.type = "hidden";
        hiddenFieldCat.name = `points[${index}][categorie_id]`;
        hiddenFieldCat.value = point.categorie_id || 0;
        hiddenFieldsContainer.appendChild(hiddenFieldCat);

        // Champ pour l'ID de la sous-catégorie
        const hiddenFieldSousCat = document.createElement("input");
        hiddenFieldSousCat.type = "hidden";
        hiddenFieldSousCat.name = `points[${index}][sous_categorie_id]`;
        hiddenFieldSousCat.value = point.sous_categorie_id || 0;
        hiddenFieldsContainer.appendChild(hiddenFieldSousCat);
      });
    },
  };

  // Exposer le module globalement
  global.AuditManager = AuditManager;

  // Initialisation automatique simplifiée
  // Une seule méthode d'initialisation automatique lors du chargement complet du document
  document.addEventListener("DOMContentLoaded", function () {
    // Un court délai pour éviter les problèmes de timing
    setTimeout(function () {
      if (global.AuditManager && !global.AuditManager.isInitialized) {
        try {
          global.AuditManager.init();
        } catch (error) {
          // Silencieux en cas d'erreur
        }
      }
    }, 150);
  });

  return AuditManager;
})(typeof window !== "undefined" ? window : this);
