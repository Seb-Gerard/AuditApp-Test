// Stockage des points sélectionnés avec leur ordre
const selectedPoints = new Map();
let pointsOrder = []; // Tableau pour maintenir l'ordre des points
let selectedCategories = new Set(); // Pour stocker les ID des catégories sélectionnées
let selectedSousCategories = new Set(); // Pour stocker les ID des sous-catégories sélectionnées

// Fonction pour initialiser les menus déroulants
function initDropdownChecklists() {
  const dropdownButtons = document.querySelectorAll(".dropdown-check-button");

  dropdownButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Trouver la liste d'items associée à ce bouton
      const itemsList = this.nextElementSibling;

      // Toggle la classe d'affichage
      itemsList.classList.toggle("show");

      // Fermer les autres listes
      document.querySelectorAll(".dropdown-check-items").forEach((list) => {
        if (list !== itemsList && list.classList.contains("show")) {
          list.classList.remove("show");
        }
      });
    });
  });

  // Fermer tous les dropdowns quand on clique ailleurs sur la page
  document.addEventListener(
    "click",
    function (event) {
      if (!event.target.closest(".dropdown-check-list")) {
        document.querySelectorAll(".dropdown-check-items").forEach((list) => {
          list.classList.remove("show");
        });
      }
    },
    true
  );
}

// Fonction pour mettre à jour le texte du dropdown des catégories
function updateCategoriesText() {
  const checkedCategories = document.querySelectorAll(
    ".categorie-checkbox:checked"
  );
  const textSpan = document.getElementById("categories_text");

  if (checkedCategories.length === 0) {
    textSpan.textContent = "Sélectionnez les catégories";
  } else if (checkedCategories.length === 1) {
    textSpan.textContent =
      checkedCategories[0].nextElementSibling.textContent.trim();
  } else {
    textSpan.textContent = `${checkedCategories.length} catégories sélectionnées`;
  }
}

// Fonction pour mettre à jour le texte du dropdown des sous-catégories
function updateSousCategoriesText() {
  const checkedSousCategories = document.querySelectorAll(
    ".sous-categorie-checkbox:checked"
  );
  const textSpan = document.getElementById("sous_categories_text");

  if (checkedSousCategories.length === 0) {
    textSpan.textContent = "Sélectionnez les sous-catégories";
  } else if (checkedSousCategories.length === 1) {
    textSpan.textContent =
      checkedSousCategories[0].nextElementSibling.textContent.trim();
  } else {
    textSpan.textContent = `${checkedSousCategories.length} sous-catégories sélectionnées`;
  }
}

// Fonction pour récupérer les sous-catégories en fonction des catégories sélectionnées
function loadSousCategories() {
  const categorieCheckboxes = document.querySelectorAll(
    ".categorie-checkbox:checked"
  );
  const categorieIds = Array.from(categorieCheckboxes).map((cb) => cb.value);
  const sousCategContainer = document.getElementById(
    "sous_categories_container"
  );
  const textSpan = document.getElementById("sous_categories_text");

  // Si aucune catégorie n'est sélectionnée, réinitialiser le conteneur des sous-catégories
  if (categorieIds.length === 0) {
    sousCategContainer.innerHTML = "";
    textSpan.textContent =
      "Veuillez d'abord sélectionner au moins une catégorie";
    selectedSousCategories.clear();
    loadPointsVigilance();
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
        selectedSousCategories.clear();
        loadPointsVigilance();
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

      // Réappliquer les sélections précédentes si elles existent encore
      document
        .querySelectorAll(".sous-categorie-checkbox")
        .forEach((checkbox) => {
          if (selectedSousCategories.has(checkbox.value)) {
            checkbox.checked = true;
          }
        });

      // Vérifier si des sous-catégories sélectionnées ont disparu
      // et les supprimer de l'ensemble des sélections
      let hasChanged = false;
      const availableSousCategoriesIds = new Set(
        data.map((item) => item.id.toString())
      );

      selectedSousCategories.forEach((id) => {
        if (!availableSousCategoriesIds.has(id)) {
          selectedSousCategories.delete(id);
          hasChanged = true;
        }
      });

      // Si la sélection a changé, recharger les points de vigilance
      if (hasChanged) {
        loadPointsVigilance();
      }

      // Attacher les événements aux nouvelles checkboxes
      attachSousCategorieEvents();

      // Mettre à jour le texte du dropdown
      updateSousCategoriesText();
    })
    .catch((error) => {
      console.error("Erreur:", error);
      sousCategContainer.innerHTML =
        '<div class="text-danger p-2">Erreur lors du chargement des sous-catégories</div>';
      textSpan.textContent = "Erreur de chargement";
    });
}

