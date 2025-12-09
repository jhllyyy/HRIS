<?php
require 'config.php';

if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$msg = '';

// Ensure trainings table exists (simple migration)
$create_sql = "CREATE TABLE IF NOT EXISTS trainings (
    training_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    description TEXT,
    status VARCHAR(20) DEFAULT 'Pending',
    applied_date DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $create_sql);

// Use explicit column names for the trainings table
$trainings_table = 'trainings';
$train_emp_col = 'emp_id';
$train_id_col = 'training_id';
$train_applied_col = 'applied_date';

// Add Training
if (isset($_POST['add_training'])) {
    $emp_id = (int)$_POST['employee_id'];
    $title = mysqli_real_escape_string($conn, $_POST['training_title']);
    $start = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end = mysqli_real_escape_string($conn, $_POST['end_date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $sql = "INSERT INTO `" . $trainings_table . "` (`" . $train_emp_col . "`, title, start_date, end_date, description) 
            VALUES ($emp_id, '$title', '$start', " . ($end ? "'$end'" : "NULL") . ", '$description')";

    if (mysqli_query($conn, $sql)) {
        $msg = "<div class='success'>Training added successfully!</div>";
    } else {
        $msg = "<div class='error'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Approve Training
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($conn, "UPDATE `" . $trainings_table . "` SET status='Approved' WHERE `" . $train_id_col . "`=$id");
    $msg = "<div class='success'>Training approved!</div>";
}

// Reject Training
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    mysqli_query($conn, "UPDATE `" . $trainings_table . "` SET status='Rejected' WHERE `" . $train_id_col . "`=$id");
    $msg = "<div class='success'>Training rejected!</div>";
}

// Get employees for dropdown
$employees_result = mysqli_query($conn, "SELECT emp_id AS id, CONCAT(first_name, ' ', last_name) AS name FROM employees WHERE status='Active' ORDER BY name");
$employees = [];
if ($employees_result) {
    while ($emp = mysqli_fetch_assoc($employees_result)) {
        $employees[] = $emp;
    }
} else {
    $msg .= "<div class='error'>Employee query error: " . mysqli_error($conn) . "</div>";
}

// Get all trainings (reusing leaves table) and handle errors
// Query trainings from the new trainings table
$trainings_query = "SELECT t.*, CONCAT(e.first_name, ' ', e.last_name) AS name, e.position 
                               FROM `" . $trainings_table . "` t 
                               JOIN employees e ON t.`" . $train_emp_col . "` = e.emp_id 
                               ORDER BY t.`" . $train_applied_col . "` DESC";
$trainings_res = mysqli_query($conn, $trainings_query);
$trainings = [];
if ($trainings_res) {
    while ($row = mysqli_fetch_assoc($trainings_res)) {
        if (!isset($row['id']) && isset($row[$train_id_col])) { $row['id'] = $row[$train_id_col]; }
        if (!isset($row['applied_date']) && isset($row[$train_applied_col])) { $row['applied_date'] = $row[$train_applied_col]; }
        $trainings[] = $row;
    }
} else {
    $msg .= "<div class='error'>Trainings query error: " . mysqli_error($conn) . "</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Training Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #1f3a57; color: white; padding: 18px 28px; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 28px auto; padding: 0 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 6px 18px rgba(19,37,59,0.04); margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        button { padding: 10px 18px; background: #0b9444; color: #fff; border: none; border-radius: 8px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eef2f6; }
        th { background: #fbfcfd; font-weight: 600; }
        .success { background: #e6f4ea; color: #0b6a35; padding: 12px; border-radius: 6px; margin-bottom: 12px; }
        .error { background: #ffecec; color: #8b1e1e; padding: 12px; border-radius: 6px; margin-bottom: 12px; }
        .btn { padding: 6px 10px; border-radius: 6px; color: #fff; text-decoration: none; }
        .btn-approve { background: #0b9444; }
        .btn-reject { background: #d64545; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Training Management</h1>
        <a href="dashboard.php" style="color:#fff; text-decoration:none;">← Back</a>
    </div>
    <div class="container">
        <?php echo $msg; ?>
        <div class="card">
            <h2 style="margin-bottom:12px;">Add Training</h2>
            <form method="POST">
                <div class="form-row">
                    <select name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo $emp['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="training_title" placeholder="Training Title" required>
                    <input type="date" name="start_date" required>
                    <input type="date" name="end_date">
                </div>
                <textarea name="description" placeholder="Description" style="min-height:80px; margin-bottom:10px;"></textarea>
                <button type="submit" name="add_training">Add Training</button>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-bottom:12px;">All Trainings</h2>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Added On</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trainings as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['position']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                        <td><?php echo $row['end_date'] ? date('M d, Y', strtotime($row['end_date'])) : '-'; ?></td>
                        <td><?php echo $row['applied_date'] ? date('M d, Y', strtotime($row['applied_date'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>
                            <?php if ($row['status'] == 'Pending'): ?>
                                <a href="?approve=<?php echo $row['id']; ?>" class="btn btn-approve">Approve</a>
                                <a href="?reject=<?php echo $row['id']; ?>" class="btn btn-reject">Reject</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
