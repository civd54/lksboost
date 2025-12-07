<?php
// order.php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Récupérer le service depuis l'URL
$service_id = isset($_GET['service']) ? intval($_GET['service']) : 0;
$service = getServiceById($service_id);

if (!$service) {
    header('Location: catalog.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de sécurité invalide";
    } else {
        $link = trim($_POST['link']);
        $quantity = intval($_POST['quantity']);
        
        // Validation
        if (empty($link)) {
            $error = "Veuillez saisir un lien";
        } elseif ($quantity < $service['min_amount'] || $quantity > $service['max_amount']) {
            $error = "Quantité invalide. Min: {$service['min_amount']}, Max: {$service['max_amount']}";
        } else {
            // Calculer le prix
            $price_per_unit = $service['our_price'] / 1000;
            $total_price = round($price_per_unit * $quantity, 4);
            
            // Créer la commande
            $order_id = createOrder($service_id, $link, $quantity, $total_price);
            
            if ($order_id) {
                // Récupérer le numéro de commande
                $db = getDB();
                $order = $db->get_row("SELECT order_number FROM orders WHERE id = ?", [$order_id]);
                
                // Rediriger vers le paiement
                header('Location: payment.php?order=' . $order['order_number']);
                exit();
            } else {
                $error = "Erreur lors de la création de la commande";
            }
        }
    }
}

// Générer un token CSRF
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #8A2BE2;
            --gradient-primary: linear-gradient(135deg, #8A2BE2, #6A1E9E);
        }
        
        .order-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-bolt"></i> LKSBoost
            </a>
            <a href="catalog.php" class="btn btn-outline-primary">Retour</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="order-card p-4">
                    <h2 class="mb-4">Commander: <?php echo htmlspecialchars($service['name']); ?></h2>
                    
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Lien du contenu</label>
                            <input type="url" name="link" class="form-control" 
                                   placeholder="https://www.tiktok.com/@username/video/..." required>
                            <small class="text-muted">Collez le lien direct de votre vidéo/post</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Quantité</label>
                            <input type="number" name="quantity" id="quantity" 
                                   class="form-control"
                                   min="<?php echo $service['min_amount']; ?>"
                                   max="<?php echo $service['max_amount']; ?>"
                                   value="<?php echo $service['min_amount']; ?>" required>
                            <small class="text-muted">
                                Min: <?php echo number_format($service['min_amount']); ?> | 
                                Max: <?php echo number_format($service['max_amount']); ?>
                            </small>
                        </div>
                        
                        <div class="mb-4 p-3 bg-light rounded">
                            <h5>Récapitulatif</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Prix unitaire:</span>
                                <span id="unit-price"><?php echo CURRENCY . number_format($service['our_price'] / 1000, 4); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Quantité:</span>
                                <span id="quantity-display"><?php echo number_format($service['min_amount']); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span id="total-price"><?php echo CURRENCY . number_format(($service['our_price'] / 1000) * $service['min_amount'], 4); ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-lock me-2"></i>Procéder au paiement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const unitPrice = <?php echo $service['our_price'] / 1000; ?>;
        const minQty = <?php echo $service['min_amount']; ?>;
        const maxQty = <?php echo $service['max_amount']; ?>;
        const currency = '<?php echo CURRENCY; ?>';
        
        const quantityInput = document.getElementById('quantity');
        const quantityDisplay = document.getElementById('quantity-display');
        const totalPriceElement = document.getElementById('total-price');
        const unitPriceElement = document.getElementById('unit-price');
        
        function updatePrice() {
            const quantity = parseInt(quantityInput.value) || minQty;
            const total = unitPrice * quantity;
            
            quantityDisplay.textContent = quantity.toLocaleString();
            totalPriceElement.textContent = currency + total.toFixed(4);
            unitPriceElement.textContent = currency + (unitPrice * 1000).toFixed(4) + ' / 1000';
        }
        
        quantityInput.addEventListener('input', updatePrice);
        updatePrice(); // Initialisation
    </script>
</body>
</html>