// Fonction pour attacher les événements aux checkboxes de sous-catégories
function attachSousCategorieEvents() {
  document.querySelectorAll(".sous-categorie-checkbox").forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      if (this.checked) {
        selectedSousCategories.add(this.value);
      } else {
        selectedSousCategories.delete(this.value);
      }

      updateSousCategoriesText();
      loadPointsVigilance();
    });
  });
}

// Fonction pour charger les points de vigilance en fonction des sous-catégories sélectionnées
function loadPointsVigilance() {
  const sousCategorieIds = Array.from(selectedSousCategories);

  if (sousCategorieIds.length === 0) {
    document.getElementById("points_vigilance_container").innerHTML = "";
    return;
  }

  // Construire l'URL avec tous les IDs de sous-catégories sélectionnées
  const queryParams = sousCategorieIds
    .map((id) => `sous_categorie_id[]=${id}`)
    .join("&");

  fetch(`index.php?action=audits&method=getPointsVigilance&${queryParams}`)
    .then((response) => response.json())
    .then((data) => {
      window.allPoints = data; // Stocker tous les points pour référence future
      updateAvailablePoints();
    })
    .catch((error) => {
      console.error("Erreur:", error);
      document.getElementById("points_vigilance_container").innerHTML =
        '<p class="text-danger">Erreur lors du chargement des points de vigilance</p>';
    });
}

function updateAvailablePoints() {
  const container = document.getElementById("points_vigilance_container");

  if (!window.allPoints || window.allPoints.length === 0) {
    container.innerHTML =
      '<p class="text-muted">Aucun point de vigilance disponible pour les sous-catégories sélectionnées</p>';
    return;
  }

  let html = '<table class="table table-bordered mt-4">';
  html +=
    '<thead><tr><th class="col-1">Action</th><th class="col-2">Catégorie</th><th class="col-2">Sous-catégorie</th><th class="col-7">Points de vigilance</th></tr></thead>';
  html += "<tbody>";

  window.allPoints.forEach((point) => {
    // Ne pas afficher les points déjà sélectionnés
    if (selectedPoints.has(point.id.toString())) return;

    html += `
            <tr id="point-${point.id}">
                <td class="col-1 text-center align-middle">
                    <button type="button" class="btn btn-sm btn-success add-point" 
                            data-point-id="${point.id}"
                            data-point-nom="${point.nom}"
                            data-point-description="${point.description || ""}">
                        <i class="fas fa-plus"></i>
                    </button>
                </td>
                <td class="col-2 align-middle">${
                  point.categorie_nom || "-"
                }</td>
                <td class="col-2 align-middle">${
                  point.sous_categorie_nom || "-"
                }</td>
                <td class="col-7 align-middle">${point.nom}</td>
            </tr>
        `;
  });

  html += "</tbody></table>";
  container.innerHTML = html;

  // Attacher les événements aux boutons d'ajout
  attachAddEvents();
}

function attachAddEvents() {
  document.querySelectorAll(".add-point").forEach((button) => {
    button.addEventListener("click", function () {
      const pointId = this.dataset.pointId;
      const pointNom = this.dataset.pointNom;
      const pointRow = document.getElementById(`point-${pointId}`);
      const categorieName = pointRow
        .querySelector("td:nth-child(2)")
        .textContent.trim();
      const sousCategorieNom = pointRow
        .querySelector("td:nth-child(3)")
        .textContent.trim();

      // Ajouter le point au tableau des sélectionnés
      selectedPoints.set(pointId, {
        nom: pointNom,
        description: this.dataset.pointDescription,
        categorie_nom: categorieName,
        sous_categorie_nom: sousCategorieNom,
      });

      // Ajouter l'ID à la fin de l'ordre
      pointsOrder.push(pointId);

      // Ajouter un champ caché pour le formulaire
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = `points[${pointId}][statut]`;
      input.value = "1";
      document.querySelector("form").appendChild(input);

      // Mettre à jour les deux tableaux
      updateAvailablePoints();
      updateSelectedTable();
    });
  });
}

