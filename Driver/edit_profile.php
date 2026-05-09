<?php
include("header.php");
include("connect.php");

$driver_id = $_GET['id'];
$q = mysqli_query($con, "SELECT * FROM driver_master WHERE driver_id='$driver_id'");
$row = mysqli_fetch_array($q);
if(isset($_POST['btn_update'])) {
    $driver_name = $_POST['driver_name'];
    $driver_email = $_POST['driver_email'];
    $password = $_POST['password'];
    $driver_mobile = $_POST['driver_mobile'];
    $dob = $_POST['dob'];
    // $doj = $_POST['doj'];
    $driver_image = $_FILES['profile_image']['name'];
    $tmp = $_FILES['profile_image']['tmp_name'];
    $dst = "images/driver_profile/" . $driver_image;
    $driver_prof = move_uploaded_file($tmp, $dst);

    $experience_years = $_POST['experience_years'];
    $license_number = $_POST['license_number'];
    $license_expiry_date = $_POST['license_expiry_date'];
    $license_img = $_FILES['license_image']['name'];
    $tmp = $_FILES['license_image']['tmp_name'];
    $dst = "images/driver_licence/".$license_img;
    $licence = move_uploaded_file($tmp, $dst);

    $aadhar_number = $_POST['aadhar_number'];
    $aadhar_img = $_FILES['aadhar_image']['name'];
    $tmp = $_FILES['aadhar_image']['tmp_name'];
    $dst = "images/driver_aadhar/".$aadhar_img;
    $aadhar = move_uploaded_file($tmp, $dst);

    $q = mysqli_query($con, "update driver_master set driver_id='$driver_id', driver_name='$driver_name', driver_email='$driver_email', password='$password', driver_mobile='$driver_mobile', dob='$dob', profile_image='$driver_image', license_number='$license_number', license_image='$license_img', license_expiry_date='$license_expiry_date', experience_years='$experience_years', aadhar_number='$aadhar_number', aadhar_image='$aadhar_img' where driver_id=$driver_id");
    if($q){
        echo "<script>
            alert('Your Profile Has Been Updated!. Please Login Your Account.')
            window.location.href = 'register.php';
        </script>";
    }else {
        echo "Not Updated";
    }
}
?>

<div class="flex-1 overflow-y-auto p-4 md:p-8">

    <!-- Page Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Profile</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm">
            Update your personal and vehicle information
        </p>
    </div>

    <!-- Full Width Form -->
    <form method="POST" enctype="multipart/form-data"
          class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">

        <!-- PROFILE IMAGE -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                Profile Image
            </label>
            <div class="flex items-center gap-6 mt-3">
                <img src="images/driver_profile/<?= $row['profile_image']; ?>"
                     class="w-28 h-28 rounded-full border border-gray-300 dark:border-gray-600 object-cover">
                <input type="file" name="profile_image"
                       class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200
                              dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            </div>
        </div>

        <!-- PERSONAL INFO -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    Full Name
                </label>
                <input type="text" name="driver_name" value="<?= $row['driver_name']; ?>"
                       class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700
                              border border-gray-200 dark:border-gray-600 rounded-lg
                              text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    Email
                </label>
                <input type="email" name="driver_email" value="<?= $row['driver_email']; ?>"
                       class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700
                              border border-gray-200 dark:border-gray-600 rounded-lg
                              text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    Password
                </label>
                <input type="password" name="password" value="<?= $row['password']; ?>"
                       class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700
                              border border-gray-200 dark:border-gray-600 rounded-lg
                              text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    Phone Number
                </label>
                <input type="text" name="driver_mobile" value="<?= $row[4]; ?>"
                       class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700
                              border border-gray-200 dark:border-gray-600 rounded-lg
                              text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    Date of Birth
                </label>
                <input type="date" name="dob" value="<?= $row['dob']; ?>"
                       class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700
                              border border-gray-200 dark:border-gray-600 rounded-lg
                              text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    Aadhar Number
                </label>
                <input type="text" name="aadhar_number" value="<?= $row['aadhar_number']; ?>"
                       class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700
                              border border-gray-200 dark:border-gray-600 rounded-lg
                              text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
        </div>

        <!-- AADHAR IMAGE -->
        <div class="mt-6">
            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                Aadhar Image
            </label>
            <div class="flex items-center gap-6 mt-3">
                <img src="images/driver_aadhar/<?= $row['aadhar_image']; ?>"
                     class="w-40 rounded-lg border border-gray-300 dark:border-gray-600">
                <input type="file" name="aadhar_image"
                       class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200
                              dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            </div>
        </div>

        <!-- VEHICLE INFO -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">

            <div>
                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    Experience
                </label>
                <input type="text" name="experience_years" value="<?= $row[11]; ?>"
                       class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700
                              border border-gray-200 dark:border-gray-600 rounded-lg
                              text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    License Number
                </label>
                <input type="text" name="license_number" value="<?= $row['license_number']; ?>"
                       class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700
                              border border-gray-200 dark:border-gray-600 rounded-lg
                              text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    License Expiry Date
                </label>
                <input type="date" name="license_expiry_date" value="<?= $row[10]; ?>"
                       class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700
                              border border-gray-200 dark:border-gray-600 rounded-lg
                              text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
            </div>
        </div>

        <!-- LICENSE IMAGE -->
        <div class="mt-6">
            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                License Image
            </label>
            <div class="flex items-center gap-6 mt-3">
                <img src="images/driver_licence/<?= $row['license_image']; ?>"
                     class="w-40 rounded-lg border border-gray-300 dark:border-gray-600">
                <input type="file" name="license_image"
                       class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200
                              dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex justify-end gap-4 mt-8">
            <a href="profile.php"
               class="px-6 py-2 border border-gray-400 text-gray-600 dark:text-gray-300
                      rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                Cancel
            </a>
            <button type="submit" name="btn_update"
                    class="px-8 py-2 bg-primary text-white rounded-lg font-semibold
                           hover:opacity-90 transition">
                Save Changes
            </button>
        </div>

    </form>
</div>