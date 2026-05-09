<?php 
    include('connect.php');
    session_name('admin_session');
    session_start();
    include('../components/navbar.php'); 
?>
    <title>Insert Brands</title>

    <style>

        html, body {
            height: 100%;
            overflow: hidden;
        }

        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0b5ed7;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --border-color: #dee2e6;
            --focus-color: rgba(13, 110, 253, 0.25);
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .content {
                min-height: calc(100vh - 70px); /* adjust if navbar height differs */
                display: flex;
                align-items: center;
                justify-content: center; /* horizontal center */
        }

        .main-container {
                width: 100%;
        }

        .form-card {
            max-width: 600px;
            margin: auto;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .card-body {
            padding: 2.5rem;
        }

        .form-footer {
            background: #f8f9fa;
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }
        
    </style>
</head>

<body>

<!-- ===== MAIN PANEL CONTENT ===== -->
<div class="main-panel1">
    <div class="content">
        <div class="main-container">

<?php
if (isset($_POST['insbrand'])) {

    // ✅ Trim + sanitize brand name
    $brand_name = trim($_POST['pname']);
    $brand_name = htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8');

    // ✅ File info
    $brand_logo = $_FILES['txtpic']['name'];
    $tmp        = $_FILES['txtpic']['tmp_name'];
    $size       = $_FILES['txtpic']['size'];
    $error      = $_FILES['txtpic']['error'];

    // ✅ Allowed image types
    $allowed_ext  = ['jpg','jpeg','png','webp'];
    $file_ext     = strtolower(pathinfo($brand_logo, PATHINFO_EXTENSION));

    // ✅ Safe file name (prevent special chars)
    $brand_logo = preg_replace("/[^a-zA-Z0-9._-]/", "", $brand_logo);

    $dst = "./images/brand_images/" . $brand_logo;

    // ================= VALIDATION =================

    if (empty($brand_name) || empty($brand_logo)) {
        echo "<div class='alert alert-warning'>All fields are required</div>";
        exit;
    }

    // Only letters + spaces check (server-side)
    if (!preg_match("/^[A-Za-z\s]+$/", $brand_name)) {
        echo "<div class='alert alert-danger'>Invalid brand name format</div>";
        exit;
    }

    // Upload error check
    if ($error !== 0) {
        echo "<div class='alert alert-danger'>File upload error</div>";
        exit;
    }

    // Extension validation
    if (!in_array($file_ext, $allowed_ext)) {
        echo "<div class='alert alert-danger'>Only JPG, PNG, WEBP allowed</div>";
        exit;
    }

    // File size limit (2MB)
    if ($size > 2 * 1024 * 1024) {
        echo "<div class='alert alert-danger'>Image must be under 2MB</div>";
        exit;
    }

    // Check real image MIME
    $check = getimagesize($tmp);
    if ($check === false) {
        echo "<div class='alert alert-danger'>Invalid image file</div>";
        exit;
    }

    // ================= DATABASE CHECK =================

    $result = mysqli_query($con,"SELECT * FROM brand_master WHERE brand_name='$brand_name'");
    if(mysqli_num_rows($result) > 0) {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Already Exists!',
                text: 'This brand name already exists!..',
                confirmButtonColor: '#d33'
            });
        </script>";
    } 
    else {

        if (move_uploaded_file($tmp, $dst)) {

            $q = "insert into brand_master values ('','$brand_name','$brand_logo',1)";

            if (mysqli_query($con, $q)) {
                echo "<div class='alert alert-success alert-dismissible fade show w-75 mx-auto text-center' role='alert'>
                        Your Brand Added Successfully!.. <a href='view_brands.php'>View</a>
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
            } else {
                echo "<div class='alert alert-danger'>Database error</div>";
            }

        } else {
            echo "<div class='alert alert-warning'>Image upload failed</div>";
        }
    }
}
?>

            <div class="card form-card">
                <div class="card-body">
                    <h4><i class="bi bi-plus-circle"></i> Insert New Brand</h4>
                    <small>Add a new brand to your system</small>
                </div>

                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Brand Name *</label>
                            <input type="text"
                                   name="pname"
                                   class="form-control"
                                   pattern="[A-Za-z\s]+"
                                   title="Only alphabets allowed"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Brand Image *</label>
                            <input type="file"
                                   name="txtpic"
                                   class="form-control"
                                   accept="image/*"
                                   required>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit"
                                    name="insbrand"
                                    class="btn btn-primary px-4">
                                <i class="bi bi-check-circle"></i> Save Brand
                            </button>
                            <button type="reset"
                                    class="btn btn-secondary px-4">
                                Reset
                            </button>
                        </div>

                    </form>
                </div>

                <div class="form-footer">
                    All fields marked * are mandatory
                </div>
            </div>

        </div>
    </div>
</div>
<?php include('../components/footer.php'); ?>