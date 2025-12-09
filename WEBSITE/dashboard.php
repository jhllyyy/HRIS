<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get statistics
$total_employees = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'Active'")->fetch_assoc()['count'];
$present_today = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE() AND status = 'Present'")->fetch_assoc()['count'];
    // Count pending trainings (uses new trainings table)
    $trainings_count = 0;
    $cnt_res = $conn->query("SELECT COUNT(*) as count FROM trainings WHERE status = 'Pending'");
    if ($cnt_res) { $trainings_count = $cnt_res->fetch_assoc()['count']; }

// Get recent employees
$recent_employees = $conn->query("SELECT * FROM employees ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS - Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Jost", sans-serif;
            background: #f5f5f5ff;
            font-weight: 500;
            font-size: 16px;
        }
        .header {
            background: #3256a8;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        img {
            width : 60px;
            height : 60px;
        }
        .header h1 { font-size: 24px; }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: 1px solid white;
     
            text-decoration: none;
            font-size: 14px;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .menu-card {
            background: white;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: transform 0.2s;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .menu-card h3 {
            margin-top: 10px;
            color: #667eea;
        }
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
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
            color: #333;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-active { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="header">
        <h1> HR Information System</h1>
        <img src = "Camarines_Sur_Polytechnic_Colleges_Logo.png">
        <div class="user-info">
            <span>Welcome, <?php echo $_SESSION['full_name'] ?? $_SESSION['username']; ?> (<?php echo $_SESSION['role']; ?>)</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Employees</h3>
                <div class="number"><?php echo $total_employees; ?></div>
            </div>
            <div class="stat-card">
                <h3>Present Today</h3>
                <div class="number"><?php echo $present_today; ?></div>
            </div>
            <div class="stat-card">
                    <h3>Trainings</h3>
                    <div class="number"><?php echo $trainings_count; ?></div>
            </div>
        </div>
        
        <div class="menu-grid">
            <a href="employees.php" class="menu-card">
                <h3>👥 Employees</h3>
                <p>Manage employee records</p>
            </a>
            <a href="attendance.php" class="menu-card">
                <h3> Attendance</h3>
                <p>Track daily attendance</p>
            </a>
                <a href="trainings.php" class="menu-card">
                    <h3>  Trainings</h3>
                    <p>Manage trainings</p>
            </a>
            <a href="payroll.php" class="menu-card">
                <h3> Payroll</h3>
                <p>Process payroll</p>
            </a>
        </div>
        
        <div class="table-container">
            <h2 style="margin-bottom: 20px;">Employees</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($emp = $recent_employees->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $emp['emp_id']; ?></td>
                        <td><?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?></td>
                        <td><?php echo $emp['email']; ?></td>
                        <td><?php echo $emp['department']; ?></td>
                        <td><?php echo $emp['position']; ?></td>
                        <td><span class="badge badge-active"><?php echo $emp['status']; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>