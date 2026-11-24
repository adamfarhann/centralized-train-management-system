<?php
require_once '../config/connection.php';

// ==========================================
// 1. PROCESS FORM SUBMISSIONS
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // --------------------------------------
    // ADD INSPECTION LOG
    // --------------------------------------

    if ($action === 'add') {

        $asset_id = (int) ($_POST['asset_id'] ?? 0);
        $inspection_date = $_POST['inspection_date'] ?? '';
        $inspector_name = trim($_POST['inspector_name'] ?? '');
        $findings = trim($_POST['findings'] ?? '');
        $defect_description = trim($_POST['defect_description'] ?? '');
        $inspection_status = $_POST['inspection_status'] ?? 'Pending';

        $allowed_status = [
            'Completed',
            'Pending',
            'Requires Repair'
        ];

        // Validate required fields
        if (
            $asset_id < 1 ||
            $inspection_date === '' ||
            $inspector_name === '' ||
            $findings === '' ||
            !in_array($inspection_status, $allowed_status, true)
        ) {
            die("Please provide valid inspection information.");
        }

        // Make sure selected asset is a Bridge or Catenary
        $check = $conn->prepare(
            "SELECT a.asset_id
             FROM assets a
             INNER JOIN asset_types t
             ON a.type_id = t.type_id
             WHERE a.asset_id = ?
             AND LOWER(t.type_name) IN ('bridge', 'catenary')"
        );

        $check->bind_param("i", $asset_id);
        $check->execute();

        $check_result = $check->get_result();
        $valid_asset = $check_result->num_rows > 0;

        $check->close();

        if (!$valid_asset) {
            die("Please select a valid Bridge or Catenary asset.");
        }

        // Insert inspection log
        $stmt = $conn->prepare(
            "INSERT INTO inspection_logs
            (asset_id, inspection_date, inspector_name,
             findings, defect_description, inspection_status)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "isssss",
            $asset_id,
            $inspection_date,
            $inspector_name,
            $findings,
            $defect_description,
            $inspection_status
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: catenary_bridge_logs.php?added=1");
            exit;
        } else {
            die("Error adding inspection: " .
                htmlspecialchars($stmt->error));
        }
    }

    // --------------------------------------
    // UPDATE INSPECTION LOG
    // --------------------------------------

    elseif ($action === 'update') {

        $log_id = (int) ($_POST['log_id'] ?? 0);
        $asset_id = (int) ($_POST['asset_id'] ?? 0);
        $inspection_date = $_POST['inspection_date'] ?? '';
        $inspector_name = trim($_POST['inspector_name'] ?? '');
        $findings = trim($_POST['findings'] ?? '');
        $defect_description = trim($_POST['defect_description'] ?? '');
        $inspection_status = $_POST['inspection_status'] ?? '';

        $allowed_status = [
            'Completed',
            'Pending',
            'Requires Repair'
        ];

        if (
            $log_id < 1 ||
            $asset_id < 1 ||
            $inspection_date === '' ||
            $inspector_name === '' ||
            $findings === '' ||
            !in_array($inspection_status, $allowed_status, true)
        ) {
            die("Invalid inspection information.");
        }

        // Check asset type
        $check = $conn->prepare(
            "SELECT asset_id FROM assets
             WHERE asset_id = ?
             AND type_id IN (
                 SELECT type_id FROM asset_types
                 WHERE LOWER(type_name) IN ('bridge', 'catenary')
             )"
        );

        $check->bind_param("i", $asset_id);
        $check->execute();

        $valid_asset = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$valid_asset) {
            die("Invalid Bridge or Catenary asset.");
        }

        // Update inspection
        $stmt = $conn->prepare(
            "UPDATE inspection_logs SET
                asset_id = ?,
                inspection_date = ?,
                inspector_name = ?,
                findings = ?,
                defect_description = ?,
                inspection_status = ?
             WHERE log_id = ?"
        );

        $stmt->bind_param(
            "isssssi",
            $asset_id,
            $inspection_date,
            $inspector_name,
            $findings,
            $defect_description,
            $inspection_status,
            $log_id
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: catenary_bridge_logs.php?updated=1");
            exit;
        } else {
            die("Update failed: " .
                htmlspecialchars($stmt->error));
        }
    }

    // --------------------------------------
    // DELETE INSPECTION LOG
    // --------------------------------------

    elseif ($action === 'delete') {

        $log_id = (int) ($_POST['log_id'] ?? 0);

        if ($log_id < 1) {
            die("Invalid inspection log ID.");
        }

        $stmt = $conn->prepare(
            "DELETE il
             FROM inspection_logs il
             INNER JOIN assets a
             ON il.asset_id = a.asset_id
             INNER JOIN asset_types t
             ON a.type_id = t.type_id
             WHERE il.log_id = ?
             AND LOWER(t.type_name) IN ('bridge', 'catenary')"
        );

        $stmt->bind_param("i", $log_id);

        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0) {
                $stmt->close();
                header("Location: catenary_bridge_logs.php?deleted=1");
                exit;
            } else {
                die("Inspection log not found.");
            }

        } else {
            die("Delete failed: " .
                htmlspecialchars($stmt->error));
        }
    }
}

