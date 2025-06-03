<?php
require_once 'auth.php';
$user = requireLogin();
require_once 'config.php';

header('Content-Type: application/json');

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

switch ($action) {
    case 'get_user':
        // IDOR: Any authenticated user can get any user's data
        $stmt = $db->prepare("SELECT id, username, email, first_name, last_name, role, department, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        
        if ($userData) {
            echo json_encode(['success' => true, 'data' => $userData]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
        break;
        
    case 'get_user_profile':
        // IDOR: Access to sensitive profile data
        $stmt = $db->prepare("
            SELECT u.*, up.phone, up.address, up.bio, up.salary, up.emergency_contact 
            FROM users u 
            LEFT JOIN user_profiles up ON u.id = up.user_id 
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profileData = $result->fetch_assoc();
        
        if ($profileData) {
            echo json_encode(['success' => true, 'data' => $profileData]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Profile not found']);
        }
        break;
        
    case 'get_document':
        // IDOR: Access to any document
        $stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $docData = $result->fetch_assoc();
        
        if ($docData) {
            echo json_encode(['success' => true, 'data' => $docData]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Document not found']);
        }
        break;
        
    case 'get_order':
        // IDOR: Access to any order
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderData = $result->fetch_assoc();
        
        if ($orderData) {
            echo json_encode(['success' => true, 'data' => $orderData]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
        }
        break;
        
    case 'get_message':
        // IDOR: Access to any message
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $msgData = $result->fetch_assoc();
        
        if ($msgData) {
            echo json_encode(['success' => true, 'data' => $msgData]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Message not found']);
        }
        break;
        
    case 'get_report':
        // IDOR: Access to any report
        $stmt = $db->prepare("SELECT * FROM reports WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reportData = $result->fetch_assoc();
        
        if ($reportData) {
            echo json_encode(['success' => true, 'data' => $reportData]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Report not found']);
        }
        break;
        
    case 'update_user':
        // IDOR: Update any user (if POST data provided)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = json_decode(file_get_contents('php://input'), true);
            
            if ($postData) {
                $stmt = $db->prepare("UPDATE users SET email = ?, first_name = ?, last_name = ? WHERE id = ?");
                $stmt->bind_param("sssi", 
                    $postData['email'], 
                    $postData['first_name'], 
                    $postData['last_name'], 
                    $id
                );
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Update failed']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'No data provided']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'POST method required']);
        }
        break;
        
    case 'delete_document':
        // IDOR: Delete any document
        $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Delete failed']);
        }
        break;
        
    case 'list_user_data':
        // IDOR: List all data for a specific user
        $userData = [];
        
        // Get user info
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $userData['user'] = $stmt->get_result()->fetch_assoc();
        
        // Get user documents
        $stmt = $db->prepare("SELECT * FROM documents WHERE owner_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $userData['documents'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get user orders
        $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $userData['orders'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get user messages
        $stmt = $db->prepare("SELECT * FROM messages WHERE recipient_id = ? OR sender_id = ?");
        $stmt->bind_param("ii", $id, $id);
        $stmt->execute();
        $userData['messages'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $userData]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

$db->close(); 