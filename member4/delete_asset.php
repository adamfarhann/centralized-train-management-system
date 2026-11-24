<?php
require_once '../config/connection.php';

// Check form submission
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['delete_asset'])
) {
    header("Location: asset_detail.php");
    exit;
}

// Get asset ID
$asset_id = (int) ($_POST['asset_id'] ?? 0);

if ($asset_id < 1) {
    die("Invalid asset ID.");
}

// Prepare delete statement
$sql = "DELETE FROM assets WHERE asset_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $asset_id);

// Execute delete
if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $conn->close();

        header("Location: asset_detail.php?deleted=1");
        exit;
    } else {
        die("Asset not found.");
    }

} else {
    die("Delete failed: " . htmlspecialchars($stmt->error));
}
?>
