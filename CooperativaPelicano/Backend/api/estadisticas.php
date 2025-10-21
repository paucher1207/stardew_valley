<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Venta.php';

$database = new Database();
$db = $database->getConnection();
$venta = new Venta($db);

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $stats = $venta->getStats();
        echo json_encode($stats);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error al obtener estadísticas: " . $e->getMessage()));
    }
}
?>