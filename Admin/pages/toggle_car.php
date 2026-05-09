<?php
include('connect.php');
session_name('admin_session');
session_start();

header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate inputs
$id     = isset($_GET['id'])     ? (int)$_GET['id']     : 0;
$status = isset($_GET['status']) ? (int)$_GET['status'] : -1;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid car ID']);
    exit;
}

if ($status !== 0 && $status !== 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

// Check car exists
$check = mysqli_query($con, "SELECT car_id, is_enabled FROM car_master WHERE car_id = $id");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Car not found']);
    exit;
}

$car = mysqli_fetch_assoc($check);

// Already in desired state
if ((int)$car['is_enabled'] === $status) {
    echo json_encode([
        'success' => true,
        'message' => 'No change needed',
        'car_id'  => $id,
        'status'  => $status,
    ]);
    exit;
}

// Perform update
$result = mysqli_query($con, "UPDATE car_master SET is_enabled = $status WHERE car_id = $id");

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => $status === 1 ? 'Car enabled successfully' : 'Car disabled successfully',
        'car_id'  => $id,
        'status'  => $status,
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($con),
    ]);
}
?>