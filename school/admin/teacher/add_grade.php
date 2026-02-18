<?php
require "../db.php";

if ($_SESSION["role"] != "teacher" && $_SESSION["role"] != "admin") {
    header("Location: ../../login.php");
    exit();
}

$msg = "";
$errors = [];

// Fetch students (include roll)
$students = $conn->query("SELECT students.id, students.full_name, students.class_id, students.roll FROM students");

// Fetch subjects
$subjects = $conn->query("SELECT id, name FROM subjects");

// Fetch classes
$classes = $conn->query("SELECT id, class_name FROM classes");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST["student_id"];
    $subject_id = $_POST["subject_id"];
    $score = trim($_POST["score"]);
    $status = trim($_POST["status"]);
    $optional = isset($_POST["optional"]) ? 1 : 0;
    $selected_roll = trim($_POST['selected_roll'] ?? '');

    if (empty($student_id)) {
        $errors['student'] = "Please select a student.";
    } else {
        // Verify student exists
        $sstmt = $conn->prepare("SELECT roll FROM students WHERE id = ? LIMIT 1");
        $sstmt->bind_param("i", $student_id);
        $sstmt->execute();
        $sres = $sstmt->get_result();
        if (!$sres || $sres->num_rows == 0) {
            $errors['student'] = "Selected student not found.";
        } else {
            $srow = $sres->fetch_assoc();
            $student_roll = $srow['roll'] ?? '';
            if ($selected_roll !== '' && $student_roll !== $selected_roll) {
                $errors['student'] = "Selected student does not match the provided roll.";
            }
        }
    }

    if (empty($subject_id)) {
        $errors['subject'] = "Please select a subject.";
    }
    if ($score === '') {
        $errors['score'] = "Score is required.";
    } elseif (!is_numeric($score) || $score < 0 || $score > 100) {
        $errors['score'] = "Score must be a number between 0 and 100.";
    }
    if (empty($status)) {
        $errors['status'] = "Status is required.";
    }

    if (empty($errors)) {
        $grade = $score . '/' . $status;

        // Ensure numeric values are integers
        $student_id = (int)$student_id;
        $subject_id = (int)$subject_id;
        $optional = (int)$optional;

        $stmt_err = '';
        $ok = false;

        // Try inserting with optional column (newer DBs)
        $stmt = $conn->prepare(
            "INSERT INTO grades(student_id, subject_id, grade, optional)
             VALUES(?,?,?,?)"
        );
        if ($stmt !== false) {
            $stmt->bind_param("iisi", $student_id, $subject_id, $grade, $optional);
            if ($stmt->execute()) $ok = true; else $stmt_err = $stmt->error;
        } else {
            // Prepare failed, likely because 'optional' column doesn't exist; try fallback without it
            $stmt = $conn->prepare(
                "INSERT INTO grades(student_id, subject_id, grade) VALUES(?,?,?)"
            );
            if ($stmt !== false) {
                $stmt->bind_param("iis", $student_id, $subject_id, $grade);
                if ($stmt->execute()) $ok = true; else $stmt_err = $stmt->error;
            } else {
                $stmt_err = $conn->error;
            }
        }

        if ($ok) {
            $msg = "Grade saved successfully";
        } else {
            $errors['db'] = "Could not save grade: " . htmlspecialchars($stmt_err ?: $conn->error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Grade</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        form { display: flex; flex-direction: column; }
        select, input { margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #218838; }
        .success { color: green; text-align: center; }
        .error { color: red; font-size: 0.9em; }
        .back { text-align: center; margin-top: 20px; }
        .back a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Student Grade</h2>
        <?php if ($msg): ?>
            <p class="success"><?= $msg ?></p>
        <?php endif; ?>
        <?php if (isset($errors['db'])): ?>
            <p class="error"><?= $errors['db'] ?></p>
        <?php endif; ?>

        <form method="post">
            <select name="class_filter" id="class_filter">
                <option value="">All Classes</option>
                           <option value="">a</option>
                                      <option value="">b</option>
                <?php while($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['class_name'] ?></option>
                <?php endwhile; ?>
            </select>

            <input type="text" id="roll_filter" name="roll_filter" placeholder="Filter by Roll" value="<?= isset($_POST['roll_filter']) ? htmlspecialchars($_POST['roll_filter']) : '' ?>" style="margin:10px 0; padding:8px;">
            <input type="hidden" name="selected_roll" id="selected_roll" value="<?= htmlspecialchars($_POST['selected_roll'] ?? '') ?>">

            <select name="student_id" id="student_select">
                <option value="">Select Student</option>
                <?php while($s = $students->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" data-class="<?= $s['class_id'] ?>" data-roll="<?= htmlspecialchars($s['roll'] ?? '') ?>" <?= (isset($_POST['student_id']) && $_POST['student_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['full_name']) ?><?= isset($s['roll']) && $s['roll'] !== '' ? ' (Roll: '.htmlspecialchars($s['roll']).')' : '' ?></option>
                <?php endwhile; ?>
            </select>
            <?php if (isset($errors['student'])): ?>
                <p class="error"><?= $errors['student'] ?></p>
            <?php endif; ?>

            <select name="subject_id">
                <option value="">Select Subject</option>
                <?php while($sub = $subjects->fetch_assoc()): ?>
                    <option value="<?= $sub['id'] ?>" <?= (isset($_POST['subject_id']) && $_POST['subject_id'] == $sub['id']) ? 'selected' : '' ?>><?= $sub['name'] ?></option>
                <?php endwhile; ?>
            </select>
            <?php if (isset($errors['subject'])): ?>
                <p class="error"><?= $errors['subject'] ?></p>
            <?php endif; ?>

            <input type="number" name="score" placeholder="Score (0-100)" min="0" max="100" value="<?= isset($_POST['score']) ? htmlspecialchars($_POST['score']) : '' ?>">
            <?php if (isset($errors['score'])): ?>
                <p class="error"><?= $errors['score'] ?></p>
            <?php endif; ?>

            <select name="status">
                <option value="">Select Status</option>
                <option value="P" <?= (isset($_POST['status']) && $_POST['status'] == 'P') ? 'selected' : '' ?>>Pass</option>
                <option value="F" <?= (isset($_POST['status']) && $_POST['status'] == 'F') ? 'selected' : '' ?>>Fail</option>
                <option value="I" <?= (isset($_POST['status']) && $_POST['status'] == 'I') ? 'selected' : '' ?>>Incomplete</option>
            </select>
            <?php if (isset($errors['status'])): ?>
                <p class="error"><?= $errors['status'] ?></p>
            <?php endif; ?>
            <label><input type="checkbox" name="optional" value="1" <?= isset($_POST['optional']) ? 'checked' : '' ?>> Optional Grade</label>
            <button type="submit">Save Grade</button>
        </form>

        <div class="back">
            <a href="dashboard.php">Back to Dashboard</a> | <a href="../logout.php">Logoutd</a> | <a href="../logout.php">Logout</a>
        </div>
    </div>
</body>
</html>
<script>
var classFilter = document.getElementById('class_filter');
var rollFilter = document.getElementById('roll_filter');
var studentOptions = document.querySelectorAll('#student_select option');

function applyStudentFilters() {
    var selectedClass = classFilter.value;
    var rollVal = rollFilter.value.trim();
    var visibleCount = 0;
    var lastVisible = null;
    studentOptions.forEach(function(option) {
        if (option.value === '') {
            option.style.display = 'block';
            return;
        }
        var matchesClass = (selectedClass === '' || option.getAttribute('data-class') === selectedClass);
        var ro = option.getAttribute('data-roll') || '';
        var matchesRoll = (rollVal === '' || ro.indexOf(rollVal) !== -1);
        if (matchesClass && matchesRoll) {
            option.style.display = 'block';
            visibleCount++;
            lastVisible = option;
        } else {
            option.style.display = 'none';
        }
    });
    // Auto-select if only one student remains
    var studentSelect = document.getElementById('student_select');
    if (visibleCount === 1 && lastVisible) {
        studentSelect.value = lastVisible.value;
        // update hidden selected_roll
        var sr = document.getElementById('selected_roll');
        if (sr) sr.value = lastVisible.getAttribute('data-roll') || '';
    }
}

if (classFilter) classFilter.addEventListener('change', applyStudentFilters);
if (rollFilter) rollFilter.addEventListener('input', applyStudentFilters);

// Update selected_roll when user manually selects a student
var studentSelectEl = document.getElementById('student_select');
if (studentSelectEl) {
    studentSelectEl.addEventListener('change', function() {
        var sr = document.getElementById('selected_roll');
        var opt = this.options[this.selectedIndex];
        if (sr && opt) sr.value = opt.getAttribute('data-roll') || '';
    });
}

// Apply filters on load (and set selected roll if an option is pre-selected)
applyStudentFilters();
if (studentSelectEl) {
    var initOpt = studentSelectEl.options[studentSelectEl.selectedIndex];
    if (initOpt) {
        var sr = document.getElementById('selected_roll');
        if (sr) sr.value = initOpt.getAttribute('data-roll') || '';
    }
}
</script>
