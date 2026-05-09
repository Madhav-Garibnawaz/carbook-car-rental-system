<?php 
    include("header.php"); 
    include("connect.php");
?>
    <!-- Main Content -->
        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8">
        <?php  
        if(isset($_SESSION['driver_mail']) && isset($_SESSION['password'])){
            $driver_mail = $_SESSION['driver_mail'];
            $password = $_SESSION['password'];
            $q = mysqli_query($con, "select * from driver_master where driver_email='$driver_mail' and password='$password'");
            $row = mysqli_fetch_array($q);
            }
        ?>
            <!-- Profile Header Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-32"></div>
                <div class="px-6 pb-6">
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between -mt-16">
                        <div class="flex flex-col md:flex-row md:items-end">
                            <img src="images/driver_profile/<?= $row['profile_image']; ?>" class="w-32 h-32 rounded-full border-4 border-white dark:border-gray-800 shadow-lg mb-4 md:mb-0">
                            <div class="md:ml-6">
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $row[1]; ?></h2>
                                <p class="text-gray-500 dark:text-gray-400">Professional Driver • Join At : <?php echo $row[6]; ?></p>
                                <div class="flex items-center mt-2 space-x-4">
                                    <!-- <div class="flex items-center">
                                        <span class="text-yellow-500 text-lg mr-1">★</span>
                                        <span class="font-bold text-gray-900 dark:text-white">4.8</span>
                                        <span class="text-gray-500 text-sm ml-1">(127 rides)</span>
                                    </div> -->
                                    <?php
                                        $status = $row['status'];

                                        if ($status == 0) {
                                            echo '<span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-full">
                                                    <i class="fas fa-circle text-[8px] mr-1"></i>Request
                                                </span>';
                                        }
                                        elseif ($status == 1) {
                                            echo '<span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">
                                                    <i class="fas fa-circle text-[8px] mr-1"></i>Approved
                                                </span>';
                                        }
                                        elseif ($status == 2) {
                                            echo '<span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full">
                                                    <i class="fas fa-circle text-[8px] mr-1"></i>Rejected
                                                </span>';
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <a href="edit_profile.php?id=<?= $row['driver_id']; ?>" class="mt-4 md:mt-0 px-6 py-2 border border-primary text-primary hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg font-semibold transition">
                            <i class="fas fa-edit mr-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <!-- <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">127</div>
                    <div class="text-sm text-gray-500">Total Rides</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">4.8</div>
                    <div class="text-sm text-gray-500">Rating</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">98%</div>
                    <div class="text-sm text-gray-500">Acceptance</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">2.1K</div>
                    <div class="text-sm text-gray-500">Total Earnings</div>
                </div>
            </div> -->

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Personal Information -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="font-bold text-lg dark:text-white">Personal Information</h3>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4">

                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                Full Name
                            </label>
                            <input type="text" value="<?= $row[1]; ?>" 
                                class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 
                                        border border-gray-200 dark:border-gray-600 rounded-lg 
                                        text-gray-900 dark:text-white focus:outline-none 
                                        focus:ring-2 focus:ring-primary" readonly>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                Email
                            </label>
                            <input type="email" value="<?= $row[2]; ?>" 
                                class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 
                                        border border-gray-200 dark:border-gray-600 rounded-lg 
                                        text-gray-900 dark:text-white focus:outline-none 
                                        focus:ring-2 focus:ring-primary" readonly>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                Phone Number
                            </label>
                            <input type="tel" value="+91 <?= $row[4]; ?>" 
                                class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 
                                        border border-gray-200 dark:border-gray-600 rounded-lg 
                                        text-gray-900 dark:text-white focus:outline-none 
                                        focus:ring-2 focus:ring-primary" readonly>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                Date of Birth
                            </label>
                            <input type="date" value="<?= $row[5]; ?>" 
                                class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 
                                        border border-gray-200 dark:border-gray-600 rounded-lg 
                                        text-gray-900 dark:text-white focus:outline-none 
                                        focus:ring-2 focus:ring-primary" readonly>
                        </div>

                        <!-- Aadhar Number -->
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                Aadhar Number
                            </label>
                            <input type="text" value="<?= $row['aadhar_number']; ?>" 
                                class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 
                                        border border-gray-200 dark:border-gray-600 rounded-lg 
                                        text-gray-900 dark:text-white focus:outline-none 
                                        focus:ring-2 focus:ring-primary" readonly>
                        </div>

                        <!-- Aadhar Image -->
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                Your Aadhar
                            </label>

                            <div class="mt-3">
                                <img src="images/driver_aadhar/<?= $row['aadhar_image']; ?>" 
                                    alt="Aadhar Image"
                                    class="w-48 rounded-lg border border-gray-200 dark:border-gray-600">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Vehicle Information -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-lg dark:text-white">Vehicle Information</h3>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">experience</label>
                            <input type="text" value="<?= $row['11']; ?>" class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">license number</label>
                            <input type="text" value="<?= $row['8']; ?>" class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">license expiry date</label>
                            <input type="text" value="<?= $row['10']; ?>" class="w-full mt-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                Your License
                            </label>

                            <div class="mt-3">
                                <img src="images/driver_licence/<?= $row['license_image']; ?>" 
                                    alt="Aadhar Image"
                                    class="w-48 rounded-lg border border-gray-200 dark:border-gray-600">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Documents Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-lg dark:text-white">Documents & Verification</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-id-card text-success text-2xl mr-4"></i>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">Driver's License</p>
                                <p class="text-xs text-gray-500">Expires: <?= $row['10']; ?></p>
                            </div>
                        </div>
                        <span class="text-success font-bold"><i class="fas fa-check-circle"></i></span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-file-alt text-success text-2xl mr-4"></i>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">Vehicle Registration</p>
                                <p class="text-xs text-gray-500">Expires: Jun 2025</p>
                            </div>
                        </div>
                        <span class="text-success font-bold"><i class="fas fa-check-circle"></i></span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt text-success text-2xl mr-4"></i>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">Insurance Certificate</p>
                                <p class="text-xs text-gray-500">Expires: Mar 2026</p>
                            </div>
                        </div>
                        <span class="text-success font-bold"><i class="fas fa-check-circle"></i></span>
                    </div>

                    <?php
    if ($status == 1) {  // Approved / Active
?>
    <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-clipboard-check text-success text-2xl mr-4"></i>
            <div>
                <p class="font-semibold text-gray-900 dark:text-white">Background Check</p>
                <p class="text-xs text-gray-500">Approved</p>
            </div>
        </div>
        <span class="text-success font-bold">
            <i class="fas fa-check-circle"></i>
        </span>
    </div>
<?php
    } else {   // Pending or Request
?>
    <div class="flex items-center justify-between p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-clipboard-check text-yellow-600 dark:text-yellow-400 text-2xl mr-4"></i>
            <div>
                <p class="font-semibold text-gray-900 dark:text-white">Background Check</p>
                <p class="text-xs text-gray-500">Pending Review</p>
            </div>
        </div>
        <span class="text-yellow-600 dark:text-yellow-400 font-bold">
            <i class="fas fa-clock"></i>
        </span>
    </div>
<?php
    }
?>

                </div>
            </div>


            <!-- Danger Zone -->
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-6 mt-6">
                <h3 class="font-bold text-lg text-red-700 dark:text-red-400 mb-4">Danger Zone</h3>
                <div class="flex flex-col md:flex-row gap-4">
                    <a href="logout.php" class="px-6 py-2 border border-red-500 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg font-semibold transition">
                        Log Out
                    </a>
                    <a href="delete_profile.php?x=<?= $row['driver_id']; ?>" class="px-6 py-2 bg-red-600 text-white hover:bg-red-700 rounded-lg font-semibold transition">
                        Delete Account
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
