<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        body {
            background: #f5f5f5;
            min-height: 100vh;
            padding: 40px 0;
        }

        .driver-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card-header {
            background: #4da6ff;
            color: white;
            padding: 25px;
            border-bottom: 3px solid #3399ff;
        }

        .card-header h2 {
            margin: 0;
            font-size: 24px;
        }

        .content-wrapper {
            display: flex;
            flex-wrap: wrap;
        }

        .left-section {
            flex: 0 0 300px;
            background: #e6f2ff;
            padding: 30px;
            border-right: 1px solid #cce5ff;
        }

        .profile-image {
            width: 180px;
            height: 180px;
            border-radius: 10px;
            object-fit: cover;
            border: 3px solid #4da6ff;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .driver-name {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .driver-email {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .quick-info {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #4da6ff;
        }

        .quick-info-item {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .quick-info-item:last-child {
            margin-bottom: 0;
        }

        .quick-info-label {
            font-weight: 600;
            color: #4da6ff;
            display: block;
        }

        .quick-info-value {
            color: #333;
        }

        .right-section {
            flex: 1;
            padding: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-card {
            background: #f8fbff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #cce5ff;
        }

        .info-card-title {
            font-weight: 600;
            color: #4da6ff;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .info-card-title i {
            margin-right: 8px;
        }

        .info-card-value {
            color: #333;
            font-size: 16px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #4da6ff;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #4da6ff;
        }

        .section-title:first-child {
            margin-top: 0;
        }

        .document-preview {
            background: #f8fbff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #cce5ff;
            margin-bottom: 20px;
        }

        .document-label {
            font-weight: 600;
            color: #4da6ff;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .document-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s;
        }

        .document-image:hover {
            transform: scale(1.02);
        }

        .status-control {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #4da6ff;
        }

        .status-display {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
        }

        .status-requested {
            color: #ffc107;
        }

        .status-approved {
            color: #28a745;
        }

        .status-rejected {
            color: #dc3545;
        }

        .status-buttons {
            display: flex;
            gap: 10px;
            flex-direction: column;
        }

        .btn-status {
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-status:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-requested {
            background: #ffc107;
        }

        .btn-requested:hover:not(:disabled) {
            background: #e0a800;
        }

        .btn-approved {
            background: #28a745;
        }

        .btn-approved:hover:not(:disabled) {
            background: #218838;
        }

        .btn-rejected {
            background: #dc3545;
        }

        .btn-rejected:hover:not(:disabled) {
            background: #c82333;
        }

        .action-buttons {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e6f2ff;
            text-align: center;
        }

        .btn-primary {
            background: #4da6ff;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: #3399ff;
        }

        .btn-outline-secondary {
            border: 2px solid #6c757d;
            padding: 10px 30px;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                flex-direction: column;
            }

            .left-section {
                flex: 1;
                border-right: none;
                border-bottom: 1px solid #cce5ff;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php
        include('connect.php');
        $x = $_GET['id'];
        $qry = mysqli_query($con,"select * from driver_master where driver_id=$x");
        $row = mysqli_fetch_array($qry);
    ?>
    <div class="container">
        <div class="driver-card">
            <!-- Header -->
            <div class="card-header">
                <h2><i class="fas fa-id-card me-2"></i>Driver Details</h2>
            </div>

            <div class="content-wrapper">
                <!-- Left Section - Profile -->
                <div class="left-section">
                    <img src="../../Driver/images/driver_profile/<?= $row['profile_image']; ?>" alt="Driver Profile" class="profile-image" id="profileImage">
                    
                    <div class="driver-name" id="driverName"><?= $row['driver_name']; ?></div>
                    <div class="driver-email" id="driverEmail"><?= $row['driver_email']; ?></div>

                    <div class="quick-info">
                        <div class="quick-info-item">
                            <span class="quick-info-label">Mobile</span>
                            <span class="quick-info-value" id="driverMobile">+91 <?= $row['driver_mobile']; ?></span>
                        </div>
                        <div class="quick-info-item">
                            <span class="quick-info-label">Experience</span>
                            <span class="quick-info-value" id="experienceYears"><?= $row['experience_years']; ?></span>
                        </div>
                    </div>

                    <!-- Status Control -->
                    <div class="status-control mt-4">
                        <div class="status-display">
                            Current Status: <br><span>
                                <?php
                                        $status = $row[14];
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
                            </span>
                        </div>
                        <div class="status-buttons">
                            <!-- <button class="btn-status btn-requested" id="btnRequested" onclick="changeStatus('Requested')">
                                <i class="fas fa-clock me-2"></i>Requested
                            </button> -->
                            <button class="btn-status btn-approved" id="btnApproved" onclick="changeStatus('Approved')">
                                <i class="fas fa-check-circle me-2"></i>Approved
                            </button>
                            <button class="btn-status btn-rejected" id="btnRejected" onclick="changeStatus('Rejected')">
                                <i class="fas fa-times-circle me-2"></i>Rejected
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Section - Details -->
                <div class="right-section">
                    <!-- Personal Information -->
                    <h5 class="section-title">Personal Information</h5>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-calendar"></i>
                                Date of Birth
                            </div>
                            <div class="info-card-value" id="driverDob"><?= $row['dob']; ?></div>
                        </div>

                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-calendar-check"></i>
                                Date of Joining
                            </div>
                            <div class="info-card-value" id="driverDoj"><?= $row['doj']; ?></div>
                        </div>

                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-lock"></i>
                                Password
                            </div>
                            <div class="info-card-value"><?= $row['password']; ?></div>
                        </div>
                    </div>

                    <!-- License Information -->
                    <h5 class="section-title">License Information</h5>

                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-id-card-alt"></i>
                                License Number
                            </div>
                            <div class="info-card-value" id="licenseNumber"><?= $row['license_number']; ?></div>
                        </div>

                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-calendar-times"></i>
                                License Expiry
                            </div>
                            <div class="info-card-value" id="licenseExpiry"><?= $row['license_expiry_date']; ?></div>
                        </div>
                    </div>

                    <div class="document-preview">
                        <div class="document-label">
                            <i class="fas fa-image me-2"></i>License Document
                        </div>
                        <img src="../../Driver/images/driver_licence/<?= $row['license_image']; ?>" alt="License" class="document-image" id="licenseImage">
                    </div>

                    <!-- Aadhar Information -->
                    <h5 class="section-title">Aadhar Information</h5>

                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="fas fa-fingerprint"></i>
                                Aadhar Number
                            </div>
                            <div class="info-card-value" id="aadharNumber"><?= $row['aadhar_number']; ?></div>
                        </div>
                    </div>

                    <div class="document-preview">
                        <div class="document-label">
                            <i class="fas fa-image me-2"></i>Aadhar Document
                        </div>
                        <img src="../../Driver/images/driver_aadhar/<?= $row['aadhar_image']; ?>" alt="Aadhar" class="document-image" id="aadharImage">
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="driver_master.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>