<?php
include('connect.php');

if (isset($_GET['id']) && isset($_GET['status'])) {

    $id = (int) $_GET['id'];
    $status = (int) $_GET['status'];

    // Security check: allow only 0 or 1
    if ($status === 0 || $status === 1) {

        // 1. Toggle brand
        $q = "UPDATE brand_master 
              SET is_active = $status 
              WHERE brand_id = $id";

        if (mysqli_query($con, $q)) {

            // 2. Cascade → categories of this brand
            mysqli_query($con, "UPDATE category_master SET is_active = $status WHERE brand_id = $id");

            // 3. Cascade → models of this brand
            mysqli_query($con, "UPDATE model_master SET is_active = $status WHERE brand_id = $id");

            // 4. Cascade → cars of this brand
            mysqli_query($con, "UPDATE car_master SET is_enabled = $status WHERE brand_id = $id");

            header("Location: view_brands.php");
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