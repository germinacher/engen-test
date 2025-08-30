<?php
include "db.php";
require __DIR__ . '/vendor/autoload.php';
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\Client\PreApprovalPlan\PreApprovalPlanClient;

// Configurar token de acceso (lee de variable de entorno MP_ACCESS_TOKEN si está disponible)
$mpToken = getenv('MP_ACCESS_TOKEN') ?: "APP_USR-7374749047926419-083007-9b5acd96d25205c04d822cb8dabdb134-2655556529";
MercadoPagoConfig::setAccessToken($mpToken);
// Control del entorno y base URL desde variables de entorno (útil para ngrok)
$mpEnv = getenv('MP_ENV') ?: null; // use 'local' to allow localhost tests
if ($mpEnv === 'local') {
    MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
}
$baseUrl = getenv('MP_BASE_URL') ?: 'http://localhost/engen';

$nombre = $_POST['nombre'];
$email  = $_POST['email'];

$stmt = $conn->prepare("INSERT INTO clientes (nombre, email, estado) VALUES (?, ?, 'pendiente')");
$stmt->bind_param("ss", $nombre, $email);
$stmt->execute();
$cliente_id = $stmt->insert_id;

// Opción B: crear un plan (preapproval_plan) y redirigir al init_point del plan
$planClient = new PreApprovalPlanClient();
$planRequest = [
    "back_url" => $baseUrl,
    "reason" => "Plan Suscripción ENGEN",
    "external_reference" => "plan_cliente_$cliente_id",
    "auto_recurring" => [
        "frequency" => 1,
        "frequency_type" => "months",
        "repetitions" => 12,
        "transaction_amount" => 100,
        "currency_id" => "ARS"
    ]
];

try {
    $plan = $planClient->create($planRequest);
} catch (\MercadoPago\Exceptions\MPApiException $e) {
    // Mostrar detalles útiles para depuración
    $apiResponse = $e->getApiResponse();
    $status = $apiResponse ? $apiResponse->getStatusCode() : 'N/A';
    $content = $apiResponse ? $apiResponse->getContent() : $e->getMessage();
    // Puedes registrar $status y $content en un log; aquí mostramos en pantalla
    header('Content-Type: application/json');
    echo json_encode(["error" => true, "status" => $status, "content" => $content]);
    exit;
} catch (\Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["error" => true, "message" => $e->getMessage()]);
    exit;
}

// Guardar el plan id en la tabla clientes (campo mp_subscription_id)
$plan_id = $plan->id ?? null;
$stmt = $conn->prepare("UPDATE clientes SET mp_subscription_id=? WHERE id=?");
$stmt->bind_param("si", $plan_id, $cliente_id);
$stmt->execute();

// Mostrar formulario para tokenizar la tarjeta en el frontend y enviar card_token_id
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Completar suscripción</title>
    <style>body{font-family:Segoe UI,Arial;margin:2rem}label{display:block;margin-top:1rem}</style>
</head>
<body>
    <h1>Completar suscripción</h1>
    <p>Se creó el plan con ID: <strong><?= htmlspecialchars($plan_id) ?></strong></p>
    <p>Ahora tokenizá la tarjeta en el frontend con Mercado Pago y pegá el <em>card_token_id</em> abajo (esto es un ejemplo mínimo).
    Idealmente integrar Mercado Pago JS para generar automáticamente el token y enviarlo al backend.</p>

        <!-- Formulario que tokeniza tarjeta usando Mercado Pago JS SDK v2 -->
        <form id="form-checkout" action="create_subscription.php" method="POST">
            <input type="hidden" name="cliente_id" value="<?= htmlspecialchars($cliente_id) ?>">
            <input type="hidden" name="plan_id" value="<?= htmlspecialchars($plan_id) ?>">
            <input type="hidden" id="card_token_id" name="card_token_id" />

            <label>Nombre del titular
                <input id="form-checkout__cardholderName" type="text" required style="width:100%;padding:8px;margin-top:6px">
            </label>

            <label>Número de tarjeta
                <input id="form-checkout__cardNumber" type="text" inputmode="numeric" autocomplete="cc-number" required style="width:100%;padding:8px;margin-top:6px">
            </label>

            <label>Fecha de expiración (MM/YY)
                <input id="form-checkout__expiryDate" type="text" placeholder="MM/YY" required style="width:100%;padding:8px;margin-top:6px">
            </label>

            <label>CVV
                <input id="form-checkout__securityCode" type="text" inputmode="numeric" required style="width:100%;padding:8px;margin-top:6px">
            </label>

            <label>Email del pagador
                <input id="form-checkout__email" type="email" name="payer_email" required style="width:100%;padding:8px;margin-top:6px">
            </label>

            <button id="form-checkout__submit" type="submit" style="margin-top:1rem;padding:10px 16px">Pagar y crear suscripción</button>
        </form>

        <script src="https://sdk.mercadopago.com/js/v2"></script>
        <script>
            (function(){
                const publicKey = "<?= htmlspecialchars(getenv('MP_PUBLIC_KEY') ?: 'MP_PUBLIC_KEY_HERE') ?>";
                if (!publicKey || publicKey === 'MP_PUBLIC_KEY_HERE') {
                    console.warn('MP_PUBLIC_KEY no configurada. Define MP_PUBLIC_KEY en las variables de entorno.');
                }
                const mp = new MercadoPago(publicKey, {locale: 'es-AR'});

                const cardForm = mp.cardForm({
                    amount: '100.00',
                    autoMount: false,
                    form: {
                        id: 'form-checkout',
                        cardholderName: {
                            id: 'form-checkout__cardholderName',
                            placeholder: 'Titular'
                        },
                        cardNumber: {
                            id: 'form-checkout__cardNumber',
                            placeholder: 'Número'
                        },
                        expiryDate: {
                            id: 'form-checkout__expiryDate',
                            placeholder: 'MM/YY'
                        },
                        securityCode: {
                            id: 'form-checkout__securityCode',
                            placeholder: 'CVV'
                        },
                        email: {
                            id: 'form-checkout__email',
                            placeholder: 'email@ejemplo.com'
                        }
                    },
                    callbacks: {
                        onFormMounted: error => {
                            if (error) console.warn('onFormMounted error', error);
                            cardForm.mount();
                        },
                        onSubmit: event => {
                            event.preventDefault();
                            // Crear token y luego enviar el formulario
                            cardForm.createCardToken().then(function(cardToken){
                                if (cardToken && cardToken.id) {
                                    document.getElementById('card_token_id').value = cardToken.id;
                                    document.getElementById('form-checkout').submit();
                                } else {
                                    alert('No se pudo generar el token de la tarjeta.');
                                }
                            }).catch(function(err){
                                console.error('createCardToken error', err);
                                alert('Error al tokenizar la tarjeta. Ver consola.');
                            });
                        }
                    }
                });
            })();
        </script>

    <hr>
    <h3>Ejemplo (integración recomendada)</h3>
    <p>Incluir en tu HTML el SDK de Mercado Pago y usarlo para generar el token y enviarlo automáticamente al formulario anterior.</p>
    <pre style="background:#f5f5f5;padding:10px">&lt;script src="https://sdk.mercadopago.com/js/v2"&gt;&lt;/script&gt;
// Inicializar con tu public key
// const mp = new MercadoPago('MP_PUBLIC_KEY');
// Generar token y asignar al campo card_token_id antes de enviar el formulario
    </pre>
</body>
</html>
<?php
exit;
?>