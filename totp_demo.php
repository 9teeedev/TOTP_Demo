<?php
session_start();
include 'otphp/lib/otphp.php';
include 'Base32.php';

// Editable account metadata
$useraccount = '67040249128@udru.ac.th';
$secret = 'NODEJSPHP.';

$a = new Base32en();
$secretcode = $a->base32_encode($secret);
$totp = new \OTPHP\TOTP($secretcode);
$chl = $totp->provisioning_uri($useraccount);

$timeStep = method_exists($totp, 'getPeriod') ? (int) $totp->getPeriod() : 30;
$secondsIntoWindow = time() % $timeStep;
$secondsRemaining = $timeStep - $secondsIntoWindow;
if ($secondsRemaining === 0) {
    $secondsRemaining = $timeStep;
}

$verificationMessage = null;
$verificationState = null;
$lastUserOtp = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_otp = trim($_POST['user_otp'] ?? '');
    $lastUserOtp = $user_otp;

    if ($totp->verify($user_otp)) {
        $verificationState = true;
        $verificationMessage = 'เข้าสู่ระบบสำเร็จ (Verification successful).';
    } else {
        $verificationState = false;
        $verificationMessage = 'รหัสไม่ถูกต้อง โปรดลองอีกครั้ง (Verification failed).';
    }

    $_SESSION['verified'] = $verificationState;
    $_SESSION['msg'] = $verificationMessage;
    $_SESSION['user_otp'] = $lastUserOtp;
} elseif (isset($_SESSION['msg'])) {
    $verificationMessage = $_SESSION['msg'];
    $verificationState = $_SESSION['verified'] ?? null;
    $lastUserOtp = $_SESSION['user_otp'] ?? '';
}

unset($_SESSION['msg'], $_SESSION['verified'], $_SESSION['user_otp']);

$showSetup = $_SERVER['REQUEST_METHOD'] !== 'POST';

