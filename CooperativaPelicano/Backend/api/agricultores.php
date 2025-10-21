<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Agricultor.php';

$database = new Database();
$db = $database->getConnection();
$agricultor = new Agricultor($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $stmt = $agricultor->read();
        $num = $stmt->rowCount();

        if($num > 0) {
            $agricultores_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($agricultores_arr, $row);
            }
            echo json_encode($agricultores_arr);
        } else {
            echo json_encode(array());
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->nombre) && !empty($data->granja) && !empty($data->correo)) {
            $agricultor->nombre = $data->nombre;
            $agricultor->granja = $data->granja;
            $agricultor->correo = $data->correo;

            if($agricultor->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Agricultor creado correctamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear el agricultor"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id) && !empty($data->nombre) && !empty($data->granja) && !empty($data->correo)) {
            $agricultor->id = $data->id;
            $agricultor->nombre = $data->nombre;
            $agricultor->granja = $data->granja;
            $agricultor->correo = $data->correo;

            if($agricultor->update()) {
                echo json_encode(array("message" => "Agricultor actualizado correctamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar el agricultor"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos"));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            $agricultor->id = $data->id;

            if($agricultor->delete()) {
                echo json_encode(array("message" => "Agricultor eliminado correctamente"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar el agricultor"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID no proporcionado"));
        }
        break;
}
?>