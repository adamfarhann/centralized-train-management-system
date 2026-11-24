
<?php
require_once '../config/connection.php';

// ==========================================
// 1. ADD NEW ASSET
// ==========================================

if (isset($_POST['add_asset'])) {

    $asset_name = trim($_POST['asset_name']);
    $type_id = (int) $_POST['type_id'];
    $location = trim($_POST['location']);
    $condition_status = $_POST['condition_status'];
    $last_inspection = !empty($_POST['last_inspection'])
        ? $_POST['last_inspection']
        : null;
    $description = trim($_POST['description']);

    $allowed_conditions = ['Good', 'Fair', 'Poor'];

    if (
        $asset_name === '' ||
        $location === '' ||
        $type_id < 1 ||
        !in_array($condition_status, $allowed_conditions, true)
    ) {
        die("Invalid asset information.");
    }

    $stmt = $conn->prepare(
        "INSERT INTO assets
        (asset_name, type_id, location,
         condition_status, last_inspection, description)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param(
        "sissss",
        $asset_name,
        $type_id,
        $location,
        $condition_status,
        $last_inspection,
        $description
    );

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: asset_detail.php?added=1");
        exit;
    } else {
        die("Error adding asset: " .
            htmlspecialchars($stmt->error));
    }
}

// ==========================================
// 2. RETRIEVE ALL ASSETS
// ==========================================

$sql = "SELECT assets.*, asset_types.type_name
        FROM assets
        INNER JOIN asset_types
        ON assets.type_id = asset_types.type_id
        ORDER BY assets.asset_id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Database query failed: " .
        htmlspecialchars($conn->error));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Asset Management | Railway TMS</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f2f5f9;
            margin: 0;
            padding: 30px;
        }

        h1 {
            color: #17365d;
        }

        h2 {
            color: #17365d;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #17365d;
            color: white;
        }

        .empty {
            background: white;
            padding: 25px;
            text-align: center;
        }

        .asset-form {
            background: white;
            padding: 25px;
            margin: 25px 0;
            border-radius: 10px;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
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
            font-weight: bold;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .delete-form {
            margin: 8px 0;
        }

        .delete-button {
            background: #c0392b;
        }

    </style>
</head>

<body>

<div class="container">

    <h1>Railway Asset Management</h1>

    <?php if (isset($_GET['added'])): ?>
    <div class="success">
        Asset added successfully!
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="success">
        Asset updated successfully!
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="success">
        Asset deleted successfully!
    </div>
<?php endif; ?>

    <!-- ==================================
         3. ADD ASSET FORM
    =================================== -->

    <form method="POST" action="" class="asset-form">

        <h2>Add New Railway Asset</h2>

        <label>Asset Name:</label>

        <input
            type="text"
            name="asset_name"
            placeholder="Example: Railway Bridge A"
            required
        >

        <br><br>

        <label>Asset Type:</label>

        <select name="type_id" required>

            <option value="">Select Asset Type</option>

            <option value="1">Bridge</option>

            <option value="2">Tunnel</option>

            <option value="3">Catenary</option>

        </select>

        <br><br>

        <label>Location:</label>

        <input
            type="text"
            name="location"
            placeholder="Example: Johor"
            required
        >

        <br><br>

        <label>Condition:</label>

        <select name="condition_status" required>

            <option value="Good">Good</option>

            <option value="Fair">Fair</option>

            <option value="Poor">Poor</option>

        </select>

        <br><br>

        <label>Last Inspection:</label>

        <input
            type="date"
            name="last_inspection"
        >

        <br><br>

        <label>Description:</label>

        <textarea
            name="description"
            placeholder="Enter asset description"
        ></textarea>

        <br><br>

        <button type="submit" name="add_asset">
            Add Asset
        </button>

    </form>

    <hr>

    <!-- ==================================
         4. DISPLAY ASSET RECORDS
    =================================== -->

    <p>
        Manage railway infrastructure assets
        and their conditions.
    </p>

    <table>

        <tr>
            <th>Asset ID</th>
            <th>Asset Name</th>
            <th>Asset Type</th>
            <th>Location</th>
            <th>Condition</th>
            <th>Last Inspection</th>
            <th>Actions</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>

            <?php while ($row = $result->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($row['asset_id']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['asset_name']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['type_name']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['location']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['condition_status']); ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row['last_inspection'] ?? 'Not recorded'
                        );
                        ?>
                    </td>

                    <td>

                        <!-- EDIT BUTTON -->

                        <a href="edit_asset.php?id=<?php
                            echo urlencode($row['asset_id']);
                        ?>">
                            Edit
                        </a>

                        <!-- DELETE BUTTON -->

                        <form
                            action="delete_asset.php"
                            method="POST"
                            class="delete-form"
                            onsubmit="return confirm('Are you sure you want to delete this asset?');"
                        >

                            <input
                                type="hidden"
                                name="asset_id"
                                value="<?php
                                    echo htmlspecialchars($row['asset_id']);
                                ?>"
                            >

                            <button
                                type="submit"
                                name="delete_asset"
                                class="delete-button"
                            >
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>

                <td colspan="7" class="empty">
                    No railway assets found.
                </td>

            </tr>

        <?php endif; ?>

    </table>

</div>

</body>
</html>

<?php
$result->free();
$conn->close();
?>
