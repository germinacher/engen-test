<?php
include "db.php";
require __DIR__ . '/vendor/autoload.php';
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\PreApproval\PreApprovalClient;

// Configurar token (desde entorno o fallback)
$mpToken = getenv('MP_ACCESS_TOKEN') ?: "APP_USR-7374749047926419-083007-9b5acd96d25205c04d822cb8dabdb134-2655556529";
MercadoPagoConfig::setAccessToken($mpToken);

$cliente_id = $_POST['cliente_id'] ?? null;
$plan_id = $_POST['plan_id'] ?? null;
$card_token = $_POST['card_token_id'] ?? null;

if (!$cliente_id || !$plan_id || !$card_token) {
    header('Content-Type: application/json');
    echo json_encode(["error" => true, "message" => "Faltan datos: cliente_id, plan_id o card_token_id"]);
    exit;
}

$client = new PreApprovalClient();

$subRequest = [
  "preapproval_plan_id" => $plan_id,
  "reason" => "Suscripción mensual ENGEN",
  "external_reference" => "cliente_" . $cliente_id,
  "payer_email" => null, // opcional si se crea con token de tarjeta
  "card_token_id" => $card_token,
  "status" => "authorized",
  "auto_recurring" => [
    "start_date" => date("c"),
    "end_date" => date("c", strtotime("+1 year")),
    "transaction_amount" => 100,
    "currency_id" => "ARS"
  ],
  "back_url" => getenv('MP_BASE_URL') ?: 'http://localhost/engen'
];

try {
    $subscription = $client->create($subRequest);
} catch (\MercadoPago\Exceptions\MPApiException $e) {
    $apiResponse = $e->getApiResponse();
    $status = $apiResponse ? $apiResponse->getStatusCode() : 'N/A';
    $content = $apiResponse ? $apiResponse->getContent() : $e->getMessage();
    header('Content-Type: application/json');
    echo json_encode(["error" => true, "status" => $status, "content" => $content]);
    exit;
} catch (\Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["error" => true, "message" => $e->getMessage()]);
    exit;
}

// Guardar el id de la suscripción en la DB
$mp_sub_id = $subscription->id ?? null;
$stmt = $conn->prepare("UPDATE clientes SET mp_subscription_id=?, estado='activo' WHERE id=?");
$stmt->bind_param("si", $mp_sub_id, $cliente_id);
$stmt->execute();

// Redirigir al success
header("Location: " . (getenv('MP_BASE_URL') ?: 'http://localhost/engen') . "/success.php?id=" . $cliente_id);
exit;
