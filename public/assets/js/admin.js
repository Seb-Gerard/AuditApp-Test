/**
 * Module d'administration pour gérer les catégories, sous-catégories, et points de vigilance.
 * Dépend de Bootstrap pour les composants UI (onglets, modals, etc.)
 */

// Fonction utilitaire pour afficher une fenêtre modale Bootstrap
function showModal(elementId) {
  const modalElement = document.getElementById(elementId);
  if (modalElement && window.bootstrap && window.bootstrap.Modal) {
    new window.bootstrap.Modal(modalElement).show();
  } else {
    console.error(
      "Bootstrap ou l'élément modal n'est pas disponible",
      elementId
    );
  }
}

// Fonctions pour la gestion des catégories
function editCategorie(id, nom, description) {
  // Récupération des éléments du DOM et vérification des types
  const idElement = document.getElementById("edit_categorie_id");
  const nomElement = document.getElementById("edit_categorie_nom");
  const descElement = document.getElementById("edit_categorie_description");

  if (idElement && nomElement && descElement) {
    idElement.value = id;
    nomElement.value = nom;
    descElement.value = description || "";

    showModal("editCategorieModal");
  }
}

function confirmDeleteCategorie(id, nom) {
  const idElement = document.getElementById("delete_categorie_id");
  const nameElement = document.getElementById("delete_categorie_name");

  if (idElement && nameElement) {
    idElement.value = id;
    nameElement.textContent = nom;

    showModal("deleteCategorieModal");
  }
}

// Fonctions pour la gestion des sous-catégories
function editSousCategorie(id, categorieId, nom, description) {
  const idElement = document.getElementById("edit_souscategorie_id");
  const categoryElement = document.getElementById(
    "edit_souscategorie_category"
  );
  const nomElement = document.getElementById("edit_souscategorie_nom");
  const descElement = document.getElementById("edit_souscategorie_description");

  if (idElement && categoryElement && nomElement && descElement) {
    idElement.value = id;
    categoryElement.value = categorieId;
    nomElement.value = nom;
    descElement.value = description || "";

    showModal("editSousCategorieModal");
  }
}

function confirmDeleteSousCategorie(id, nom) {
  const idElement = document.getElementById("delete_souscategorie_id");
  const nameElement = document.getElementById("delete_souscategorie_name");

  if (idElement && nameElement) {
    idElement.value = id;
    nameElement.textContent = nom;

    showModal("deleteSousCategorieModal");
  }
}

// Fonctions pour la gestion des points de vigilance
function editPoint(id, sous_categorie_id, nom, description, image) {
  const idElement = document.getElementById("edit_point_id");
  const nomElement = document.getElementById("edit_point_nom");
  const descElement = document.getElementById("edit_point_description");

  if (idElement && nomElement && descElement) {
    idElement.value = id;
    nomElement.value = nom;
    descElement.value = description || "";

    // Récupérer la catégorie parente de la sous-catégorie
    fetch(
      "index.php?controller=admin&method=getSousCategorieDetails&id=" +
        sous_categorie_id,
      {
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      }
    )
      .then((response) => {
        if (!response.ok) {
          throw new Error("Erreur réseau ou session expirée");
        }
        return response.json();
      })
      .then((data) => {
        if (data.categorie_id) {
          const categoryElement = document.getElementById(
            "edit_point_category"
          );
          if (categoryElement) {
            categoryElement.value = data.categorie_id;
            // Déclencher le changement pour charger les sous-catégories
            const event = new Event("change");
            categoryElement.dispatchEvent(event);

            // Attendre un peu que les sous-catégories soient chargées
            setTimeout(() => {
              const subcategoryElement = document.getElementById(
                "edit_point_subcategory"
              );
              if (subcategoryElement) {
                subcategoryElement.value = sous_categorie_id;
              }
            }, 300);
          }
        }
      });

    // Afficher l'image existante s'il y en a une
    const imagePreview = document.getElementById("edit_point_image_preview");
    if (imagePreview) {
      imagePreview.innerHTML = "";

      if (image) {
        const img = document.createElement("img");
        img.src = "public/uploads/points_vigilance/" + image;
        img.alt = "Image existante";
        img.className = "img-thumbnail";
        img.style.maxWidth = "200px";
        imagePreview.appendChild(img);
      }
    }

    // Afficher le modal
    try {
      const modalElement = document.getElementById("editPointModal");
      if (modalElement && typeof bootstrap !== "undefined" && bootstrap.Modal) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
      } else {
        showModal("editPointModal");
      }
    } catch (e) {
      console.error("Erreur lors de l'affichage du modal:", e);
      // Fallback au cas où bootstrap n'est pas disponible
      showModal("editPointModal");
    }
  }
}

function confirmDeletePoint(id, nom) {
  const idElement = document.getElementById("delete_point_id");
  const nameElement = document.getElementById("delete_point_name");

  if (idElement && nameElement) {
    idElement.value = id;
    nameElement.textContent = nom;

    showModal("deletePointModal");
  }
}

