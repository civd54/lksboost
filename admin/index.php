<?php
// admin/index.php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$db = getDB();
$stats = $db->get_row("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'in progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(price) as revenue
    FROM orders
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">Admin LKSBoost</span>
            <a href="../index.php" class="text-white">Voir le site</a>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h1>Tableau de bord</h1>
        
        <!-- Cartes de statistiques -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Commandes totales</h5>
                        <h2><?php echo $stats['total_orders']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Terminées</h5>
                        <h2><?php echo $stats['completed']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">En cours</h5>
                        <h2><?php echo $stats['in_progress']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Chiffre d'affaires</h5>
                        <h2><?php echo CURRENCY . number_format($stats['revenue'], 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Liens rapides -->
        <div class="row mt-5">
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-list fa-3x mb-3"></i>
                        <h4>Commandes</h4>
                        <a href="orders.php" class="btn btn-primary">Gérer</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-cogs fa-3x mb-3"></i>
                        <h4>Services</h4>
                        <a href="services.php" class="btn btn-primary">Gérer</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-cog fa-3x mb-3"></i>
                        <h4>Paramètres</h4>
                        <a href="settings.php" class="btn btn-primary">Configurer</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dernières commandes -->
        <div class="card mt-4">
            <div class="card-body">
                <h5>Dernières commandes</h5>
                <?php
                $orders = $db->get_all(
                    "SELECT o.*, s.name as service_name 
                     FROM orders o 
                     JOIN services s ON o.service_id = s.id 
                     ORDER BY created_at DESC LIMIT 10"
                );
                ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Service</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_number']; ?></td>
                            <td><?php echo $order['service_name']; ?></td>
                            <td><?php echo $order['currency'] . $order['price']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $order['status'] === 'completed' ? 'success' : 
                                           ($order['status'] === 'in progress' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>