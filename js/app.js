class ArticleManager {
    constructor() {
        this.form = document.querySelector('form');
        this.articlesContainer = document.querySelector('.articles-list');
        this.init();
    }

    async init() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/Audit/sw.js');
                console.log('ServiceWorker registered');
                this.setupEventListeners();
                await this.syncArticles();
                this.loadArticles();
            } catch (error) {
                console.error('ServiceWorker registration failed:', error);
            }
        }
    }

    async syncArticles() {
        try {
            // Récupérer les articles de la base de données MySQL
            const response = await fetch('/Audit/index.php?action=getArticles');
            if (!response.ok) throw new Error('Erreur lors de la récupération des articles');
            
            const articles = await response.json();
            
            // Sauvegarder chaque article dans IndexedDB
            for (const article of articles) {
                await this.saveArticleOffline(article);
            }
        } catch (error) {
            console.error('Erreur de synchronisation:', error);
        }
    }

    setupEventListeners() {
        this.form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(this.form);
            const article = {
                title: formData.get('title'),
                content: formData.get('content')
            };

            try {
                // Try to save online first
                const response = await fetch('/Audit/index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData)
                });

                if (!response.ok) {
                    throw new Error('Network error');
                }

                // If online save succeeds, also save offline
                await this.saveArticleOffline(article);
                this.form.reset();
                this.loadArticles();
            } catch (error) {
                // If online save fails, save offline only
                await this.saveArticleOffline(article);
                this.form.reset();
                this.loadArticles();
            }
        });
    }

    async saveArticleOffline(article) {
        if (navigator.serviceWorker.controller) {
            return new Promise((resolve, reject) => {
                const messageHandler = (event) => {
                    if (event.data.type === 'ARTICLE_SAVED') {
                        navigator.serviceWorker.removeEventListener('message', messageHandler);
                        if (event.data.success) {
                            resolve();
                        } else {
                            reject(new Error(event.data.error));
                        }
                    }
                };

                navigator.serviceWorker.addEventListener('message', messageHandler);
                navigator.serviceWorker.controller.postMessage({
                    type: 'SAVE_ARTICLE',
                    article: article
                });
            });
        }
    }

    async loadArticles() {
        if (navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'GET_ARTICLES'
            });
        }
    }

    displayArticles(articles) {
        if (!this.articlesContainer) return;

        this.articlesContainer.innerHTML = articles.length ? articles.map(article => `
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">${this.escapeHtml(article.title)}</h5>
                    <p class="card-text">${this.escapeHtml(article.content).replace(/\n/g, '<br>')}</p>
                    <p class="card-text"><small class="text-muted">Créé le ${new Date(article.created_at).toLocaleString()}</small></p>
                </div>
            </div>
        `).join('') : '<p class="text-center">Aucun article pour le moment.</p>';
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
document.addEventListener('DOMContentLoaded', () => {
    new ArticleManager();
}); 