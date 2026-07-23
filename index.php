<?php
session_start();

// Database Connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "impact_hope";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-create needed tables if they don't exist yet
$conn->query("CREATE TABLE IF NOT EXISTS unhcr_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    target_camp VARCHAR(100) NOT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    effective_date DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS hospital_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(100) NOT NULL,
    hospital_name VARCHAR(255) NOT NULL,
    hospital_phone VARCHAR(50) NOT NULL,
    student_id VARCHAR(100) NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    school_name VARCHAR(255) NOT NULL,
    age INT NOT NULL,
    gender VARCHAR(20) NOT NULL,
    course_studied VARCHAR(255) NOT NULL,
    parent_names VARCHAR(255) NOT NULL,
    camp VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    sector VARCHAR(100) NOT NULL,
    cell VARCHAR(100) NOT NULL,
    village VARCHAR(100) NOT NULL,
    disease VARCHAR(255) NOT NULL,
    medication TEXT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    treatment_datetime DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS partner_schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    tuition_fee DECIMAL(10,2) NOT NULL,
    boarding_fee DECIMAL(10,2) DEFAULT 0,
    announcements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Simulated UNHCR API Verification function
function verifyWithUNHCR($id_number, $parent_name) {
    if (!empty($id_number) && strlen($id_number) >= 5) {
        return ['verified' => true, 'message' => 'Verified refugee status via UNHCR API'];
    }
    return ['verified' => false, 'message' => 'Invalid or unverified UNHCR ID'];
}

// Simulated Email/SMS sending function
function sendNotification($email, $phone, $status, $student_name) {
    return true;
}

// Log actions
function logAction($conn, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO system_logs (action, details) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $action, $details);
        $stmt->execute();
    }
}

$message = "";
$message_type = "";

// 1. REGISTRATION LOGIC
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);
    if ($stmt->execute()) {
        $message = "Account created successfully! You can now log in below.";
        $message_type = "success";
        logAction($conn, "Registration", "User registered: $username");
    } else {
        $message = "Username already exists.";
        $message_type = "danger";
    }
}

// 2. LOGIN LOGIC
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['username'];
            logAction($conn, "Login", "User logged in: " . $user['username']);
            header("Location: index.php");
            exit;
        } else {
            $message = "Incorrect password.";
            $message_type = "danger";
        }
    } else {
        $message = "User not found.";
        $message_type = "danger";
    }
}

// 3. LOGOUT LOGIC
if (isset($_GET['logout'])) {
    logAction($conn, "Logout", "User logged out: " . $_SESSION['user']);
    session_destroy();
    header("Location: index.php");
    exit;
}

// Helper upload function
function uploadFile($file_key) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = time() . "_" . basename($_FILES[$file_key]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES[$file_key]["tmp_name"], $target_file)) {
            return $target_file;
        }
    }
    return "";
}

// 4. UNIVERSITY APPLICATION SUBMISSION
if (isset($_POST['submit_university'])) {
    $name = $_POST['student_name'];
    $marks = intval($_POST['marks']);
    $national_id = $_POST['national_id'];
    $asylum_cert = $_POST['asylum_cert'];
    $origin_country = $_POST['origin_country'];
    $province = $_POST['province'];
    $district = $_POST['district'];
    $sector = $_POST['sector'];
    $cell = $_POST['cell'];
    $village = $_POST['village'];
    $parent_phone = $_POST['parent_phone'];
    $parent_names = $_POST['parent_names'];
    $email = $_POST['email'];
    $father_name = $_POST['father_name'];
    $mother_name = $_POST['mother_name'];
    $alevel_subject = $_POST['alevel_subject'];
    $camp = $_POST['camp'];
    
    $result_slip = uploadFile('result_slip');
    $proof_photocopy = uploadFile('proof_photocopy');
    
    $unhcr = verifyWithUNHCR($national_id, $parent_names);
    
    if ($marks >= 80 && $unhcr['verified']) {
        $status = "Accepted";
    } else {
        $status = "Rejected";
    }
    
    $stmt = $conn->prepare("INSERT INTO university (student_name, marks, national_id, asylum_cert, origin_country, province, district, sector, cell, village, parent_phone, parent_names, email, father_name, mother_name, alevel_subject, camp, result_slip, proof_photocopy, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissssssssssssssssss", $name, $marks, $national_id, $asylum_cert, $origin_country, $province, $district, $sector, $cell, $village, $parent_phone, $parent_names, $email, $father_name, $mother_name, $alevel_subject, $camp, $result_slip, $proof_photocopy, $status);
    
    if ($stmt->execute()) {
        sendNotification($email, $parent_phone, $status, $name);
        logAction($conn, "University App", "Submitted application for $name. Status: $status");
        $message = "Application submitted! Status: $status (Email sent to $email)";
        $message_type = ($status == "Accepted") ? "success" : "warning";
    } else {
        $message = "Error submitting application.";
        $message_type = "danger";
    }
}

