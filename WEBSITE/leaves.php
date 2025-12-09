<?php
require 'config.php';

if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$msg = '';

// Detect leaves table columns so we can adapt to different schemas
$leave_columns_res = mysqli_query($conn, "SHOW COLUMNS FROM leaves");
$leave_cols = [];
$leave_emp_col = 'employee_id';
$leave_id_col = 'id';
$applied_col = 'applied_date';
if ($leave_columns_res) {
    while ($c = mysqli_fetch_assoc($leave_columns_res)) {
        $leave_cols[] = $c['Field'];
    }
    foreach (['employee_id','emp_id','user_id','employee','emp'] as $cname) {
        if (in_array($cname, $leave_cols)) { $leave_emp_col = $cname; break; }
    }
    if (in_array('id', $leave_cols)) {
        $leave_id_col = 'id';
    } elseif (in_array('leave_id', $leave_cols)) {
        $leave_id_col = 'leave_id';
    }
    foreach (['applied_date','created_at','applied_on','date_applied'] as $cname) {
        if (in_array($cname, $leave_cols)) { $applied_col = $cname; break; }
    }
}

// Apply for Leave
if (isset($_POST['apply'])) {
    $emp_id = (int)$_POST['employee_id'];
    $type = mysqli_real_escape_string($conn, $_POST['leave_type']);
    $start = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end = mysqli_real_escape_string($conn, $_POST['end_date']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    
            // Insert using detected employee column name
            $sql = "INSERT INTO leaves (`" . $leave_emp_col . "`, leave_type, start_date, end_date, reason) 
                VALUES ($emp_id, '$type', '$start', '$end', '$reason')";
    
    if (mysqli_query($conn, $sql)) {
        $msg = "<div class='success'>Leave request submitted successfully!</div>";
    } else {
        $msg = "<div class='error'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Approve Leave
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($conn, "UPDATE leaves SET status='Approved' WHERE " . $leave_id_col . "=$id");
    $msg = "<div class='success'>Leave approved!</div>";
}

// Reject Leave
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    mysqli_query($conn, "UPDATE leaves SET status='Rejected' WHERE " . $leave_id_col . "=$id");
    $msg = "<div class='success'>Leave rejected!</div>";
}

// Get employees for dropdown (use actual employee columns)
$employees_result = mysqli_query($conn, "SELECT emp_id AS id, CONCAT(first_name, ' ', last_name) AS name FROM employees WHERE status='Active' ORDER BY name");
$employees = [];
if ($employees_result) {
    while ($emp = mysqli_fetch_assoc($employees_result)) {
        $employees[] = $emp;
    }
} else {
    $msg .= "<div class='error'>Employee query error: " . mysqli_error($conn) . "</div>";
}

// Get all leave requests and handle errors
$leaves_result = mysqli_query($conn, "SELECT l.*, CONCAT(e.first_name, ' ', e.last_name) AS name, e.position 
                               FROM leaves l 
                               JOIN employees e ON l." . $leave_emp_col . " = e.emp_id 
                               ORDER BY l." . $applied_col . " DESC");
$leaves = [];
if ($leaves_result) {
    while ($row = mysqli_fetch_assoc($leaves_result)) {
        // Normalize keys so template can rely on 'id' and 'applied_date'
        if (!isset($row['id']) && isset($row[$leave_id_col])) {
            $row['id'] = $row[$leave_id_col];
        }
        if (!isset($row['applied_date']) && isset($row[$applied_col])) {
            $row['applied_date'] = $row[$applied_col];
        }
        $leaves[] = $row;
    }
} else {
    $msg .= "<div class='error'>Leaves query error: " . mysqli_error($conn) . "</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Leave Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        button {
            padding: 10px 25px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background: #5568d3; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .btn {
            padding: 5px 15px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
            margin-right: 5px;
        }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        a.back {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏖️ Leave Management</h1>
        <a href="dashboard.php" class="back">← Back</a>
    </div>
    
    <div class="container">
        <?php echo $msg; ?>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">Apply for Leave</h2>
            <form method="POST">
                <div class="form-row">
                    <select name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo $emp['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="leave_type" required>
                        <option value="">Select Leave Type</option>
                        <option value="Sick">Sick Leave</option>
                        <option value="Vacation">Vacation</option>
                        <option value="Emergency">Emergency</option>
                        <option value="Personal">Personal</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="date" name="start_date" placeholder="Start Date" required>
                    <input type="date" name="end_date" placeholder="End Date" required>
                </div>
                <textarea name="reason" placeholder="Reason for leave" required></textarea>
                <button type="submit" name="apply" style="margin-top: 15px;">Submit Leave Request</button>
            </form>
        </div>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">All Leave Requests</h2>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Applied On</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaves as $row): 
                        $start = strtotime($row['start_date']);
                        $end = strtotime($row['end_date']);
                        $days = round(($end - $start) / 86400) + 1;
                    ?>
                    <tr>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['position']; ?></td>
                        <td><?php echo $row['leave_type']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                        <td><?php echo $days; ?> days</td>
                        <td><?php echo substr($row['reason'], 0, 50); ?>...</td>
                        <td><?php echo date('M d, Y', strtotime($row['applied_date'])); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
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