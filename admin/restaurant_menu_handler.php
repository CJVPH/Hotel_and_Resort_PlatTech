<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

header('Content-Type: application/json');

error_log('RestaurantMenuHandler: ' . $_SERVER['REQUEST_METHOD'] . ' request received');

try {
    $conn = getDBConnection();
    error_log('RestaurantMenuHandler: Database connection successful');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        error_log('RestaurantMenuHandler: Action = ' . $action);
        
        switch ($action) {
            case 'add_item':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $prepTime = intval($_POST['prep_time'] ?? 0);
                
                error_log("RestaurantMenuHandler: Adding item - name=$name, category=$category, price=$price, prep_time=$prepTime");
                
                if (empty($name) || empty($description) || empty($category) || $price <= 0 || $prepTime <= 0) {
                    throw new Exception('All fields are required and must be valid');
                }
                
                $imageName = '';
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/restaurant/';
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Validate file type
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    $fileType = $_FILES['image']['type'];
                    
                    if (!in_array($fileType, $allowedTypes)) {
                        throw new Exception('Only JPEG, PNG, GIF, and WebP images are allowed');
                    }
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        throw new Exception('Image file too large (max 5MB)');
                    }
                    
                    // Get file extension
                    $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $imageName = uniqid() . '_' . time() . '.' . $extension;
                    $uploadPath = $uploadDir . $imageName;
                    
                    // Move uploaded file
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                        throw new Exception('Failed to upload image file');
                    }
                }
                
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO restaurant_menu_items (name, description, category, price, prep_time, image, available) VALUES (?, ?, ?, ?, ?, ?, 1)");
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                
                $stmt->bind_param("sssdis", $name, $description, $category, $price, $prepTime, $imageName);
                
                if (!$stmt->execute()) {
                    // Delete uploaded image if database insert fails
                    if ($imageName && file_exists($uploadDir . $imageName)) {
                        unlink($uploadDir . $imageName);
                    }
                    throw new Exception('Execute failed: ' . $stmt->error);
                }
                
                $itemId = $conn->insert_id;
                error_log('RestaurantMenuHandler: Item added with ID: ' . $itemId);
                
                echo json_encode([
                    'success' => true,
                    'id' => $itemId,
                    'image' => $imageName,
                    'message' => 'Menu item added successfully'
                ]);
                $stmt->close();
                break;
                
            case 'update_item':
                $id = intval($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $prepTime = intval($_POST['prep_time'] ?? 0);
                
                error_log("RestaurantMenuHandler: Updating item $id - name=$name, price=$price");
                
                if ($id <= 0 || empty($name) || empty($description) || empty($category) || $price <= 0 || $prepTime <= 0) {
                    throw new Exception('All fields are required and must be valid');
                }
                
                $stmt = $conn->prepare("UPDATE restaurant_menu_items SET name = ?, description = ?, category = ?, price = ?, prep_time = ? WHERE id = ?");
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                
                $stmt->bind_param("sssdii", $name, $description, $category, $price, $prepTime, $id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed: ' . $stmt->error);
                }
                
                error_log('RestaurantMenuHandler: Item updated successfully');
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item updated successfully'
                ]);
                $stmt->close();
                break;
                
            case 'get_items':
                error_log('RestaurantMenuHandler: Getting all items');
                
                $result = $conn->query("SELECT * FROM restaurant_menu_items ORDER BY created_at DESC");
                if (!$result) {
                    throw new Exception('Query failed: ' . $conn->error);
                }
                
                $items = [];
                
                while ($row = $result->fetch_assoc()) {
                    $items[] = [
                        'id' => intval($row['id']),
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'category' => $row['category'],
                        'price' => floatval($row['price']),
                        'prep_time' => intval($row['prep_time']),
                        'image' => $row['image'] ?? '',
                        'available' => boolval($row['available'])
                    ];
                }
                
                error_log('RestaurantMenuHandler: Found ' . count($items) . ' items');
                
                echo json_encode([
                    'success' => true,
                    'items' => $items
                ]);
                break;
                
            case 'toggle_item':
                $id = intval($_POST['id'] ?? 0);
                $available = intval($_POST['available'] ?? 0);
                
                error_log("RestaurantMenuHandler: Toggling item $id to available=$available");
                
                $stmt = $conn->prepare("UPDATE restaurant_menu_items SET available = ? WHERE id = ?");
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                
                $stmt->bind_param("ii", $available, $id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed: ' . $stmt->error);
                }
                
                error_log('RestaurantMenuHandler: Item toggled successfully');
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item updated successfully'
                ]);
                $stmt->close();
                break;
                
            case 'delete_item':
                $id = intval($_POST['id'] ?? 0);
                
                error_log("RestaurantMenuHandler: Deleting item $id");
                
                $stmt = $conn->prepare("DELETE FROM restaurant_menu_items WHERE id = ?");
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                
                $stmt->bind_param("i", $id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed: ' . $stmt->error);
                }
                
                error_log('RestaurantMenuHandler: Item deleted successfully');
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item deleted successfully'
                ]);
                $stmt->close();
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
