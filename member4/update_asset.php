<?php
require_once '../config/connection.php';

// Check form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['update_asset'])) {
    header("Location: asset_detail.php");
    exit;
}

// Get submitted information
$asset_id = (int) ($_POST['asset_id'] ?? 0);
$asset_name = trim($_POST['asset_name'] ?? '');
$type_id = (int) ($_POST['type_id'] ?? 0);
$location = trim($_POST['location'] ?? '');
$condition_status = $_POST['condition_status'] ?? '';
$last_inspection = !empty($_POST['last_inspection'])
    ? $_POST['last_inspection']
    : null;
$description = trim($_POST['description'] ?? '');

// Validate information
$allowed_conditions = ['Good', 'Fair', 'Poor'];

if (
    $asset_id < 1 ||
    $asset_name === '' ||
    $type_id < 1 ||
    $location === '' ||
    !in_array($condition_status, $allowed_conditions, true)
) {
    die("Invalid asset information.");
}

// Check whether asset exists
$check = $conn->prepare(
    "SELECT asset_id FROM assets WHERE asset_id = ?"
);

$check->bind_param("i", $asset_id);
$check->execute();

$check_result = $check->get_result();

if ($check_result->num_rows === 0) {
    die("Asset not found.");
}

$check->close();

// Update asset information
$sql = "UPDATE assets SET
        asset_name = ?,
        type_id = ?,
        location = ?,
        condition_status = ?,
        last_inspection = ?,
        description = ?
        WHERE asset_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . htmlspecialchars($conn->error));
}

$stmt->bind_param(
    "sissssi",
    $asset_name,
    $type_id,
    $location,
    $condition_status,
    $last_inspection,
    $description,
    $asset_id
);

// Execute update
if ($stmt->execute()) {

    $stmt->close();
    $conn->close();

    header("Location: asset_detail.php?updated=1");
    exit;

} else {

    die("Update failed: " . htmlspecialchars($stmt->error));

}
?>
