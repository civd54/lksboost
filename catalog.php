<?php
// catalog.php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$services = getAllActiveServices();
$categories = getServiceCategories();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #8A2BE2;
            --gradient-primary: linear-gradient(135deg, #8A2BE2, #6A1E9E);
            --shadow: 0 10px 30px rgba(138, 43, 226, 0.15);
        }
        
        .service-card {
            border-radius: 15px;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .filter-btn {
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            padding: 8px 20px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background: var(--gradient-primary);
            color: white;
            border-color: var(--primary);
        }
        
        .service-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation (similaire à index.php) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-bolt"></i> LKSBoost
            </a>
            <a href="index.php" class="btn btn-outline-primary">Retour à l'accueil</a>
        </div>
    </nav>

    <div class="container py-5">
        <h1 class="text-center mb-5">Nos Services</h1>
        
        <!-- Filtres -->
        <div class="mb-5">
            <h5 class="mb-3">Filtrer par plateforme:</h5>
            <div class="d-flex flex-wrap">
                <div class="filter-btn active" data-category="all">Toutes</div>
                <?php foreach ($categories as $category): ?>
                <div class="filter-btn" data-category="<?php echo $category; ?>">
                    <?php echo ucfirst($category); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Services -->
        <div class="row g-4" id="services-container">
            <?php foreach ($services as $service): ?>
            <div class="col-md-4" data-category="<?php echo $service['category']; ?>">
                <div class="service-card p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="service-icon me-3">
                            <i class="fab fa-<?php echo $service['category']; ?>"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($service['name']); ?></h5>
                            <small class="text-muted"><?php echo ucfirst($service['category']); ?></small>
                        </div>
                    </div>
                    
                    <p class="text-primary fw-bold fs-4 mb-3">
                        <?php echo CURRENCY . number_format($service['our_price'], 4); ?> / 1000
                    </p>
                    
                    <ul class="list-unstyled mb-4">
                        <li><i class="fas fa-check text-success me-2"></i> Min: <?php echo number_format($service['min_amount']); ?></li>
                        <li><i class="fas fa-check text-success me-2"></i> Max: <?php echo number_format($service['max_amount']); ?></li>
                        <li><i class="fas fa-check text-success me-2"></i> Livraison rapide</li>
                    </ul>
                    
                    <a href="order.php?service=<?php echo $service['id']; ?>" class="btn btn-primary w-100">
                        Commander
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Filtrage des services
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Activer le bouton cliqué
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const category = this.getAttribute('data-category');
                const services = document.querySelectorAll('[data-category]');
                
                services.forEach(service => {
                    if (category === 'all' || service.getAttribute('data-category') === category) {
                        service.style.display = 'block';
                    } else {
                        service.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>