// Fonction d'initialisation principale
function initAdminPanel() {
  // Activation de l'onglet en fonction du paramètre dans l'URL
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get("tab");

  if (tab) {
    const tabMap = {
      categories: "nav-categories-tab",
      subcategories: "nav-subcategories-tab",
      points: "nav-points-tab",
    };

    const tabId = tabMap[tab];
    if (tabId) {
      const tabElement = document.getElementById(tabId);
      if (tabElement && window.bootstrap && window.bootstrap.Tab) {
        const tabInstance = new window.bootstrap.Tab(tabElement);
        tabInstance.show();
      }
    }
  }

  // Gestion des sous-catégories
  const subcategoryCategorySelect = document.getElementById(
    "subcategory_category"
  );
  if (subcategoryCategorySelect) {
    subcategoryCategorySelect.addEventListener("change", function () {
      const categoryId = this.value;
      const subcategoriesList = document.getElementById("subcategories-list");
      const subcategoriesContainer = document.getElementById(
        "subcategories-container"
      );
      const categorySelect = document.getElementById("subcategory_category");
      let selectedCategoryName = "";

      if (categorySelect) {
        const selectedIndex = categorySelect.selectedIndex;
        if (
          selectedIndex >= 0 &&
          categorySelect.options &&
          categorySelect.options[selectedIndex]
        ) {
          selectedCategoryName = categorySelect.options[selectedIndex].text;
        }
      }

      if (categoryId) {
        // Charger les sous-catégories via AJAX
        fetch(
          `index.php?controller=admin&method=getSousCategories&categorie_id=${categoryId}`,
          {
            headers: {
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        )
          .then((response) => {
            if (!response.ok) {
              throw new Error("Erreur réseau ou session expirée");
            }
            return response.json();
          })
          .then((data) => {
            if (subcategoriesContainer) {
              subcategoriesContainer.innerHTML = ""; // Vider la liste
            }

            // Vérifier si data est un objet avec une propriété error
            if (data.error) {
              if (subcategoriesContainer) {
                subcategoriesContainer.innerHTML = `<li class="list-group-item text-danger">${data.error}</li>`;
              }
              return;
            }

            // Vérifier si data est un tableau valide
            if (!Array.isArray(data)) {
              if (subcategoriesContainer) {
                subcategoriesContainer.innerHTML =
                  '<li class="list-group-item text-danger">Format de données invalide</li>';
              }
              return;
            }

            if (data.length === 0) {
              if (subcategoriesContainer) {
                subcategoriesContainer.innerHTML =
                  '<li class="list-group-item">Aucune sous-catégorie trouvée pour cette catégorie</li>';
              }
              return;
            }

            data.forEach((sousCateg) => {
              // Vérifier que toutes les propriétés requises sont présentes
              if (!sousCateg.id || !sousCateg.nom || !sousCateg.categorie_id) {
                console.error("Sous-catégorie invalide:", sousCateg);
                return;
              }

              if (subcategoriesContainer) {
                subcategoriesContainer.innerHTML += `
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <strong>${sousCateg.nom}</strong>
                                        <br><small class="text-muted">Catégorie: ${selectedCategoryName}</small>
                                        ${
                                          sousCateg.description
                                            ? `<br><small>${sousCateg.description}</small>`
                                            : ""
                                        }
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editSousCategorie(${
                                                  sousCateg.id
                                                }, ${
                  sousCateg.categorie_id
                }, '${sousCateg.nom.replace(/'/g, "\\'")}', '${(
                  sousCateg.description || ""
                ).replace(/'/g, "\\'")}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDeleteSousCategorie(${
                                                  sousCateg.id
                                                }, '${sousCateg.nom.replace(
                  /'/g,
                  "\\'"
                )}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </li>
                            `;
              }
            });

            if (subcategoriesList) {
              subcategoriesList.style.display = "block";
            }
          })
          .catch((error) => {
            console.error("Erreur:", error);
            // Vérifier si c'est une erreur d'authentification
            if (error.message.includes("session expirée")) {
              // Ajouter une alerte dans le conteneur
              if (subcategoriesContainer) {
                subcategoriesContainer.innerHTML =
                  '<div class="alert alert-danger">Votre session a expiré. Veuillez vous reconnecter.</div>';
              }
              // Rediriger vers la page de connexion après un court délai
              setTimeout(() => {
                window.location.href = "index.php?controller=auth&action=login";
              }, 2000);
            } else {
              if (subcategoriesContainer) {
                subcategoriesContainer.innerHTML =
                  '<li class="list-group-item text-danger">Erreur lors du chargement des sous-catégories</li>';
              }
            }
            if (subcategoriesList) {
              subcategoriesList.style.display = "block";
            }
          });
      } else {
        if (subcategoriesContainer) {
          subcategoriesContainer.innerHTML = "";
        }
        if (subcategoriesList) {
          subcategoriesList.style.display = "none";
        }
      }
    });
  }

  // Gestion des points de vigilance
  const vigilanceCategorySelect = document.getElementById("vigilance_category");
  if (vigilanceCategorySelect) {
    vigilanceCategorySelect.addEventListener("change", function () {
      const categoryId = this.value;
      const subcategorySelect = document.getElementById(
        "vigilance_subcategory"
      );
      const formFields = document.getElementById("vigilance-form-fields");
      const pointsList = document.getElementById("points-list");

      if (subcategorySelect) {
        subcategorySelect.innerHTML =
          '<option value="">Sélectionnez une sous-catégorie</option>';
        subcategorySelect.disabled = !categoryId;
      }

      if (formFields) {
        formFields.style.display = "none";
      }

      if (pointsList) {
        pointsList.style.display = "none";
      }

      if (categoryId) {
        fetch(
          `index.php?controller=admin&method=getSousCategories&categorie_id=${categoryId}`,
          {
            headers: {
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        )
          .then((response) => {
            if (!response.ok) {
              throw new Error("Erreur réseau ou session expirée");
            }
            return response.json();
          })
          .then((data) => {
            if (subcategorySelect) {
              data.forEach((sousCateg) => {
                subcategorySelect.innerHTML += `
                                <option value="${sousCateg.id}">${sousCateg.nom}</option>
                            `;
              });
            }
          });
      }
    });
  }

  const vigilanceSubcategorySelect = document.getElementById(
    "vigilance_subcategory"
  );
  if (vigilanceSubcategorySelect) {
    vigilanceSubcategorySelect.addEventListener("change", function () {
      const subcategoryId = this.value;
      const formFields = document.getElementById("vigilance-form-fields");
      const pointsList = document.getElementById("points-list");
      const pointsContainer = document.getElementById("points-container");

      if (subcategoryId) {
        // Charger les points de vigilance via AJAX
        fetch(
          `index.php?controller=admin&method=getPointsVigilance&sous_categorie_id=${subcategoryId}`,
          {
            headers: {
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        )
          .then((response) => {
            if (!response.ok) {
              throw new Error("Erreur réseau ou session expirée");
            }
            return response.json();
          })
          .then((data) => {
            if (pointsContainer) {
              pointsContainer.innerHTML = ""; // Vider la liste
              data.forEach((point) => {
                pointsContainer.innerHTML += `
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <strong>${point.nom}</strong>
                                        <br><small class="text-muted">
                                            ${point.categorie_nom} > ${
                  point.sous_categorie_nom
                }
                                        </small>
                                        ${
                                          point.description
                                            ? `<br><small>${point.description}</small>`
                                            : ""
                                        }
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editPoint(${
                                                  point.id
                                                }, ${
                  point.sous_categorie_id
                }, '${point.nom.replace(/'/g, "\\'")}', '${(
                  point.description || ""
                ).replace(/'/g, "\\'")}', '${point.image}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDeletePoint(${
                                                  point.id
                                                }, '${point.nom.replace(
                  /'/g,
                  "\\'"
                )}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </li>
                            `;
              });
            }

            if (formFields) {
              formFields.style.display = "block";
            }

            if (pointsList) {
              pointsList.style.display = "block";
            }
          })
          .catch((error) => {
            console.error("Erreur:", error);
            // Vérifier si c'est une erreur d'authentification
            if (error.message.includes("session expirée")) {
              // Ajouter une alerte dans le conteneur
              if (pointsContainer) {
                pointsContainer.innerHTML =
                  '<div class="alert alert-danger">Votre session a expiré. Veuillez vous reconnecter.</div>';
              }
              // Rediriger vers la page de connexion après un court délai
              setTimeout(() => {
                window.location.href = "index.php?controller=auth&action=login";
              }, 2000);
            } else {
              if (pointsContainer) {
                pointsContainer.innerHTML =
                  '<li class="list-group-item text-danger">Erreur lors du chargement des points de vigilance</li>';
              }
            }
          });
      } else {
        if (formFields) {
          formFields.style.display = "none";
        }

        if (pointsList) {
          pointsList.style.display = "none";
        }
      }
    });
  }

  // Gestion du changement de catégorie dans le modal d'édition des points
  const editPointCategorySelect = document.getElementById(
    "edit_point_category"
  );
  if (editPointCategorySelect) {
    editPointCategorySelect.addEventListener("change", function () {
      const categoryId = this.value;
      const subcategorySelect = document.getElementById(
        "edit_point_subcategory"
      );

      if (categoryId) {
        fetch(
          `index.php?controller=admin&method=getSousCategories&categorie_id=${categoryId}`,
          {
            headers: {
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        )
          .then((response) => {
            if (!response.ok) {
              throw new Error("Erreur réseau ou session expirée");
            }
            return response.json();
          })
          .then((data) => {
            if (subcategorySelect) {
              subcategorySelect.innerHTML =
                '<option value="">Sélectionnez une sous-catégorie</option>';
              data.forEach((sousCateg) => {
                subcategorySelect.innerHTML += `
                                <option value="${sousCateg.id}">${sousCateg.nom}</option>
                            `;
              });
            }
          });
      } else if (subcategorySelect) {
        subcategorySelect.innerHTML =
          '<option value="">Sélectionnez d\'abord une catégorie</option>';
      }
    });
  }
}

// Exécuter l'initialisation quand le DOM est chargé
document.addEventListener("DOMContentLoaded", initAdminPanel);
