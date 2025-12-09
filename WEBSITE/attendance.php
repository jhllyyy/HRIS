<?php
require 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$msg = '';

// Mark Attendance
if (isset($_POST['mark'])) {
    $emp_id = (int)$_POST['employee_id'];
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time_in = mysqli_real_escape_string($conn, $_POST['time_in']);
    $time_out = mysqli_real_escape_string($conn, $_POST['time_out']);
    
        $sql = "INSERT INTO attendance (emp_id, date, time_in, time_out) 
            VALUES ($emp_id, '$date', '$time_in', '$time_out')";
    
    if (mysqli_query($conn, $sql)) {
        $msg = "<div class='success'>Attendance marked successfully!</div>";
    } else {
        $msg = "<div class='error'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Get employees for dropdown (use actual column names) and handle errors
$employees_result = mysqli_query($conn, "SELECT emp_id AS id, CONCAT(first_name, ' ', last_name) AS name FROM employees WHERE status='Active' ORDER BY name");
$employees = [];
if ($employees_result) {
    while ($emp = mysqli_fetch_assoc($employees_result)) {
        $employees[] = $emp;
    }
} else {
    $msg .= "<div class='error'>Employee query error: " . mysqli_error($conn) . "</div>";
}

// Get today's attendance and handle errors
$today = date('Y-m-d');
$attendance_result = mysqli_query($conn, "SELECT a.*, CONCAT(e.first_name, ' ', e.last_name) AS name, e.position 
                                   FROM attendance a 
                                   JOIN employees e ON a.emp_id = e.emp_id 
                                   WHERE a.date = '$today'
                                   ORDER BY a.date DESC, a.time_in DESC");
$attendance = [];
if ($attendance_result) {
    while ($row = mysqli_fetch_assoc($attendance_result)) {
        $attendance[] = $row;
    }
} else {
    $msg .= "<div class='error'>Attendance query error: " . mysqli_error($conn) . "</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: #1f3a57;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container {
            max-width: 1200px;
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
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
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
        <h1> Attendance Tracking</h1>
        <a href="dashboard.php" class="back">← Back</a>
    </div>
    
    <div class="container">
        <?php echo $msg; ?>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">Mark Attendance</h2>
            <form method="POST">
                <div class="form-row">
                    <select name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo $emp['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    <input type="time" name="time_in" placeholder="Time In" required>
                    <input type="time" name="time_out" placeholder="Time Out">
                </div>
                <button type="submit" name="mark">Mark Attendance</button>
            </form>
        </div>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">Today's Attendance (<?php echo date('F d, Y'); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($attendance) > 0): ?>
                        <?php foreach ($attendance as $row): ?>
                        <tr>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['position']; ?></td>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['time_in']; ?></td>
                            <td><?php echo $row['time_out'] ?? 'Not yet'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No attendance records for today</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>