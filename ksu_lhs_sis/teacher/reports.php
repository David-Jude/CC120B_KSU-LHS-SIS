<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only teacher can access this page
checkRole('teacher');

$teacherId = $_SESSION['user_id'];
$classId = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'attendance';

// Get teacher's classes
$classes = getTeacherClasses($teacherId);

// Generate report data
$reportData = [];
if ($classId > 0) {
    if ($reportType == 'attendance') {
        // Attendance summary report
        $stmt = $conn->prepare("SELECT 
                                   a.student_id,
                                   up.first_name,
                                   up.last_name,
                                   COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
                                   COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent,
                                   COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late,
                                   COUNT(CASE WHEN a.status = 'excused' THEN 1 END) as excused,
                                   COUNT(*) as total
                               FROM attendance a
                               JOIN students s ON a.student_id = s.student_id
                               JOIN users u ON s.user_id = u.user_id
                               JOIN user_profiles up ON u.user_id = up.user_id
                               WHERE a.class_id = ?
                               GROUP BY a.student_id");
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        // Grades summary report
        $stmt = $conn->prepare("SELECT 
                                   g.student_id,
                                   up.first_name,
                                   up.last_name,
                                   AVG(CASE WHEN g.quarter = '1' THEN g.grade END) as q1,
                                   AVG(CASE WHEN g.quarter = '2' THEN g.grade END) as q2,
                                   AVG(CASE WHEN g.quarter = '3' THEN g.grade END) as q3,
                                   AVG(CASE WHEN g.quarter = '4' THEN g.grade END) as q4,
                                   AVG(g.grade) as final
                               FROM grades g
                               JOIN students s ON g.student_id = s.student_id
                               JOIN users u ON s.user_id = u.user_id
                               JOIN user_profiles up ON u.user_id = up.user_id
                               WHERE g.class_id = ?
                               GROUP BY g.student_id");
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$pageTitle = "Generate Reports";
require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <h2 class="mb-4">Generate Reports</h2>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-5">
                        <label for="class_id" class="form-label">Class</label>
                        <select class="form-select" id="class_id" name="class_id" required>
                            <option value="">Select a class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>" <?php echo $class['class_id'] == $classId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['subject_name'] . ' - ' . $class['section']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="attendance" <?php echo $reportType == 'attendance' ? 'selected' : ''; ?>>Attendance Summary</option>
                            <option value="grades" <?php echo $reportType == 'grades' ? 'selected' : ''; ?>>Grades Summary</option>
                            <option value="form137">Form 137</option>
                            <option value="form8">Form 8</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($classId > 0 && !empty($reportData)): ?>
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5>
                        <?php 
                        $classInfo = $classes[array_search($classId, array_column($classes, 'class_id'))];
                        echo htmlspecialchars($classInfo['subject_name'] . ' - ' . $classInfo['section']);
                        ?>
                    </h5>
                    <button class="btn btn-sm btn-success" onclick="window.print()">Print Report</button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Student</th>
                                <?php if ($reportType == 'attendance'): ?>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Excused</th>
                                    <th>Total</th>
                                <?php else: ?>
                                    <th>1st Quarter</th>
                                    <th>2nd Quarter</th>
                                    <th>3rd Quarter</th>
                                    <th>4th Quarter</th>
                                    <th>Final Grade</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <?php if ($reportType == 'attendance'): ?>
                                    <td><?php echo $row['present']; ?></td>
                                    <td><?php echo $row['absent']; ?></td>
                                    <td><?php echo $row['late']; ?></td>
                                    <td><?php echo $row['excused']; ?></td>
                                    <td><?php echo $row['total']; ?></td>
                                <?php else: ?>
                                    <td><?php echo number_format($row['q1'], 2); ?></td>
                                    <td><?php echo number_format($row['q2'], 2); ?></td>
                                    <td><?php echo number_format($row['q3'], 2); ?></td>
                                    <td><?php echo number_format($row['q4'], 2); ?></td>
                                    <td><?php echo number_format($row['final'], 2); ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>