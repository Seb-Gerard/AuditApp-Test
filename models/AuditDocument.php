<?php
require_once __DIR__ . '/../config/database.php';

class AuditDocument {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ajoute un document ou une photo à un point d'audit
     *
     * @param int $auditId ID de l'audit
     * @param int $pointVigilanceId ID du point de vigilance
     * @param string $type Type de fichier ('photo' ou 'document')
     * @param array $file Informations sur le fichier ($_FILES)
     * @return bool|int Succès ou échec de l'opération, ou l'ID du document en cas de succès
     */
    public function ajouter($auditId, $pointVigilanceId, $type, $file) {
        try {
            error_log("Début de la méthode ajouter pour audit_id:$auditId, point_vigilance_id:$pointVigilanceId, type:$type");
            
            // Vérifier si le fichier a été correctement fourni
            if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
                error_log("Erreur: fichier temporaire manquant");
                throw new Exception("Aucun fichier n'a été téléchargé");
            }
            
            if (!file_exists($file['tmp_name'])) {
                error_log("Erreur: le fichier temporaire n'existe pas: " . $file['tmp_name']);
                throw new Exception("Le fichier temporaire n'existe pas");
            }
            
            error_log("Fichier temporaire existe: " . $file['tmp_name'] . ", taille: " . filesize($file['tmp_name']) . " octets");

            // Vérifier le type de fichier
            if ($type !== 'photo' && $type !== 'document') {
                error_log("Type de fichier non valide: $type");
                throw new Exception("Type de fichier non valide");
            }

            // Générer un nom unique pour le fichier
            $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid() . '_' . $auditId . '_' . $pointVigilanceId . '.' . $extension;
            
            error_log("Nom original: $originalName, extension: $extension, nouveau nom: $newFileName");

            // Chemin de destination
            $uploadDir = ($type === 'photo') 
                ? 'public/uploads/audit_photos/'
                : 'public/uploads/audit_documents/';
            
            // Vérifier que le répertoire de destination existe
            if (!is_dir($uploadDir)) {
                error_log("Création du répertoire de destination: $uploadDir");
                if (!mkdir($uploadDir, 0755, true)) {
                    error_log("Impossible de créer le répertoire de destination: $uploadDir");
                    throw new Exception("Impossible de créer le répertoire de destination");
                }
            }
            
            // Vérifier les permissions d'écriture
            if (!is_writable($uploadDir)) {
                error_log("Le répertoire n'est pas accessible en écriture: $uploadDir");
                throw new Exception("Le répertoire de destination n'est pas accessible en écriture");
            }
            
            $destination = $uploadDir . $newFileName;
            error_log("Destination du fichier: $destination");

            // Déplacer le fichier vers le répertoire de destination
            // Utiliser copy puis unlink au lieu de move_uploaded_file pour les fichiers temporaires créés manuellement
            if ($file['tmp_name'] && file_exists($file['tmp_name'])) {
                if (copy($file['tmp_name'], $destination)) {
                    error_log("Fichier copié avec succès de {$file['tmp_name']} vers $destination");
                    
                    // Vérifier que le fichier a bien été copié
                    if (file_exists($destination)) {
                        error_log("Vérification: fichier destination existe, taille: " . filesize($destination) . " octets");
                    } else {
                        error_log("Erreur: le fichier de destination n'existe pas après copie");
                        throw new Exception("Le fichier n'a pas été correctement copié");
                    }
                } else {
                    $errorMsg = "Erreur lors de la copie du fichier: " . error_get_last()['message'] ?? 'Raison inconnue';
                    error_log($errorMsg);
                    throw new Exception("Erreur lors du téléchargement du fichier");
                }
            } else {
                error_log("Erreur: le fichier source n'existe pas au moment de la copie");
                throw new Exception("Le fichier source n'existe pas");
            }

            // Enregistrer les informations du fichier dans la base de données
            $sql = "INSERT INTO audit_point_documents (
                audit_id, point_vigilance_id, type, nom_fichier, chemin_fichier
            ) VALUES (
                :audit_id, :point_vigilance_id, :type, :nom_fichier, :chemin_fichier
            )";

            error_log("Insertion dans la base de données: audit_id=$auditId, point_vigilance_id=$pointVigilanceId, type=$type, nom_fichier={$originalName}.{$extension}, chemin_fichier=$destination");

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':audit_id' => $auditId,
                ':point_vigilance_id' => $pointVigilanceId,
                ':type' => $type,
                ':nom_fichier' => $originalName . '.' . $extension,
                ':chemin_fichier' => $destination
            ]);
            
            $documentId = $this->db->lastInsertId();
            error_log("Document ajouté avec succès, ID: $documentId");
            
            return $documentId;

        } catch (Exception $e) {
            error_log("Erreur lors de l'ajout d'un document: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les documents et photos d'un point d'audit
     *
     * @param int $auditId ID de l'audit
     * @param int $pointVigilanceId ID du point de vigilance
     * @param string $type Type de fichier ('photo', 'document' ou null pour tous les types)
     * @return array Liste des documents
     */
    public function getDocumentsByPoint($auditId, $pointVigilanceId, $type = null) {
        try {
            $sql = "SELECT * FROM audit_point_documents 
                    WHERE audit_id = :audit_id AND point_vigilance_id = :point_vigilance_id";
            
            $params = [
                ':audit_id' => $auditId,
                ':point_vigilance_id' => $pointVigilanceId
            ];

            // Ajouter la condition sur le type si spécifié
            if ($type) {
                $sql .= " AND type = :type";
                $params[':type'] = $type;
            }

            $sql .= " ORDER BY date_ajout DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des documents: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Supprime un document ou une photo
     *
     * @param int $id ID du document
     * @return bool Succès ou échec de l'opération
     */
    public function supprimer($id) {
        try {
            // Récupérer d'abord les informations du fichier pour pouvoir le supprimer physiquement
            $sql = "SELECT chemin_fichier FROM audit_point_documents WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                throw new Exception("Document non trouvé");
            }

            // Supprimer le fichier physique
            if (file_exists($document['chemin_fichier'])) {
                if (!unlink($document['chemin_fichier'])) {
                    error_log("Impossible de supprimer le fichier: " . $document['chemin_fichier']);
                }
            }

            // Supprimer l'enregistrement de la base de données
            $sql = "DELETE FROM audit_point_documents WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);

        } catch (Exception $e) {
            error_log("Erreur lors de la suppression d'un document: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère un document par son ID
     *
     * @param int $id ID du document
     * @return array|false Les informations du document ou false si non trouvé
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM audit_point_documents WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du document: " . $e->getMessage());
            return false;
        }
    }
} 