<?php
require_once 'includes/header.php';

$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;
$order = getOrderById($order_id);

if (!$order) {
    header('Location: catalog.php');
    exit;
}

// Traitement du paiement
if ($_POST && isset($_POST['process_payment'])) {
    $payment_method = $_POST['payment_method'];
    
    // Traiter le paiement selon la méthode choisie
    if (processPayment($order_id, $payment_method, $order['price'])) {
        // Si paiement réussi, envoyer la commande à Exobooster
        $exobooster_response = sendOrderToExobooster($order);
        
        if ($exobooster_response && isset($exobooster_response['order'])) {
            // Mettre à jour la commande avec l'ID Exobooster
            updateOrderWithExoboosterId($order_id, $exobooster_response['order']);
            
            // Rediriger vers la page de confirmation
            header('Location: status.php?order=' . $order['order_number']);
            exit;
        } else {
            $error = "Erreur lors de l'envoi de la commande à Exobooster";
        }
    } else {
        $error = "Erreur lors du traitement du paiement";
    }
}
?>

<div class="container">
    <h1>Paiement</h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Récapitulatif de commande</h3>
                </div>
                <div class="card-body">
                    <p><strong>Service:</strong> <?= htmlspecialchars($order['service_name']) ?></p>
                    <p><strong>Lien:</strong> <?= htmlspecialchars($order['link']) ?></p>
                    <p><strong>Quantité:</strong> <?= $order['quantity'] ?></p>
                    <p><strong>Prix total:</strong> <?= CURRENCY . $order['price'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Méthode de paiement</h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="payment-form">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="stripe" value="stripe" checked>
                                <label class="form-check-label" for="stripe">
                                    <i class="fab fa-cc-stripe"></i> Carte Bancaire (Stripe)
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="paypal" value="paypal">
                                <label class="form-check-label" for="paypal">
                                    <i class="fab fa-paypal"></i> PayPal
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="mobile_money" value="mobile_money">
                                <label class="form-check-label" for="mobile_money">
                                    <i class="fas fa-mobile-alt"></i> Mobile Money
                                </label>
                            </div>
                        </div>
                        
                        <!-- Section Stripe (affichée par défaut) -->
                        <div id="stripe-section" class="payment-section">
                            <div class="mb-3">
                                <label for="card-element" class="form-label">
                                    Informations de la carte
                                </label>
                                <div id="card-element" class="form-control">
                                    <!-- Stripe Elements s'affichera ici -->
                                </div>
                                <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                            </div>
                        </div>
                        
                        <!-- Section PayPal (cachée par défaut) -->
                        <div id="paypal-section" class="payment-section" style="display: none;">
                            <p>Vous serez redirigé vers PayPal pour compléter votre paiement.</p>
                        </div>
                        
                        <!-- Section Mobile Money (cachée par défaut) -->
                        <div id="mobile_money-section" class="payment-section" style="display: none;">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Numéro de téléphone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="mb-3">
                                <label for="operator" class="form-label">Opérateur</label>
                                <select class="form-control" id="operator" name="operator">
                                    <option value="orange">Orange Money</option>
                                    <option value="mtn">MTN Mobile Money</option>
                                    <option value="moov">Moov Money</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" name="process_payment" class="btn btn-success btn-lg w-100">
                            Payer <?= CURRENCY . $order['price'] ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
// Gestion de l'affichage des sections de paiement
const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
paymentMethods.forEach(method => {
    method.addEventListener('change', function() {
        // Cacher toutes les sections
        document.querySelectorAll('.payment-section').forEach(section => {
            section.style.display = 'none';
        });
        
        // Afficher la section correspondante
        const sectionId = this.value + '-section';
        document.getElementById(sectionId).style.display = 'block';
    });
});

// Configuration Stripe
const stripe = Stripe('<?= STRIPE_PUBLIC_KEY ?>');
const elements = stripe.elements();
const cardElement = elements.create('card');
cardElement.mount('#card-element');

// Gestion des erreurs de carte
cardElement.addEventListener('change', function(event) {
    const displayError = document.getElementById('card-errors');
    if (event.error) {
        displayError.textContent = event.error.message;
    } else {
        displayError.textContent = '';
    }
});
</script>

<?php
require_once 'includes/footer.php';
?>