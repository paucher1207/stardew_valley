<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Venta.php';

$database = new Database();
$db = $database->getConnection();
$venta = new Venta($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $stmt = $venta->read();
        $ventas_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($ventas_arr, $row);
        }
        echo json_encode($ventas_arr);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id_producto) && !empty($data->fecha) && !empty($data->cantidad)) {
            
            $venta->id_producto = $data->id_producto;
            $venta->fecha = $data->fecha;
            $venta->cantidad = $data->cantidad;

            try {
                if($venta->create()) {
                    http_response_code(201);
                    echo json_encode(array("message" => "Venta registrada correctamente"));
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(array("message" => $e->getMessage()));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
        }
        break;
}
?>