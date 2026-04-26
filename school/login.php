<?php
require "admin/db.php";

$error = "";
$success = "";
$tab = 'login';
$loginData = ['username' => ''];
$registerData = ['role' => '', 'username' => '', 'fullname' => '', 'gender' => '', 'class_id' => '', 'roll' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_GET['action'])) {
        if ($_GET['action'] == 'register') {
            $tab = 'register';
            $errors = [];
            $registerData['role'] = $_POST["role"] ?? '';
            $registerData['username'] = trim($_POST["username"] ?? '');
            $raw_password = $_POST["password"] ?? '';
            $registerData['fullname'] = trim($_POST["fullname"] ?? '');
            $registerData['gender'] = $_POST["gender"] ?? '';
            $registerData['class_id'] = $_POST["class_id"] ?? '';
            $registerData['roll'] = trim($_POST["roll"] ?? '');

            if (empty($registerData['role'])) {
                $errors['role'] = "Role is required.";
            }
            if (empty($registerData['username'])) {
                $errors['username'] = "Username is required.";
            }
            if (empty($raw_password)) {
                $errors['password'] = "Password is required.";
            } elseif (strlen($raw_password) < 6 || !preg_match('/\\d/', $raw_password)) {
                $errors['password'] = "Password must be at least 6 characters and include at least one number.";
            }
            if (empty($registerData['fullname'])) {
                $errors['fullname'] = "Full name is required.";
            }
            if ($registerData['role'] == 'student') {
                if (empty($registerData['gender'])) {
                    $errors['gender'] = "Gender is required.";
                }
                if (empty($registerData['class_id'])) {
                    $errors['class_id'] = "Class is required.";
                }
                if (empty($registerData['roll'])) {
                    $errors['roll'] = "Roll is required.";
                } elseif (!ctype_digit($registerData['roll'])) {
                    $errors['roll'] = "Roll must be a number.";
                }
            }

            if (empty($errors)) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
                $stmt->bind_param("s", $registerData['username']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows == 0) {
                    $password = password_hash($raw_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users(username,password,role) VALUES(?,?,?)");
                    $stmt->bind_param("sss", $registerData['username'], $password, $registerData['role']);
                    $stmt->execute();
                    $user_id = $conn->insert_id;

                    if ($registerData['role'] == 'student') {
                        $stmt2 = $conn->prepare("INSERT INTO students(user_id,full_name,gender,class_id,roll) VALUES(?,?,?,?,?)");
                        $stmt2->bind_param("issis", $user_id, $registerData['fullname'], $registerData['gender'], $registerData['class_id'], $registerData['roll']);
                        $stmt2->execute();
                    } elseif ($registerData['role'] == 'teacher') {
                        $stmt2 = $conn->prepare("INSERT INTO teachers(user_id,full_name) VALUES(?,?)");
                        $stmt2->bind_param("is", $user_id, $registerData['fullname']);
                        $stmt2->execute();
                    }

                    $_SESSION["user_id"] = $user_id;
                    $_SESSION["role"] = $registerData['role'];
                    $_SESSION["username"] = $registerData['username'];

                    if ($registerData['role'] == "admin") {
                        header("Location: admin/dashboard.php");
                    } elseif ($registerData['role'] == "teacher") {
                        header("Location: admin/teacher/dashbord.php");
                    } else {
                        header("Location: admin/teacher/student/dashbord.php");
                    }
                    exit();
                } else {
                    $errors['username'] = "Username already exists.";
                }
            }
        } elseif ($_GET['action'] == 'forgot') {
            $tab = 'forgot';
            $username = trim($_POST["username"] ?? '');
            $new_password = $_POST["new_password"] ?? '';
            if ($username && $new_password) {
                if (strlen($new_password) < 6 || !preg_match('/\\d/', $new_password)) {
                    $error = 'New password must be at least 6 characters and include at least one number.';
                } else {
                    $newpass = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
                    $stmt->bind_param("ss", $newpass, $username);
                    $stmt->execute();
                    if ($stmt->affected_rows > 0) {
                        $success = "Password reset successfully. Please login with your new password.";
                    } else {
                        $error = "Username not found.";
                    }
                }
            } else {
                $error = "Username and new password are required.";
            }
        }
    } else {
        $loginData['username'] = trim($_POST["username"] ?? '');
        $password = $_POST["password"] ?? '';
        $loginError = '';

        if (empty($loginData['username']) || empty($password)) {
            $loginError = "All login fields are required.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
            $stmt->bind_param("s", $loginData['username']);
            $stmt->execute();
            $result = $stmt->get_result();

            $user = null;
            if ($result && $result->num_rows == 1) {
                $user = $result->fetch_assoc();
            } else {
                $stmt2 = $conn->prepare("SELECT u.* FROM users u JOIN students s ON s.user_id = u.id WHERE TRIM(s.roll) = TRIM(?)");
                $stmt2->bind_param("s", $loginData['username']);
                $stmt2->execute();
                $r2 = $stmt2->get_result();
                if ($r2 && $r2->num_rows == 1) {
                    $user = $r2->fetch_assoc();
                } elseif ($r2 && $r2->num_rows > 1) {
                    $matched = null;
                    $matches = 0;
                    while ($row = $r2->fetch_assoc()) {
                        if (password_verify($password, $row['password'])) {
                            $matched = $row;
                            $matches++;
                        }
                    }
                    if ($matches == 1) {
                        $user = $matched;
                    } elseif ($matches > 1) {
                        $loginError = "Multiple users match these credentials. Contact administrator.";
                    } else {
                        $loginError = "Wrong password.";
                    }
                }
            }

            if ($user) {
                if (password_verify($password, $user["password"])) {
                    $_SESSION["user_id"] = $user['id'];
                    $_SESSION["role"] = $user['role'];
                    $_SESSION["username"] = $user['username'];

                    if ($user["role"] == "admin") {
                        header("Location: admin/dashboard.php");
                    } elseif ($user["role"] == "teacher") {
                        header("Location: admin/teacher/dashbord.php");
                    } else {
                        header("Location: admin/teacher/student/dashbord.php");
                    }
                    exit();
                } else {
                    $loginError = "Wrong password.";
                }
            }
        }

        if (empty($loginError) && empty($user)) {
            $loginError = "User not found.";
        }

        $error = $loginError;
    }
}