function movePoint(pointId, direction) {
  const currentIndex = pointsOrder.indexOf(pointId);
  if (direction === "up" && currentIndex > 0) {
    // Échanger avec l'élément précédent
    [pointsOrder[currentIndex], pointsOrder[currentIndex - 1]] = [
      pointsOrder[currentIndex - 1],
      pointsOrder[currentIndex],
    ];
  } else if (direction === "down" && currentIndex < pointsOrder.length - 1) {
    // Échanger avec l'élément suivant
    [pointsOrder[currentIndex], pointsOrder[currentIndex + 1]] = [
      pointsOrder[currentIndex + 1],
      pointsOrder[currentIndex],
    ];
  }
  updateSelectedTable();
}

function updateSelectedTable() {
  const selectedSection = document.getElementById("selected_points_section");
  const selectedBody = document.getElementById("selected_points_body");

  if (selectedPoints.size === 0) {
    selectedSection.style.display = "none";
    pointsOrder = []; // Réinitialiser l'ordre
    return;
  }

  selectedSection.style.display = "block";

  let selectedHtml = "";
  pointsOrder.forEach((id, index) => {
    const point = selectedPoints.get(id);
    if (!point) return; // Ignorer si le point n'existe plus

    selectedHtml += `
            <tr>
                <td class="col-2">${point.categorie_nom || "-"}</td>
                <td class="col-2">${point.sous_categorie_nom || "-"}</td>
                <td class="col-7">${point.nom}</td>
                <td class="col-1 text-center">
                    <div class="btn-group" role="group">
                        ${
                          index > 0
                            ? `
                            <button type="button" class="btn btn-sm btn-secondary move-up" data-point-id="${id}">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                        `
                            : ""
                        }
                        <button type="button" class="btn btn-sm btn-danger remove-point" data-point-id="${id}">
                            <i class="fas fa-times"></i>
                        </button>
                        ${
                          index < pointsOrder.length - 1
                            ? `
                            <button type="button" class="btn btn-sm btn-secondary move-down" data-point-id="${id}">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                        `
                            : ""
                        }
                    </div>
                </td>
            </tr>
        `;
  });

  selectedBody.innerHTML = selectedHtml;

  // Réattacher les événements
  document.querySelectorAll(".remove-point").forEach((button) => {
    button.addEventListener("click", function () {
      const pointId = this.dataset.pointId;
      selectedPoints.delete(pointId);
      pointsOrder = pointsOrder.filter((id) => id !== pointId);
      document
        .querySelector(`input[name="points[${pointId}][statut]"]`)
        ?.remove();
      updateAvailablePoints();
      updateSelectedTable();
    });
  });

  // Attacher les événements de déplacement
  document.querySelectorAll(".move-up").forEach((button) => {
    button.addEventListener("click", function () {
      movePoint(this.dataset.pointId, "up");
    });
  });

  document.querySelectorAll(".move-down").forEach((button) => {
    button.addEventListener("click", function () {
      movePoint(this.dataset.pointId, "down");
    });
  });

  // Ajouter des champs cachés pour l'ordre
  const orderInputs = document.querySelectorAll('input[name^="points_order"]');
  orderInputs.forEach((input) => input.remove());

  pointsOrder.forEach((pointId, index) => {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = `points_order[${index}]`;
    input.value = pointId;
    document.querySelector("form").appendChild(input);
  });
}

// Fonction d'initialisation principale
function initAuditForm() {
  // Initialiser les menus déroulants
  initDropdownChecklists();

  // Attacher les événements aux checkboxes de catégories
  document.querySelectorAll(".categorie-checkbox").forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      if (this.checked) {
        selectedCategories.add(this.value);
      } else {
        selectedCategories.delete(this.value);

        // Désélectionner les sous-catégories associées à cette catégorie dans l'interface
        document
          .querySelectorAll(
            `.sous-categorie-checkbox[data-categorie-id="${this.value}"]`
          )
          .forEach((cb) => {
            if (cb.checked) {
              cb.checked = false;
              selectedSousCategories.delete(cb.value);
            }
          });
      }

      updateCategoriesText();
      loadSousCategories();
    });
  });

  // Validation du formulaire
  const forms = document.querySelectorAll(".needs-validation");
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener(
      "submit",
      function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add("was-validated");
      },
      false
    );
  });
}

// Exécuter l'initialisation quand le DOM est chargé
document.addEventListener("DOMContentLoaded", initAuditForm);