// 5. A-LEVEL APPLICATION SUBMISSION
if (isset($_POST['submit_alevel'])) {
    $name = $_POST['student_name'];
    $marks = intval($_POST['marks']);
    $national_id = $_POST['national_id'];
    $asylum_cert = $_POST['asylum_cert'];
    $origin_country = $_POST['origin_country'];
    $province = $_POST['province'];
    $district = $_POST['district'];
    $sector = $_POST['sector'];
    $cell = $_POST['cell'];
    $village = $_POST['village'];
    $parent_phone = $_POST['parent_phone'];
    $parent_names = $_POST['parent_names'];
    $email = $_POST['email'];
    $father_name = $_POST['father_name'];
    $mother_name = $_POST['mother_name'];
    $camp = $_POST['camp'];
    
    $result_slip = uploadFile('result_slip');
    $proof_photocopy = uploadFile('proof_photocopy');
    
    $unhcr = verifyWithUNHCR($national_id, $parent_names);
    
    if ($marks >= 70 && $unhcr['verified']) {
        $status = "Accepted";
    } else {
        $status = "Rejected";
    }
    
    $stmt = $conn->prepare("INSERT INTO alevel (student_name, marks, national_id, asylum_cert, origin_country, province, district, sector, cell, village, parent_phone, parent_names, email, father_name, mother_name, camp, result_slip, proof_photocopy, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssssssssssssssss", $name, $marks, $national_id, $asylum_cert, $origin_country, $province, $district, $sector, $cell, $village, $parent_phone, $parent_names, $email, $father_name, $mother_name, $camp, $result_slip, $proof_photocopy, $status);
    
    if ($stmt->execute()) {
        sendNotification($email, $parent_phone, $status, $name);
        logAction($conn, "Alevel App", "Submitted application for $name. Status: $status");
        $message = "A-Level Application submitted! Status: $status";
        $message_type = ($status == "Accepted") ? "success" : "warning";
    }
}

