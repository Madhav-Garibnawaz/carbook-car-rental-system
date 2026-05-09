<?php
session_name('user_session');
session_start();
?>      
      <?php ob_start(); ?>
      <?php
        require('connect.php');
        require "vendor/autoload.php";

        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;

        // Modal message variables
        $modalTitle = '';
        $modalMessage = '';
        $modalType = ''; // 'success' | 'error' | 'warn' | 'blocked'
        $modalRedirect = '';

        if(isset($_POST['register'])) {

            $uname = $_POST['uname'];
            $dob = $_POST['dob'];
            $gen = $_POST['gen'];
            $email = $_POST['email'];
            $mobno = $_POST['mobno'];
            $address = $_POST['address'];
            $pass = $_POST['pass'];
            $confirm_pass = $_POST['confirm_pass'];
            $pin = $_POST['pin'];

            $dobDate  = new DateTime($dob);
            $today    = new DateTime();
            $age      = $today->diff($dobDate)->y;

            $valid_mobile = preg_match('/^[6-9][0-9]{9}$/', $mobno);

            $check_email  = mysqli_query($con, "SELECT ui FROM users_master WHERE email='$email'");
            $check_mobile = mysqli_query($con, "SELECT ui FROM users_master WHERE mobno='$mobno'");

            if($pass != $confirm_pass){
                $modalTitle   = 'Password Mismatch';
                $modalMessage = 'The passwords you entered do not match. Please try again.';
                $modalType    = 'error';
            } elseif($age < 18){
                $modalTitle   = 'Age Requirement';
                $modalMessage = 'You must be at least <strong>18 years old</strong> to create an account.';
                $modalType    = 'error';
            } elseif(!$valid_mobile){
                $modalTitle   = 'Invalid Mobile Number';
                $modalMessage = 'Indian mobile numbers must start with <strong>6, 7, 8, or 9</strong> and be exactly 10 digits.';
                $modalType    = 'error';
            } elseif(mysqli_num_rows($check_email) > 0){
                $modalTitle   = 'Email Already Registered';
                $modalMessage = 'An account with this email address <strong>'.htmlspecialchars($email).'</strong> already exists. Please sign in or use a different email.';
                $modalType    = 'error';
            } elseif(mysqli_num_rows($check_mobile) > 0){
                $modalTitle   = 'Mobile Already Registered';
                $modalMessage = 'An account with mobile number <strong>'.htmlspecialchars($mobno).'</strong> already exists. Please use a different number.';
                $modalType    = 'error';
            } else {

                $userphoto = $_FILES['userphoto']['name'];
                $tmp = $_FILES['userphoto']['tmp_name'];
                $dst = "user_profile/" . $userphoto;
                move_uploaded_file($tmp, $dst);

                $q = mysqli_query($con,"INSERT INTO users_master
                (uname,email,mobno,address,dob,pin,gen,pass,photo,status)
                VALUES
                ('$uname','$email','$mobno','$address','$dob','$pin','$gen','$pass','$userphoto',0)");

                if($q){
                    $otp = rand(100000,999999);
                    mysqli_query($con,"UPDATE users_master SET otp='$otp', status=1 WHERE email='$email'");

                    $mail = new PHPMailer(true);
                    try{
                        $mail->isSMTP();
                        $mail->Host       = "smtp.gmail.com";
                        $mail->SMTPAuth   = true;
                        $mail->Username   = "your-email@gmail.com";
                        $mail->Password   = "YOUR_APP_PASSWORD";
                        $mail->SMTPSecure = "tls";
                        $mail->Port       = 587;
                        $mail->setFrom("your-email@gmail.com","Car Rental");
                        $mail->addAddress($email,$uname);
                        $mail->isHTML(true);
                        $mail->Subject = "Your OTP Verification Code";
                        $mail->Body    = "<h2>Hello $uname</h2><h1>$otp</h1>";
                        $mail->send();

                        $modalTitle = 'Account Created!';
                        $modalMessage = 'We\'ve sent a 6-digit OTP to <strong>' . htmlspecialchars($email) . '</strong>. Please check your inbox to verify your account.';
                        $modalType = 'success';
                        $modalRedirect = "verify.php?email=" . urlencode($email);

                    } catch(Exception $e){
                        $modalTitle = 'Mail Error';
                        $modalMessage = 'Your account was created but we could not send the verification email. Error: ' . htmlspecialchars($mail->ErrorInfo);
                        $modalType = 'warn';
                    }
                } else {
                    $modalTitle = 'Registration Failed';
                    $modalMessage = 'Something went wrong while creating your account. The email may already be registered.';
                    $modalType = 'error';
                }
            }
        }
      ?>
      <?php ob_end_flush(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AutoDrive — Sign In / Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue: #1a4b8c;
      --blue-dark: #123570;
      --blue-light: #e8f0fc;
      --sea: #2a9d8f;
      --sea-dark: #1f7a6e;
      --sea-light: #e0f5f3;
      --black: #0f1923;
      --white: #ffffff;
      --off: #f2f6fb;
      --gray: #6c7a8d;
      --light: #dde6f0;
      --border: #c8d6e8;
      --error: #e74c3c;
      --error-light: #fdf0ef;
      --success: #2a9d8f;
      --warn: #f39c12;
      --blocked: #dc2626;
    }

    body {
      background-color: var(--off);
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 30px 16px;
      position: relative;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        radial-gradient(circle at 12% 12%, rgba(26,75,140,0.09) 0%, transparent 50%),
        radial-gradient(circle at 88% 88%, rgba(42,157,143,0.09) 0%, transparent 50%);
      pointer-events: none;
      z-index: 0;
    }

    body::after {
      content: 'AUTODRIVE';
      position: fixed;
      bottom: -30px;
      right: -30px;
      font-family: 'Syne', sans-serif;
      font-size: clamp(80px, 18vw, 200px);
      font-weight: 800;
      color: rgba(26,75,140,0.04);
      pointer-events: none;
      letter-spacing: -0.02em;
      line-height: 1;
      z-index: 0;
    }

    .page-wrapper {
      width: 100%;
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .card-wrapper {
      width: 100%;
      max-width: 460px;
      transition: max-width 0.4s cubic-bezier(.4,0,.2,1);
    }
    .card-wrapper.wide { max-width: 860px; }

    .brand-header { text-align: center; margin-bottom: 22px; }
    .logo-pill {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: 50px;
      padding: 8px 22px 8px 10px;
      box-shadow: 0 2px 16px rgba(26,75,140,0.1);
    }
    .logo-icon {
      width: 36px; height: 36px;
      background: var(--blue);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }
    .logo-icon svg { fill: white; width: 20px; height: 20px; }
    .logo-text {
      font-family: 'Syne', sans-serif;
      font-size: 20px;
      font-weight: 800;
      color: var(--black);
      letter-spacing: -0.01em;
    }
    .logo-text span { color: var(--sea); }
    .brand-sub { margin-top: 9px; font-size: 12.5px; color: var(--gray); }

    .form-card {
      background: var(--white);
      border-radius: 18px;
      border: 1px solid var(--border);
      box-shadow: 0 6px 36px rgba(26,75,140,0.1);
      overflow: hidden;
    }

    .tab-switcher {
      display: flex;
      background: var(--off);
      border-bottom: 1px solid var(--border);
      padding: 6px;
      gap: 4px;
    }
    .tab-btn {
      flex: 1;
      padding: 10px 20px;
      background: none;
      border: none;
      border-radius: 10px;
      font-family: 'Syne', sans-serif;
      font-size: 12.5px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: var(--gray);
      cursor: pointer;
      transition: all 0.22s ease;
    }
    .tab-btn.active {
      background: var(--white);
      color: var(--blue);
      box-shadow: 0 2px 10px rgba(26,75,140,0.12);
    }

    .form-panel { display: none; }
    .form-panel.active { display: block; }
    #loginPanel { padding: 36px 38px; max-width: 420px; margin: 0 auto; }
    #registerPanel { padding: 30px 36px; }

    .reg-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0 32px;
    }
    .col-full { grid-column: 1 / -1; }

    .section-label {
      font-family: 'Syne', sans-serif;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--sea);
      margin-bottom: 16px;
      padding-bottom: 8px;
      border-bottom: 2px solid var(--sea-light);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .section-label::before {
      content: '';
      width: 4px; height: 14px;
      background: var(--sea);
      border-radius: 2px;
      display: inline-block;
      flex-shrink: 0;
    }

    .field-group { margin-bottom: 16px; }
    .form-label {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.07em;
      color: var(--gray);
      margin-bottom: 6px;
      text-transform: uppercase;
      display: block;
    }
    .form-control, .form-select {
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      background: var(--off);
      color: var(--black);
      padding: 10px 14px;
      transition: border-color 0.18s, background 0.18s, box-shadow 0.18s;
      width: 100%;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--sea);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(42,157,143,0.13);
      outline: none;
    }
    .form-control::placeholder { color: #aab5c4; }

    .form-control.is-valid { border-color: var(--success) !important; background: var(--white); }
    .form-control.is-invalid { border-color: var(--error) !important; background: var(--error-light); }
    .form-control.is-invalid:focus { box-shadow: 0 0 0 3px rgba(231,76,60,0.13); border-color: var(--error) !important; }

    .input-wrap { position: relative; }
    .input-wrap .form-control { padding-right: 36px; }
    .input-status {
      position: absolute;
      right: 11px;
      top: 50%;
      transform: translateY(-50%);
      width: 18px; height: 18px;
      border-radius: 50%;
      display: none;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: 700;
      pointer-events: none;
      flex-shrink: 0;
    }
    .input-wrap .form-control.is-valid  ~ .input-status { display: flex; background: var(--sea-light); color: var(--sea); }
    .input-wrap .form-control.is-valid  ~ .input-status::after { content: '✓'; }
    .input-wrap .form-control.is-invalid ~ .input-status { display: flex; background: #fde8e6; color: var(--error); }
    .input-wrap .form-control.is-invalid ~ .input-status::after { content: '✕'; }
    textarea.form-control { padding-right: 14px !important; }

    .field-error {
      font-size: 11px;
      color: var(--error);
      margin-top: 5px;
      display: none;
      align-items: center;
      gap: 4px;
      font-weight: 500;
    }
    .field-error.show { display: flex; }
    .field-error::before { content: '⚠'; font-size: 10px; flex-shrink: 0; }

    .img-upload-area {
      border: 1.5px dashed var(--border);
      border-radius: 9px;
      background: var(--off);
      padding: 14px;
      text-align: center;
      cursor: pointer;
      transition: all 0.18s;
      position: relative;
    }
    .img-upload-area:hover { border-color: var(--sea); background: var(--sea-light); }
    .img-upload-area.is-invalid { border-color: var(--error); background: var(--error-light); }
    .img-upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .img-upload-area .up-icon { font-size: 18px; display: block; margin-bottom: 3px; }
    .img-upload-area p { font-size: 12px; color: var(--gray); margin: 0; }
    .file-name { font-size: 12px; color: var(--sea); margin-top: 4px; font-weight: 500; }

    .gender-group { display: flex; gap: 8px; }
    .gender-option { flex: 1; }
    .gender-option input[type="radio"] { display: none; }
    .gender-option label {
      display: block;
      text-align: center;
      padding: 9px 4px;
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-size: 11.5px;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      cursor: pointer;
      transition: all 0.16s;
      color: var(--gray);
      background: var(--off);
    }
    .gender-option input[type="radio"]:checked + label {
      background: var(--sea-light);
      border-color: var(--sea);
      color: var(--sea-dark);
    }
    .gender-group.is-invalid label { border-color: var(--error); background: var(--error-light); }

    .pass-wrapper { position: relative; display: flex; align-items: center; }
    .pass-wrapper .form-control { padding-right: 72px; }
    .pass-icons {
      position: absolute;
      right: 0; top: 0; bottom: 0;
      display: flex; align-items: center;
      padding-right: 10px;
      pointer-events: none;
    }
    .pass-status {
      width: 20px; height: 20px;
      border-radius: 50%;
      display: none;
      align-items: center; justify-content: center;
      font-size: 10px; font-weight: 700;
      flex-shrink: 0; pointer-events: none; margin-right: 6px;
    }
    .pass-status.show-valid   { display: flex; background: var(--sea-light); color: var(--sea); }
    .pass-status.show-valid::after   { content: '✓'; }
    .pass-status.show-invalid { display: flex; background: #fde8e6; color: var(--error); }
    .pass-status.show-invalid::after { content: '✕'; }
    .pass-divider { width: 1px; height: 16px; background: var(--border); margin-right: 8px; flex-shrink: 0; }
    .pass-toggle {
      background: none; border: none; cursor: pointer; padding: 0;
      color: var(--gray); display: flex; align-items: center;
      pointer-events: all; transition: color 0.15s; flex-shrink: 0;
    }
    .pass-toggle:hover { color: var(--blue); }
    .pass-toggle svg { width: 17px; height: 17px; display: block; }

    .pass-strength-bar { display: flex; gap: 4px; margin-top: 8px; }
    .pass-strength-bar span {
      flex: 1; height: 4px; border-radius: 10px;
      background: var(--light); transition: background 0.3s ease;
    }
    .pass-strength-bar span.active-weak   { background: #e74c3c; }
    .pass-strength-bar span.active-fair   { background: var(--warn); }
    .pass-strength-bar span.active-good   { background: #3498db; }
    .pass-strength-bar span.active-strong { background: var(--success); }

    .pass-strength-label {
      font-size: 11px; font-weight: 600; margin-top: 5px;
      letter-spacing: 0.06em; text-transform: uppercase; display: none;
    }
    .pass-strength-label.show { display: block; }
    .pass-strength-label.weak   { color: #e74c3c; }
    .pass-strength-label.fair   { color: var(--warn); }
    .pass-strength-label.good   { color: #3498db; }
    .pass-strength-label.strong { color: var(--success); }

    .pass-instructions {
      background: var(--off); border: 1px solid var(--border);
      border-radius: 9px; padding: 12px 14px; margin-top: 8px; display: none;
    }
    .pass-instructions.show { display: block; }
    .pass-instructions p {
      font-size: 11px; font-weight: 700; letter-spacing: 0.07em;
      text-transform: uppercase; color: var(--gray); margin-bottom: 8px;
    }
    .pass-rule {
      display: flex; align-items: center; gap: 8px;
      font-size: 12.5px; color: var(--gray); margin-bottom: 5px; transition: color 0.2s;
    }
    .pass-rule:last-child { margin-bottom: 0; }
    .pass-rule .rule-icon {
      width: 16px; height: 16px; border-radius: 50%; background: var(--light);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 9px; transition: background 0.2s;
    }
    .pass-rule.met { color: var(--success); }
    .pass-rule.met .rule-icon { background: var(--sea-light); color: var(--sea); }
    .pass-rule.unmet .rule-icon { background: #fde8e6; color: var(--error); }

    .form-check-input {
      border-radius: 4px !important; border-color: var(--border) !important;
      width: 15px; height: 15px; cursor: pointer;
    }
    .form-check-input:checked { background-color: var(--sea) !important; border-color: var(--sea) !important; }
    .form-check-input:focus { box-shadow: 0 0 0 3px rgba(42,157,143,0.15) !important; }
    .form-check-label { font-size: 13px; color: var(--gray); cursor: pointer; }
    .form-check-input.is-invalid { border-color: var(--error) !important; }

    .link-accent { color: var(--sea); text-decoration: none; font-weight: 500; }
    .link-accent:hover { text-decoration: underline; color: var(--sea-dark); }

    .btn-submit {
      width: 100%; padding: 13px; background: var(--blue); border: none;
      border-radius: 10px; color: #fff; font-family: 'Syne', sans-serif;
      font-size: 13.5px; font-weight: 700; letter-spacing: 0.07em;
      text-transform: uppercase; cursor: pointer;
      transition: background 0.18s, transform 0.12s, box-shadow 0.18s;
      box-shadow: 0 4px 16px rgba(26,75,140,0.22);
    }
    .btn-submit:hover { background: var(--blue-dark); box-shadow: 0 6px 22px rgba(26,75,140,0.3); transform: translateY(-1px); }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit.sea { background: var(--sea); box-shadow: 0 4px 16px rgba(42,157,143,0.26); }
    .btn-submit.sea:hover { background: var(--sea-dark); box-shadow: 0 6px 22px rgba(42,157,143,0.33); }

    .toast-msg {
      display: none; padding: 11px 22px; background: var(--black);
      color: #fff; font-size: 13px; font-weight: 500; border-left: 4px solid var(--sea);
    }
    .toast-msg.show { display: block; }

    .center-text { text-align: center; font-size: 13px; color: var(--gray); margin-top: 14px; }
    .login-badge { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 26px; }
    .login-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--sea); }
    .login-badge span {
      font-size: 11px; color: var(--gray); letter-spacing: 0.1em;
      font-family: 'Syne', sans-serif; font-weight: 700; text-transform: uppercase;
    }

    /* ═══════════════════════════════════════════
       THEMED MODAL
    ═══════════════════════════════════════════ */
    .cb-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15,25,35,0.55);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.22s ease;
    }
    .cb-modal-overlay.open {
      opacity: 1;
      pointer-events: all;
    }
    .cb-modal {
      background: var(--white);
      border-radius: 18px;
      border: 1px solid var(--border);
      box-shadow: 0 16px 60px rgba(26,75,140,0.18);
      width: 100%;
      max-width: 400px;
      overflow: hidden;
      transform: translateY(16px) scale(0.97);
      transition: transform 0.26s cubic-bezier(.4,0,.2,1);
    }
    .cb-modal-overlay.open .cb-modal {
      transform: translateY(0) scale(1);
    }
    .cb-modal-header {
      padding: 22px 24px 16px;
      display: flex;
      align-items: flex-start;
      gap: 14px;
      border-bottom: 1px solid var(--border);
    }
    .cb-modal-icon {
      width: 42px; height: 42px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px;
      flex-shrink: 0;
    }
    .cb-modal-icon.success  { background: var(--sea-light); }
    .cb-modal-icon.error    { background: var(--error-light); }
    .cb-modal-icon.warn     { background: #fef8ec; }
    .cb-modal-icon.blocked  { background: #fef2f2; }
    .cb-modal-title {
      font-family: 'Syne', sans-serif;
      font-size: 15px;
      font-weight: 800;
      color: var(--black);
      margin: 0 0 3px;
      letter-spacing: -0.01em;
    }
    .cb-modal-subtitle {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }
    .cb-modal-subtitle.success  { color: var(--sea); }
    .cb-modal-subtitle.error    { color: var(--error); }
    .cb-modal-subtitle.warn     { color: var(--warn); }
    .cb-modal-subtitle.blocked  { color: var(--blocked); }
    .cb-modal-body {
      padding: 18px 24px;
      font-size: 13.5px;
      color: var(--gray);
      line-height: 1.6;
    }
    .cb-modal-body strong { color: var(--black); font-weight: 600; }
    .cb-modal-footer {
      padding: 0 24px 22px;
      display: flex;
      gap: 10px;
    }
    .cb-modal-btn {
      flex: 1;
      padding: 11px 16px;
      border: none;
      border-radius: 10px;
      font-family: 'Syne', sans-serif;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.07em;
      text-transform: uppercase;
      cursor: pointer;
      transition: all 0.16s;
    }
    .cb-modal-btn.primary {
      background: var(--blue);
      color: #fff;
      box-shadow: 0 4px 14px rgba(26,75,140,0.22);
    }
    .cb-modal-btn.primary:hover { background: var(--blue-dark); }
    .cb-modal-btn.primary.sea {
      background: var(--sea);
      box-shadow: 0 4px 14px rgba(42,157,143,0.24);
    }
    .cb-modal-btn.primary.sea:hover { background: var(--sea-dark); }
    .cb-modal-btn.primary.red {
      background: var(--blocked);
      box-shadow: 0 4px 14px rgba(220,38,38,0.24);
    }
    .cb-modal-btn.primary.red:hover { background: #b91c1c; }
    .cb-modal-btn.secondary {
      background: var(--off);
      color: var(--gray);
      border: 1.5px solid var(--border);
    }
    .cb-modal-btn.secondary:hover { background: var(--light); color: var(--black); }
    .cb-modal-accent {
      height: 4px;
      width: 100%;
    }
    .cb-modal-accent.success { background: var(--sea); }
    .cb-modal-accent.error   { background: var(--error); }
    .cb-modal-accent.warn    { background: var(--warn); }
    .cb-modal-accent.blocked { background: var(--blocked); }

    @media (max-width: 620px) {
      .reg-grid { grid-template-columns: 1fr; }
      .col-full { grid-column: 1; }
      #loginPanel { padding: 28px 22px; }
      #registerPanel { padding: 24px 20px; }
    }
  </style>
</head>
<body>

<!-- ══ THEMED MODAL ══ -->
<div class="cb-modal-overlay" id="cbModalOverlay" role="dialog" aria-modal="true" aria-labelledby="cbModalTitle">
  <div class="cb-modal" id="cbModal">
    <div class="cb-modal-accent" id="cbModalAccent"></div>
    <div class="cb-modal-header">
      <div class="cb-modal-icon" id="cbModalIcon"></div>
      <div>
        <div class="cb-modal-subtitle" id="cbModalSubtitle"></div>
        <div class="cb-modal-title" id="cbModalTitle"></div>
      </div>
    </div>
    <div class="cb-modal-body" id="cbModalBody"></div>
    <div class="cb-modal-footer" id="cbModalFooter"></div>
  </div>
</div>

<div class="page-wrapper">

  <div class="brand-header">
    <div class="logo-pill">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
      </div>
      <div class="logo-text">Car<span>Book</span></div>
    </div>
    <div class="brand-sub">Your trusted car marketplace</div>
  </div>

  <div class="card-wrapper" id="cardWrapper">
    <div class="form-card">

      <div class="tab-switcher">
        <button class="tab-btn active" id="tabLogin" onclick="switchTab('login')" type="button">Sign In</button>
        <button class="tab-btn" id="tabRegister" onclick="switchTab('register')" type="button">Create Account</button>
      </div>

      <div class="toast-msg" id="toastMsg"></div>

      <!-- ══ LOGIN ══ -->
      <div class="form-panel active" id="loginPanel">

        <?php
          if(isset($_POST['login'])){
              $loginEmail = $_POST['loginEmail'];
              $loginPassword = $_POST['loginPassword'];

              $q = mysqli_query($con,"SELECT * FROM users_master 
                                      WHERE email='$loginEmail' 
                                      AND pass='$loginPassword'");

              if(mysqli_num_rows($q) > 0){
                  $user = mysqli_fetch_assoc($q);

                  if($user['status'] == 0){
                      // Not yet verified — send to OTP page
                      $modalTitle    = 'Account Pending';
                      $modalMessage  = 'Your account has not been verified yet. Please check your email for the OTP and complete verification.';
                      $modalType     = 'warn';
                      $modalRedirect = "verify.php?email=" . urlencode($loginEmail);

                  } elseif($user['status'] == 1){
                      // Blocked / deactivated — store for user_request page
                      $_SESSION['req_user_name']  = $user['uname'];
                      $_SESSION['req_user_email'] = $user['email'];
                      $_SESSION['req_user_id']    = $user['ui'];

                      $modalType    = 'blocked';
                      $modalTitle   = 'Account Blocked';
                      $modalMessage = 'Your account has been <strong>blocked or deactivated</strong> by the administrator. You can submit an appeal request to get it reviewed and re-activated.';

                  } else {
                      // status == 2 → active
                      $_SESSION['user_id']   = $user['ui'];
                      $_SESSION['username']  = $user['uname'];
                      $_SESSION['useremail'] = $user['email'];

                      $modalTitle    = 'Welcome Back!';
                      $modalMessage  = 'You have signed in successfully. Redirecting you now…';
                      $modalType     = 'success';
                      $modalRedirect = 'index.php';
                  }
              } else {
                  $modalTitle   = 'Login Failed';
                  $modalMessage = 'The email or password you entered is incorrect. Please try again.';
                  $modalType    = 'error';
              }
          }
        ?>

        <form method="post" id="loginForm" novalidate>
          <div class="login-badge">
            <div class="dot"></div>
            <span>Secure Login</span>
            <div class="dot"></div>
          </div>

          <div class="field-group">
            <label class="form-label">Email Address</label>
            <div class="input-wrap">
              <input type="email" class="form-control" placeholder="you@example.com" id="loginEmail" name="loginEmail"/>
              <span class="input-status"></span>
            </div>
            <div class="field-error" id="err-loginEmail">Please enter a valid email address.</div>
          </div>

          <div class="field-group">
            <label class="form-label">Password</label>
            <div class="pass-wrapper">
              <input type="password" class="form-control" placeholder="••••••••" id="loginPassword" name="loginPassword"/>
              <div class="pass-icons">
                <div class="pass-status" id="ps-loginPassword"></div>
                <div class="pass-divider" id="pd-loginPassword" style="display:none"></div>
                <button type="button" class="pass-toggle" onclick="togglePass('loginPassword')" aria-label="Toggle password">
                  <svg id="eye-loginPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>
            <div class="field-error" id="err-loginPassword">Password is required.</div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check m-0">
              <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
              <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <a href="#" class="link-accent" style="font-size:12px;">Forgot password?</a>
          </div>

          <button class="btn-submit" type="submit" name="login" onclick="return handleLoginValidation(event)">Sign In</button>
          <div class="center-text">
            New to AutoDrive? <a href="#" class="link-accent" onclick="switchTab('register')">Create an account</a>
          </div>
        </form>
      </div>

      <!-- ══ REGISTER ══ -->
      <div class="form-panel" id="registerPanel">
        <form method="post" enctype="multipart/form-data" id="registerForm" novalidate>
          <div class="reg-grid">

            <div class="section-label col-full">Personal Information</div>

            <div class="field-group">
              <label class="form-label">Full Name <span style="color:var(--error)">*</span></label>
              <div class="input-wrap">
                <input type="text" class="form-control" placeholder="Enter Your Name" id="regName" name="uname"/>
                <span class="input-status"></span>
              </div>
              <div class="field-error" id="err-regName">Full name is required.</div>
            </div>

            <div class="field-group">
              <label class="form-label">Date of Birth <span style="color:var(--error)">*</span></label>
              <div class="input-wrap">
                <input type="date" class="form-control" id="regDob" name="dob"/>
                <span class="input-status"></span>
              </div>
              <div class="field-error" id="err-regDob">Date of birth is required.</div>
            </div>

            <div class="field-group">
              <label class="form-label">Gender <span style="color:var(--error)">*</span></label>
              <div class="gender-group" id="genderGroup">
                <div class="gender-option">
                  <input type="radio" name="gen" id="gMale" value="Male">
                  <label for="gMale">Male</label>
                </div>
                <div class="gender-option">
                  <input type="radio" name="gen" id="gFemale" value="Female">
                  <label for="gFemale">Female</label>
                </div>
                <div class="gender-option">
                  <input type="radio" name="gen" id="gOther" value="Other">
                  <label for="gOther">Other</label>
                </div>
              </div>
              <div class="field-error" id="err-gender">Please select your gender.</div>
            </div>

            <div class="field-group">
              <label class="form-label">Profile Photo</label>
              <div class="img-upload-area" id="uploadArea">
                <input type="file" accept="image/*" id="regImage" name="userphoto" onchange="showFileName(this)"/>
                <span class="up-icon">📷</span>
                <p>Click to upload your photo</p>
                <div class="file-name" id="fileNameDisplay"></div>
              </div>
            </div>

            <div class="section-label col-full">Contact Details</div>

            <div class="field-group">
              <label class="form-label">Email Address <span style="color:var(--error)">*</span></label>
              <div class="input-wrap">
                <input type="email" class="form-control" placeholder="you@example.com" id="regEmail" name="email"/>
                <span class="input-status"></span>
              </div>
              <div class="field-error" id="err-regEmail">Please enter a valid email address.</div>
            </div>

            <div class="field-group">
              <label class="form-label">Mobile Number <span style="color:var(--error)">*</span></label>
              <div class="input-wrap">
                <input type="tel" class="form-control" placeholder="9876543210" id="regMobile" name="mobno" maxlength="10"/>
                <span class="input-status"></span>
              </div>
              <div class="field-error" id="err-regMobile">Mobile number must be exactly 10 digits.</div>
            </div>

            <div class="field-group col-full">
              <label class="form-label">Address <span style="color:var(--error)">*</span></label>
              <textarea class="form-control" rows="2" placeholder="Street, City, State" id="regAddress" name="address" style="resize:none;"></textarea>
              <div class="field-error" id="err-regAddress">Address is required.</div>
            </div>

            <div class="field-group">
              <label class="form-label">PIN / ZIP Code <span style="color:var(--error)">*</span></label>
              <div class="input-wrap">
                <input type="text" class="form-control" placeholder="395001" maxlength="6" id="regPin" name="pin"/>
                <span class="input-status"></span>
              </div>
              <div class="field-error" id="err-regPin">PIN code must be exactly 6 digits.</div>
            </div>

            <div class="section-label col-full">Security</div>

            <div class="field-group">
              <label class="form-label">Password <span style="color:var(--error)">*</span></label>
              <div class="pass-wrapper">
                <input type="password" class="form-control" placeholder="Min. 8 characters" id="regPassword" name="pass"
                  oninput="checkPasswordStrength(this.value)" onfocus="showPassInstructions()" onblur="hidePassInstructions()"/>
                <div class="pass-icons">
                  <div class="pass-status" id="ps-regPassword"></div>
                  <div class="pass-divider" id="pd-regPassword" style="display:none"></div>
                  <button type="button" class="pass-toggle" onclick="togglePass('regPassword')" aria-label="Toggle password">
                    <svg id="eye-regPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  </button>
                </div>
              </div>
              <div class="pass-strength-bar" id="strengthBar">
                <span id="bar1"></span><span id="bar2"></span><span id="bar3"></span><span id="bar4"></span>
              </div>
              <div class="pass-strength-label" id="strengthLabel"></div>
              <div class="pass-instructions" id="passInstructions">
                <p>Password must include:</p>
                <div class="pass-rule" id="rule-length"><div class="rule-icon">✕</div> At least 8 characters</div>
                <div class="pass-rule" id="rule-upper"><div class="rule-icon">✕</div> At least one uppercase letter (A–Z)</div>
                <div class="pass-rule" id="rule-lower"><div class="rule-icon">✕</div> At least one lowercase letter (a–z)</div>
                <div class="pass-rule" id="rule-number"><div class="rule-icon">✕</div> At least one number (0–9)</div>
                <div class="pass-rule" id="rule-special"><div class="rule-icon">✕</div> At least one special character (!@#$%^&*)</div>
              </div>
              <div class="field-error" id="err-regPassword">Password must be at least 8 characters with uppercase, lowercase, number, and special character.</div>
            </div>

            <div class="field-group">
              <label class="form-label">Confirm Password <span style="color:var(--error)">*</span></label>
              <div class="pass-wrapper">
                <input type="password" class="form-control" placeholder="Repeat password"
                  id="regConfirm" name="confirm_pass"
                  oninput="checkConfirmMatch()"/>
                <div class="pass-icons">
                  <div class="pass-status" id="ps-regConfirm"></div>
                  <div class="pass-divider" id="pd-regConfirm" style="display:none"></div>
                  <button type="button" class="pass-toggle" onclick="togglePass('regConfirm')" aria-label="Toggle password">
                    <svg id="eye-regConfirm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  </button>
                </div>
              </div>
              <div class="field-error" id="err-regConfirm">Passwords do not match.</div>
            </div>

            <div class="col-full mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                <label class="form-check-label" for="agreeTerms">
                  I agree to the <a href="#" class="link-accent">Terms of Service</a> and <a href="#" class="link-accent">Privacy Policy</a>
                </label>
              </div>
              <div class="field-error" id="err-terms">You must agree to the Terms of Service to continue.</div>
            </div>

            <div class="col-full">
              <button class="btn-submit sea" type="submit" name="register" onclick="return handleRegisterValidation(event)">Create Account</button>
            </div>

            <div class="col-full center-text">
              Already registered? <a href="#" class="link-accent" onclick="switchTab('login')">Sign in</a>
            </div>

          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
  /* ═══════════════════════════════════════════
     THEMED MODAL ENGINE
  ═══════════════════════════════════════════ */
  const MODAL_CONFIG = {
    success: { icon: '✓', subtitle: 'Success',   btnClass: 'sea' },
    error:   { icon: '✕', subtitle: 'Error',      btnClass: '' },
    warn:    { icon: '⚠', subtitle: 'Attention', btnClass: '' },
    blocked: { icon: '🚫', subtitle: 'Account Blocked', btnClass: 'red' },
  };

  function showModal(type, title, bodyHtml, redirectUrl) {
    const cfg      = MODAL_CONFIG[type] || MODAL_CONFIG.error;
    const overlay  = document.getElementById('cbModalOverlay');
    const accent   = document.getElementById('cbModalAccent');
    const iconEl   = document.getElementById('cbModalIcon');
    const subtitleEl = document.getElementById('cbModalSubtitle');
    const titleEl  = document.getElementById('cbModalTitle');
    const bodyEl   = document.getElementById('cbModalBody');
    const footerEl = document.getElementById('cbModalFooter');

    accent.className     = 'cb-modal-accent ' + type;
    iconEl.className     = 'cb-modal-icon ' + type;
    iconEl.textContent   = cfg.icon;
    subtitleEl.className = 'cb-modal-subtitle ' + type;
    subtitleEl.textContent = cfg.subtitle;
    titleEl.textContent  = title;
    bodyEl.innerHTML     = bodyHtml;
    footerEl.innerHTML   = '';

    if (type === 'blocked') {
      // Two buttons: OK (dismiss) + Submit Request (redirect)
      const okBtn = document.createElement('button');
      okBtn.className   = 'cb-modal-btn secondary';
      okBtn.textContent = 'OK';
      okBtn.onclick     = closeModal;
      footerEl.appendChild(okBtn);

      const reqBtn = document.createElement('button');
      reqBtn.className   = 'cb-modal-btn primary red';
      reqBtn.textContent = 'Submit Request';
      reqBtn.onclick     = () => { window.location.href = 'user_request.php'; };
      footerEl.appendChild(reqBtn);

    } else if (redirectUrl) {
      const btn = document.createElement('button');
      btn.className   = 'cb-modal-btn primary ' + cfg.btnClass;
      btn.textContent = type === 'success' ? 'Continue' : 'Go to Verification';
      btn.onclick     = () => { window.location.href = redirectUrl; };
      footerEl.appendChild(btn);

    } else {
      const btn = document.createElement('button');
      btn.className   = 'cb-modal-btn primary ' + cfg.btnClass;
      btn.textContent = 'OK, Got It';
      btn.onclick     = closeModal;
      footerEl.appendChild(btn);

      if (type === 'error' || type === 'warn') {
        const sec = document.createElement('button');
        sec.className   = 'cb-modal-btn secondary';
        sec.textContent = 'Dismiss';
        sec.onclick     = closeModal;
        footerEl.insertBefore(sec, btn);
      }
    }

    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    document.getElementById('cbModalOverlay').classList.remove('open');
    document.body.style.overflow = '';
  }

  document.getElementById('cbModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
  });

  /* ── Fire PHP-generated modal on page load ── */
  <?php if($modalType): ?>
  window.addEventListener('DOMContentLoaded', function() {
    <?php if(strpos($modalMessage, 'Register') !== false || isset($_POST['register'])): ?>
    switchTab('register');
    <?php endif; ?>
    showModal(
      '<?= $modalType ?>',
      '<?= addslashes($modalTitle) ?>',
      '<?= addslashes($modalMessage) ?>',
      <?= ($modalRedirect && $modalType !== 'blocked') ? "'".addslashes($modalRedirect)."'" : 'null' ?>
    );
  });
  <?php endif; ?>

  /* ═══════════════════════════════════════════
     TAB SWITCHER
  ═══════════════════════════════════════════ */
  function switchTab(tab) {
    const isReg = tab === 'register';
    document.getElementById('tabLogin').classList.toggle('active', !isReg);
    document.getElementById('tabRegister').classList.toggle('active', isReg);
    document.getElementById('loginPanel').classList.toggle('active', !isReg);
    document.getElementById('registerPanel').classList.toggle('active', isReg);
    document.getElementById('cardWrapper').classList.toggle('wide', isReg);
    document.getElementById('toastMsg').classList.remove('show');
  }

  <?php if(isset($_POST['register'])): ?>
  document.addEventListener('DOMContentLoaded', function() { switchTab('register'); });
  <?php endif; ?>

  function showToast(msg, isError) {
    const t = document.getElementById('toastMsg');
    t.textContent = msg;
    t.style.borderLeftColor = isError ? '#e74c3c' : '#2a9d8f';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 4000);
  }

  function showFileName(input) {
    document.getElementById('fileNameDisplay').textContent =
      input.files[0] ? '✓ ' + input.files[0].name : '';
  }

  function togglePass(fieldId) {
    const field = document.getElementById(fieldId);
    const eyeIcon = document.getElementById('eye-' + fieldId);
    const isHidden = field.type === 'password';
    field.type = isHidden ? 'text' : 'password';
    eyeIcon.innerHTML = isHidden
      ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
         <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
         <line x1="1" y1="1" x2="23" y2="23"></line>`
      : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
         <circle cx="12" cy="12" r="3"></circle>`;
  }

  function showPassInstructions() {
    document.getElementById('passInstructions').classList.add('show');
  }
  function hidePassInstructions() {
    const val = document.getElementById('regPassword').value;
    if (!val) document.getElementById('passInstructions').classList.remove('show');
  }

  function checkPasswordStrength(val) {
    const rules = {
      length:  val.length >= 8,
      upper:   /[A-Z]/.test(val),
      lower:   /[a-z]/.test(val),
      number:  /[0-9]/.test(val),
      special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(val),
    };

    updateRule('rule-length',  rules.length);
    updateRule('rule-upper',   rules.upper);
    updateRule('rule-lower',   rules.lower);
    updateRule('rule-number',  rules.number);
    updateRule('rule-special', rules.special);

    if (val.length > 0) document.getElementById('passInstructions').classList.add('show');
    else document.getElementById('passInstructions').classList.remove('show');

    const score = Object.values(rules).filter(Boolean).length;
    const bars  = ['bar1','bar2','bar3','bar4'];
    const classMap = ['active-weak','active-fair','active-good','active-strong'];
    const labelEl  = document.getElementById('strengthLabel');

    bars.forEach(b => { document.getElementById(b).className = ''; });
    labelEl.className = 'pass-strength-label';
    if (val.length === 0) { labelEl.classList.remove('show'); return; }

    labelEl.classList.add('show');
    let level, label, cls;
    if (score <= 1)                      { level = 1; label = 'Weak';   cls = 'weak'; }
    else if (score === 2)                { level = 2; label = 'Fair';   cls = 'fair'; }
    else if (score === 3 || score === 4) { level = 3; label = 'Good';   cls = 'good'; }
    else                                 { level = 4; label = 'Strong'; cls = 'strong'; }

    for (let i = 0; i < level; i++) document.getElementById(bars[i]).className = classMap[level - 1];
    labelEl.textContent = label;
    labelEl.classList.add(cls);

    const field = document.getElementById('regPassword');
    if (score === 5) {
      field.classList.remove('is-invalid'); field.classList.add('is-valid');
      updatePassStatus(field, 'valid');
    } else if (val.length > 0) {
      field.classList.remove('is-valid'); updatePassStatus(field, 'none');
    } else {
      updatePassStatus(field, 'none');
    }

    checkConfirmMatch();
  }

  function updateRule(ruleId, met) {
    const el = document.getElementById(ruleId);
    const icon = el.querySelector('.rule-icon');
    el.className = 'pass-rule ' + (met ? 'met' : (el.classList.contains('was-checked') ? 'unmet' : ''));
    icon.textContent = met ? '✓' : '✕';
  }

  function checkConfirmMatch() {
    const passVal    = document.getElementById('regPassword').value;
    const confirmVal = document.getElementById('regConfirm').value;
    const field      = document.getElementById('regConfirm');
    const errEl      = document.getElementById('err-regConfirm');

    if (!confirmVal) {
      field.classList.remove('is-valid', 'is-invalid');
      errEl.classList.remove('show');
      updatePassStatus(field, 'none');
      return;
    }

    if (passVal === confirmVal) { setValid(field, errEl); }
    else { setInvalid(field, errEl, 'Passwords do not match.'); }
  }

  function setValid(field, errEl) {
    field.classList.remove('is-invalid'); field.classList.add('is-valid');
    errEl.classList.remove('show');
    updatePassStatus(field, 'valid');
  }
  function setInvalid(field, errEl, msg) {
    field.classList.remove('is-valid'); field.classList.add('is-invalid');
    if (msg) errEl.textContent = msg;
    errEl.classList.add('show');
    updatePassStatus(field, 'invalid');
  }
  function clearState(field, errEl) {
    field.classList.remove('is-valid','is-invalid');
    errEl.classList.remove('show');
    updatePassStatus(field, 'none');
  }

  function updatePassStatus(field, state) {
    const ps = document.getElementById('ps-' + field.id);
    const pd = document.getElementById('pd-' + field.id);
    if (!ps) return;
    ps.className = 'pass-status';
    if (state === 'valid')        { ps.classList.add('show-valid');   if (pd) pd.style.display = 'block'; }
    else if (state === 'invalid') { ps.classList.add('show-invalid'); if (pd) pd.style.display = 'block'; }
    else                          { if (pd) pd.style.display = 'none'; }
  }

  /* ═══════════════════════════════════════════
     LIVE VALIDATION
  ═══════════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', function() {

    function liveValidate(id, errId, validator) {
      const el  = document.getElementById(id);
      const err = document.getElementById(errId);
      if (!el || !err) return;
      el.addEventListener('input', () => { if (el.value) validator(el, err); else clearState(el, err); });
      el.addEventListener('blur',  () => validator(el, err));
    }

    liveValidate('regName', 'err-regName', (el, err) => {
  el.value.trim().length >= 2 ? setValid(el, err) : setInvalid(el, err, 'Full name must be at least 2 characters.');
});
document.getElementById('regName').addEventListener('keypress', function(e) {
  if (!/[a-zA-Z\s]/.test(e.key)) e.preventDefault();
});
document.getElementById('regName').addEventListener('input', function() {
  this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
});

    (function(){
      const dobEl = document.getElementById('regDob');
      const max   = new Date();
      max.setFullYear(max.getFullYear() - 18);
      dobEl.max = max.toISOString().split('T')[0];
    })();

    liveValidate('regDob', 'err-regDob', (el, err) => {
      if(!el.value){ setInvalid(el, err, 'Date of birth is required.'); return; }
      const dob = new Date(el.value);
      const min18 = new Date();
      min18.setFullYear(min18.getFullYear() - 18);
      dob <= min18 ? setValid(el, err) : setInvalid(el, err, 'You must be at least 18 years old.');
    });

    liveValidate('regEmail', 'err-regEmail', (el, err) => {
      /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim()) ? setValid(el, err) : setInvalid(el, err, 'Please enter a valid email address.');
    });

    liveValidate('regMobile', 'err-regMobile', (el, err) => {
      /^[6-9][0-9]{9}$/.test(el.value.trim()) ? setValid(el, err) : setInvalid(el, err, 'Must be 10 digits starting with 6, 7, 8, or 9.');
    });
    document.getElementById('regMobile').addEventListener('keypress', function(e) {
      if (!/[0-9]/.test(e.key)) e.preventDefault();
    });

    liveValidate('regAddress', 'err-regAddress', (el, err) => {
      el.value.trim().length >= 5 ? setValid(el, err) : setInvalid(el, err, 'Please enter a complete address (min 5 characters).');
    });

    liveValidate('regPin', 'err-regPin', (el, err) => {
      /^[0-9]{6}$/.test(el.value.trim()) ? setValid(el, err) : setInvalid(el, err, 'PIN code must be exactly 6 digits.');
    });
    document.getElementById('regPin').addEventListener('keypress', function(e) {
      if (!/[0-9]/.test(e.key)) e.preventDefault();
    });

    liveValidate('regPassword', 'err-regPassword', (el, err) => {
      const v = el.value;
      const strong = v.length >= 8 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /[0-9]/.test(v) && /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v);
      strong ? setValid(el, err) : setInvalid(el, err, 'Password must have 8+ chars, uppercase, lowercase, number & special character.');
    });

    liveValidate('loginEmail', 'err-loginEmail', (el, err) => {
      /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim()) ? setValid(el, err) : setInvalid(el, err, 'Please enter a valid email address.');
    });
    liveValidate('loginPassword', 'err-loginPassword', (el, err) => {
      el.value.length > 0 ? setValid(el, err) : setInvalid(el, err, 'Password is required.');
    });

    document.getElementById('agreeTerms').addEventListener('change', function() {
      document.getElementById('err-terms').classList.toggle('show', !this.checked);
      this.classList.toggle('is-invalid', !this.checked);
    });

    document.querySelectorAll('input[name="gen"]').forEach(radio => {
      radio.addEventListener('change', function() {
        document.getElementById('genderGroup').classList.remove('is-invalid');
        document.getElementById('err-gender').classList.remove('show');
      });
    });
  });

  /* ═══════════════════════════════════════════
     LOGIN FORM VALIDATION
  ═══════════════════════════════════════════ */
  function handleLoginValidation(e) {
    let valid = true;
    const email    = document.getElementById('loginEmail');
    const pass     = document.getElementById('loginPassword');
    const errEmail = document.getElementById('err-loginEmail');
    const errPass  = document.getElementById('err-loginPassword');

    if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
      setInvalid(email, errEmail, 'Please enter a valid email address.'); valid = false;
    } else { setValid(email, errEmail); }

    if (!pass.value) {
      setInvalid(pass, errPass, 'Password is required.'); valid = false;
    } else { setValid(pass, errPass); }

    if (!valid) {
      e.preventDefault();
      showToast('Please fix the errors before signing in.', true);
      return false;
    }
    return true;
  }

  /* ═══════════════════════════════════════════
     REGISTER FORM VALIDATION
  ═══════════════════════════════════════════ */
  function handleRegisterValidation(e) {
    let valid = true;

    function v(id, errId, testFn, msg) {
      const el  = document.getElementById(id);
      const err = document.getElementById(errId);
      if (!testFn(el.value)) { setInvalid(el, err, msg); valid = false; }
      else { setValid(el, err); }
    }

    v('regName', 'err-regName', val => val.trim().length >= 2, 'Full name must be at least 2 characters.');

    (function(){
      const el  = document.getElementById('regDob');
      const err = document.getElementById('err-regDob');
      if(!el.value){
        setInvalid(el, err, 'Date of birth is required.'); valid = false;
      } else {
        const dob   = new Date(el.value);
        const min18 = new Date();
        min18.setFullYear(min18.getFullYear() - 18);
        if(dob > min18){ setInvalid(el, err, 'You must be at least 18 years old.'); valid = false; }
        else { setValid(el, err); }
      }
    })();

    v('regEmail',   'err-regEmail',   val => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim()), 'Please enter a valid email address.');
    v('regMobile',  'err-regMobile',  val => /^[6-9][0-9]{9}$/.test(val.trim()),            'Must be 10 digits starting with 6, 7, 8, or 9.');
    v('regAddress', 'err-regAddress', val => val.trim().length >= 5,                        'Please enter a complete address (min 5 characters).');
    v('regPin',     'err-regPin',     val => /^[0-9]{6}$/.test(val.trim()),                 'PIN code must be exactly 6 digits.');

    const genderErr     = document.getElementById('err-gender');
    const genderGroup   = document.getElementById('genderGroup');
    const genderChecked = document.querySelector('input[name="gen"]:checked');
    if (!genderChecked) {
      genderGroup.classList.add('is-invalid'); genderErr.classList.add('show'); valid = false;
    } else {
      genderGroup.classList.remove('is-invalid'); genderErr.classList.remove('show');
    }

    const passVal  = document.getElementById('regPassword').value;
    const passEl   = document.getElementById('regPassword');
    const passErr  = document.getElementById('err-regPassword');
    const isStrong = passVal.length >= 8
      && /[A-Z]/.test(passVal) && /[a-z]/.test(passVal)
      && /[0-9]/.test(passVal) && /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(passVal);

    if (!isStrong) {
      setInvalid(passEl, passErr, 'Password must be 8+ chars with uppercase, lowercase, number & special character.');
      document.getElementById('passInstructions').classList.add('show');
      ['rule-length','rule-upper','rule-lower','rule-number','rule-special'].forEach(r => {
        document.getElementById(r).classList.add('was-checked');
      });
      checkPasswordStrength(passVal);
      valid = false;
    } else {
      setValid(passEl, passErr);
    }

    const confirmEl  = document.getElementById('regConfirm');
    const confirmErr = document.getElementById('err-regConfirm');
    if (!confirmEl.value) {
      setInvalid(confirmEl, confirmErr, 'Please confirm your password.');
      valid = false;
    } else if (confirmEl.value !== passVal) {
      setInvalid(confirmEl, confirmErr, 'Passwords do not match.');
      valid = false;
    } else {
      setValid(confirmEl, confirmErr);
    }

    const terms    = document.getElementById('agreeTerms');
    const termsErr = document.getElementById('err-terms');
    if (!terms.checked) {
      terms.classList.add('is-invalid'); termsErr.classList.add('show'); valid = false;
    } else {
      terms.classList.remove('is-invalid'); termsErr.classList.remove('show');
    }

    if (!valid) {
      e.preventDefault();
      showToast('Please fix all errors before creating your account.', true);
      const firstErr = document.querySelector('#registerPanel .is-invalid, #registerPanel .gender-group.is-invalid');
      if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return false;
    }
    return true;
  }
</script>
</body>
</html>