<?php
// payment.php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Récupérer la commande
$order_number = $_GET['order'] ?? '';
$order = getOrderByNumber($order_number);

if (!$order || $order['status'] !== 'pending') {
    header('Location: catalog.php');
    exit();
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
    <title>Paiement - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover,
        .payment-method.active {
            border-color: #8A2BE2;
            background-color: rgba(138, 43, 226, 0.05);
        }
        
        .StripeElement {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 10px;
            background: white;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="text-center mb-5">Paiement Sécurisé</h1>
                
                <!-- Résumé de commande -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>Commande #<?php echo $order['order_number']; ?></h5>
                        <p><strong>Service:</strong> <?php echo $order['service_name']; ?></p>
                        <p><strong>Montant:</strong> <?php echo $order['currency'] . number_format($order['price'], 4); ?></p>
                    </div>
                </div>
                
                <!-- Méthodes de paiement -->
                <form id="payment-form" action="process_payment.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    
                    <!-- Choix de la méthode -->
                    <div class="mb-4">
                        <h5>Choisissez votre moyen de paiement:</h5>
                        
                        <div class="payment-method active" onclick="selectPaymentMethod('stripe')">
                            <input type="radio" name="payment_method" value="stripe" checked hidden>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fab fa-cc-stripe fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Carte Bancaire (Stripe)</h6>
                                    <p class="mb-0 text-muted">Visa, Mastercard, American Express</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="payment-method" onclick="selectPaymentMethod('paypal')">
                            <input type="radio" name="payment_method" value="paypal" hidden>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fab fa-paypal fa-2x" style="color: #003087;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">PayPal</h6>
                                    <p class="mb-0 text-muted">Payer avec votre compte PayPal</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulaire Stripe -->
                    <div id="stripe-form" class="payment-form">
                        <div class="mb-3">
                            <label>Informations de carte</label>
                            <div id="card-element" class="form-control"></div>
                            <div id="card-errors" class="text-danger mt-2"></div>
                        </div>
                    </div>
                    
                    <!-- Bouton de paiement -->
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-lock me-2"></i>
                        Payer <?php echo $order['currency'] . number_format($order['price'], 4); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Configuration Stripe
        const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');
        const elements = stripe.elements();
        const cardElement = elements.create('card');
        cardElement.mount('#card-element');
        
        // Gestion des erreurs
        cardElement.addEventListener('change', function(event) {
            const displayError = document.getElementById('card-errors');
            displayError.textContent = event.error ? event.error.message : '';
        });
        
        // Sélection de la méthode de paiement
        function selectPaymentMethod(method) {
            // Mettre à jour le radio button
            document.querySelector(`input[name="payment_method"][value="${method}"]`).checked = true;
            
            // Mettre à jour l'apparence
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }
        
        // Soumission du formulaire
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            if (paymentMethod === 'stripe') {
                // Traitement Stripe
                const {paymentMethod, error} = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                });
                
                if (error) {
                    document.getElementById('card-errors').textContent = error.message;
                    return;
                }
                
                // Ajouter le paymentMethod.id au formulaire
                const paymentMethodId = document.createElement('input');
                paymentMethodId.type = 'hidden';
                paymentMethodId.name = 'stripe_payment_method_id';
                paymentMethodId.value = paymentMethod.id;
                form.appendChild(paymentMethodId);
                
                // Soumettre le formulaire
                form.submit();
            } else {
                // Pour PayPal, redirection via le backend
                form.submit();
            }
        });
    </script>
</body>
</html>