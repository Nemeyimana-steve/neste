<?php
// ==========================================
// 1. DATABASE CONFIGURATION
// ==========================================
$host = 'localhost';
$db   = 'biometric_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// Reba ko Table yuzuye muri Database
$pdo->exec("CREATE TABLE IF NOT EXISTS full_citizens_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uin VARCHAR(20) UNIQUE NOT NULL,
    national_id_ref VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    gender VARCHAR(10) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    thumbprint_hash VARCHAR(255) NOT NULL,
    fingerprints_hash VARCHAR(255) NOT NULL,
    iris_hash VARCHAR(255) NOT NULL,
    face_photo LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = "";
$error = "";

// ==========================================
// 2. ENROLLMENT ENGINE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref_id       = trim($_POST['ref_id'] ?? '');
    $full_name    = trim($_POST['full_name'] ?? '');
    $dob          = trim($_POST['dob'] ?? '');
    $gender       = trim($_POST['gender'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $thumbprint   = trim($_POST['thumbprint'] ?? '');
    $fingerprints = trim($_POST['fingerprints'] ?? '');
    $iris         = trim($_POST['iris'] ?? '');
    $photo        = trim($_POST['photo'] ?? '');

    if (!empty($ref_id) && !empty($full_name) && !empty($dob) && !empty($phone) && !empty($thumbprint) && !empty($fingerprints) && !empty($photo)) {
        
        $thumb_hash = hash('sha256', $thumbprint);
        $fp_hash    = hash('sha256', $fingerprints);
        $iris_hash  = hash('sha256', $iris);

        // ABIS De-duplication Check
        $check = $pdo->prepare("SELECT uin FROM full_citizens_registry WHERE thumbprint_hash = ? OR fingerprints_hash = ?");
        $check->execute([$thumb_hash, $fp_hash]);
        
        if ($check->fetch()) {
            $error = "<strong>ABIS DE-DUPLICATION FAILED!</strong> Ibi biranga umuntu biyunguruwe ko abandi bamaze kwandikwa!";
        } else {
            $uin = "UIN-" . rand(10000000, 99999999);
            $stmt = $pdo->prepare("INSERT INTO full_citizens_registry (uin, national_id_ref, full_name, dob, gender, phone, thumbprint_hash, fingerprints_hash, iris_hash, face_photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$uin, $ref_id, $full_name, $dob, $gender, $phone, $thumb_hash, $fp_hash, $iris_hash, $photo])) {
                $message = "<strong>REGISTRATION SUCCESSFUL!</strong> UIN Yaremywe: <u>$uin</u>";
            } else {
                $error = "Hano habaye ikosa mu kubika data.";
            }
        }
    } else {
        $error = "Nyamuneka uzuze imyirondoro n'ibiranga umuntu vyose zvagengewe!";
    }
}

$citizens = $pdo->query("SELECT * FROM full_citizens_registry ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="rw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>National Identity Security Engine</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 12px; }
        .header { text-align: center; border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 25px; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert-success { background: #064e3b; color: #6ee7b7; border: 1px solid #059669; }
        .alert-danger { background: #7f1d1d; color: #fca5a5; border: 1px solid #dc2626; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; color: #94a3b8; font-size: 0.9rem; }
        .input-group input, .input-group select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: #fff; }

        .devices-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 15px; margin-top: 20px; }
        .device-card { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 15px; text-align: center; }

        .progress-bar { width: 100%; height: 8px; background: #334155; border-radius: 4px; overflow: hidden; margin: 12px 0; }
        .progress-fill { height: 100%; width: 0%; background: #22c55e; transition: width 0.3s; }

        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; background: #334155; color: #94a3b8; }
        .status-badge.active { background: #065f46; color: #34d399; }

        .btn-capture { background: #0284c7; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; }
        #camera-preview { width: 100%; height: 150px; background: #000; border-radius: 6px; object-fit: cover; }
        .btn-submit { width: 100%; background: #16a34a; color: white; border: none; padding: 15px; font-size: 1.1rem; border-radius: 8px; font-weight: bold; margin-top: 25px; cursor: pointer; }

        table { width: 100%; border-collapse: collapse; margin-top: 30px; background: #0f172a; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #334155; }
        th { background: #1e293b; color: #38bdf8; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>National Identity & Biometric Registry</h1>
        <p style="color:#94a3b8;">Full Registration Engine (Imyirondoro, Igikumwe, Intoki, Amaso, n'Isura)</p>
    </div>

    <?php if($message): ?><div class="alert alert-success"><?=$message?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>

    <form action="index.php" method="POST">

        <!-- 1. IMYIRONDORO YOSE -->
        <h3>1. Imyirondoro yawe Yose (Demographics)</h3>
        <div class="form-grid">
            <div class="input-group">
                <label>National ID / Application Ref</label>
                <input type="text" name="ref_id" placeholder="11995800..." required />
            </div>
            <div class="input-group">
                <label>Izina Ryose (Full Name)</label>
                <input type="text" name="full_name" placeholder="Amazina yose" required />
            </div>
            <div class="input-group">
                <label>Itariki y'Amavuko (DOB)</label>
                <input type="date" name="dob" required />
            </div>
            <div class="input-group">
                <label>Igitsina (Gender)</label>
                <select name="gender" required>
                    <option value="Male">Gabo</option>
                    <option value="Female">Gore</option>
                </select>
            </div>
            <div class="input-group">
                <label>Nimero ya Telefone</label>
                <input type="text" name="phone" placeholder="078..." required />
            </div>
        </div>

        <!-- 2. IBIRANGA UMUNTU (BIOMETRICS & PHOTO) -->
        <h3>2. Biometric Captures (Isura, Igikumwe, Intoki, Amaso)</h3>
        <div class="devices-grid">
            
            <!-- A. ISURA / IFOTO -->
            <div class="device-card">
                <h4>1. Isura / Ifoto (Face)</h4>
                <video id="camera-preview" autoplay playsinline></video>
                <div id="face-status" class="status-badge" style="margin-top:8px;">Camera Standby</div>
                <input type="hidden" name="photo" id="photo_input">
                <button type="button" class="btn-capture" style="margin-top:8px;" onclick="captureFace()">Fata Ifoto (Capture)</button>
            </div>

            <!-- B. IGIKUMWE -->
            <div class="device-card">
                <h4>2. Igikumwe (Thumbprint)</h4>
                <p style="color:#94a3b8; font-size:0.8rem;">Soma Igikumwe</p>
                <div id="thumb-status" class="status-badge">Standby</div>
                <div class="progress-bar"><div id="thumb-progress" class="progress-fill"></div></div>
                <input type="hidden" name="thumbprint" id="thumb_input">
                <button type="button" class="btn-capture" onclick="simulateDevice('thumb')">Soma Igikumwe</button>
            </div>

            <!-- C. INTOKI ZOSE -->
            <div class="device-card">
                <h4>3. Intoki zose 10 (Fingerprints)</h4>
                <p style="color:#94a3b8; font-size:0.8rem;">Slap Scanner Capture</p>
                <div id="fp-status" class="status-badge">Standby</div>
                <div class="progress-bar"><div id="fp-progress" class="progress-fill"></div></div>
                <input type="hidden" name="fingerprints" id="fp_input">
                <button type="button" class="btn-capture" onclick="simulateDevice('fp')">Soma Intoki Zose</button>
            </div>

            <!-- D. AMASO -->
            <div class="device-card">
                <h4>4. Amaso (Iris Scan)</h4>
                <p style="color:#94a3b8; font-size:0.8rem;">Dual Iris Capture</p>
                <div id="iris-status" class="status-badge">Standby</div>
                <div class="progress-bar"><div id="iris-progress" class="progress-fill"></div></div>
                <input type="hidden" name="iris" id="iris_input">
                <button type="button" class="btn-capture" onclick="simulateDevice('iris')">Soma Amaso</button>
            </div>

        </div>

        <button type="submit" class="btn-submit">OHEREZA UBUSARE MURI SYSTEM (ENROLL)</button>
    </form>

    <!-- LIST YA ABATURAGE -->
    <h3 style="margin-top: 40px; color: #38bdf8;">Abaturage Bamaze Kwandikwa</h3>
    <table>
        <thead>
            <tr>
                <th>UIN</th>
                <th>National ID</th>
                <th>Izina</th>
                <th>Ifoto</th>
                <th>Telefone</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($citizens)): ?>
                <tr><td colspan="5" style="text-align:center; color:#94a3b8;">Nta muturage uragera mu bubiko.</td></tr>
            <?php else: ?>
                <?php foreach($citizens as $c): ?>
                    <tr>
                        <td><strong style="color:#22c55e;"><?= htmlspecialchars($c['uin']) ?></strong></td>
                        <td><?= htmlspecialchars($c['national_id_ref']) ?></td>
                        <td><?= htmlspecialchars($c['full_name']) ?></td>
                        <td><img src="<?= $c['face_photo'] ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;"></td>
                        <td><?= htmlspecialchars($c['phone']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Camera Capture Setup
const video = document.getElementById('camera-preview');
const photoInput = document.getElementById('photo_input');

if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.getUserMedia({ video: true }).then(stream => { video.srcObject = stream; });
}

function captureFace() {
    const canvas = document.createElement('canvas');
    canvas.width = 200; canvas.height = 150;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    photoInput.value = canvas.toDataURL('image/jpeg');
    document.getElementById('face-status').innerText = "Captured OK";
    document.getElementById('face-status').className = "status-badge active";
}

// Biometric Capture Simulator
function simulateDevice(type) {
    let score = 0;
    let interval = setInterval(() => {
        score += 25;
        document.getElementById(type + '-progress').style.width = score + '%';
        if (score >= 100) {
            clearInterval(interval);
            document.getElementById(type + '-status').innerText = "Captured OK";
            document.getElementById(type + '-status').className = "status-badge active";
            document.getElementById(type + '_input').value = "DEVICE_HARDWARE_DATA_" + type.toUpperCase();
        }
    }, 150);
}
</script>

</body>
</html>