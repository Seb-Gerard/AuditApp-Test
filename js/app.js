class ArticleManager {
  constructor() {
    this.articlesContainer = document.querySelector(".articles-list");
    this.syncButton = document.getElementById("sync-button");
    this.form = document.getElementById("article-form");
    this.init();
  }

  async init() {
    this.setupEventListeners();

    // Attendre que ArticleDB soit disponible depuis db.js
    if (typeof ArticleDB !== "undefined") {
      // Sur la page d'index, charger les articles
      if (this.articlesContainer) {
        try {
          await this.loadArticles();
        } catch (error) {
          this.articlesContainer.innerHTML =
            '<div class="alert alert-danger">Erreur lors du chargement des articles: ' +
            (error.message || "Erreur inconnue") +
            "</div>";
        }
      }
    } else {
      if (this.articlesContainer) {
        this.articlesContainer.innerHTML =
          '<div class="alert alert-danger">Erreur: La base de données n\'est pas disponible. Vérifiez que le script db.js est chargé correctement.</div>';
      }
    }
  }

  setupEventListeners() {
    // Configuration du formulaire d'articles (page create.php)
    if (this.form) {
      this.form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const title = document.getElementById("title").value;
        const content = document.getElementById("content").value;

        if (!title || !content) {
          return;
        }

        const article = {
          title: title,
          content: content,
          created_at: new Date().toISOString(),
        };

        try {
          // Sauvegarder l'article dans IndexedDB via ArticleDB
          await ArticleDB.saveArticle(article);

          // Si en ligne, essayer de synchroniser immédiatement
          if (navigator.onLine) {
            await ArticleDB.syncWithServer();
          }

          // Rediriger vers la liste des articles après l'enregistrement
          window.location.href = "index.php?action=articles";
        } catch (error) {
          alert(
            "Erreur lors de la sauvegarde de l'article: " +
              (error.message || "Erreur inconnue")
          );
        }
      });
    }

    // Configuration du bouton de synchronisation (page index.php)
    if (this.syncButton) {
      this.syncButton.addEventListener("click", async () => {
        if (!navigator.onLine) {
          alert(
            "Vous êtes hors ligne. La synchronisation n'est pas possible pour le moment."
          );
          return;
        }

        this.syncButton.disabled = true;
        try {
          await ArticleDB.syncWithServer();
          await this.loadArticles();
        } catch (error) {
          alert("Erreur lors de la synchronisation. Veuillez réessayer.");
        } finally {
          this.syncButton.disabled = false;
        }
      });
    }
  }

  async loadArticles() {
    if (!this.articlesContainer) {
      return;
    }

    try {
      // Vérifier que ArticleDB est disponible
      if (typeof ArticleDB === "undefined") {
        throw new Error("ArticleDB n'est pas défini");
      }

      // Vérifier que getArticles est disponible
      if (typeof ArticleDB.getArticles !== "function") {
        throw new Error("ArticleDB.getArticles n'est pas une fonction");
      }

      const articles = await ArticleDB.getArticles();
      this.displayArticles(articles || []);
    } catch (error) {
      this.articlesContainer.innerHTML =
        '<div class="alert alert-danger">Erreur lors du chargement des articles: ' +
        (error.message || "Erreur inconnue") +
        "</div>";
    }
  }

  displayArticles(articles) {
    if (!this.articlesContainer) {
      return;
    }

    if (articles.length === 0) {
      this.articlesContainer.innerHTML =
        '<p class="text-center">Aucun article pour le moment.</p>';
      return;
    }

    // Trier les articles par date (plus récent d'abord)
    articles.sort((a, b) => {
      const dateA = new Date(a.created_at || 0);
      const dateB = new Date(b.created_at || 0);
      return dateB.getTime() - dateA.getTime();
    });

    this.articlesContainer.innerHTML = articles
      .map(
        (article) => `
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">${this.escapeHtml(
                      article.title
                    )}</h5>
                    <p class="card-text">${this.escapeHtml(
                      article.content
                    ).replace(/\n/g, "<br>")}</p>
                    <p class="card-text">
                        <small class="text-muted">
                            Créé le ${new Date(
                              article.created_at
                            ).toLocaleString()}
                            ${article.server_id ? " (Synchronisé)" : " (Local)"}
                        </small>
                    </p>
                </div>
            </div>
        `
      )
      .join("");
  }

  escapeHtml(unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
}

// Initialize the article manager when the DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  // Initialiser sur les pages d'articles (index et création)
  if (
    document.querySelector(".articles-page") ||
    document.getElementById("article-form")
  ) {
    new ArticleManager();
  }
});
