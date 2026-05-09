<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriverConnect - Dashboard</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">

    <!-- Leaflet Map CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Routing Machine -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css"/>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: '#3B82F6',
                        success: '#10B981',
                        danger: '#EF4444',
                        dark: '#1F2937',
                        surface: '#F3F4F6'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-surface text-gray-800 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-200 h-screen flex flex-col md:flex-row overflow-hidden">

    <!-- Sidebar Navigation -->
    <nav class="md:w-20 lg:w-64 bg-white dark:bg-gray-800 shadow-lg z-50 flex flex-row md:flex-col justify-around md:justify-start py-2 md:py-6 shrink-0 order-2 md:order-1 border-t md:border-t-0 md:border-r border-gray-200 dark:border-gray-700">
        <div class="hidden md:flex items-center justify-center mb-8 px-4">
            <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-white font-bold text-xl">
                <i class="fas fa-car"></i>
            </div>
            <span class="ml-3 font-bold text-xl hidden lg:block">DriverConnect</span>
        </div>

            <a href="index.php"
   class="nav-item flex flex-col md:flex-row items-center md:px-6 py-3 <?= isActive('index.php'); ?>">
            <i class="fas fa-home text-xl md:w-8"></i>
            <span class="text-xs md:text-base mt-1 md:mt-0 md:ml-3 lg:inline hidden">Home</span>
            </a>
        <a href="rides.php"
   class="nav-item flex flex-col md:flex-row items-center md:px-6 py-3 <?= isActive('rides.php'); ?>">
            <i class="fas fa-car text-xl md:w-8"></i>
            <span class="text-xs md:text-base mt-1 md:mt-0 md:ml-3 lg:inline hidden">My Rides</span>
        </a>
        <a href="profile.php"
   class="nav-item flex flex-col md:flex-row items-center md:px-6 py-3 <?= isActive('profile.php'); ?>">
            <i class="fas fa-user text-xl md:w-8"></i>
            <span class="text-xs md:text-base mt-1 md:mt-0 md:ml-3 lg:inline hidden">Profile</span>
        </a>
    </nav>
    <main class="flex-1 flex flex-col h-full overflow-hidden order-1 md:order-2">
        <?php  
        include("connect.php");

        // ── Driver side: always use 'driver_session' so it stays isolated ────────
        if (session_status() === PHP_SESSION_NONE) {
            session_name('driver_session');
            session_start();
        }

        $current_page = basename($_SERVER['PHP_SELF']);

        function isActive($page) {
            global $current_page;
            return $current_page === $page
                ? 'text-primary bg-blue-50 dark:bg-gray-700'
                : 'text-gray-500 hover:text-primary hover:bg-blue-50 dark:hover:bg-gray-700';
        }

        if(isset($_SESSION['driver_id'])){
            $driver_id = (int)$_SESSION['driver_id'];
            $q = mysqli_query($con, "SELECT * FROM driver_master WHERE driver_id='$driver_id'");
            $row = mysqli_fetch_array($q);
        } else {
            header("Location: register.php");
            exit();
        }
        ?>
        <!-- Top Header -->
        <header class="h-16 bg-white dark:bg-gray-800 shadow-sm flex items-center justify-between px-4 md:px-6 shrink-0 z-40">
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">My Profile</h1>

            <div class="flex items-center space-x-4">
                <button id="darkModeToggle" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:inline"></i>
                </button>
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-sm font-bold text-gray-800 dark:text-white"><?php echo $row[1]; ?></span>
                    <span class="text-xs text-gray-500"><?php echo $row[8]; ?></span>
                </div>
                <img src="images/driver_profile/<?= $row['profile_image']; ?>" class="w-10 h-10 rounded-full border-2 border-primary">
            </div>
        </header>