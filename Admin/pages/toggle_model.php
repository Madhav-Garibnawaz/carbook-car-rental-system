<?php
include('connect.php');

if (isset($_GET['id']) && isset($_GET['status'])) {

    $id = (int) $_GET['id'];
    $status = (int) $_GET['status'];

    // Security check: allow only 0 or 1
    if ($status === 0 || $status === 1) {

        // 1. Toggle model
        $q = "UPDATE model_master 
              SET is_active = $status 
              WHERE model_id = $id";

        if (mysqli_query($con, $q)) {

            // 2. Cascade → cars of this model
            mysqli_query($con, "UPDATE car_master SET is_enabled = $status WHERE model_id = $id");

            header("Location: view_models.php");
            exit();
        } else {
            echo "Database Error";
        }

    } else {
        echo "Invalid status";
    }

} else {
    echo "Invalid request";
}
?>