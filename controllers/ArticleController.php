<?php
require_once __DIR__ . '/../models/Article.php';

class ArticleController {
    private $article;

    public function __construct() {
        $this->article = new Article();
    }

    public function index() {
        // Vérifier si c'est une requête AJAX
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode($this->article->getAll());
            exit;
        }
        
        $articles = $this->article->getAll();
        
        // Inclure le header, le contenu et le footer
        include_once __DIR__ . '/../includes/header.php';
        include_once __DIR__ . '/../views/articles/index.php';
        include_once __DIR__ . '/../includes/footer.php';
    }

    public function create() {
        // Si c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Récupérer les données selon le type de requête
                if ($this->isAjaxRequest()) {
                    $data = json_decode(file_get_contents('php://input'), true);
                    if (!$data || !isset($data['title']) || !isset($data['content'])) {
                        $this->sendJsonError('Données invalides', 400);
                        return;
                    }
                    $article = [
                        'title' => $data['title'],
                        'content' => $data['content'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                } else {
                    // Traitement normal du formulaire HTML
                    if (!isset($_POST['title']) || !isset($_POST['content']) || empty($_POST['title']) || empty($_POST['content'])) {
                        $_SESSION['error'] = 'Le titre et le contenu sont requis';
                        header('Location: /Audit/index.php?action=create');
                        exit;
                    }
                    
                    $article = [
                        'title' => $_POST['title'],
                        'content' => $_POST['content'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                }

                // Créer l'article
                if ($this->article->create($article)) {
                    $articleId = $this->article->getLastInsertId();
                    
                    // Si c'est une requête AJAX, retourner l'ID en JSON
                    if ($this->isAjaxRequest()) {
                        header('Content-Type: application/json');
                        echo json_encode(['id' => $articleId]);
                        exit;
                    }
                    
                    // Sinon, rediriger avec un message de succès
                    $_SESSION['success'] = 'Article créé avec succès';
                    header('Location: /Audit/index.php');
                    exit;
                } else {
                    if ($this->isAjaxRequest()) {
                        $this->sendJsonError('Erreur lors de la création de l\'article', 500);
                        return;
                    }
                    
                    $_SESSION['error'] = 'Erreur lors de la création de l\'article';
                    header('Location: /Audit/index.php?action=create');
                    exit;
                }
            } catch (Exception $e) {
                if ($this->isAjaxRequest()) {
                    $this->sendJsonError($e->getMessage(), 500);
                    return;
                }
                
                $_SESSION['error'] = $e->getMessage();
                header('Location: /Audit/index.php?action=create');
                exit;
            }
        }
        
        // Afficher le formulaire de création avec header et footer
        include_once __DIR__ . '/../includes/header.php';
        include_once __DIR__ . '/../views/articles/create.php';
        include_once __DIR__ . '/../includes/footer.php';
    }
    
    /**
     * Vérifie si la requête est une requête AJAX
     * @return bool
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Envoie une réponse d'erreur en JSON
     * @param string $message Message d'erreur
     * @param int $code Code HTTP
     */
    private function sendJsonError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
} 