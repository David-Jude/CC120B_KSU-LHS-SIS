<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only admin can access this page
checkRole('admin');

// Handle user status change
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'deny', 'delete'])) {
        $status = $action == 'approve' ? 'active' : ($action == 'deny' ? 'inactive' : '');
        
        if ($action == 'delete') {
            // Delete user (you might want to implement soft delete instead)
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            $stmt->bind_param("si", $status, $userId);
        }
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "User updated successfully";
        } else {
            $_SESSION['error'] = "Error updating user";
        }
        
        header("Location: manage_users.php");
        exit();
    }
}

// Get all users
$users = getAllUsers();

$pageTitle = "Manage Users";
require_once '../includes/header.php';

// Display messages
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<div class="row">
    <div class="col-lg-12">
        <h2 class="mb-4">Manage Users</h2>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['status'] == 'active' ? 'success' : 
                                             ($user['status'] == 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['status'] == 'pending'): ?>
                                        <a href="manage_users.php?action=approve&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                        <a href="manage_users.php?action=deny&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger">Deny</a>
                                    <?php endif; ?>
                                    <a href="manage_users.php?action=delete&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>