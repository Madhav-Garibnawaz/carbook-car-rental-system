<?php
include('connect.php');

if (isset($_GET['id']) && isset($_GET['status'])) {

    $id = (int) $_GET['id'];
    $status = (int) $_GET['status'];

    // Security check: allow only 0 or 1
    if ($status === 0 || $status === 1) {

        // 1. Toggle the category itself
        $q = "UPDATE category_master 
              SET is_active = $status 
              WHERE category_id = $id";

        if (mysqli_query($con, $q)) {

            // 2. Toggle all models belonging to this category
            mysqli_query($con, "UPDATE model_master 
                                SET is_active = $status 
                                WHERE category_id = $id");

            // 3. Get all model IDs that belong to this category
            $model_result = mysqli_query($con, "SELECT model_id FROM model_master WHERE category_id = $id");

            $model_ids = [];
            while ($row = mysqli_fetch_assoc($model_result)) {
                $model_ids[] = $row['model_id'];
            }

            // 4. Cascade → disable/enable all cars belonging to those models
            if (!empty($model_ids)) {
                $model_ids_str = implode(',', $model_ids);
                mysqli_query($con, "UPDATE car_master 
                                    SET is_enabled = $status 
                                    WHERE model_id IN ($model_ids_str)");
            }

            header("Location: view_categories.php");
            exit();

        } else {
            echo "Database Error: " . mysqli_error($con);
        }

    } else {
        echo "Invalid status";
    }

} else {
    echo "Invalid request";
}
?>