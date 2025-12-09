<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT u.*, e.first_name, e.last_name 
            FROM users u 
            LEFT JOIN employees e ON u.emp_id = e.emp_id 
            WHERE u.username = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Support both hashed and legacy plain-text passwords.
        $stored = $user['password'];
        $password_ok = false;

        if (!empty($stored) && password_verify($password, $stored)) {
            // Modern hashed password
            $password_ok = true;
        } elseif ($password === $stored) {
            // Legacy plain-text password: allow login and upgrade to a hash
            $password_ok = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            if ($update) {
                $update->bind_param("si", $newHash, $user['user_id']);
                $update->execute();
                $update->close();
            }
        }

        if ($password_ok) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['emp_id'] = $user['emp_id'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS - Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body {
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
           background: url(background.jpg);
           animation: gradientBG 15s ease infinite;
           overflow: hidden;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-container {
            position: relative;
            background: white;
            padding: 40px;
            border-radius: 10px;
            backdrop-filter:  blur(15px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0px 25px 45px rgba(0,0,0,0.4);
            z-index: 10;
            overflow: hidden;
            width: 380px;
            max-width: 400px;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.05);
            transform: skewX(-15deg);
            transition: 0.5s;
            pointer-events: none;
            background-size: 400% 400%;
            animation: rotateBG 10s linear infinite;
            z-index: -1;
        }   
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            opacity: 0.9;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .info {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>HR Information System</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="info">
            Default Login: admin / admin123
        </div>
    </div>
</body>
</html>