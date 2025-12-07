<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/api.php';
require_once 'includes/functions.php';

// Récupérer l'ID du service depuis l'URL
$service_id = isset($_GET['service']) ? intval($_GET['service']) : 0;

// Si aucun service n'est spécifié, rediriger vers le catalogue
if ($service_id <= 0) {
    header('Location: catalog.php');
    exit();
}

// Récupérer les informations du service depuis la base de données
$db = getDB();
$service = $db->get_row(
    "SELECT * FROM services WHERE id = ? AND is_active = 1",
    [$service_id]
);

// Si le service n'existe pas ou n'est pas actif, rediriger
if (!$service) {
    header('Location: catalog.php');
    exit();
}

// Récupérer la marge depuis les paramètres
$settings = $db->get_row("SELECT margin_percentage, currency FROM settings LIMIT 1");
$margin_percentage = $settings['margin_percentage'] ?? 30.00;
$currency = $settings['currency'] ?? '€';

// Initialiser les variables d'erreur et de succès
$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valider le token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Token de sécurité invalide. Veuillez réessayer.";
    } else {
        // Récupérer et valider les données
        $link = trim($_POST['link']);
        $quantity = intval($_POST['quantity']);
        
        // Validation des données
        if (empty($link)) {
            $errors[] = "Veuillez saisir un lien valide.";
        }
        
        if ($quantity < $service['min_amount']) {
            $errors[] = "La quantité minimum est de " . $service['min_amount'];
        }
        
        if ($quantity > $service['max_amount']) {
            $errors[] = "La quantité maximum est de " . $service['max_amount'];
        }
        
        // Si aucune erreur, créer la commande
        if (empty($errors)) {
            // Calculer le prix total
            $price_per_unit = $service['our_price'] / 1000;
            $total_price = round($price_per_unit * $quantity, 4);
            
            // Générer un numéro de commande unique
            $order_number = 'LKS' . date('Ymd') . strtoupper(substr(uniqid(), -8));
            
            // Insérer la commande dans la base de données
            $order_id = $db->insert('orders', [
                'order_number' => $order_number,
                'service_id' => $service_id,
                'link' => $link,
                'quantity' => $quantity,
                'price' => $total_price,
                'currency' => $currency,
                'status' => 'pending',
                'user_ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ]);
            
            if ($order_id) {
                // Stocker l'ID de commande dans la session pour le paiement
                $_SESSION['current_order_id'] = $order_id;
                $_SESSION['current_order_number'] = $order_number;
                
                // Rediriger vers la page de paiement
                header('Location: payment.php?order=' . $order_number);
                exit();
            } else {
                $errors[] = "Une erreur est survenue lors de la création de la commande. Veuillez réessayer.";
            }
        }
    }
}

// Générer un nouveau token CSRF pour le formulaire
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander - LKSBoost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #8A2BE2;
            --primary-dark: #6A1E9E;
            --primary-light: #9B4DE3;
            --secondary: #FF6B8B;
            --accent: #00D2B8;
            --dark: #1A1A2E;
            --light: #F8F9FA;
            --gradient-primary: linear-gradient(135deg, #8A2BE2, #6A1E9E);
            --gradient-accent: linear-gradient(135deg, #FF6B8B, #FF8E53);
            --gradient-success: linear-gradient(135deg, #00D2B8, #00A896);
            --shadow: 0 10px 30px rgba(138, 43, 226, 0.15);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: var(--light);
        }

        /* Header & Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 0.7rem 1.8rem;
            font-weight: 600;
            transition: var(--transition);
            border-radius: 50px;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(138, 43, 226, 0.3);
        }

        /* Hero Section */
        .page-hero {
            background: var(--gradient-primary);
            color: white;
            padding: 120px 0 60px;
            position: relative;
            overflow: hidden;
        }

        .page-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" opacity="0.05"><polygon fill="white" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }

        .page-hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .page-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        /* Order Section */
        .order-section {
            padding: 80px 0;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .order-header {
            background: var(--gradient-primary);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .order-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .order-body {
            padding: 2rem;
        }

        .service-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
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
            margin-right: 1rem;
        }

        .service-details h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .service-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .meta-item i {
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.1);
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            transform: scale(1.1);
        }

        .quantity-input {
            flex: 1;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .price-summary {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .price-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .price-item:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary);
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .note-box {
            background: #e8f4fd;
            border-left: 4px solid var(--primary);
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #333;
        }

        .note-box i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3rem;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .step.active .step-number {
            background: var(--gradient-primary);
            color: white;
            transform: scale(1.1);
        }

        .step-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #666;
        }

        .step.active .step-label {
            color: var(--primary);
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 80px 0 0;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 1.5rem 0;
            margin-top: 3rem;
            text-align: center;
            color: #bbb;
        }

        @media (max-width: 768px) {
            .page-hero {
                padding: 100px 0 50px;
            }
            
            .page-hero h1 {
                font-size: 2rem;
            }
            
            .order-body {
                padding: 1.5rem;
            }
            
            .progress-steps::before {
                left: 15%;
                right: 15%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-bolt me-2"></i>LKSBoost
            </a>
            <div class="d-flex">
                <a href="catalog.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <div class="page-hero-content">
                <h1>Passer Votre Commande</h1>
                <p>Remplissez les détails pour commander votre service</p>
            </div>
        </div>
    </section>

    <!-- Order Section -->
    <section class="order-section">
        <div class="container">
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Commande</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label">Paiement</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Confirmation</div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="order-card">
                        <div class="order-header">
                            <h2>Détails de la Commande</h2>
                        </div>
                        
                        <div class="order-body">
                            <!-- Afficher les erreurs -->
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <h5 class="alert-heading">Erreurs à corriger :</h5>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Résumé du service -->
                            <div class="service-summary">
                                <div class="d-flex align-items-center">
                                    <div class="service-icon">
                                        <?php 
                                        $icon_map = [
                                            'tiktok' => 'fab fa-tiktok',
                                            'instagram' => 'fab fa-instagram',
                                            'youtube' => 'fab fa-youtube',
                                            'facebook' => 'fab fa-facebook'
                                        ];
                                        $icon = $icon_map[$service['category']] ?? 'fas fa-bolt';
                                        ?>
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="service-details">
                                        <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                                        <p class="text-muted mb-0">
                                            Prix : <strong><?php echo $currency . number_format($service['our_price'], 4); ?> / 1000</strong>
                                        </p>
                                    </div>
                                </div>
                                <div class="service-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <span>Livraison : 5-30 min</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-shield-alt"></i>
                                        <span>Garantie : 30 jours</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-bolt"></i>
                                        <span>Commence rapidement</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulaire de commande -->
                            <form method="POST" id="order-form">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                
                                <!-- Lien/URL -->
                                <div class="form-group">
                                    <label for="link" class="form-label">
                                        <i class="fas fa-link me-2"></i>Lien de votre contenu
                                    </label>
                                    <input 
                                        type="url" 
                                        class="form-control" 
                                        id="link" 
                                        name="link" 
                                        placeholder="https://www.tiktok.com/@votrecompte/video/..."
                                        value="<?php echo isset($_POST['link']) ? htmlspecialchars($_POST['link']) : ''; ?>"
                                        required
                                    >
                                    <small class="form-text text-muted">
                                        Collez ici le lien direct de votre vidéo TikTok, Instagram, YouTube ou Facebook.
                                    </small>
                                </div>

                                <!-- Quantité -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-hashtag me-2"></i>Quantité
                                    </label>
                                    <div class="quantity-control">
                                        <button type="button" class="quantity-btn" id="decrease-qty">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input 
                                            type="number" 
                                            class="form-control quantity-input" 
                                            id="quantity" 
                                            name="quantity" 
                                            min="<?php echo $service['min_amount']; ?>"
                                            max="<?php echo $service['max_amount']; ?>"
                                            value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : $service['min_amount']; ?>"
                                            required
                                        >
                                        <button type="button" class="quantity-btn" id="increase-qty">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">
                                        Minimum : <?php echo number_format($service['min_amount']); ?> | 
                                        Maximum : <?php echo number_format($service['max_amount']); ?>
                                    </small>
                                </div>

                                <!-- Résumé du prix -->
                                <div class="price-summary">
                                    <h5 class="mb-3">Récapitulatif du prix</h5>
                                    <div class="price-item">
                                        <span>Prix unitaire (pour 1000) :</span>
                                        <span id="unit-price"><?php echo $currency . number_format($service['our_price'], 4); ?></span>
                                    </div>
                                    <div class="price-item">
                                        <span>Quantité :</span>
                                        <span id="quantity-display"><?php echo number_format($service['min_amount']); ?></span>
                                    </div>
                                    <div class="price-item">
                                        <span>Prix total :</span>
                                        <span id="total-price"><?php echo $currency . number_format(($service['our_price'] / 1000) * $service['min_amount'], 4); ?></span>
                                    </div>
                                </div>

                                <!-- Note importante -->
                                <div class="note-box">
                                    <p><i class="fas fa-info-circle"></i> <strong>Important :</strong></p>
                                    <ul class="mb-0">
                                        <li>Ne partagez jamais votre mot de passe. Nous n'en avons pas besoin.</li>
                                        <li>La livraison commence généralement dans les 5 à 30 minutes.</li>
                                        <li>Pour les grandes quantités, la livraison peut prendre jusqu'à 72 heures.</li>
                                        <li>Vous recevrez une confirmation par email après le paiement.</li>
                                    </ul>
                                </div>

                                <!-- Bouton de soumission -->
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-lock me-2"></i>Procéder au paiement
                                    </button>
                                    <a href="catalog.php" class="btn btn-outline-primary">
                                        <i class="fas fa-shopping-cart me-2"></i>Voir d'autres services
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2023 LKSBoost. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Données du service
        const serviceData = {
            unitPrice: <?php echo $service['our_price']; ?>,
            minQuantity: <?php echo $service['min_amount']; ?>,
            maxQuantity: <?php echo $service['max_amount']; ?>,
            currency: '<?php echo $currency; ?>'
        };

        // Éléments DOM
        const quantityInput = document.getElementById('quantity');
        const decreaseBtn = document.getElementById('decrease-qty');
        const increaseBtn = document.getElementById('increase-qty');
        const quantityDisplay = document.getElementById('quantity-display');
        const totalPriceElement = document.getElementById('total-price');
        const unitPriceElement = document.getElementById('unit-price');

        // Formater le prix
        function formatPrice(amount) {
            return serviceData.currency + amount.toFixed(4);
        }

        // Calculer le prix unitaire pour 1000
        const unitPricePer1000 = serviceData.unitPrice;
        
        // Calculer le prix par unité
        const pricePerUnit = unitPricePer1000 / 1000;

        // Mettre à jour l'affichage du prix
        function updatePriceDisplay() {
            const quantity = parseInt(quantityInput.value) || serviceData.minQuantity;
            const totalPrice = pricePerUnit * quantity;
            
            quantityDisplay.textContent = quantity.toLocaleString();
            totalPriceElement.textContent = formatPrice(totalPrice);
            
            // Mettre à jour le prix unitaire (pour 1000)
            unitPriceElement.textContent = formatPrice(unitPricePer1000);
        }

        // Gérer l'augmentation de la quantité
        increaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value) || serviceData.minQuantity;
            if (currentValue < serviceData.maxQuantity) {
                currentValue = Math.min(currentValue + 1000, serviceData.maxQuantity);
                quantityInput.value = currentValue;
                updatePriceDisplay();
            }
        });

        // Gérer la diminution de la quantité
        decreaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value) || serviceData.minQuantity;
            if (currentValue > serviceData.minQuantity) {
                currentValue = Math.max(currentValue - 1000, serviceData.minQuantity);
                quantityInput.value = currentValue;
                updatePriceDisplay();
            }
        });

        // Mettre à jour le prix quand la quantité change manuellement
        quantityInput.addEventListener('input', function() {
            let value = parseInt(this.value) || serviceData.minQuantity;
            
            // Valider les limites
            if (value < serviceData.minQuantity) {
                value = serviceData.minQuantity;
            } else if (value > serviceData.maxQuantity) {
                value = serviceData.maxQuantity;
            }
            
            this.value = value;
            updatePriceDisplay();
        });

        // Validation du formulaire
        document.getElementById('order-form').addEventListener('submit', function(e) {
            const linkInput = document.getElementById('link');
            const quantity = parseInt(quantityInput.value);
            
            // Validation du lien
            if (!linkInput.value.trim()) {
                e.preventDefault();
                alert('Veuillez saisir un lien valide.');
                linkInput.focus();
                return;
            }
            
            // Validation de l'URL
            try {
                new URL(linkInput.value);
            } catch (_) {
                e.preventDefault();
                alert('Veuillez saisir une URL valide (commençant par http:// ou https://).');
                linkInput.focus();
                return;
            }
            
            // Validation de la quantité
            if (quantity < serviceData.minQuantity || quantity > serviceData.maxQuantity) {
                e.preventDefault();
                alert(`La quantité doit être comprise entre ${serviceData.minQuantity.toLocaleString()} et ${serviceData.maxQuantity.toLocaleString()}.`);
                quantityInput.focus();
                return;
            }
            
            // Afficher un message de chargement
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...';
            submitBtn.disabled = true;
        });

        // Mise à jour initiale
        updatePriceDisplay();

        // Animation du scroll pour la navigation
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });
    </script>
</body>
</html>