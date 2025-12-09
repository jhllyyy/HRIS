<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $dob = sanitize($_POST['date_of_birth']);
            $gender = sanitize($_POST['gender']);
            $address = sanitize($_POST['address']);
            $hire_date = sanitize($_POST['hire_date']);
            $department = sanitize($_POST['department']);
            $position = sanitize($_POST['position']);
            $salary = sanitize($_POST['salary']);
            
            $sql = "INSERT INTO employees (first_name, last_name, email, phone, date_of_birth, gender, address, hire_date, department, position, salary) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssd", $first_name, $last_name, $email, $phone, $dob, $gender, $address, $hire_date, $department, $position, $salary);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Employee added successfully!</div>';
            } else {
                $message = '<div class="alert alert-error">Error: ' . $conn->error . '</div>';
            }
        } elseif ($_POST['action'] == 'delete') {
            $emp_id = (int)$_POST['emp_id'];
            $conn->query("DELETE FROM employees WHERE emp_id = $emp_id");
            $message = '<div class="alert alert-success">Employee deleted successfully!</div>';
        }
    }
}

// Get all employees
$employees = $conn->query("SELECT * FROM employees ORDER BY emp_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS - Employees</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: #1f3a57;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: 1px solid white;
            text-decoration: none;
        }
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input, select, textarea {
            padding: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        button {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        button:hover { opacity: 0.9; }
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
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1> Employee Management</h1>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <?php echo $message; ?>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">Add New Employee</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hire Date *</label>
                        <input type="date" name="hire_date" required>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department">
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position">
                    </div>
                    <div class="form-group">
                        <label>Salary</label>
                        <input type="number" step="0.01" name="salary">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"></textarea>
                </div>
                
                <button type="submit">Add Employee</button>
            </form>
        </div>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">Employee List</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($emp = $employees->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $emp['emp_id']; ?></td>
                        <td><?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?></td>
                        <td><?php echo $emp['email']; ?></td>
                        <td><?php echo $emp['phone']; ?></td>
                        <td><?php echo $emp['department']; ?></td>
                        <td><?php echo $emp['position']; ?></td>
                        <td>$<?php echo number_format($emp['salary'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $emp['status'] == 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $emp['status']; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this employee?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="emp_id" value="<?php echo $emp['emp_id']; ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>