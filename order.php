<?php
require_once 'includes/header.php';

// Récupérer le service sélectionné
$service_id = isset($_GET['service']) ? intval($_GET['service']) : 0;
$service = getServiceById($service_id);

if (!$service) {
    header('Location: catalog.php');
    exit;
}

// Traitement du formulaire
if ($_POST && isset($_POST['place_order'])) {
    $link = trim($_POST['link']);
    $quantity = intval($_POST['quantity']);
    
    // Validation
    $errors = [];
    if (empty($link)) {
        $errors[] = "Veuillez saisir un lien";
    }
    if ($quantity < $service['min_amount'] || $quantity > $service['max_amount']) {
        $errors[] = "La quantité doit être entre " . $service['min_amount'] . " et " . $service['max_amount'];
    }
    
    if (empty($errors)) {
        // Calculer le prix total
        $price_per_unit = $service['our_price'] / 1000;
        $total_price = $price_per_unit * $quantity;
        
        // Créer la commande en base
        $order_id = createOrder($service_id, $link, $quantity, $total_price);
        
        if ($order_id) {
            // Rediriger vers la page de paiement
            header('Location: payment.php?order=' . $order_id);
            exit;
        } else {
            $errors[] = "Erreur lors de la création de la commande";
        }
    }
}
?>

<div class="container">
    <h1>Passer une commande</h1>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Détails du service</h3>
                </div>
                <div class="card-body">
                    <h4><?= htmlspecialchars($service['name']) ?></h4>
                    <p><strong>Prix:</strong> <?= CURRENCY . $service['our_price'] ?> / 1000</p>
                    <p><strong>Quantité minimum:</strong> <?= $service['min_amount'] ?></p>
                    <p><strong>Quantité maximum:</strong> <?= $service['max_amount'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Informations de commande</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="link" class="form-label">Lien / Username</label>
                            <input type="text" class="form-control" id="link" name="link" 
                                   value="<?= isset($_POST['link']) ? htmlspecialchars($_POST['link']) : '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantité</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   min="<?= $service['min_amount'] ?>" max="<?= $service['max_amount'] ?>"
                                   value="<?= isset($_POST['quantity']) ? $_POST['quantity'] : $service['min_amount'] ?>" required>
                            <div class="form-text">
                                Min: <?= $service['min_amount'] ?> - Max: <?= $service['max_amount'] ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Prix total</label>
                            <div class="total-price h4 text-primary" id="total-price">
                                <?= CURRENCY ?>0.00
                            </div>
                        </div>
                        
                        <button type="submit" name="place_order" class="btn btn-primary btn-lg w-100">
                            Procéder au paiement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calcul du prix en temps réel
const quantityInput = document.getElementById('quantity');
const totalPriceElement = document.getElementById('total-price');
const pricePerUnit = <?= $service['our_price'] / 1000 ?>;

function updateTotalPrice() {
    const quantity = parseInt(quantityInput.value) || 0;
    const total = (pricePerUnit * quantity).toFixed(2);
    totalPriceElement.textContent = '<?= CURRENCY ?>' + total;
}

quantityInput.addEventListener('input', updateTotalPrice);
// Calcul initial
updateTotalPrice();
</script>

<?php
require_once 'includes/footer.php';
?>