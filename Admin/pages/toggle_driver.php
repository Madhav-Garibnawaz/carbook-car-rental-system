<?php
include('connect.php');

if (isset($_GET['id']) && isset($_GET['status'])) {

    $id = intval($_GET['id']);
    $status = intval($_GET['status']);

    $update = mysqli_query($con, "UPDATE driver_master SET status='$status' WHERE driver_id='$id'");

    if ($update) {
        header("Location: driver_master.php");
        exit();
    } else {
        echo "Failed to update status.";
    }
}
?>