$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Management Access</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="tabs">
                <button type="button" class="tab active" data-tab="login">Login</button>
                <button type="button" class="tab" data-tab="register">Register</button>
                <button type="button" class="tab" data-tab="forgot">Reset Password</button>
            </div>

            <div id="login" class="tab-content active">
                <h2>Login</h2>
                <form id="loginForm" method="post" action="" autocomplete="on">
                    <input type="text" name="username" placeholder="Username or Roll Number" autocomplete="username" required value="<?= htmlspecialchars($loginData['username'] ?? '') ?>">
                    <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
                    <button id="loginBtn" type="submit">Login</button>
                </form>
                <?php if (!empty($error)): ?>
                    <p class="error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <p class="success"><?= htmlspecialchars($success) ?></p>
                <?php endif; ?>
                <p class="helper-links">Use your username or student roll. <a href="#register" onclick="showTab('register'); return false;">Create account</a> or <a href="#forgot" onclick="showTab('forgot'); return false;">reset password</a>.</p>
            </div>

            <div id="register" class="tab-content">
                <h2>Create Account</h2>
                <form method="post" action="?action=register" autocomplete="on">
                    <select name="role" id="roleSelect" required>
                        <option value="">Select Role</option>
                        <option value="admin" <?= ($registerData['role'] === 'admin' ? 'selected' : '') ?>>Admin</option>
                        <option value="teacher" <?= ($registerData['role'] === 'teacher' ? 'selected' : '') ?>>Teacher</option>
                        <option value="student" <?= ($registerData['role'] === 'student' ? 'selected' : '') ?>>Student</option>
                    </select>
                    <?php if (isset($errors['role'])): ?><p class="error"><?= $errors['role'] ?></p><?php endif; ?>
                    <input type="text" name="username" placeholder="Username" autocomplete="username" required value="<?= htmlspecialchars($registerData['username']) ?>">
                    <?php if (isset($errors['username'])): ?><p class="error"><?= $errors['username'] ?></p><?php endif; ?>
                    <input type="password" name="password" placeholder="Password" autocomplete="new-password" required pattern="(?=.*\\d).{6,}" title="At least 6 characters and include at least one number">
                    <?php if (isset($errors['password'])): ?><p class="error"><?= $errors['password'] ?></p><?php endif; ?>
                    <input type="text" name="fullname" placeholder="Full Name" required value="<?= htmlspecialchars($registerData['fullname']) ?>">
                    <?php if (isset($errors['fullname'])): ?><p class="error"><?= $errors['fullname'] ?></p><?php endif; ?>
                    <select name="gender" id="genderSelect">
                        <option value="">Select Gender</option>
                        <option value="male" <?= ($registerData['gender'] === 'male' ? 'selected' : '') ?>>Male</option>
                        <option value="female" <?= ($registerData['gender'] === 'female' ? 'selected' : '') ?>>Female</option>
                    </select>
                    <?php if (isset($errors['gender'])): ?><p class="error"><?= $errors['gender'] ?></p><?php endif; ?>
                    <select name="class_id" id="class_id" style="display: <?= ($registerData['role'] === 'student' ? 'block' : 'none') ?>;">
                        <option value="">Select Class</option>
                        <?php if ($classes): while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?= $class['id'] ?>" <?= ($registerData['class_id'] == $class['id'] ? 'selected' : '') ?>><?= htmlspecialchars($class['class_name']) ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                    <?php if (isset($errors['class_id'])): ?><p class="error"><?= $errors['class_id'] ?></p><?php endif; ?>
                    <input type="text" name="roll" id="roll" placeholder="Roll Number" style="display: <?= ($registerData['role'] === 'student' ? 'block' : 'none') ?>;" value="<?= htmlspecialchars($registerData['roll']) ?>">
                    <?php if (isset($errors['roll'])): ?><p class="error"><?= $errors['roll'] ?></p><?php endif; ?>
                    <button type="submit">Create Account</button>
                </form>
            </div>

            <div id="forgot" class="tab-content">
                <h2>Reset Password</h2>
                <form method="post" action="?action=forgot" autocomplete="on">
                    <input type="text" name="username" placeholder="Username" autocomplete="username" required>
                    <input type="password" name="new_password" placeholder="New Password" autocomplete="new-password" required pattern="(?=.*\\d).{6,}" title="At least 6 characters and include at least one number">
                    <button type="submit">Reset Password</button>
                </form>
                <p class="helper-links"><a href="#login" onclick="showTab('login'); return false;">Back to login</a></p>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(function(button) {
                button.classList.toggle('active', button.dataset.tab === tabName);
            });
            document.querySelectorAll('.tab-content').forEach(function(content) {
                content.classList.toggle('active', content.id === tabName);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.tab').forEach(function(button) {
                button.addEventListener('click', function() {
                    showTab(this.dataset.tab);
                });
            });

            var selectedTab = location.hash ? location.hash.replace('#', '') : '<?= htmlspecialchars($tab) ?>';
            if (['login','register','forgot'].includes(selectedTab)) {
                showTab(selectedTab);
            }

            var loginForm = document.getElementById('loginForm');
            var loginBtn = document.getElementById('loginBtn');
            if (loginForm && loginBtn) {
                loginForm.addEventListener('submit', function() {
                    loginBtn.disabled = true;
                    loginBtn.textContent = 'Logging in…';
                });
            }

            var roleSelect = document.getElementById('roleSelect');
            var classSelect = document.getElementById('class_id');
            var rollInput = document.getElementById('roll');
            if (roleSelect) {
                roleSelect.addEventListener('change', function() {
                    var isStudent = this.value === 'student';
                    classSelect.style.display = isStudent ? 'block' : 'none';
                    rollInput.style.display = isStudent ? 'block' : 'none';
                });
            }
        });
    </script>
</body>
</html>
