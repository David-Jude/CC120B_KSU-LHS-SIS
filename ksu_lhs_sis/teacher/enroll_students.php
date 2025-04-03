<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only teacher can access this page
checkRole('teacher');

$teacherId = $_SESSION['user_id'];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $classId = intval($_POST['class_id']);
    $studentId = intval($_POST['student_id']);
    
    // Check if student is already enrolled
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND class_id = ?");
    $stmt->bind_param("ii", $studentId, $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message = "Student is already enrolled in this class.";
    } else {
        // Enroll student
        $stmt = $conn->prepare("INSERT INTO enrollments (student_id, class_id, enrollment_date) VALUES (?, ?, CURDATE())");
        $stmt->bind_param("ii", $studentId, $classId);
        
        if ($stmt->execute()) {
            $message = "Student enrolled successfully!";
            logActivity($teacherId, "Enrolled student $studentId in class $classId");
        } else {
            $message = "Error enrolling student.";
        }
    }
}

// Get teacher's classes
$classes = getTeacherClasses($teacherId);

// Get all active students
$stmt = $conn->prepare("SELECT s.student_id, u.user_id, up.first_name, up.last_name 
                       FROM students s
                       JOIN users u ON s.user_id = u.user_id
                       JOIN user_profiles up ON u.user_id = up.user_id
                       WHERE u.status = 'active'");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = "Enroll Students";
require_once '../includes/header.php';

if ($message): ?>
    <div class="alert alert-info"><?php echo $message; ?></div>
<?php endif; ?>
?>

<div class="row">
    <div class="col-lg-12">
        <h2 class="mb-4">Enroll Students</h2>
        
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="class_id" class="form-label">Class</label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">Select a class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>">
                                        <?php echo htmlspecialchars($class['subject_name'] . ' - ' . $class['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Select a student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['student_id']; ?>">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Enroll Student</button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Currently Enrolled Students</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Student</th>
                                <th>Enrollment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT e.enrollment_id, e.enrollment_date, e.status,
                                                  s.subject_name, c.section,
                                                  up.first_name, up.last_name
                                           FROM enrollments e
                                           JOIN classes c ON e.class_id = c.class_id
                                           JOIN subjects s ON c.subject_id = s.subject_id
                                           JOIN students st ON e.student_id = st.student_id
                                           JOIN users u ON st.user_id = u.user_id
                                           JOIN user_profiles up ON u.user_id = up.user_id
                                           WHERE c.teacher_id = ?
                                           ORDER BY s.subject_name, up.last_name");
                            $stmt->bind_param("i", $teacherId);
                            $stmt->execute();
                            $enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            foreach ($enrollments as $enrollment):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment['subject_name'] . ' - ' . $enrollment['section']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $enrollment['status'] == 'active' ? 'success' : 
                                             ($enrollment['status'] == 'dropped' ? 'danger' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst($enrollment['status']); ?>
                                    </span>
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