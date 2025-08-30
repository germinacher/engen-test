<?php
include "db.php";
require __DIR__ . '/vendor/autoload.php';
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\PreApproval\PreApprovalClient;

// Configurar token de acceso (lee de variable de entorno MP_ACCESS_TOKEN si está disponible)
$mpToken = getenv('MP_ACCESS_TOKEN') ?: "APP_USR-7374749047926419-083007-9b5acd96d25205c04d822cb8dabdb134-2655556529";
MercadoPagoConfig::setAccessToken($mpToken);

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data["type"]) && $data["type"] === "preapproval") {
    $preapproval_id = $data["data"]["id"];
    $client = new PreApprovalClient();
    try {
        $preapproval = $client->get($preapproval_id);

        if ($preapproval) {
            $estado = "pendiente";
            if ($preapproval->status == "authorized" || $preapproval->status == "active") {
                $estado = "activo";
            } elseif ($preapproval->status == "cancelled" || $preapproval->status == "canceled") {
                $estado = "cancelado";
            }

            $stmt = $conn->prepare("UPDATE clientes SET estado=? WHERE mp_subscription_id=?");
            $stmt->bind_param("ss", $estado, $preapproval_id);
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Registrar o manejar errores si es necesario
    }
}
http_response_code(200);
?>