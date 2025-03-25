<?php
session_start();
require_once __DIR__ . '/controllers/ArticleController.php';

$controller = new ArticleController();
$controller->create();
$controller->index();
  