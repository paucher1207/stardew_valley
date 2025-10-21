<?php
class Venta {
    private $conn;
    private $table_name = "ventas";

    public $id;
    public $id_producto;
    public $fecha;
    public $cantidad;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT v.*, p.nombre as producto_nombre, p.precio, 
                         a.nombre as agricultor_nombre, a.granja 
                  FROM " . $this->table_name . " v 
                  LEFT JOIN productos p ON v.id_producto = p.id 
                  LEFT JOIN agricultores a ON p.id_agricultor = a.id 
                  ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        // Iniciar transacción
        $this->conn->beginTransaction();

        try {
            // 1. Verificar stock disponible
            $query_stock = "SELECT stock FROM productos WHERE id = ? FOR UPDATE";
            $stmt_stock = $this->conn->prepare($query_stock);
            $stmt_stock->bindParam(1, $this->id_producto);
            $stmt_stock->execute();
            
            $producto = $stmt_stock->fetch(PDO::FETCH_ASSOC);
            
            if(!$producto) {
                throw new Exception("Producto no encontrado");
            }

            if($producto['stock'] < $this->cantidad) {
                throw new Exception("Stock insuficiente. Stock disponible: " . $producto['stock']);
            }

            // 2. Insertar venta
            $query_venta = "INSERT INTO " . $this->table_name . " 
                           SET id_producto=:id_producto, fecha=:fecha, cantidad=:cantidad";
            
            $stmt_venta = $this->conn->prepare($query_venta);

            // Limpiar datos
            $this->id_producto = filter_var($this->id_producto, FILTER_SANITIZE_NUMBER_INT);
            $this->fecha = htmlspecialchars(strip_tags(trim($this->fecha)));
            $this->cantidad = filter_var($this->cantidad, FILTER_SANITIZE_NUMBER_INT);

            $stmt_venta->bindParam(":id_producto", $this->id_producto);
            $stmt_venta->bindParam(":fecha", $this->fecha);
            $stmt_venta->bindParam(":cantidad", $this->cantidad);

            if(!$stmt_venta->execute()) {
                throw new Exception("Error al registrar la venta");
            }

            // 3. Actualizar stock
            $nuevo_stock = $producto['stock'] - $this->cantidad;
            $query_update = "UPDATE productos SET stock = ? WHERE id = ?";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(1, $nuevo_stock);
            $stmt_update->bindParam(2, $this->id_producto);

            if(!$stmt_update->execute()) {
                throw new Exception("Error al actualizar el stock");
            }

            // Confirmar transacción
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getStats() {
        $stats = [];

        // Ventas por agricultor
        $query1 = "SELECT a.nombre, a.granja, SUM(v.cantidad * p.precio) as total_ventas
                   FROM ventas v
                   JOIN productos p ON v.id_producto = p.id
                   JOIN agricultores a ON p.id_agricultor = a.id
                   GROUP BY a.id, a.nombre, a.granja";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->execute();
        $stats['ventas_por_agricultor'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        // Productos por tipo
        $query2 = "SELECT tipo, COUNT(*) as cantidad 
                   FROM productos 
                   GROUP BY tipo";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->execute();
        $stats['productos_por_tipo'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Ventas del mes actual
        $query3 = "SELECT SUM(v.cantidad * p.precio) as total_mes
                   FROM ventas v
                   JOIN productos p ON v.id_producto = p.id
                   WHERE MONTH(v.fecha) = MONTH(CURRENT_DATE()) 
                   AND YEAR(v.fecha) = YEAR(CURRENT_DATE())";
        $stmt3 = $this->conn->prepare($query3);
        $stmt3->execute();
        $stats['ventas_mes_actual'] = $stmt3->fetch(PDO::FETCH_ASSOC);

        // Productos más vendidos
        $query4 = "SELECT p.nombre, SUM(v.cantidad) as total_vendido
                   FROM ventas v
                   JOIN productos p ON v.id_producto = p.id
                   GROUP BY p.id, p.nombre
                   ORDER BY total_vendido DESC
                   LIMIT 5";
        $stmt4 = $this->conn->prepare($query4);
        $stmt4->execute();
        $stats['productos_mas_vendidos'] = $stmt4->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }
}
?>