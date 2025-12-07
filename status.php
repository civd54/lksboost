<?php
// status.php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$order_number = $_GET['order'] ?? '';
$order = getOrderByNumber($order_number);

if (!$order) {
    die('Commande non trouvée');
}

// Si la commande a un ID Exobooster, récupérer le statut
$status_details = null;
if ($order['exobooster_order_id']) {
    $api = new ExoboosterAPI();
    $status_details = $api->getOrderStatus($order['exobooster_order_id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Commande - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-5">Suivi de Commande</h1>
        
        <div class="card">
            <div class="card-body">
                <h4>Commande #<?php echo $order['order_number']; ?></h4>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6>Détails de la commande:</h6>
                        <p><strong>Service:</strong> <?php echo $order['service_name']; ?></p>
                        <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                        <p><strong>Montant:</strong> <?php echo $order['currency'] . number_format($order['price'], 4); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Statut:</h6>
                        <?php
                        $status_class = [
                            'pending' => 'warning',
                            'in progress' => 'info',
                            'completed' => 'success',
                            'partial' => 'primary',
                            'cancelled' => 'danger',
                            'refunded' => 'secondary'
                        ][$order['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?> fs-6">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                        
                        <?php if ($status_details): ?>
                        <div class="mt-3">
                            <p><strong>Début:</strong> <?php echo $status_details['start_count'] ?? 0; ?></p>
                            <p><strong>Reste:</strong> <?php echo $status_details['remains'] ?? 0; ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($order['exobooster_order_id']): ?>
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    ID de commande Exobooster: <?php echo $order['exobooster_order_id']; ?>
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                    <a href="catalog.php" class="btn btn-outline-primary">Nouvelle commande</a>
                </div>
            </div>
        </div>
        
        <!-- Formulaire de recherche pour d'autres commandes -->
        <div class="card mt-4">
            <div class="card-body">
                <h5>Rechercher une autre commande</h5>
                <form action="status.php" method="GET" class="d-flex">
                    <input type="text" name="order" class="form-control me-2" 
                           placeholder="Numéro de commande (ex: LKS20231201ABC123)">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>