// ==========================================
// 2. GET ASSETS FOR DROPDOWN
// ==========================================

$asset_sql = "SELECT a.asset_id, a.asset_name, t.type_name
              FROM assets a
              INNER JOIN asset_types t
              ON a.type_id = t.type_id
              WHERE LOWER(t.type_name) IN ('bridge', 'catenary')
              ORDER BY a.asset_name ASC";

$asset_result = $conn->query($asset_sql);

if (!$asset_result) {
    die("Unable to retrieve assets: " .
        htmlspecialchars($conn->error));
}

// ==========================================
// 3. GET INSPECTION TO EDIT
// ==========================================

$edit_log = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {

    $edit_id = (int) $_GET['edit'];

    $edit_sql = "SELECT il.*
                 FROM inspection_logs il
                 INNER JOIN assets a
                 ON il.asset_id = a.asset_id
                 INNER JOIN asset_types t
                 ON a.type_id = t.type_id
                 WHERE il.log_id = ?
                 AND LOWER(t.type_name) IN ('bridge', 'catenary')";

    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();

    $edit_log = $edit_stmt->get_result()->fetch_assoc();

    $edit_stmt->close();

    if (!$edit_log) {
        die("Inspection log not found.");
    }
}

// ==========================================
// 4. RETRIEVE ALL INSPECTION LOGS
// ==========================================

$log_sql = "SELECT il.*, a.asset_name, t.type_name
            FROM inspection_logs il
            INNER JOIN assets a
            ON il.asset_id = a.asset_id
            INNER JOIN asset_types t
            ON a.type_id = t.type_id
            WHERE LOWER(t.type_name) IN ('bridge', 'catenary')
            ORDER BY il.log_id DESC";

$log_result = $conn->query($log_sql);

