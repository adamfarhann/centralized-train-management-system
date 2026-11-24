
<?php
require_once '../config/connection.php';

// Check whether asset ID exists
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Asset ID.");
}

$id = (int) $_GET['id'];

// Retrieve asset information
$sql = "SELECT * FROM assets WHERE asset_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$asset = $result->fetch_assoc();

$stmt->close();

if (!$asset) {
    die("Asset not found.");
}

// Retrieve asset types
$type_sql = "SELECT * FROM asset_types";
$type_result = $conn->query($type_sql);

if (!$type_result) {
    die("Unable to retrieve asset types.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Asset | Railway TMS</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f5f9;
            padding: 30px;
        }

        .container {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h2 {
            color: #17365d;
        }

        label {
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 18px;
            box-sizing: border-box;
        }

        button {
            background: #17365d;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #24558a;
        }

        a {
            color: #17365d;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Edit Railway Asset</h2>

    <form action="update_asset.php" method="POST">

        <input type="hidden"
               name="asset_id"
               value="<?php echo htmlspecialchars($asset['asset_id']); ?>">

        <label>Asset Name:</label>

        <input
            type="text"
            name="asset_name"
            value="<?php echo htmlspecialchars($asset['asset_name']); ?>"
            required
        >

        <label>Asset Type:</label>

        <select name="type_id" required>

            <?php while ($type = $type_result->fetch_assoc()): ?>

                <option
                    value="<?php echo htmlspecialchars($type['type_id']); ?>"
                    <?php echo ($asset['type_id'] == $type['type_id']) ? 'selected' : ''; ?>
                >
                    <?php echo htmlspecialchars($type['type_name']); ?>
                </option>

            <?php endwhile; ?>

        </select>

        <label>Location:</label>

        <input
            type="text"
            name="location"
            value="<?php echo htmlspecialchars($asset['location']); ?>"
            required
        >

        <label>Condition:</label>

        <select name="condition_status" required>

            <option value="Good"
                <?php echo ($asset['condition_status'] == 'Good') ? 'selected' : ''; ?>>
                Good
            </option>

            <option value="Fair"
                <?php echo ($asset['condition_status'] == 'Fair') ? 'selected' : ''; ?>>
                Fair
            </option>

            <option value="Poor"
                <?php echo ($asset['condition_status'] == 'Poor') ? 'selected' : ''; ?>>
                Poor
            </option>

        </select>

        <label>Last Inspection:</label>

        <input
            type="date"
            name="last_inspection"
            value="<?php echo htmlspecialchars($asset['last_inspection'] ?? ''); ?>"
        >

        <label>Description:</label>

        <textarea
            name="description"
            rows="4"
        ><?php echo htmlspecialchars($asset['description'] ?? ''); ?></textarea>

        <button type="submit" name="update_asset">
            Update Asset
        </button>

        <a href="asset_detail.php">Cancel</a>

    </form>

</div>

</body>
</html>

<?php
$conn->close();
?>

