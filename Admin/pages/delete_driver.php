<?php
include('connect.php');

if (!isset($_GET['id'])) {
    header("Location: driver_master.php");
    exit();
}

$id = (int)$_GET['id'];

/* ---------- CHECK IF DRIVER USED ---------- */
$check = mysqli_query($con,
    "SELECT 1 FROM booking_master WHERE driver_id = $id LIMIT 1"
);

if (mysqli_num_rows($check) > 0) {
    echo "<script>
        alert('❌ Driver already assigned to bookings. Cannot delete.');
        window.location='driver_master.php';
    </script>";
    exit();
}

/* ---------- GET IMAGES ---------- */
$get = mysqli_query($con,
    "SELECT profile_image, license_image, aadhar_image
     FROM driver_master
     WHERE driver_id = $id"
);

$data = mysqli_fetch_assoc($get);

if ($data) {

    @unlink("../../Driver/images/driver_profile/".$data['profile_image']);
    @unlink("../../Driver/images/driver_licence/".$data['license_image']);
    @unlink("../../Driver/images/driver_aadhar/".$data['aadhar_image']);

    /* ---------- DELETE DRIVER ---------- */
    mysqli_query($con,"DELETE FROM driver_master WHERE driver_id=$id");

    echo "<script>
        alert('✅ Driver deleted successfully');
        window.location='driver_master.php';
    </script>";
}
?>