if (!$log_result) {
    die("Unable to retrieve inspection logs: " .
        htmlspecialchars($conn->error));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Catenary & Bridge Logs | Railway TMS</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f2f5f9;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        h1, h2 {
            color: #17365d;
        }

        .form-container {
            background: white;
            padding: 25px;
            margin: 25px 0;
            border-radius: 10px;
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
            padding: 11px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #24558a;
        }

        .delete-button {
            background: #c0392b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #17365d;
            color: white;
        }

        .message {
            padding: 14px;
            margin: 15px 0;
            border-radius: 5px;
            background: #d4edda;
            color: #155724;
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        a {
            color: #17365d;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 25px;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Catenary & Bridge Inspection Logs</h1>

    <p>
        Manage inspection records for railway bridges
        and catenary infrastructure.
    </p>

    <!-- SUCCESS MESSAGES -->

    <?php if (isset($_GET['added'])): ?>
        <div class="message">
            Inspection log added successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="message">
            Inspection log updated successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="message">
            Inspection log deleted successfully!
        </div>
    <?php endif; ?>

    <!-- ==================================
         5. ADD / EDIT INSPECTION FORM
    =================================== -->

    <div class="form-container">

        <h2>
            <?php echo $edit_log ? 'Edit Inspection Log' : 'Add New Inspection Log'; ?>
        </h2>

        <form method="POST" action="">

            <?php if ($edit_log): ?>

                <input type="hidden"
                       name="log_id"
                       value="<?php echo htmlspecialchars($edit_log['log_id']); ?>">

                <input type="hidden"
                       name="action"
                       value="update">

            <?php else: ?>

                <input type="hidden"
                       name="action"
                       value="add">

            <?php endif; ?>

            <label>Railway Asset:</label>

            <select name="asset_id" required>

                <option value="">Select Bridge or Catenary Asset</option>

                <?php while ($asset = $asset_result->fetch_assoc()): ?>

                    <option
                        value="<?php echo htmlspecialchars($asset['asset_id']); ?>"
                        <?php
                        if (
                            $edit_log &&
                            $edit_log['asset_id'] == $asset['asset_id']
                        ) {
                            echo 'selected';
                        }
                        ?>
                    >
                        <?php
                        echo htmlspecialchars(
                            $asset['asset_name'] .
                            ' (' . $asset['type_name'] . ')'
                        );
                        ?>
                    </option>

                <?php endwhile; ?>

            </select>

            <label>Inspection Date:</label>

            <input
                type="date"
                name="inspection_date"
                value="<?php echo htmlspecialchars($edit_log['inspection_date'] ?? ''); ?>"
                required
            >

            <label>Inspector Name:</label>

            <input
                type="text"
                name="inspector_name"
                placeholder="Enter inspector name"
                value="<?php echo htmlspecialchars($edit_log['inspector_name'] ?? ''); ?>"
                maxlength="150"
                required
            >

            <label>Inspection Findings:</label>

            <textarea
                name="findings"
                rows="3"
                placeholder="Enter inspection findings"
                required
            ><?php echo htmlspecialchars($edit_log['findings'] ?? ''); ?></textarea>

            <label>Defect Description:</label>

            <textarea
                name="defect_description"
                rows="3"
                placeholder="Describe any defects (if applicable)"
            ><?php echo htmlspecialchars($edit_log['defect_description'] ?? ''); ?></textarea>

            <label>Inspection Status:</label>

            <select name="inspection_status" required>

                <?php
                $current_status = $edit_log['inspection_status'] ?? 'Pending';
                ?>

                <option value="Pending"
                    <?php echo ($current_status === 'Pending') ? 'selected' : ''; ?>>
                    Pending
                </option>

                <option value="Completed"
                    <?php echo ($current_status === 'Completed') ? 'selected' : ''; ?>>
                    Completed
                </option>

                <option value="Requires Repair"
                    <?php echo ($current_status === 'Requires Repair') ? 'selected' : ''; ?>>
                    Requires Repair
                </option>

            </select>

            <button type="submit">
                <?php echo $edit_log ? 'Update Inspection' : 'Add Inspection'; ?>
            </button>

            <?php if ($edit_log): ?>

                <a href="catenary_bridge_logs.php">
                    Cancel
                </a>

            <?php endif; ?>

        </form>

    </div>

    <hr>

    <!-- ==================================
         6. DISPLAY INSPECTION LOGS
    =================================== -->

    <h2>Inspection Records</h2>

    <table>

        <tr>
            <th>Log ID</th>
            <th>Asset Name</th>
            <th>Asset Type</th>
            <th>Inspection Date</th>
            <th>Inspector</th>
            <th>Findings</th>
            <th>Defects</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php if ($log_result->num_rows > 0): ?>

            <?php while ($row = $log_result->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($row['log_id']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['asset_name']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['type_name']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['inspection_date']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['inspector_name']); ?>
                    </td>

                    <td>
                        <?php echo nl2br(htmlspecialchars($row['findings'])); ?>
                    </td>

                    <td>
                        <?php echo nl2br(htmlspecialchars($row['defect_description'] ?? 'None')); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['inspection_status']); ?>
                    </td>

                    <td>

                        <div class="actions">

                            <a href="catenary_bridge_logs.php?edit=<?php
                                echo urlencode($row['log_id']);
                            ?>">
                                Edit
                            </a>

                            <form method="POST"
                                  action=""
                                  onsubmit="return confirm('Are you sure you want to delete this inspection log?');">

                                <input type="hidden"
                                       name="action"
                                       value="delete">

                                <input type="hidden"
                                       name="log_id"
                                       value="<?php echo htmlspecialchars($row['log_id']); ?>">

                                <button type="submit"
                                        class="delete-button">
                                    Delete
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="9" class="empty">
                    No Bridge or Catenary inspection logs found.
                </td>
            </tr>

        <?php endif; ?>

    </table>

</div>

</body>
</html>

<?php
$conn->close();
?>
