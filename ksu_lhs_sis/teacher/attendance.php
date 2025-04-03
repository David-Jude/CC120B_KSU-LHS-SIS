<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only teacher can access this page
checkRole('teacher');

$teacherId = $_SESSION['user_id'];
$classId = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $classId = intval($_POST['class_id']);
    $date = $_POST['date'];
    
    // Get students in class
    $students = getClassStudents($classId);
    
    foreach ($students as $student) {
        $studentId = $student['student_id'];
        $status = $_POST['attendance'][$studentId] ?? 'absent';
        $remarks = $_POST['remarks'][$studentId] ?? '';
        
        // Check if attendance already exists for this date
        $stmt = $conn->prepare("SELECT attendance_id FROM attendance 
                               WHERE student_id = ? AND class_id = ? AND date = ?");
        $stmt->bind_param("iis", $studentId, $classId, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing attendance
            $attendance = $result->fetch_assoc();
            $stmt = $conn->prepare("UPDATE attendance SET status = ?, remarks = ?
                                   WHERE attendance_id = ?");
            $stmt->bind_param("ssi", $status, $remarks, $attendance['attendance_id']);
        } else {
            // Insert new attendance
            $stmt = $conn->prepare("INSERT INTO attendance 
                                   (student_id, class_id, date, status, remarks)
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $studentId, $classId, $date, $status, $remarks);
        }
        
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Attendance saved successfully!";
    header("Location: attendance.php?class_id=$classId&date=$date");
    exit();
}

// Get teacher's classes
$classes = getTeacherClasses($teacherId);

// Get students for selected class
$students = [];
if ($classId > 0) {
    $students = getClassStudents($classId);
    
    // Get attendance for selected date
    $attendance = [];
    $stmt = $conn->prepare("SELECT student_id, status, remarks 
                            FROM attendance 
                            WHERE class_id = ? AND date = ?");
    $stmt->bind_param("is", $classId, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $attendance[$row['student_id']] = $row;
    }
}

$pageTitle = "Student Attendance";
require_once '../includes/header.php';

if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}
?>

<div class="row">
    <div class="col-lg-12">
        <h2 class="mb-4">Student Attendance</h2>
        
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
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date); ?>" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Load</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($classId > 0): ?>
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="class_id" value="<?php echo $classId; ?>">
                    <input type="hidden" name="date" value="<?php echo $date; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): 
                                    $studentAttendance = $attendance[$student['student_id']] ?? ['status' => 'present', 'remarks' => ''];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td>
                                        <select class="form-select" name="attendance[<?php echo $student['student_id']; ?>]">
                                            <option value="present" <?php echo $studentAttendance['status'] == 'present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo $studentAttendance['status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                            <option value="late" <?php echo $studentAttendance['status'] == 'late' ? 'selected' : ''; ?>>Late</option>
                                            <option value="excused" <?php echo $studentAttendance['status'] == 'excused' ? 'selected' : ''; ?>>Excused</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="remarks[<?php echo $student['student_id']; ?>]" value="<?php echo htmlspecialchars($studentAttendance['remarks']); ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">Save Attendance</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>