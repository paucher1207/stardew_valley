// URLs de la API
const API_URL = {
    farmers: 'api/agricultores.php',
    products: 'api/productos.php',
    sales: 'api/ventas.php',
    stats: 'api/estadisticas.php'
};

// Estado global de la aplicación
let appState = {
    currentFarmerId: null,
    currentProductId: null,
    farmers: [],
    products: [],
    sales: []
};

// Funciones para gestionar pestañas
document.querySelectorAll('.nav-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        // Remover clase active de todas las pestañas y contenidos
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Agregar clase active a la pestaña y contenido seleccionados
        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');
        
        // Cargar datos específicos de la pestaña
        if (tab.dataset.tab === 'farmers') {
            loadFarmers();
        } else if (tab.dataset.tab === 'products') {
            loadProducts();
            loadFarmersForProducts();
        } else if (tab.dataset.tab === 'sales') {
            loadSales();
            loadProductsForSales();
        } else if (tab.dataset.tab === 'stats') {
            loadStats();
        }
    });
});

// Funciones para agricultores
async function loadFarmers() {
    try {
        showLoading('farmers-table', 'Cargando agricultores...');
        const response = await fetch(API_URL.farmers);
        if (!response.ok) throw new Error('Error al cargar agricultores');
        const farmers = await response.json();
        
        appState.farmers = farmers;
        
        const tbody = document.querySelector('#farmers-table tbody');
        tbody.innerHTML = '';
        
        if (farmers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No hay agricultores registrados</td></tr>';
            return;
        }
        
        farmers.forEach(farmer => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${farmer.id}</td>
                <td>${escapeHtml(farmer.nombre)}</td>
                <td>${escapeHtml(farmer.granja)}</td>
                <td>${escapeHtml(farmer.correo)}</td>
                <td class="actions">
                    <button class="btn btn-secondary" onclick="editFarmer(${farmer.id})">Editar</button>
                    <button class="btn btn-danger" onclick="deleteFarmer(${farmer.id})">Eliminar</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        showMessage('farmer-message', 'Error al cargar agricultores: ' + error.message, 'error');
    }
}

function editFarmer(id) {
    const farmer = appState.farmers.find(f => f.id === id);
    if (farmer) {
        document.getElementById('farmer-id').value = farmer.id;
        document.getElementById('farmer-name').value = farmer.nombre;
        document.getElementById('farmer-farm').value = farmer.granja;
        document.getElementById('farmer-email').value = farmer.correo;
        document.getElementById('farmer-submit').textContent = 'Actualizar Agricultor';
        document.getElementById('farmer-cancel').style.display = 'inline-block';
        appState.currentFarmerId = id;
    }
}

async function deleteFarmer(id) {
    if (!confirm('¿Está seguro de que desea eliminar este agricultor?')) return;
    
    try {
        const response = await fetch(API_URL.farmers, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al eliminar agricultor');
        }
        
        const result = await response.json();
        showMessage('farmer-message', result.message, 'success');
        loadFarmers();
        
        // Si estamos en la pestaña de productos, recargar también
        if (document.getElementById('products').classList.contains('active')) {
            loadProducts();
            loadFarmersForProducts();
        }
    } catch (error) {
        showMessage('farmer-message', error.message, 'error');
    }
}

document.getElementById('farmer-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const id = document.getElementById('farmer-id').value;
    const nombre = document.getElementById('farmer-name').value.trim();
    const granja = document.getElementById('farmer-farm').value.trim();
    const correo = document.getElementById('farmer-email').value.trim();
    
    // Validaciones
    let valid = true;
    clearErrors('farmer');
    
    if (!nombre) {
        showError('farmer-name-error', 'El nombre es requerido');
        valid = false;
    }
    
    if (!granja) {
        showError('farmer-farm-error', 'La granja es requerida');
        valid = false;
    }
    
    if (!correo) {
        showError('farmer-email-error', 'El correo es requerido');
        valid = false;
    } else if (!isValidEmail(correo)) {
        showError('farmer-email-error', 'El formato del correo no es válido');
        valid = false;
    }
    
    if (!valid) return;
    
    const farmerData = { nombre, granja, correo };
    if (id) farmerData.id = parseInt(id);
    
    // Deshabilitar botón y mostrar loading
    const submitBtn = document.getElementById('farmer-submit');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading"></span> Guardando...';
    
    try {
        const success = await saveFarmer(farmerData);
        if (success) {
            // Limpiar formulario
            this.reset();
            document.getElementById('farmer-id').value = '';
            document.getElementById('farmer-submit').textContent = 'Guardar Agricultor';
            document.getElementById('farmer-cancel').style.display = 'none';
            appState.currentFarmerId = null;
        }
    } finally {
        // Restaurar botón
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});

document.getElementById('farmer-cancel').addEventListener('click', function() {
    document.getElementById('farmer-form').reset();
    document.getElementById('farmer-id').value = '';
    document.getElementById('farmer-submit').textContent = 'Guardar Agricultor';
    this.style.display = 'none';
    clearErrors('farmer');
    appState.currentFarmerId = null;
});

async function saveFarmer(farmerData) {
    try {
        const url = farmerData.id ? API_URL.farmers : API_URL.farmers;
        const method = farmerData.id ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(farmerData)
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al guardar agricultor');
        }
        
        const result = await response.json();
        showMessage('farmer-message', result.message, 'success');
        loadFarmers();
        
        // Si estamos en la pestaña de productos, recargar también
        if (document.getElementById('products').classList.contains('active')) {
            loadFarmersForProducts();
        }
        
        return true;
    } catch (error) {
        showMessage('farmer-message', error.message, 'error');
        return false;
    }
}

