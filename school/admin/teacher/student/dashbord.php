<?php
require "../../../admin/db.php";
if ($_SESSION["role"] != "student") {
    header("Location: ../../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
$full_name = 'Student';
$roll = '';
if ($user_id) {
    $stmt = $conn->prepare("SELECT full_name, roll FROM students WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $full_name = $row['full_name'];
        $roll = $row['roll'] ?? '';
    }
}
?>

<style>
.dashboard { max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.08); text-align:center; }
.dashboard h2 { margin-top:0; }
.dashboard .welcome { color:#333; margin-bottom:4px; }
.dashboard .roll { color:#666; margin-top:0; margin-bottom:12px; }
.btn { display:inline-block; margin:6px 8px; padding:10px 16px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px; }
.btn:hover { background:#0056b3; }
.btn.logout { background:#dc3545; }
.btn.logout:hover { background:#c82333; }
</style>

<div class="dashboard">
    <h2>Student Dashboard</h2>
    <h3 class="welcome">Welcome, <?= htmlspecialchars($full_name) ?></h3>
    <p class="roll">Roll: <?= htmlspecialchars($roll ?: 'N/A') ?></p>

    <?php if ($roll): ?>
        <a class="btn" href="../../view_by_roll.php?roll=<?= urlencode($roll) ?>">View Grades (by Roll)</a>
    <?php else: ?>
        <a class="btn" href="view_grades.php">View Grades</a>
    <?php endif; ?>
    <a class="btn logout" href="../../../admin/logout.php">Logout</a>
</div>
