<?php
class Producto {
    private $conn;
    private $table_name = "productos";

    public $id;
    public $nombre;
    public $tipo;
    public $precio;
    public $stock;
    public $id_agricultor;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT p.*, a.nombre as agricultor_nombre, a.granja 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN agricultores a ON p.id_agricultor = a.id 
                  ORDER BY p.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET nombre=:nombre, tipo=:tipo, precio=:precio, stock=:stock, id_agricultor=:id_agricultor";
        
        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags(trim($this->nombre)));
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        $this->precio = filter_var($this->precio, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->stock = filter_var($this->stock, FILTER_SANITIZE_NUMBER_INT);
        $this->id_agricultor = filter_var($this->id_agricultor, FILTER_SANITIZE_NUMBER_INT);

        // Vincular valores
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":id_agricultor", $this->id_agricultor);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET nombre=:nombre, tipo=:tipo, precio=:precio, stock=:stock, id_agricultor=:id_agricultor 
                 WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags(trim($this->nombre)));
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        $this->precio = filter_var($this->precio, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->stock = filter_var($this->stock, FILTER_SANITIZE_NUMBER_INT);
        $this->id_agricultor = filter_var($this->id_agricultor, FILTER_SANITIZE_NUMBER_INT);
        $this->id = filter_var($this->id, FILTER_SANITIZE_NUMBER_INT);

        // Vincular valores
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":id_agricultor", $this->id_agricultor);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $this->id = filter_var($this->id, FILTER_SANITIZE_NUMBER_INT);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getByAgricultor($id_agricultor) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_agricultor = ?";
        $stmt = $this->conn->prepare($query);
        $id_agricultor = filter_var($id_agricultor, FILTER_SANITIZE_NUMBER_INT);
        $stmt->bindParam(1, $id_agricultor);
        $stmt->execute();
        return $stmt;
    }

    public function updateStock($id, $nuevo_stock) {
        $query = "UPDATE " . $this->table_name . " SET stock = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $nuevo_stock = filter_var($nuevo_stock, FILTER_SANITIZE_NUMBER_INT);
        $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        $stmt->bindParam(1, $nuevo_stock);
        $stmt->bindParam(2, $id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>