<!-- Ce fichier est inclus par le contrôleur, qui a déjà chargé le header -->
<div class="container mt-5">
    <h1>Créer un article</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <form action="/Audit/index.php?action=create" method="POST" class="mt-4">
        <div class="mb-3">
            <label for="title" class="form-label">Titre</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        
        <div class="mb-3">
            <label for="content" class="form-label">Contenu</label>
            <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Créer l'article</button>
        <a href="/Audit/index.php" class="btn btn-secondary">Retour</a>
    </form>
</div> 