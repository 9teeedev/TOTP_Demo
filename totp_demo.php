<?php
    session_start();
    include 'otphp/lib/otphp.php';
    include 'Base32.php';

    // edit account here
    $useraccount = "67040249128@udru.ac.th";
    // edit secret code here
    $secret = "NODEJSPHP.";
    
    $a = new Base32en();
    $secretcode = $a->base32_encode($secret);
    $totp = new \OTPHP\TOTP($secretcode);
    $chl = $totp->provisioning_uri($useraccount); 
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_otp = $_POST['user_otp'];
        if($totp->verify($user_otp)){
            $_SESSION['verified'] = true;
            $_SESSION['msg'] = "Verification successful!";
        }
        else {
            $_SESSION['verified'] = false;
            $_SESSION['msg'] = "Verification failed!";
        }
        $_SESSION['user_otp'] = $user_otp;
    } else {
        $user_otp = '';
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Demo</title>
</head>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 50px auto;
        max-width: 600px;
        text-align: center;
    }
    h1 {
        color: #333;
    }
    form {
        margin-bottom: 20px;
    }
    label {
        margin-right: 10px;
    }
    input[type="text"] {
        padding: 5px;
        font-size: 16px;
    }
    input[type="submit"] {
        padding: 5px 10px;
        font-size: 16px;
        cursor: pointer;
    }
</style>
<body>
    <h1>OTP Verification</h1>
    <form action="" method="post">
        <label for="otp">Enter OTP:</label>
        <input type="text" id="otp" name="user_otp" required>
        <input type="submit" value="Verify OTP">
    </form>
    <?php
        if (isset($_SESSION['msg'])) {
            echo "<p id='user-otp'>" . $_SESSION['user_otp'] . "</p>";
            if($_SESSION['verified']) {
                echo "<p style='color: green;' id='msg'>" . $_SESSION['msg'] . "</p>";
            } else {
                echo "<p style='color: red;' id='msg'>" . $_SESSION['msg'] . "</p>";
            }
            unset($_SESSION['msg']);
        }
    ?>
    
    <br>
    <!-- <img src='https://www.google.com/chart?chs=250x250&chld=M|0&cht=qr&chl=<?php //echo $chl; ?>'> -->
    <img src=' https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $chl; ?>'>

    <?php
        echo "<br><h2 id='otp'>OTP ===> ". $totp->now(). "</h2>";
    ?>
</body>
<script>
    setInterval(function(){
        location.reload();
    }, 30000);
    // unset message after 10 seconds
    setTimeout(function() {
        const msgElement = document.getElementById('msg');
        const userOtpElement = document.getElementById('user-otp');
        if (msgElement) {
            msgElement.style.display = 'none';
            userOtpElement.style.display = 'none';
        }
    }, 10000);
</script>
    
</script>
</html>

