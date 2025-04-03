<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Only teacher can access this page
checkRole('teacher');

$teacherId = $_SESSION['user_id'];
$classes = getTeacherClasses($teacherId);

$pageTitle = "Teacher Dashboard";
require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <h2 class="mb-4">Teacher Dashboard</h2>
        
        <div class="row">
            <?php foreach ($classes as $class): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($class['subject_name']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">Section: <?php echo htmlspecialchars($class['section']); ?></h6>
                        <p class="card-text">School Year: <?php echo htmlspecialchars($class['school_year']); ?></p>
                        <a href="attendance.php?class_id=<?php echo $class['class_id']; ?>" class="card-link">Attendance</a>
                        <a href="grades.php?class_id=<?php echo $class['class_id']; ?>" class="card-link">Grades</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>