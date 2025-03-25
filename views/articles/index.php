<?php
require_once dirname(__FILE__, 3) . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-body">
                    <h1 class="card-title text-center mb-4">Accueil</h1>
                    <p class="text-center mb-4">Bienvenue sur le site de l'entreprise</p>
                    
                    <form action="index.php" method="post">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre</label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="Entrez le titre" required>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Contenu</label>
                            <textarea class="form-control" id="content" name="content" rows="5" placeholder="Entrez le contenu" required></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Envoyer</button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h2 class="mb-4">Articles récents</h2>
                    <div class="articles-list">
                        <?php if (!empty($articles)): ?>
                            <?php foreach ($articles as $article): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($article['content'])); ?></p>
                                        <p class="card-text"><small class="text-muted">Créé le <?php echo date('d/m/Y H:i', strtotime($article['created_at'])); ?></small></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center">Aucun article pour le moment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/Audit/js/app.js"></script>

<?php
require_once dirname(__FILE__, 3) . '/includes/footer.php';
?> 