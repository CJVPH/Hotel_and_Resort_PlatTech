<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_service':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $duration = intval($_POST['duration'] ?? 0);
                
                if (empty($name) || empty($description) || $price <= 0 || $duration <= 0) {
                    throw new Exception('All fields are required and must be valid');
                }
                
                $imageName = '';
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/spa/';
                    
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
                $stmt = $conn->prepare("INSERT INTO spa_services (name, description, price, duration, image, enabled) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("ssdis", $name, $description, $price, $duration, $imageName);
                
                if (!$stmt->execute()) {
                    // Delete uploaded image if database insert fails
                    if ($imageName && file_exists($uploadDir . $imageName)) {
                        unlink($uploadDir . $imageName);
                    }
                    throw new Exception('Failed to save service to database');
                }
                
                $serviceId = $conn->insert_id;
                
                echo json_encode([
                    'success' => true,
                    'id' => $serviceId,
                    'image' => $imageName,
                    'message' => 'Service added successfully'
                ]);
                break;
                
            case 'update_service':
                $id = intval($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $duration = intval($_POST['duration'] ?? 0);
                
                if ($id <= 0 || empty($name) || empty($description) || $price <= 0 || $duration <= 0) {
                    throw new Exception('All fields are required and must be valid');
                }
                
                $imageName = '';
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/spa/';
                    
                    // Validate file type
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    $fileType = $_FILES['image']['type'];
                    
                    if (!in_array($fileType, $allowedTypes)) {
                        throw new Exception('Only JPEG, PNG, GIF, and WebP images are allowed');
                    }
                    
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        throw new Exception('Image file too large (max 5MB)');
                    }
                    
                    // Delete old image
                    $result = $conn->query("SELECT image FROM spa_services WHERE id = $id");
                    if ($result && $row = $result->fetch_assoc()) {
                        if ($row['image']) {
                            $oldImagePath = $uploadDir . $row['image'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                    }
                    
                    // Upload new image
                    $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $imageName = uniqid() . '_' . time() . '.' . $extension;
                    $uploadPath = $uploadDir . $imageName;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                        throw new Exception('Failed to upload image file');
                    }
                    
                    // Update with new image
                    $stmt = $conn->prepare("UPDATE spa_services SET name = ?, description = ?, price = ?, duration = ?, image = ? WHERE id = ?");
                    $stmt->bind_param("ssdisi", $name, $description, $price, $duration, $imageName, $id);
                } else {
                    // Update without changing image
                    $stmt = $conn->prepare("UPDATE spa_services SET name = ?, description = ?, price = ?, duration = ? WHERE id = ?");
                    $stmt->bind_param("ssdii", $name, $description, $price, $duration, $id);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update service');
                }
                
                echo json_encode([
                    'success' => true,
                    'image' => $imageName,
                    'message' => 'Service updated successfully'
                ]);
                break;
                
            case 'get_services':
                $result = $conn->query("SELECT * FROM spa_services ORDER BY created_at DESC");
                $services = [];
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $services[] = [
                            'id' => intval($row['id']),
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'price' => floatval($row['price']),
                            'duration' => intval($row['duration']),
                            'image' => $row['image'],
                            'enabled' => boolval($row['enabled'])
                        ];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'services' => $services
                ]);
                break;
                
            case 'toggle_service':
                $id = intval($_POST['id'] ?? 0);
                $enabled = intval($_POST['enabled'] ?? 0);
                
                $stmt = $conn->prepare("UPDATE spa_services SET enabled = ? WHERE id = ?");
                $stmt->bind_param("ii", $enabled, $id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update service');
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Service updated successfully'
                ]);
                break;
                
            case 'delete_service':
                $id = intval($_POST['id'] ?? 0);
                
                // Get image name to delete file
                $result = $conn->query("SELECT image FROM spa_services WHERE id = $id");
                if ($result && $row = $result->fetch_assoc()) {
                    if ($row['image']) {
                        $imagePath = '../uploads/spa/' . $row['image'];
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                }
                
                $stmt = $conn->prepare("DELETE FROM spa_services WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to delete service');
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Service deleted successfully'
                ]);
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
