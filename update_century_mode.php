<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$session_id = $_POST['session_id'] ?? 0;
$action = $_POST['action'] ?? '';
$elapsed_minutes = $_POST['elapsed_minutes'] ?? 0;

if (!$session_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    if ($action === 'start') {
        // Start century mode
        $stmt = $conn->prepare("
            UPDATE snooker_sessions 
            SET century_mode_start = NOW(), 
                century_mode_minutes = 0,
                century_warning_shown = 0
            WHERE session_id = ? AND status = 'Active'
        ");
        $stmt->bind_param("i", $session_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Century mode started']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to start century mode']);
        }
        $stmt->close();
        
    } elseif ($action === 'pause') {
        // Pause century mode
        $stmt = $conn->prepare("
            UPDATE snooker_sessions 
            SET century_mode_minutes = ? 
            WHERE session_id = ? AND status = 'Active'
        ");
        $stmt->bind_param("ii", $elapsed_minutes, $session_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Century mode paused']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to pause century mode']);
        }
        $stmt->close();
        
    } elseif ($action === 'reset') {
        // Reset century mode
        $stmt = $conn->prepare("
            UPDATE snooker_sessions 
            SET century_mode_start = NULL, 
                century_mode_minutes = 0,
                century_warning_shown = 0
            WHERE session_id = ? AND status = 'Active'
        ");
        $stmt->bind_param("i", $session_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Century mode reset']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reset century mode']);
        }
        $stmt->close();
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>