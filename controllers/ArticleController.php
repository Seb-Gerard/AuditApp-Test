<?php
require_once __DIR__ . '/../models/Article.php';

class ArticleController {
    private $articleModel;

    public function __construct() {
        $this->articleModel = new Article();
    }

    public function index()
    {
        try {
            // Vérifier si on demande l'affichage du formulaire de création
            if (isset($_GET['display']) && $_GET['display'] === 'create') {
                $this->displayCreateForm();
                return;
            }
            
            $articleModel = new Article();
            $articles = $articleModel->getAll();
            
            // Gérer les requêtes AJAX (pour récupération des articles en JSON)
            if ($this->isAjaxRequest()) {
                $this->sendJsonResponse($articles);
                return;
            }
            
            // Affichage normal de la page
            include_once __DIR__ . '/../views/articles/index.php';
        } catch (\Exception $e) {
            error_log('Exception dans ArticleController::index: ' . $e->getMessage());
            if ($this->isAjaxRequest()) {
                $this->sendJsonResponse(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
            } else {
                // Rediriger vers une page d'erreur ou afficher un message d'erreur
                include_once __DIR__ . '/../views/error.php';
            }
        }
    }

    /**
     * Crée un nouvel article
     * 
     * @param array $data Données de l'article
     * @return array|bool Données de l'article créé ou false si erreur
     */
    public function create(array $data = []): array|bool
    {
        // Traiter les soumissions de formulaire standard (méthode POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$this->isAjaxRequest()) {
            error_log('Soumission de formulaire POST standard reçue');
            
            // Récupérer les données du formulaire
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            
            if (empty($title) || empty($content)) {
                $_SESSION['error'] = 'Le titre et le contenu sont obligatoires';
                // Rediriger vers le formulaire
                header('Location: index.php?action=articles&display=create');
                exit;
            }
            
            try {
                $articleModel = new Article();
                $result = $articleModel->create([
                    'title' => $title,
                    'content' => $content
                ]);
                
                if ($result) {
                    // Rediriger vers la liste des articles
                    header('Location: index.php?action=articles');
                    exit;
                } else {
                    $_SESSION['error'] = 'Erreur lors de la création de l\'article';
                    header('Location: index.php?action=articles&display=create');
                    exit;
                }
            } catch (\Exception $e) {
                error_log('Exception lors de la création d\'article (formulaire standard): ' . $e->getMessage());
                $_SESSION['error'] = 'Erreur serveur: ' . $e->getMessage();
                header('Location: index.php?action=articles&display=create');
                exit;
            }
        }

        // En cas d'appel direct de l'URL avec method=create, rediriger vers le formulaire
        if (!$this->isAjaxRequest() && isset($_GET['method']) && $_GET['method'] === 'create') {
            // Rediriger vers la page de création d'article normale
            header('Location: index.php?action=articles&display=create');
            exit;
        }

        // En cas de requête AJAX, on récupère les données JSON
        if ($this->isAjaxRequest()) {
            $rawData = file_get_contents('php://input');
            // Ajouter des logs pour comprendre les données reçues
            error_log('Données reçues dans ArticleController::create: ' . $rawData);
            
            // Vérifier si le contenu est vide
            if (empty($rawData)) {
                error_log('Données vides reçues dans ArticleController::create');
                $this->sendJsonResponse(['error' => 'Aucune donnée reçue'], 400);
                return false;
            }
            
            try {
                $data = json_decode($rawData, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('Erreur de décodage JSON: ' . json_last_error_msg());
                    $this->sendJsonResponse(['error' => 'Format JSON invalide: ' . json_last_error_msg()], 400);
                    return false;
                }
                
                if (!isset($data['title']) || !isset($data['content'])) {
                    error_log('Données incomplètes: title ou content manquant');
                    $this->sendJsonResponse(['error' => 'Données incomplètes: title ou content manquant'], 400);
                    return false;
                }
            } catch (\Exception $e) {
                error_log('Exception lors du traitement des données: ' . $e->getMessage());
                $this->sendJsonResponse(['error' => 'Erreur interne: ' . $e->getMessage()], 500);
                return false;
            }
        }

        // Vérification des données
        if (empty($data['title']) || empty($data['content'])) {
            if ($this->isAjaxRequest()) {
                error_log('Validation échouée: titre ou contenu vide');
                $this->sendJsonResponse([
                    'error' => 'Le titre et le contenu sont obligatoires'
                ], 400);
            }
            return false;
        }

        try {
            // Création de l'article
            $articleModel = new Article();
            $newArticleId = $articleModel->create([
                'title' => $data['title'],
                'content' => $data['content']
            ]);

            if (!$newArticleId) {
                error_log('Échec de création de l\'article en base de données');
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse([
                        'error' => 'Erreur lors de la création de l\'article'
                    ], 500);
                }
                return false;
            }

            // Récupérer l'article complet
            $newArticle = $articleModel->findById($newArticleId);
            
            if ($this->isAjaxRequest()) {
                error_log('Article créé avec succès: ID ' . $newArticleId);
                $this->sendJsonResponse($newArticle);
            }

            return $newArticle;
        } catch (\Exception $e) {
            error_log('Exception lors de la création de l\'article: ' . $e->getMessage());
            if ($this->isAjaxRequest()) {
                $this->sendJsonResponse([
                    'error' => 'Erreur serveur: ' . $e->getMessage()
                ], 500);
            }
            return false;
        }
    }
    
    // Méthode pour récupérer les articles (utilisé pour la synchronisation)
    public function getArticles() {
        $articles = $this->articleModel->getAll();
        header('Content-Type: application/json');
        echo json_encode($articles);
        exit;
    }

    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function sendJsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Affiche le formulaire de création d'article
     */
    public function displayCreateForm()
    {
        include_once __DIR__ . '/../views/articles/create.php';
    }
} 