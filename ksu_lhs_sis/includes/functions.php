<?php
require_once 'db_connect.php';

// Log activity
function logActivity($userId, $activity) {
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $activity, $ip);
    $stmt->execute();
}

// Get user info
function getUserInfo($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT u.*, up.first_name, up.last_name, up.profile_pic 
                           FROM users u 
                           JOIN user_profiles up ON u.user_id = up.user_id 
                           WHERE u.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Get student info
function getStudentInfo($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT s.* FROM students s WHERE s.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Get all users (for admin)
function getAllUsers($status = null) {
    global $conn;
    
    $query = "SELECT u.user_id, u.username, u.email, u.role, u.status, u.created_at, 
                     up.first_name, up.last_name 
              FROM users u 
              JOIN user_profiles up ON u.user_id = up.user_id";
    
    if ($status) {
        $query .= " WHERE u.status = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $status);
    } else {
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

// Update user status
function updateUserStatus($userId, $status) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->bind_param("si", $status, $userId);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], "Updated user $userId status to $status");
        return true;
    }
    
    return false;
}

// Get classes for teacher
function getTeacherClasses($teacherId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT c.class_id, s.subject_name, c.section, c.school_year 
                           FROM classes c 
                           JOIN subjects s ON c.subject_id = s.subject_id 
                           WHERE c.teacher_id = ?");
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    
    return $classes;
}

// Get students in class
function getClassStudents($classId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT s.student_id, u.user_id, up.first_name, up.last_name 
                           FROM enrollments e 
                           JOIN students s ON e.student_id = s.student_id 
                           JOIN users u ON s.user_id = u.user_id 
                           JOIN user_profiles up ON u.user_id = up.user_id 
                           WHERE e.class_id = ? AND e.status = 'active'");
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    return $students;
}

// Get student grades
function getStudentGrades($studentId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT g.grade_id, g.quarter, g.grade, g.remarks, 
                                   s.subject_name, c.section 
                           FROM grades g 
                           JOIN classes c ON g.class_id = c.class_id 
                           JOIN subjects s ON c.subject_id = s.subject_id 
                           WHERE g.student_id = ? 
                           ORDER BY s.subject_name, g.quarter");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
    
    return $grades;
}

// Generate Form 137 data
function generateForm137($studentId) {
    global $conn;
    
    // Get student info
    $stmt = $conn->prepare("SELECT u.user_id, up.first_name, up.last_name, up.birthdate, up.gender,
                                  s.lrn, s.grade_level, s.section, s.school_year
                           FROM users u
                           JOIN user_profiles up ON u.user_id = up.user_id
                           JOIN students s ON u.user_id = s.user_id
                           WHERE s.student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    // Get grades by quarter
    $quarters = ['1', '2', '3', '4'];
    $gradesByQuarter = [];
    
    foreach ($quarters as $quarter) {
        $stmt = $conn->prepare("SELECT s.subject_name, g.grade
                               FROM grades g
                               JOIN classes c ON g.class_id = c.class_id
                               JOIN subjects s ON c.subject_id = s.subject_id
                               WHERE g.student_id = ? AND g.quarter = ?
                               ORDER BY s.subject_name");
        $stmt->bind_param("is", $studentId, $quarter);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        
        $gradesByQuarter[$quarter] = $grades;
    }
    
    // Get attendance summary
    $stmt = $conn->prepare("SELECT 
                               COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                               COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                               COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                               COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused
                           FROM attendance
                           WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $attendance = $stmt->get_result()->fetch_assoc();
    
    return [
        'student' => $student,
        'grades' => $gradesByQuarter,
        'attendance' => $attendance
    ];
}
?>