$currentOtpRaw = $totp->now();
$currentOtpSplit = trim(chunk_split($currentOtpRaw, 3, ' '));
$manualSecret = trim(chunk_split(strtoupper($secretcode), 4, ' '));
$qrData = rawurlencode($chl);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional OTP Demo</title>
    <style>
        :root {
            --bg: #edf2ff;
            --card-bg: #ffffff;
            --stroke: #e4e7ec;
            --text: #0f172a;
            --subtext: #475467;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #16a34a;
            --error: #dc2626;
            --shadow: 0 25px 60px rgba(15, 23, 42, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', 'Prompt', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: radial-gradient(circle at top, #dbeafe, #eff6ff 45%, #f8fafc 80%);
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 32px;
        }

        .app-wrapper {
            width: min(1160px, 100%);
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        header {
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .eyebrow {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--primary);
            font-weight: 600;
        }

        header h1 {
            margin: 0;
            font-size: clamp(28px, 4vw, 40px);
            font-weight: 600;
        }

        header p {
            margin: 0;
            color: var(--subtext);
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 28px;
            padding: clamp(24px, 4vw, 36px);
            box-shadow: var(--shadow);
        }

        .verification-card form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        label {
            font-weight: 600;
            color: var(--text);
        }

        .otp-input {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .otp-input input {
            flex: 1 1 220px;
            padding: 20px;
            border-radius: 18px;
            border: 1px solid var(--stroke);
            font-size: 26px;
            letter-spacing: 0.32em;
            text-align: center;
            font-weight: 600;
            color: var(--text);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .otp-input input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .primary-btn {
            flex: 0 0 auto;
            padding: 0 28px;
            border: none;
            border-radius: 16px;
            background: var(--primary);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
            min-height: 60px;
        }

        .primary-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .helper {
            color: var(--subtext);
            font-size: 14px;
        }

        .status-banner {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px 20px;
            border-radius: 18px;
            border: 1px solid transparent;
            margin-top: 4px;
            position: relative;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .status-banner.hidden {
            opacity: 0;
            transform: translateY(-8px);
            pointer-events: none;
        }

        .status-banner .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-top: 6px;
        }

        .status-banner strong {
            display: block;
        }

        .status-meta {
            margin: 4px 0 0;
            font-size: 13px;
            color: var(--subtext);
        }

        .status-success {
            background: #ecfdf3;
            border-color: #bbf7d0;
            color: var(--success);
        }

        .status-success .status-dot {
            background: var(--success);
        }

        .status-error {
            background: #fef2f2;
            border-color: #fecaca;
            color: var(--error);
        }

        .status-error .status-dot {
            background: var(--error);
        }

        .dismiss {
            position: absolute;
            top: 12px;
            right: 14px;
            border: none;
            background: transparent;
            color: inherit;
            font-size: 20px;
            cursor: pointer;
            line-height: 1;
        }

        .info-grid {
            margin-top: 28px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
        }

        .info-tile {
            border: 1px solid var(--stroke);
            border-radius: 20px;
            padding: 18px 20px;
            background: #f8fafc;
        }

        .info-tile .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--subtext);
            margin-bottom: 8px;
        }

        .otp-display {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0.28em;
            text-align: center;
        }

        .countdown {
            font-size: 42px;
            font-weight: 600;
            display: flex;
            align-items: flex-end;
            gap: 6px;
        }

        .countdown .unit {
            font-size: 14px;
            color: var(--subtext);
            text-transform: uppercase;
            letter-spacing: 0.2em;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            border-radius: 999px;
            background: #e2e8f0;
            margin-top: 12px;
            overflow: hidden;
        }

        .progress-bar span {
            display: block;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--primary), #60a5fa);
            transition: width 1s linear;
        }

        .qr-card {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .qr-wrapper {
            background: #f1f5f9;
            border-radius: 24px;
            padding: 24px;
            display: flex;
            justify-content: center;
        }

        .qr-wrapper img {
            width: 220px;
            height: 220px;
        }

        .qr-steps {
            margin: 0;
            padding-left: 20px;
            color: var(--subtext);
            line-height: 1.8;
        }

        .secret-chip {
            margin-top: 8px;
            padding: 12px 16px;
            border-radius: 14px;
            background: #101828;
            color: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 14px;
            letter-spacing: 0.12em;
        }

        .secret-chip span {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.32em;
            color: #94a3b8;
        }

        @media (max-width: 640px) {
            body {
                padding: 18px;
            }

            .otp-input {
                flex-direction: column;
            }

            .otp-input input {
                font-size: 22px;
                letter-spacing: 0.2em;
            }

            .primary-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body data-period="<?php echo (int) $timeStep; ?>" data-remaining="<?php echo (int) $secondsRemaining; ?>">
    <div class="app-wrapper">
        <header>
            <span class="eyebrow">Secure Access Center</span>
            <h1>ยืนยันตัวตนด้วย One-Time Passcode</h1>
            <p>ป้อนรหัส 6 หลักจากแอป Authenticator เพื่อเข้าสู่ระบบอย่างปลอดภัย</p>
        </header>

        <div class="content-grid">
            <!-- Setup Section -->
            <section class="card qr-card" id="setup-section" style="<?php echo $showSetup ? '' : 'display:none;'; ?>">
                <span class="eyebrow">Step 1: Setup</span>
                <h2>ตั้งค่าบนมือถือของคุณ</h2>
                <p class="helper">สแกน QR หรือป้อน secret ด้านล่างเพื่อเชื่อมต่อกับแอป TOTP ที่คุณใช้งาน</p>

                <div class="qr-wrapper">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&amp;data=<?php echo $qrData; ?>" alt="QR code for authenticator provisioning">
                </div>

                <ol class="qr-steps">
                    <li>เปิด Google Authenticator, Microsoft Authenticator หรือแอป TOTP ใด ๆ</li>
                    <li>เลือกเพิ่มบัญชีใหม่แล้วสแกน QR Code ด้านบน</li>
                    <li>เมื่อสแกนเรียบร้อยแล้ว กดปุ่ม "ถัดไป" ด้านล่าง</li>
                </ol>

                <div class="secret-chip">
                    <span>Secret</span>
                    <code><?php echo htmlspecialchars($manualSecret); ?></code>
                </div>

                <div style="margin-top: 24px; text-align: center;">
                    <button type="button" class="primary-btn" onclick="showVerifyStep()" style="width: 100%;">ฉันสแกนเรียบร้อยแล้ว (Next)</button>
                </div>
            </section>

            <!-- Verify Section -->
            <section class="card verification-card" id="verify-section" style="<?php echo $showSetup ? 'display:none;' : ''; ?>">
                <span class="eyebrow">Step 2: Verify</span>
                <h2>ยืนยันรหัส OTP</h2>
                <p class="helper" style="margin-bottom: 24px;">กรอกรหัส 6 หลักที่ปรากฏบนแอปพลิเคชันของคุณ</p>
                
                <form action="" method="post" autocomplete="off">
                    <div>
                        <label for="otp">One-Time Code</label>
                        <div class="otp-input">
                            <input type="text" id="otp" name="user_otp" required maxlength="6" minlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code" value="<?php echo htmlspecialchars($lastUserOtp, ENT_QUOTES); ?>" placeholder="••••••" autofocus>
                            <button type="submit" class="primary-btn">Verify Code</button>
                        </div>
                    </div>

                    <?php if ($verificationMessage !== null): ?>
                        <div class="status-banner <?php echo $verificationState ? 'status-success' : 'status-error'; ?>" id="status-banner" role="status" aria-live="polite">
                            <span class="status-dot"></span>
                            <div>
                                <strong><?php echo htmlspecialchars($verificationMessage); ?></strong>
                            </div>
                            <button type="button" class="dismiss" id="dismiss-status" aria-label="Close notification">&times;</button>
                        </div>
                    <?php endif; ?>
                </form>
                
                <div style="margin-top: 24px; text-align: center;">
                    <button type="button" style="background:none; border:none; color: var(--subtext); cursor: pointer; text-decoration: underline;" onclick="showSetupStep()">ย้อนกลับไปหน้าสแกน QR Code</button>
                </div>
            </section>
        </div>
    </div>

    <script>
        function showVerifyStep() {
            document.getElementById('setup-section').style.display = 'none';
            document.getElementById('verify-section').style.display = 'block';
            document.getElementById('otp').focus();
        }

        function showSetupStep() {
            document.getElementById('verify-section').style.display = 'none';
            document.getElementById('setup-section').style.display = 'block';
        }

        (function () {
            const statusBanner = document.getElementById('status-banner');
            const dismissBtn = document.getElementById('dismiss-status');

            if (dismissBtn && statusBanner) {
                dismissBtn.addEventListener('click', () => {
                    statusBanner.classList.add('hidden');
                });
                setTimeout(() => statusBanner.classList.add('hidden'), 10000);
            }
        })();
    </script>
</body>
</html>