// Funciones para productos
async function loadProducts() {
    try {
        showLoading('products-table', 'Cargando productos...');
        const response = await fetch(API_URL.products);
        if (!response.ok) throw new Error('Error al cargar productos');
        const products = await response.json();
        
        appState.products = products;
        
        const tbody = document.querySelector('#products-table tbody');
        tbody.innerHTML = '';
        
        if (products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No hay productos registrados</td></tr>';
            return;
        }
        
        products.forEach(product => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${product.id}</td>
                <td>${escapeHtml(product.nombre)}</td>
                <td>${escapeHtml(product.tipo)}</td>
                <td>$${parseFloat(product.precio).toFixed(2)}</td>
                <td>${product.stock}</td>
                <td>${escapeHtml(product.agricultor_nombre || '')} - ${escapeHtml(product.granja || '')}</td>
                <td class="actions">
                    <button class="btn btn-secondary" onclick="editProduct(${product.id})">Editar</button>
                    <button class="btn btn-danger" onclick="deleteProduct(${product.id})">Eliminar</button>
                    <button class="btn" onclick="sellProduct(${product.id})">Vender</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        showMessage('product-message', 'Error al cargar productos: ' + error.message, 'error');
    }
}

async function loadFarmersForProducts() {
    try {
        const response = await fetch(API_URL.farmers);
        if (!response.ok) throw new Error('Error al cargar agricultores');
        const farmers = await response.json();
        
        const select = document.getElementById('product-farmer');
        select.innerHTML = '<option value="">Seleccione un agricultor</option>';
        
        farmers.forEach(farmer => {
            const option = document.createElement('option');
            option.value = farmer.id;
            option.textContent = `${farmer.nombre} - ${farmer.granja}`;
            select.appendChild(option);
        });
        
        // Si estamos editando un producto, seleccionar el agricultor actual
        if (appState.currentProductId) {
            const product = appState.products.find(p => p.id === appState.currentProductId);
            if (product) {
                select.value = product.id_agricultor;
            }
        }
    } catch (error) {
        console.error('Error al cargar agricultores para productos:', error);
    }
}

function editProduct(id) {
    const product = appState.products.find(p => p.id === id);
    if (product) {
        document.getElementById('product-id').value = product.id;
        document.getElementById('product-name').value = product.nombre;
        document.getElementById('product-type').value = product.tipo;
        document.getElementById('product-price').value = product.precio;
        document.getElementById('product-stock').value = product.stock;
        
        // Cargar agricultores y luego establecer el valor
        loadFarmersForProducts().then(() => {
            document.getElementById('product-farmer').value = product.id_agricultor;
        });
        
        document.getElementById('product-submit').textContent = 'Actualizar Producto';
        document.getElementById('product-cancel').style.display = 'inline-block';
        appState.currentProductId = id;
    }
}

async function deleteProduct(id) {
    if (!confirm('¿Está seguro de que desea eliminar este producto?')) return;
    
    try {
        const response = await fetch(API_URL.products, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al eliminar producto');
        }
        
        const result = await response.json();
        showMessage('product-message', result.message, 'success');
        loadProducts();
        
        // Si estamos en la pestaña de ventas, recargar también
        if (document.getElementById('sales').classList.contains('active')) {
            loadProductsForSales();
        }
    } catch (error) {
        showMessage('product-message', error.message, 'error');
    }
}

function sellProduct(id) {
    // Cambiar a la pestaña de ventas
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    document.querySelector('[data-tab="sales"]').classList.add('active');
    document.getElementById('sales').classList.add('active');
    
    // Seleccionar el producto
    document.getElementById('sale-product').value = id;
    
    // Cargar productos para ventas
    loadProductsForSales();
}

document.getElementById('product-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const id = document.getElementById('product-id').value;
    const nombre = document.getElementById('product-name').value.trim();
    const tipo = document.getElementById('product-type').value;
    const precio = parseFloat(document.getElementById('product-price').value);
    const stock = parseInt(document.getElementById('product-stock').value);
    const id_agricultor = parseInt(document.getElementById('product-farmer').value);
    
    // Validaciones
    let valid = true;
    clearErrors('product');
    
    if (!nombre) {
        showError('product-name-error', 'El nombre es requerido');
        valid = false;
    }
    
    if (!tipo) {
        showError('product-type-error', 'El tipo es requerido');
        valid = false;
    }
    
    if (!precio || precio < 0) {
        showError('product-price-error', 'El precio debe ser un número positivo');
        valid = false;
    }
    
    if (!stock || stock < 0) {
        showError('product-stock-error', 'El stock debe ser un número positivo');
        valid = false;
    }
    
    if (!id_agricultor) {
        showError('product-farmer-error', 'El agricultor es requerido');
        valid = false;
    }
    
    if (!valid) return;
    
    const productData = { nombre, tipo, precio, stock, id_agricultor };
    if (id) productData.id = parseInt(id);
    
    // Deshabilitar botón y mostrar loading
    const submitBtn = document.getElementById('product-submit');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading"></span> Guardando...';
    
    try {
        const success = await saveProduct(productData);
        if (success) {
            // Limpiar formulario
            this.reset();
            document.getElementById('product-id').value = '';
            document.getElementById('product-submit').textContent = 'Guardar Producto';
            document.getElementById('product-cancel').style.display = 'none';
            appState.currentProductId = null;
        }
    } finally {
        // Restaurar botón
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});

document.getElementById('product-cancel').addEventListener('click', function() {
    document.getElementById('product-form').reset();
    document.getElementById('product-id').value = '';
    document.getElementById('product-submit').textContent = 'Guardar Producto';
    this.style.display = 'none';
    clearErrors('product');
    appState.currentProductId = null;
});

async function saveProduct(productData) {
    try {
        const url = productData.id ? API_URL.products : API_URL.products;
        const method = productData.id ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(productData)
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al guardar producto');
        }
        
        const result = await response.json();
        showMessage('product-message', result.message, 'success');
        loadProducts();
        
        // Si estamos en la pestaña de ventas, recargar también
        if (document.getElementById('sales').classList.contains('active')) {
            loadProductsForSales();
        }
        
        return true;
    } catch (error) {
        showMessage('product-message', error.message, 'error');
        return false;
    }
}

