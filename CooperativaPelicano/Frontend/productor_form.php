<?php
require_once 'config.php';

// 1. Inicialización y Carga de Datos
$id = $_GET['id'] ?? null;
$producto = ['nombre' => '', 'tipo' => '', 'precio' => '', 'stock' => '', 'id_agricultor' => ''];
$agricultores = [];

// Obtener la lista de agricultores para el SELECT (Usabilidad)
try {
    $stmt = $pdo->prepare("SELECT id, nombre, granja FROM agricultores ORDER BY nombre");
    $stmt->execute();
    $agricultores = $stmt->fetchAll();
} catch (PDOException $e) {
    $feedback_error = "Error al cargar agricultores: " . $e->getMessage();
}

// Cargar datos si es modo EDICIÓN (UPDATE)
if ($id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $producto = $stmt->fetch() ?: $producto;
        if (!$producto['id']) {
            $feedback_error = "Producto no encontrado.";
            $id = null; // Cambiar a modo creación si no existe
        }
    } catch (PDOException $e) {
        $feedback_error = "Error al cargar el producto: " . $e->getMessage();
    }
}

// 2. Procesamiento del Formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // a. Limpiar Entradas (trim, filter_var)
    $producto['nombre'] = trim($_POST['nombre'] ?? '');
    $producto['tipo'] = trim($_POST['tipo'] ?? '');
    $producto['precio'] = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $producto['stock'] = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);
    $producto['id_agricultor'] = filter_var($_POST['id_agricultor'] ?? '', FILTER_VALIDATE_INT);
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); // Para saber si es UPDATE

    $errors = [];

    // b. Validaciones Cliente/Servidor (Todos los campos required, positivos)
    if (empty($producto['nombre']) || empty($producto['tipo'])) $errors[] = "Nombre y Tipo son requeridos.";
    if ($producto['precio'] === false || $producto['precio'] <= 0) $errors[] = "Precio debe ser un número positivo (ej: 3.50).";
    if ($producto['stock'] === false || $producto['stock'] < 0) $errors[] = "Stock debe ser un número entero no negativo.";
    if ($producto['id_agricultor'] === false || $producto['id_agricultor'] <= 0) $errors[] = "Debe seleccionar un agricultor válido.";
    
    // c. Validación de Coherencia: Comprobar existencia del Agricultor (FK check)
    if ($producto['id_agricultor'] > 0) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM agricultores WHERE id = ?");
            $stmt->execute([$producto['id_agricultor']]);
            if ($stmt->fetchColumn() == 0) {
                $errors[] = "El agricultor seleccionado no existe en la base de datos.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error de validación de agricultor: " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            // d. Usar Prepared Statements (INSERT o UPDATE)
            if ($id) {
                // UPDATE
                $sql = "UPDATE productos SET nombre = ?, tipo = ?, precio = ?, stock = ?, id_agricultor = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $producto['nombre'], $producto['tipo'], $producto['precio'], 
                    $producto['stock'], $producto['id_agricultor'], $id
                ]);
                $feedback_success = "Producto actualizado con éxito.";
            } else {
                // CREATE
                $sql = "INSERT INTO productos (nombre, tipo, precio, stock, id_agricultor) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $producto['nombre'], $producto['tipo'], $producto['precio'], 
                    $producto['stock'], $producto['id_agricultor']
                ]);
                $feedback_success = "Producto registrado con éxito. ID: " . $pdo->lastInsertId();
                // Opcional: Limpiar campos tras éxito en CREATE
                $producto = ['nombre' => '', 'tipo' => '', 'precio' => '', 'stock' => '', 'id_agricultor' => ''];
            }
        } catch (PDOException $e) {
            $feedback_error = "Error al guardar el producto: " . $e->getMessage();
        }
    } else {
        $feedback_error = "Errores de validación: <br>" . implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title><?= $id ? 'Editar' : 'Crear' ?> Producto</title>
    <style>
        /* Estilos básicos como en index.php */
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        form div { margin-bottom: 15px; }
        label { display: block; font-weight: bold; }
        input[type="text"], input[type="number"], select { width: 300px; padding: 8px; }
    </style>
</head>
<body>

    <h1><?= $id ? ' Editar Producto' : ' Registrar Producto' ?></h1>
    
    <?php if ($feedback_success): ?>
        <p class="success"><?= escape($feedback_success) ?></p>
    <?php endif; ?>
    <?php if ($feedback_error): ?>
        <p class="error"><?= $feedback_error ?></p> <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id" value="<?= escape($id) ?>">
        
        <div>
            <label for="nombre">Nombre del Producto*:</label>
            <input type="text" id="nombre" name="nombre" required 
                   value="<?= escape($producto['nombre']) ?>" placeholder="Ej: Manzana Roja">
        </div>
        
        <div>
            <label for="tipo">Tipo de Producto*:</label>
            <input type="text" id="tipo" name="tipo" required
                   value="<?= escape($producto['tipo']) ?>" placeholder="Ej: Fruta">
        </div>
        
        <div>
            <label for="precio">Precio por Unidad (€)*:</label>
            <input type="number" id="precio" name="precio" required min="0.01" step="0.01"
                   value="<?= escape($producto['precio'] ?: '') ?>" placeholder="Precio: 3.50">
        </div>
        
        <div>
            <label for="stock">Stock Inicial (Unidades)*:</label>
            <input type="number" id="stock" name="stock" required min="0" step="1"
                   value="<?= escape($producto['stock'] ?: '') ?>" placeholder="Stock: 150">
        </div>
        
        <div>
            <label for="id_agricultor">Agricultor*:</label>
            <select id="id_agricultor" name="id_agricultor" required>
                <option value="">-- Seleccione un Agricultor --</option>
                <?php foreach ($agricultores as $agricultor): ?>
                    <option value="<?= escape($agricultor['id']) ?>" 
                        <?= (int)$producto['id_agricultor'] === (int)$agricultor['id'] ? 'selected' : '' ?>>
                        <?= escape($agricultor['nombre']) ?> - <?= escape($agricultor['granja']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit"><?= $id ? 'Actualizar Producto' : 'Registrar Producto' ?></button>
        <a href="index.php">Cancelar</a>
    </form>

</body>
</html> 