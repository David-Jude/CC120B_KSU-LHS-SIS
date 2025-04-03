<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only admin can access this page
checkRole('admin');

$pageTitle = "Admin Dashboard";
require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <h2 class="mb-4">Admin Dashboard</h2>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $totalUsers = $result->fetch_assoc()['total'];
                        ?>
                        <h2 class="card-text"><?php echo $totalUsers; ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Users</h5>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $activeUsers = $result->fetch_assoc()['total'];
                        ?>
                        <h2 class="card-text"><?php echo $activeUsers; ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Pending Users</h5>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $pendingUsers = $result->fetch_assoc()['total'];
                        ?>
                        <h2 class="card-text"><?php echo $pendingUsers; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Activity</th>
                                <th>IP Address</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT al.*, u.username, up.first_name, up.last_name 
                                                   FROM activity_logs al
                                                   JOIN users u ON al.user_id = u.user_id
                                                   JOIN user_profiles up ON u.user_id = up.user_id
                                                   ORDER BY al.created_at DESC
                                                   LIMIT 10");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['activity']); ?></td>
                                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>