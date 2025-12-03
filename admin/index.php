<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier la connexion admin
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Statistiques
$total_orders = getTotalOrders();
$pending_orders = getPendingOrders();
$total_revenue = getTotalRevenue();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><?= SITE_NAME ?> - Admin</a>
            <div class="d-flex">
                <a href="../index.php" class="btn btn-outline-light me-2">Voir le site</a>
                <a href="logout.php" class="btn btn-outline-light">Déconnexion</a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action active">
                        Tableau de bord
                    </a>
                    <a href="orders.php" class="list-group-item list-group-item-action">
                        Commandes
                    </a>
                    <a href="services.php" class="list-group-item list-group-item-action">
                        Services
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        Paramètres
                    </a>
                </div>
            </div>
            
            <!-- Contenu principal -->
            <div class="col-md-9">
                <h1>Tableau de bord</h1>
                
                <!-- Cartes de statistiques -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Commandes totales</h5>
                                <h2 class="card-text"><?= $total_orders ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Commandes en attente</h5>
                                <h2 class="card-text"><?= $pending_orders ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Chiffre d'affaires</h5>
                                <h2 class="card-text"><?= CURRENCY . $total_revenue ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Dernières commandes -->
                <div class="card">
                    <div class="card-header">
                        <h5>Dernières commandes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Service</th>
                                        <th>Lien</th>
                                        <th>Quantité</th>
                                        <th>Prix</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recent_orders = getRecentOrders(10);
                                    foreach ($recent_orders as $order) {
                                        echo '<tr>';
                                        echo '<td>' . $order['order_number'] . '</td>';
                                        echo '<td>' . htmlspecialchars($order['service_name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($order['link']) . '</td>';
                                        echo '<td>' . $order['quantity'] . '</td>';
                                        echo '<td>' . CURRENCY . $order['price'] . '</td>';
                                        echo '<td><span class="badge bg-' . getStatusBadgeColor($order['status']) . '">' . $order['status'] . '</span></td>';
                                        echo '<td>' . $order['created_at'] . '</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>