// 6. SUMMER TRAINING SUBMISSION
if (isset($_POST['submit_summer'])) {
    $name = $_POST['student_name'];
    $prev = $_POST['previous_level'];
    $next = $_POST['next_level'];
    $school = $_POST['school_name'];
    $subject = $_POST['subject_studied'];
    $parent_phone = $_POST['parent_phone'];
    $gender = $_POST['gender'];
    
    $stmt = $conn->prepare("INSERT INTO summer_training (student_name, previous_level, next_level, school_name, subject_studied, parent_phone, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $prev, $next, $school, $subject, $parent_phone, $gender);
    if ($stmt->execute()) {
        logAction($conn, "Summer Training", "Enrolled: $name");
        $message = "Summer Training Registration successful!";
        $message_type = "success";
    }
}

// 7. SHORT COURSES SUBMISSION
if (isset($_POST['submit_short'])) {
    $name = $_POST['student_name'];
    $national_id = $_POST['national_id'];
    $course_studied = $_POST['course_studied'];
    $school = $_POST['school_attended'];
    $desired = $_POST['course_desired'];
    $camp = $_POST['camp'];
    $country = $_POST['origin_country'];
    $province = $_POST['province'];
    $district = $_POST['district'];
    $sector = $_POST['sector'];
    $cell = $_POST['cell'];
    $village = $_POST['village'];
    
    $diploma = uploadFile('diploma_upload');
    
    $stmt = $conn->prepare("INSERT INTO short_courses (student_name, national_id, course_studied, school_attended, course_desired, camp, origin_country, province, district, sector, cell, village, diploma_upload) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssssss", $name, $national_id, $course_studied, $school, $desired, $camp, $country, $province, $district, $sector, $cell, $village, $diploma);
    if ($stmt->execute()) {
        logAction($conn, "Short Course", "Registered: $name");
        $message = "Short Course Application Submitted successfully!";
        $message_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Impact Hope in Rwanda</title>
    <style>
        :root {
            --primary: #f28c28;
            --dark: #222;
            --light: #f9f9f9;
            --border: #ddd;
        }
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; background: var(--light); color: var(--dark); }
        
        /* Header & Navbar */
        header { background: #fff; border-bottom: 3px solid var(--primary); padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; }
        .logo-area { display: flex; align-items: center; gap: 15px; }
        .logo-area img { height: 70px; }
        .logo-area h1 { margin: 0; font-size: 24px; color: var(--dark); }
        
        nav { background: var(--dark); display: flex; flex-wrap: wrap; }
        nav a { color: #fff; padding: 14px 20px; text-decoration: none; font-weight: bold; cursor: pointer; transition: 0.3s; }
        nav a:hover, nav a.active { background: var(--primary); color: #fff; }
        
        .container { max-width: 1250px; margin: 30px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        /* Forms styling */
        h2 { border-left: 5px solid var(--primary); padding-left: 10px; margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { padding: 10px; border: 1px solid var(--border); border-radius: 4px; }
        button { background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold; }
        button:hover { background: #d77615; }
        
        /* Alerts */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        
        /* Section views */
        .page-section { display: none; }
        .page-section.active { display: block; }
        
        /* Tables styling */
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; background: #fff; font-size: 13px; }
        table th, table td { padding: 10px; border: 1px solid var(--border); text-align: left; }
        table th { background: #f2f2f2; white-space: nowrap; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-accepted { background: #28a745; color: white; }
        .badge-rejected { background: #dc3545; color: white; }
        .badge-pending { background: #ffc107; color: black; }
        
        /* Upload preview screen */
        .preview-img { max-width: 150px; border: 1px solid var(--border); margin-top: 5px; border-radius: 4px; display: block; }
        
        /* Footer styling */
        footer { background: var(--dark); color: #ccc; padding: 30px 20px; font-size: 14px; margin-top: 50px; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; }
        .footer-grid h4 { color: var(--primary); margin-top: 0; }
        .footer-bottom { text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #444; font-size: 12px; }
    </style>
</head>
<body>

<header>
    <div class="logo-area">
        <img src="images.jpg" alt="Impact Hope Logo">
        <h1>Impact Hope in Rwanda</h1>
    </div>
    <div>
        <?php if (isset($_SESSION['user'])): ?>
            <span>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong> | <a href="?logout=1" style="color: var(--primary);">Logout</a></span>
        <?php endif; ?>
    </div>
</header>

<?php if (isset($_SESSION['user'])): ?>
<!-- NAVIGATION BAR FOR LOGGED-IN USERS -->
<nav id="mainNav">
    <a onclick="showSection('university')" id="nav-university" class="active">University</a>
    <a onclick="showSection('alevel')" id="nav-alevel">A-Level</a>
    <a onclick="showSection('summer')" id="nav-summer">Summer Training</a>
    <a onclick="showSection('short_course_nav')" id="nav-short_course_nav">Non-University / Short Course</a>
    <a onclick="showSection('unhcr_updates')" id="nav-unhcr_updates">UNHCR Updates</a>
    <a onclick="showSection('hospital_info')" id="nav-hospital_info">All Hospital Information</a>
    <a onclick="showSection('all_students')" id="nav-all_students">All Students</a>
    <a onclick="showSection('all_schools')" id="nav-all_schools">All Schools (News & Fees)</a>
    <a onclick="showSection('all_info')" id="nav-all_info">All DB Information</a>
</nav>
<?php endif; ?>

<div class="container">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['user'])): ?>
        <!-- LOGIN & REGISTRATION SECTIONS -->
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 40px;">
            <div style="border-right: 1px solid var(--border); padding-right: 40px;">
                <h2>Register Account</h2>
                <form action="index.php" method="POST">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="register">Register</button>
                </form>
            </div>
            <div>
                <h2>Login Here</h2>
                <form action="index.php" method="POST">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="login">Login</button>
                </form>
            </div>
        </div>

    <?php else: ?>

        <!-- ================= UNIVERSITY SECTION ================= -->
        <div id="university" class="page-section active">
            <h2>University Application Form</h2>
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <div class="grid">
                    <div class="form-group"><label>Full Student Name</label><input type="text" name="student_name" required></div>
                    <div class="form-group"><label>A-Level Score/Marks (>=80)</label><input type="number" name="marks" required></div>
                    <div class="form-group"><label>National ID / Asylum Seeker ID</label><input type="text" name="national_id" required></div>
                    <div class="form-group"><label>Asylum Cert (if Asylum Seeker)</label><input type="text" name="asylum_cert"></div>
                </div>
                <h4 style="margin-top: 20px;">Origin Details</h4>
                <div class="grid">
                    <div class="form-group"><label>Origin Country</label><input type="text" name="origin_country" required></div>
                    <div class="form-group"><label>Province</label><input type="text" name="province" required></div>
                    <div class="form-group"><label>District</label><input type="text" name="district" required></div>
                    <div class="form-group"><label>Sector</label><input type="text" name="sector" required></div>
                    <div class="form-group"><label>Cell</label><input type="text" name="cell" required></div>
                    <div class="form-group"><label>Village</label><input type="text" name="village" required></div>
                </div>
                <h4 style="margin-top: 20px;">Academic & Guardian Details</h4>
                <div class="grid">
                    <div class="form-group"><label>A-Level Subject Studied</label><input type="text" name="alevel_subject" required></div>
                    <div class="form-group"><label>Father's Name</label><input type="text" name="father_name" required></div>
                    <div class="form-group"><label>Mother's Name</label><input type="text" name="mother_name" required></div>
                    <div class="form-group"><label>Parent/Guardian Names</label><input type="text" name="parent_names" required></div>
                    <div class="form-group"><label>Parent/Guardian Phone</label><input type="text" name="parent_phone" required></div>
                    <div class="form-group"><label>Student Email Address</label><input type="email" name="email" required></div>
                    <div class="form-group">
                        <label>Select Refugee Camp</label>
                        <select name="camp" required>
                            <option value="Kiziba">Kiziba</option>
                            <option value="Gihembe">Gihembe</option>
                            <option value="Nyabiheke">Nyabiheke</option>
                            <option value="Mugombwa">Mugombwa</option>
                            <option value="Mahama">Mahama</option>
                        </select>
                    </div>
                </div>
                <div class="grid">
                    <div class="form-group">
                        <label>Upload Result Slip (Image)</label>
                        <input type="file" name="result_slip" accept="image/*" onchange="previewImage(event, 'univ_slip_prev')" required>
                        <img id="univ_slip_prev" class="preview-img" alt="Preview Image" style="display:none;">
                    </div>
                    <div class="form-group">
                        <label>Photocopy of Original Proof (Image)</label>
                        <input type="file" name="proof_photocopy" accept="image/*" onchange="previewImage(event, 'univ_proof_prev')" required>
                        <img id="univ_proof_prev" class="preview-img" alt="Preview Image" style="display:none;">
                    </div>
                </div>
                <button type="submit" name="submit_university">Submit University Application</button>
            </form>
        </div>

        <!-- ================= A-LEVEL SECTION ================= -->
        <div id="alevel" class="page-section">
            <h2>A-Level Entry Application Form</h2>
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <div class="grid">
                    <div class="form-group"><label>Full Student Name</label><input type="text" name="student_name" required></div>
                    <div class="form-group"><label>S3 Score/Marks (>=70)</label><input type="number" name="marks" required></div>
                    <div class="form-group"><label>National ID / Asylum Seeker ID</label><input type="text" name="national_id" required></div>
                    <div class="form-group"><label>Asylum Cert (if Asylum Seeker)</label><input type="text" name="asylum_cert"></div>
                </div>
                <h4 style="margin-top: 20px;">Origin Details</h4>
                <div class="grid">
                    <div class="form-group"><label>Origin Country</label><input type="text" name="origin_country" required></div>
                    <div class="form-group"><label>Province</label><input type="text" name="province" required></div>
                    <div class="form-group"><label>District</label><input type="text" name="district" required></div>
                    <div class="form-group"><label>Sector</label><input type="text" name="sector" required></div>
                    <div class="form-group"><label>Cell</label><input type="text" name="cell" required></div>
                    <div class="form-group"><label>Village</label><input type="text" name="village" required></div>
                </div>
                <h4 style="margin-top: 20px;">Guardian & Camp Details</h4>
                <div class="grid">
                    <div class="form-group"><label>Father's Name</label><input type="text" name="father_name" required></div>
                    <div class="form-group"><label>Mother's Name</label><input type="text" name="mother_name" required></div>
                    <div class="form-group"><label>Parent/Guardian Names</label><input type="text" name="parent_names" required></div>
                    <div class="form-group"><label>Parent/Guardian Phone</label><input type="text" name="parent_phone" required></div>
                    <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
                    <div class="form-group">
                        <label>Select Refugee Camp</label>
                        <select name="camp" required>
                            <option value="Kiziba">Kiziba</option>
                            <option value="Gihembe">Gihembe</option>
                            <option value="Nyabiheke">Nyabiheke</option>
                            <option value="Mugombwa">Mugombwa</option>
                            <option value="Mahama">Mahama</option>
                        </select>
                    </div>
                </div>
                <div class="grid">
                    <div class="form-group">
                        <label>Upload Result Slip (Image)</label>
                        <input type="file" name="result_slip" accept="image/*" onchange="previewImage(event, 'alevel_slip_prev')" required>
                        <img id="alevel_slip_prev" class="preview-img" alt="Preview Image" style="display:none;">
                    </div>
                    <div class="form-group">
                        <label>Photocopy of Original Proof (Image)</label>
                        <input type="file" name="proof_photocopy" accept="image/*" onchange="previewImage(event, 'alevel_proof_prev')" required>
                        <img id="alevel_proof_prev" class="preview-img" alt="Preview Image" style="display:none;">
                    </div>
                </div>
                <button type="submit" name="submit_alevel">Submit A-Level Application</button>
            </form>
        </div>

        <!-- ================= SUMMER TRAINING ================= -->
        <div id="summer" class="page-section">
            <h2>Summer Training Registration</h2>
            <p>Dedicated to students who completed S5 / Level 4 advancing to S6 / Level 5.</p>
            <form action="index.php" method="POST">
                <div class="grid">
                    <div class="form-group"><label>Full Student Name</label><input type="text" name="student_name" required></div>
                    <div class="form-group">
                        <label>Current Level Completed</label>
                        <select name="previous_level" required>
                            <option value="S5">S5</option>
                            <option value="Level 4">Level 4 (TVET)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Next Level Entering</label>
                        <select name="next_level" required>
                            <option value="S6">S6</option>
                            <option value="Level 5">Level 5 (TVET)</option>
                        </select>
                    </div>
                </div>
                <div class="grid">
                    <div class="form-group"><label>School Name</label><input type="text" name="school_name" required></div>
                    <div class="form-group"><label>Trade / Subject Studied</label><input type="text" name="subject_studied" required></div>
                    <div class="form-group"><label>Parent Phone Number</label><input type="text" name="parent_phone" required></div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="submit_summer">Register Summer Training</button>
            </form>
        </div>

        <!-- ================= SHORT COURSES ================= -->
        <div id="short_course_nav" class="page-section">
            <h2>Non-University / Short Course Application</h2>
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <div class="grid">
                    <div class="form-group"><label>Full Student Name</label><input type="text" name="student_name" required></div>
                    <div class="form-group"><label>National ID / UNHCR ID</label><input type="text" name="national_id" required></div>
                    <div class="form-group"><label>Trade Studied in S6 / L5</label><input type="text" name="course_studied" required></div>
                    <div class="form-group"><label>School Attended</label><input type="text" name="school_attended" required></div>
                </div>
                <div class="grid">
                    <div class="form-group"><label>Desired Course</label><input type="text" name="course_desired" required></div>
                    <div class="form-group">
                        <label>Select Refugee Camp</label>
                        <select name="camp" required>
                            <option value="Kiziba">Kiziba</option>
                            <option value="Gihembe">Gihembe</option>
                            <option value="Nyabiheke">Nyabiheke</option>
                            <option value="Mugombwa">Mugombwa</option>
                            <option value="Mahama">Mahama</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Origin Country</label><input type="text" name="origin_country" required></div>
                    <div class="form-group"><label>Province</label><input type="text" name="province" required></div>
                </div>
                <div class="grid">
                    <div class="form-group"><label>District</label><input type="text" name="district" required></div>
                    <div class="form-group"><label>Sector</label><input type="text" name="sector" required></div>
                    <div class="form-group"><label>Cell</label><input type="text" name="cell" required></div>
                    <div class="form-group"><label>Village</label><input type="text" name="village" required></div>
                </div>
                <div class="grid">
                    <div class="form-group">
                        <label>Upload Diploma/Certificate (Image/PDF)</label>
                        <input type="file" name="diploma_upload" required>
                    </div>
                </div>
                <button type="submit" name="submit_short">Submit Short Course Application</button>
            </form>
        </div>

        <!-- ================= UNHCR UPDATES ================= -->
        <div id="unhcr_updates" class="page-section">
            <h2>UNHCR Updates & Announcements</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Target Camp</th>
                            <th>Effective Date</th>
                            <th>Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $unhcr_query = $conn->query("SELECT * FROM unhcr_updates ORDER BY created_at DESC");
                        if ($unhcr_query && $unhcr_query->num_rows > 0) {
                            while($row = $unhcr_query->fetch_assoc()) {
                                echo "<tr>
                                    <td><strong>".htmlspecialchars($row['title'])."</strong></td>
                                    <td>".htmlspecialchars($row['category'])."</td>
                                    <td>".htmlspecialchars($row['target_camp'])."</td>
                                    <td>".htmlspecialchars($row['effective_date'])."</td>
                                    <td><span class='badge badge-accepted'>".htmlspecialchars($row['status'])."</span></td>
                                    <td>".htmlspecialchars($row['description'])."</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No UNHCR Updates available yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= HOSPITAL INFORMATION ================= -->
        <div id="hospital_info" class="page-section">
            <h2>Hospital Treatment Records & Invoices</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Hospital</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>School</th>
                            <th>Disease</th>
                            <th>Medication</th>
                            <th>Amount Paid</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $hospital_query = $conn->query("SELECT * FROM hospital_records ORDER BY created_at DESC");
                        if ($hospital_query && $hospital_query->num_rows > 0) {
                            while($row = $hospital_query->fetch_assoc()) {
                                echo "<tr>
                                    <td><strong>".htmlspecialchars($row['invoice_number'])."</strong></td>
                                    <td>".htmlspecialchars($row['hospital_name'])."</td>
                                    <td>".htmlspecialchars($row['student_id'])."</td>
                                    <td>".htmlspecialchars($row['student_name'])."</td>
                                    <td>".htmlspecialchars($row['school_name'])."</td>
                                    <td>".htmlspecialchars($row['disease'])."</td>
                                    <td>".htmlspecialchars($row['medication'])."</td>
                                    <td>".number_format($row['amount_paid'], 2)." RWF</td>
                                    <td>".htmlspecialchars($row['treatment_datetime'])."</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9'>No hospital records registered yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= ALL STUDENTS DASHBOARD ================= -->
        <div id="all_students" class="page-section">
            <h2>All Enrolled Students Dashboard</h2>
            <p>Amakuru y'abanyeshuri bose banditse muri University, A-Level, no muri Short Courses.</p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Category</th>
                            <th>Camp</th>
                            <th>Marks / Trade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $univ_st = $conn->query("SELECT student_name, 'University' as cat, camp, marks as info, status FROM university");
                        if ($univ_st) {
                            while($r = $univ_st->fetch_assoc()) {
                                $badge = ($r['status'] == 'Accepted') ? 'badge-accepted' : 'badge-rejected';
                                echo "<tr><td>".htmlspecialchars($r['student_name'])."</td><td>".htmlspecialchars($r['cat'])."</td><td>".htmlspecialchars($r['camp'])."</td><td>".htmlspecialchars($r['info'])." Marks</td><td><span class='badge {$badge}'>".htmlspecialchars($r['status'])."</span></td></tr>";
                            }
                        }
                        
                        $alevel_st = $conn->query("SELECT student_name, 'A-Level' as cat, camp, marks as info, status FROM alevel");
                        if ($alevel_st) {
                            while($r = $alevel_st->fetch_assoc()) {
                                $badge = ($r['status'] == 'Accepted') ? 'badge-accepted' : 'badge-rejected';
                                echo "<tr><td>".htmlspecialchars($r['student_name'])."</td><td>".htmlspecialchars($r['cat'])."</td><td>".htmlspecialchars($r['camp'])."</td><td>".htmlspecialchars($r['info'])." Marks</td><td><span class='badge {$badge}'>".htmlspecialchars($r['status'])."</span></td></tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= ALL SCHOOLS (NEWS & FEES) ================= -->
        <div id="all_schools" class="page-section">
            <h2>All Partner Schools Overview (News & Fees)</h2>
            <p>Amashuri yose afitanye amasezerano na Impact Hope Rwanda, amafaranga y'ishuri n'amatangazo.</p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>School Name</th>
                            <th>Location</th>
                            <th>Tuition Fee</th>
                            <th>Boarding Fee</th>
                            <th>Announcements / News</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sch_q = $conn->query("SELECT * FROM partner_schools ORDER BY school_name ASC");
                        if ($sch_q && $sch_q->num_rows > 0) {
                            while($sch = $sch_q->fetch_assoc()) {
                                echo "<tr>
                                    <td><strong>".htmlspecialchars($sch['school_name'])."</strong></td>
                                    <td>".htmlspecialchars($sch['location'])."</td>
                                    <td>".number_format($sch['tuition_fee'], 2)." RWF</td>
                                    <td>".number_format($sch['boarding_fee'], 2)." RWF</td>
                                    <td>".htmlspecialchars($sch['announcements'])."</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>Nta makuru y'amashuri aratangazwa.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= ALL DB INFORMATION ================= -->
        <div id="all_info" class="page-section">
            <h2>All System Records (System Logs & Database History)</h2>
            <p>Incamake y'ibiri mu mashini n'amashakiro yose ya Database.</p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $logs_q = $conn->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 20");
                        if ($logs_q && $logs_q->num_rows > 0) {
                            while($log = $logs_q->fetch_assoc()) {
                                echo "<tr>
                                    <td>".$log['id']."</td>
                                    <td><strong>".htmlspecialchars($log['action'])."</strong></td>
                                    <td>".htmlspecialchars($log['details'])."</td>
                                    <td>".htmlspecialchars($log['created_at'])."</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>Nta bintu birakorwa muri log za system.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
</div>

<footer>
    <div class="footer-grid">
        <div>
            <h4>Impact Hope in Rwanda</h4>
            <p>Empowering refugee youth through education and healthcare access.</p>
        </div>
        <div>
            <h4>Quick Contact</h4>
            <p>Email: support@impacthope.org<br>Phone: +250 780 000 000</p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> Impact Hope in Rwanda. All Rights Reserved.
    </div>
</footer>

<script>
function showSection(sectionId) {
    var sections = document.getElementsByClassName('page-section');
    for (var i = 0; i < sections.length; i++) {
        sections[i].classList.remove('active');
    }
    
    var navLinks = document.querySelectorAll('nav a');
    for (var j = 0; j < navLinks.length; j++) {
        navLinks[j].classList.remove('active');
    }
    
    document.getElementById(sectionId).classList.add('active');
    var activeNav = document.getElementById('nav-' + sectionId);
    if(activeNav) {
        activeNav.classList.add('active');
    }
}

function previewImage(event, previewId) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById(previewId);
        output.src = reader.result;
        output.style.display = 'block';
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>
</body>
</html>