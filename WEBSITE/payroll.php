<?php
require 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';

// Detect payroll table columns to adapt to schema differences
$pay_columns_res = mysqli_query($conn, "SHOW COLUMNS FROM payroll");
$pay_cols = [];
$pay_emp_col = 'employee_id';
$pay_id_col = 'id';
$pay_payment_col = 'payment_date';
if ($pay_columns_res) {
    while ($c = mysqli_fetch_assoc($pay_columns_res)) {
        $pay_cols[] = $c['Field'];
    }
    foreach (['employee_id','emp_id','user_id','employee','emp'] as $cname) {
        if (in_array($cname, $pay_cols)) { $pay_emp_col = $cname; break; }
    }
    if (in_array('id', $pay_cols)) {
        $pay_id_col = 'id';
    } elseif (in_array('payroll_id', $pay_cols)) {
        $pay_id_col = 'payroll_id';
    }
    foreach (['payment_date','paid_on','date_paid'] as $cname) {
        if (in_array($cname, $pay_cols)) { $pay_payment_col = $cname; break; }
    }
}

// Generate Payroll
if (isset($_POST['generate'])) {
    $emp_id = (int)$_POST['employee_id'];
    $month = mysqli_real_escape_string($conn, $_POST['month']);
    $year = (int)$_POST['year'];
    $basic = (float)$_POST['basic_salary'];
    $allowances = (float)$_POST['allowances'];
    $deductions = (float)$_POST['deductions'];
    $net = $basic + $allowances - $deductions;
    
        // Use detected payroll employee column name
        $sql = "INSERT INTO payroll (`" . $pay_emp_col . "`, month, year, basic_salary, allowances, deductions, net_salary) 
            VALUES ($emp_id, '$month', $year, $basic, $allowances, $deductions, $net)";
    
    if (mysqli_query($conn, $sql)) {
        $message = "<div class='success'>Payroll generated successfully!</div>";
    } else {
        $message = "<div class='error'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Mark as Paid
if (isset($_GET['paid'])) {
    $id = (int)$_GET['paid'];
    $date = date('Y-m-d');
    // Use detected payroll ID and payment column names
    mysqli_query($conn, "UPDATE payroll SET status='Paid', `" . $pay_payment_col . "`='$date' WHERE `" . $pay_id_col . "`=$id");
    $message = "<div class='success'>Payment marked as paid!</div>";
}

// Get employees for dropdown (use actual employee columns) and handle errors
$employees_result = mysqli_query($conn, "SELECT emp_id AS id, CONCAT(first_name, ' ', last_name) AS name, salary, position FROM employees WHERE status='Active' ORDER BY name");
$employees = [];
if ($employees_result) {
    while ($emp = mysqli_fetch_assoc($employees_result)) {
        $employees[] = $emp;
    }
} else {
    $message .= "<div class='error'>Employee query error: " . mysqli_error($conn) . "</div>";
}

// Get all payroll records and handle errors
$payrolls_result = mysqli_query($conn, "SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) AS name, e.position 
                                 FROM payroll p 
                                 JOIN employees e ON p.`" . $pay_emp_col . "` = e.emp_id 
                                 ORDER BY p.year DESC, p.month DESC");
$payrolls = [];
if ($payrolls_result) {
    while ($row = mysqli_fetch_assoc($payrolls_result)) {
        // normalize id and payment_date keys for template
        if (!isset($row['id']) && isset($row[$pay_id_col])) { $row['id'] = $row[$pay_id_col]; }
        if (!isset($row['payment_date']) && isset($row[$pay_payment_col])) { $row['payment_date'] = $row[$pay_payment_col]; }
        $payrolls[] = $row;
    }
} else {
    $message .= "<div class='error'>Payroll query error: " . mysqli_error($conn) . "</div>";
}

// Calculate totals (safely)
$total_paid = 0;
$total_pending = 0;
$tot_paid_res = mysqli_query($conn, "SELECT SUM(net_salary) as total FROM payroll WHERE status='Paid'");
if ($tot_paid_res) {
    $total_paid = mysqli_fetch_assoc($tot_paid_res)['total'] ?? 0;
}
$tot_pending_res = mysqli_query($conn, "SELECT SUM(net_salary) as total FROM payroll WHERE status='Pending'");
if ($tot_pending_res) {
    $total_pending = mysqli_fetch_assoc($tot_pending_res)['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payroll Management</title>
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-box h3 { color: #666; margin-bottom: 10px; font-size: 14px; }
        .stat-box .amount { font-size: 28px; color: #667eea; font-weight: bold; }
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
        .badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-paid { background: #d4edda; color: #155724; }
        .btn-paid {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 12px;
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
        .amount-positive { color: #28a745; }
        .amount-negative { color: #dc3545; }
    </style>
    <script>
        function calculateNet() {
            const basic = parseFloat(document.getElementById('basic').value) || 0;
            const allowances = parseFloat(document.getElementById('allowances').value) || 0;
            const deductions = parseFloat(document.getElementById('deductions').value) || 0;
            const net = basic + allowances - deductions;
            document.getElementById('net').value = net.toFixed(2);
        }
        
        function loadSalary() {
            const select = document.getElementById('employee');
            const salary = select.options[select.selectedIndex].getAttribute('data-salary');
            document.getElementById('basic').value = salary;
            calculateNet();
        }
    </script>
</head>
<body>
    <div class="header">
        <h1> Payroll Management</h1>
        <a href="dashboard.php" class="back">← Back</a>
    </div>
    
    <div class="container">
        <?php echo $message; ?>
        
        <div class="stats">
            <div class="stat-box">
                <h3>Total Paid This Month</h3>
                <div class="amount amount-negative">₱<?php echo number_format($total_paid, 2); ?></div>
            </div>
            <div class="stat-box">
                <h3>Pending Payments</h3>
                <div class="amount amount-positive">₱<?php echo number_format($total_pending, 2); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">Generate Payroll</h2>
            <form method="POST">
                <div class="form-row">
                    <div>
                        <label>Employee</label>
                        <select name="employee_id" id="employee" onchange="loadSalary()" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" data-salary="<?php echo $emp['salary']; ?>">
                                        <?php echo $emp['name']; ?> - ₱<?php echo number_format($emp['salary'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Month</label>
                        <select name="month" required>
                            <option value="January">January</option>
                            <option value="February">February</option>
                            <option value="March">March</option>
                            <option value="April">April</option>
                            <option value="May">May</option>
                            <option value="June">June</option>
                            <option value="July">July</option>
                            <option value="August">August</option>
                            <option value="September">September</option>
                            <option value="October">October</option>
                            <option value="November">November</option>
                            <option value="December">December</option>
                        </select>
                    </div>
                    <div>
                        <label>Year</label>
                        <input type="number" name="year" value="<?php echo date('Y'); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div>
                        <label>Basic Salary</label>
                        <input type="number" step="0.01" name="basic_salary" id="basic" onkeyup="calculateNet()" required>
                    </div>
                    <div>
                        <label>Allowances</label>
                        <input type="number" step="0.01" name="allowances" id="allowances" value="0" onkeyup="calculateNet()">
                    </div>
                    <div>
                        <label>Deductions</label>
                        <input type="number" step="0.01" name="deductions" id="deductions" value="0" onkeyup="calculateNet()">
                    </div>
                    <div>
                        <label>Net Salary</label>
                        <input type="number" step="0.01" id="net" readonly style="background: #f8f9fa; font-weight: bold;">
                    </div>
                </div>
                
                <button type="submit" name="generate">Generate Payroll</button>
            </form>
        </div>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">Payroll Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Month</th>
                        <th>Year</th>
                        <th>Basic Salary</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payrolls as $row): ?>
                    <tr>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['position']; ?></td>
                        <td><?php echo $row['month']; ?></td>
                        <td><?php echo $row['year']; ?></td>
                        <td>₱<?php echo number_format($row['basic_salary'], 2); ?></td>
                        <td class="amount-positive">+₱<?php echo number_format($row['allowances'], 2); ?></td>
                        <td class="amount-negative">-₱<?php echo number_format($row['deductions'], 2); ?></td>
                        <td><strong>₱<?php echo number_format($row['net_salary'], 2); ?></strong></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td><?php echo $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : '-'; ?></td>
                        <td>
                            <?php if ($row['status'] == 'Pending'): ?>
                                <a href="?paid=<?php echo $row['id']; ?>" class="btn-paid" onclick="return confirm('Mark as paid?')">Mark Paid</a>
                            <?php else: ?>
                                ✓ Paid
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