// Funciones para ventas
async function loadSales() {
    try {
        showLoading('sales-table', 'Cargando ventas...');
        const response = await fetch(API_URL.sales);
        if (!response.ok) throw new Error('Error al cargar ventas');
        const sales = await response.json();
        
        appState.sales = sales;
        
        const tbody = document.querySelector('#sales-table tbody');
        tbody.innerHTML = '';
        
        if (sales.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No hay ventas registradas</td></tr>';
            return;
        }
        
        sales.forEach(sale => {
            const total = parseFloat(sale.precio || 0) * sale.cantidad;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${sale.id}</td>
                <td>${escapeHtml(sale.producto_nombre || 'Producto no encontrado')}</td>
                <td>${escapeHtml(sale.agricultor_nombre || '')} - ${escapeHtml(sale.granja || '')}</td>
                <td>${sale.fecha}</td>
                <td>${sale.cantidad}</td>
                <td>$${total.toFixed(2)}</td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        showMessage('sale-message', 'Error al cargar ventas: ' + error.message, 'error');
    }
}

async function loadProductsForSales() {
    try {
        const response = await fetch(API_URL.products);
        if (!response.ok) throw new Error('Error al cargar productos');
        const products = await response.json();
        
        const select = document.getElementById('sale-product');
        select.innerHTML = '<option value="">Seleccione un producto</option>';
        
        products.forEach(product => {
            const farmerName = product.agricultor_nombre ? `${product.agricultor_nombre} - ${product.granja}` : 'Agricultor no encontrado';
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = `${product.nombre} - ${farmerName} - Stock: ${product.stock} - Precio: $${parseFloat(product.precio).toFixed(2)}`;
            option.dataset.stock = product.stock;
            option.dataset.price = product.precio;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error al cargar productos para ventas:', error);
    }
}

document.getElementById('sale-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const id_producto = parseInt(document.getElementById('sale-product').value);
    const cantidad = parseInt(document.getElementById('sale-quantity').value);
    const fecha = document.getElementById('sale-date').value;
    
    // Validaciones
    let valid = true;
    clearErrors('sale');
    
    if (!id_producto) {
        showError('sale-product-error', 'El producto es requerido');
        valid = false;
    }
    
    if (!cantidad || cantidad <= 0) {
        showError('sale-quantity-error', 'La cantidad debe ser un número positivo');
        valid = false;
    }
    
    if (!fecha) {
        showError('sale-date-error', 'La fecha es requerida');
        valid = false;
    }
    
    // Validar stock
    const selectedOption = document.getElementById('sale-product').selectedOptions[0];
    if (selectedOption && cantidad > parseInt(selectedOption.dataset.stock)) {
        showError('sale-quantity-error', `La cantidad supera el stock actual. Stock disponible: ${selectedOption.dataset.stock}`);
        valid = false;
    }
    
    if (!valid) return;
    
    const saleData = { id_producto, fecha, cantidad };
    
    // Mostrar modal de confirmación
    const modal = document.getElementById('confirmation-modal');
    const confirmationText = document.getElementById('confirmation-text');
    const productName = selectedOption ? selectedOption.textContent.split(' - ')[0] : 'Producto';
    const price = selectedOption ? parseFloat(selectedOption.dataset.price) : 0;
    const total = price * cantidad;
    
    confirmationText.textContent = `¿Está seguro de que desea registrar la venta de ${cantidad} unidades de ${productName} por un total de $${total.toFixed(2)}?`;
    modal.style.display = 'flex';
    
    // Configurar confirmación
    document.getElementById('confirm-sale').onclick = async function() {
        // Deshabilitar botones del modal
        this.disabled = true;
        document.getElementById('cancel-sale').disabled = true;
        this.innerHTML = '<span class="loading"></span> Procesando...';
        
        try {
            const success = await registerSale(saleData);
            if (success) {
                // Limpiar formulario
                document.getElementById('sale-form').reset();
                // Establecer fecha actual
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('sale-date').value = today;
            }
        } finally {
            // Cerrar modal y restaurar botones
            modal.style.display = 'none';
            this.disabled = false;
            document.getElementById('cancel-sale').disabled = false;
            this.textContent = 'Confirmar';
        }
    };
    
    // Configurar cancelación
    document.getElementById('cancel-sale').onclick = function() {
        modal.style.display = 'none';
    };
});

// Cerrar modal al hacer clic en la X
document.querySelector('.close').addEventListener('click', function() {
    document.getElementById('confirmation-modal').style.display = 'none';
});

async function registerSale(saleData) {
    try {
        const response = await fetch(API_URL.sales, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(saleData)
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al registrar venta');
        }
        
        const result = await response.json();
        showMessage('sale-message', result.message, 'success');
        loadSales();
        loadProducts(); // Para actualizar el stock
        loadProductsForSales(); // Para actualizar el select de productos
        loadStats(); // Para actualizar estadísticas
        return true;
    } catch (error) {
        showMessage('sale-message', error.message, 'error');
        return false;
    }
}

// Funciones para estadísticas
async function loadStats() {
    try {
        const response = await fetch(API_URL.stats);
        if (!response.ok) throw new Error('Error al cargar estadísticas');
        const stats = await response.json();
        
        // Ventas por agricultor
        const salesByFarmerElement = document.getElementById('sales-by-farmer');
        salesByFarmerElement.innerHTML = '';
        
        if (stats.ventas_por_agricultor && stats.ventas_por_agricultor.length > 0) {
            stats.ventas_por_agricultor.forEach(item => {
                const div = document.createElement('div');
                div.innerHTML = `<p>${escapeHtml(item.nombre)} - ${escapeHtml(item.granja)}: <span class="stat-value">$${parseFloat(item.total_ventas).toFixed(2)}</span></p>`;
                salesByFarmerElement.appendChild(div);
            });
        } else {
            salesByFarmerElement.innerHTML = '<p>No hay datos de ventas por agricultor</p>';
        }
        
        // Productos por tipo
        const productsByTypeElement = document.getElementById('products-by-type');
        productsByTypeElement.innerHTML = '';
        
        if (stats.productos_por_tipo && stats.productos_por_tipo.length > 0) {
            stats.productos_por_tipo.forEach(item => {
                const div = document.createElement('div');
                div.innerHTML = `<p>${escapeHtml(item.tipo)}: <span class="stat-value">${item.cantidad}</span></p>`;
                productsByTypeElement.appendChild(div);
            });
        } else {
            productsByTypeElement.innerHTML = '<p>No hay datos de productos por tipo</p>';
        }
        
        // Ventas del mes
        const monthlySalesElement = document.getElementById('monthly-sales');
        const monthlyTotal = stats.ventas_mes_actual ? parseFloat(stats.ventas_mes_actual.total_mes || 0) : 0;
        monthlySalesElement.innerHTML = `<p class="stat-value">$${monthlyTotal.toFixed(2)}</p>`;
        
        // Productos más vendidos
        const topProductsElement = document.getElementById('top-products');
        topProductsElement.innerHTML = '';
        
        if (stats.productos_mas_vendidos && stats.productos_mas_vendidos.length > 0) {
            stats.productos_mas_vendidos.forEach(item => {
                const div = document.createElement('div');
                div.innerHTML = `<p>${escapeHtml(item.nombre)}: <span class="stat-value">${item.total_vendido}</span></p>`;
                topProductsElement.appendChild(div);
            });
        } else {
            topProductsElement.innerHTML = '<p>No hay datos de productos más vendidos</p>';
        }
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
        // Mostrar mensajes de error en cada sección
        document.getElementById('sales-by-farmer').innerHTML = '<p>Error al cargar datos</p>';
        document.getElementById('products-by-type').innerHTML = '<p>Error al cargar datos</p>';
        document.getElementById('monthly-sales').innerHTML = '<p>Error al cargar datos</p>';
        document.getElementById('top-products').innerHTML = '<p>Error al cargar datos</p>';
    }
}

// Funciones auxiliares
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showMessage(containerId, message, type) {
    const container = document.getElementById(containerId);
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    container.innerHTML = `<div class="alert ${alertClass}">${escapeHtml(message)}</div>`;
    
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

function showError(elementId, message) {
    const element = document.getElementById(elementId);
    element.textContent = message;
}

function clearErrors(prefix) {
    const errorElements = document.querySelectorAll(`[id^="${prefix}-"][id$="-error"]`);
    errorElements.forEach(element => {
        element.textContent = '';
    });
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function showLoading(containerId, message = 'Cargando...') {
    const container = document.getElementById(containerId);
    if (container) {
        const tbody = container.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="10" style="text-align: center;"><span class="loading"></span> ${message}</td></tr>`;
        }
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    loadFarmers();
    loadProducts();
    loadSales();
    loadStats();
    
    // Establecer fecha actual por defecto
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('sale-date').value = today;
    
    // Cargar agricultores para el select de productos
    loadFarmersForProducts();
});