<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

// Check if user is logged in as a Client
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated as Client.'
    ]);
    exit();
}

$username = $_SESSION['username'];
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

if ($action === 'get') {
    // Action 1: Get list of favorited property IDs
    $stmt = $conn->prepare("SELECT property_id FROM wishlists WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = intval($row['property_id']);
    }
    
    echo json_encode([
        'success' => true,
        'favorites' => $favorites
    ]);
    exit();

} elseif ($action === 'toggle') {
    // Action 2: Toggle favorite (add/remove)
    $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    
    if ($property_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid property ID.']);
        exit();
    }
    
    // Check if it already exists
    $check = $conn->prepare("SELECT id FROM wishlists WHERE username = ? AND property_id = ?");
    $check->bind_param("si", $username, $property_id);
    $check->execute();
    $res = $check->get_result();
    
    if ($res->num_rows > 0) {
        // Exists -> Remove it
        $del = $conn->prepare("DELETE FROM wishlists WHERE username = ? AND property_id = ?");
        $del->bind_param("si", $username, $property_id);
        $del->execute();
        
        echo json_encode([
            'success' => true,
            'status' => 'removed',
            'property_id' => $property_id
        ]);
        exit();
    } else {
        // Does not exist -> Add it
        $ins = $conn->prepare("INSERT INTO wishlists (username, property_id) VALUES (?, ?)");
        $ins->bind_param("si", $username, $property_id);
        
        if ($ins->execute()) {
            echo json_encode([
                'success' => true,
                'status' => 'added',
                'property_id' => $property_id
            ]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error during save.']);
            exit();
        }
    }

} elseif ($action === 'sync') {
    // Action 3: Sync LocalStorage guest items upon login
    $json = isset($_POST['property_ids']) ? $_POST['property_ids'] : '[]';
    $property_ids = json_decode($json, true);
    
    if (!is_array($property_ids)) {
        echo json_encode(['success' => false, 'message' => 'Invalid properties format.']);
        exit();
    }
    
    $synced_count = 0;
    foreach ($property_ids as $p_id) {
        $p_id = intval($p_id);
        if ($p_id <= 0) continue;
        
        // INSERT IGNORE to prevent duplicate key crashes
        $stmt = $conn->prepare("INSERT IGNORE INTO wishlists (username, property_id) VALUES (?, ?)");
        $stmt->bind_param("si", $username, $p_id);
        if ($stmt->execute()) {
            $synced_count += $conn->affected_rows;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Wishlist synchronized successfully!',
        'synced_count' => $synced_count
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Action unrecognized.']);
exit();
?>
