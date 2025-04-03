<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $result = login($username, $password);
    
    if ($result === true) {
        // Redirect based on role
        switch ($_SESSION['role']) {
            case 'admin':
                header("Location: admin/dashboard.php");
                break;
            case 'teacher':
                header("Location: teacher/dashboard.php");
                break;
            case 'student':
            case 'parent':
                header("Location: student/dashboard.php");
                break;
            default:
                header("Location: index.php");
        }
        exit();
    } else {
        $error = $result;
    }
}

$pageTitle = "Login";
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">Login to <?php echo SITE_NAME; ?></h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
            <div class="card-footer text-center">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>