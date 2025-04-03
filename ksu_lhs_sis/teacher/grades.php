<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only teacher can access this page
checkRole('teacher');

$teacherId = $_SESSION['user_id'];
$classId = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$quarter = isset($_GET['quarter']) ? $_GET['quarter'] : '1';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $classId = intval($_POST['class_id']);
    $quarter = $_POST['quarter'];
    
    // Get students in class
    $students = getClassStudents($classId);
    
    foreach ($students as $student) {
        $studentId = $student['student_id'];
        $grade = $_POST['grades'][$studentId] ?? 0;
        $remarks = $_POST['remarks'][$studentId] ?? '';
        
        // Check if grade already exists for this quarter
        $stmt = $conn->prepare("SELECT grade_id FROM grades 
                               WHERE student_id = ? AND class_id = ? AND quarter = ?");
        $stmt->bind_param("iis", $studentId, $classId, $quarter);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing grade
            $gradeRow = $result->fetch_assoc();
            $stmt = $conn->prepare("UPDATE grades SET grade = ?, remarks = ?
                                   WHERE grade_id = ?");
            $stmt->bind_param("ssi", $grade, $remarks, $gradeRow['grade_id']);
        } else {
            // Insert new grade
            $stmt = $conn->prepare("INSERT INTO grades 
                                   (student_id, class_id, quarter, grade, remarks)
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $studentId, $classId, $quarter, $grade, $remarks);
        }
        
        $stmt->execute();
    }
    
    $_SESSION['message'] = "Grades saved successfully!";
    header("Location: grades.php?class_id=$classId&quarter=$quarter");
    exit();
}

// Get teacher's classes
$classes = getTeacherClasses($teacherId);

// Get students for selected class
$students = [];
if ($classId > 0) {
    $students = getClassStudents($classId);
    
    // Get grades for selected quarter
    $grades = [];
    $stmt = $conn->prepare("SELECT student_id, grade, remarks 
                            FROM grades 
                            WHERE class_id = ? AND quarter = ?");
    $stmt->bind_param("is", $classId, $quarter);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $grades[$row['student_id']] = $row;
    }
}

$pageTitle = "Student Grades";
require_once '../includes/header.php';

if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}
?>

<div class="row">
    <div class="col-lg-12">
        <h2 class="mb-4">Student Grades</h2>
        
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
                        <label for="quarter" class="form-label">Quarter</label>
                        <select class="form-select" id="quarter" name="quarter" required>
                            <option value="1" <?php echo $quarter == '1' ? 'selected' : ''; ?>>1st Quarter</option>
                            <option value="2" <?php echo $quarter == '2' ? 'selected' : ''; ?>>2nd Quarter</option>
                            <option value="3" <?php echo $quarter == '3' ? 'selected' : ''; ?>>3rd Quarter</option>
                            <option value="4" <?php echo $quarter == '4' ? 'selected' : ''; ?>>4th Quarter</option>
                        </select>
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
                    <input type="hidden" name="quarter" value="<?php echo $quarter; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Grade</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): 
                                    $studentGrade = $grades[$student['student_id']] ?? ['grade' => '', 'remarks' => ''];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td>
                                        <input type="number" step="0.01" min="65" max="100" class="form-control" 
                                               name="grades[<?php echo $student['student_id']; ?>]" 
                                               value="<?php echo htmlspecialchars($studentGrade['grade']); ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" 
                                               name="remarks[<?php echo $student['student_id']; ?>]" 
                                               value="<?php echo htmlspecialchars($studentGrade['remarks']); ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">Save Grades</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>