<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Producto.php';

$database = new Database();
$db = $database->getConnection();
$producto = new Producto($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $stmt = $producto->read();
        $productos_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($productos_arr, $row);
        }
        echo json_encode($productos_arr);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->nombre) && !empty($data->tipo) && !empty($data->precio) && 
           !empty($data->stock) && !empty($data->id_agricultor)) {
            
            $producto->nombre = $data->nombre;
            $producto->tipo = $data->tipo;
            $producto->precio = $data->precio;
            $producto->stock = $data->stock;
            $producto->id_agricultor = $data->id_agricultor;

            if($producto->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Producto creado correctamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear el producto"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id) && !empty($data->nombre) && !empty($data->tipo) && 
           !empty($data->precio) && !empty($data->stock) && !empty($data->id_agricultor)) {
            
            $producto->id = $data->id;
            $producto->nombre = $data->nombre;
            $producto->tipo = $data->tipo;
            $producto->precio = $data->precio;
            $producto->stock = $data->stock;
            $producto->id_agricultor = $data->id_agricultor;

            if($producto->update()) {
                echo json_encode(array("message" => "Producto actualizado correctamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar el producto"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            $producto->id = $data->id;

            if($producto->delete()) {
                echo json_encode(array("message" => "Producto eliminado correctamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar el producto"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID no proporcionado"));
        }
        break;
}
?>