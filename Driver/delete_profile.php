<?php

include('connect.php');
$id = $_GET['x'];
if(isset($_POST['delete_account'])){
    $email = $_POST['driver_email'];
    $password = $_POST['password'];
    $q = mysqli_query($con, "DELETE FROM `driver_master` WHERE driver_id = $id AND password=$password");
    if($q) {
        echo "<script>";
        echo "alert('Your Account Has Been Deleted!.');";
        echo "window.location.href='register.php'";
        echo "</script>";   
    }
    else {
        echo "Not Deleted...";
    } 

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Driver Account</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #e3f2fd, #f8fbff);
            min-height: 100vh;
        }

        .delete-card {
            max-width: 420px;
            margin: 90px auto;
            border-radius: 12px;
        }

        .card-header {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border-radius: 12px 12px 0 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0b5ed7, #084298);
        }

        .alert-info {
            background-color: #e7f1ff;
            border-color: #cfe2ff;
            color: #084298;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card delete-card shadow">
        <div class="card-header text-white text-center py-3">
            <h5 class="mb-0">Delete Driver Account</h5>
        </div>

        <div class="card-body">

             <div class="alert alert-warning small">
                ⚠️ This action is <strong>permanent</strong>. Your account data will be deleted and cannot be recovered.
            </div>
            

            <form method="POST">
                <?php
                    $qry = mysqli_query($con,"select * from driver_master where driver_id=$id");
                    $row = mysqli_fetch_array($qry);
                ?>
                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-primary">
                        Email Address
                    </label>
                    <input type="email" 
                           name="driver_email" 
                           class="form-control"
                           placeholder="Enter your email"
                           value="<?php echo $row[2]; ?>"
                           readonly
                           required>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-primary">
                        Password
                    </label>
                    <input type="password" 
                           name="password" 
                           class="form-control"
                           placeholder="Enter your password"
                           required>
                </div>

                <!-- Forgot Password -->
                <div class="mb-3 text-end">
                    <a href="forgot_password.php" class="text-decoration-none text-primary small">
                        Forgot Password?
                    </a>
                </div>

                <!-- Buttons -->
                <div class="d-grid gap-2">
                    <button type="submit" 
                            name="delete_account"
                            class="btn btn-primary">
                        Delete My Account
                    </button>

                    <a href="profile.php" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
