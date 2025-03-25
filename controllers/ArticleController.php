<?php
require_once __DIR__ . '/../models/Article.php';

class ArticleController {
    private $articleModel;

    public function __construct() {
        $this->articleModel = new Article();
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
            $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);

            if ($title && $content) {
                if ($this->articleModel->create($title, $content)) {
                    $_SESSION['success'] = "Article créé avec succès !";
                } else {
                    $_SESSION['error'] = "Erreur lors de la création de l'article.";
                }
            } else {
                $_SESSION['error'] = "Veuillez remplir tous les champs.";
            }
        }
    }

    public function getArticles() {
        $articles = $this->articleModel->getAll();
        header('Content-Type: application/json');
        echo json_encode($articles);
        exit;
    }

    public function index() {
        if (isset($_GET['action']) && $_GET['action'] === 'getArticles') {
            $this->getArticles();
        }
        
        $articles = $this->articleModel->getAll();
        require_once __DIR__ . '/../views/articles/index.php';
    }
} 