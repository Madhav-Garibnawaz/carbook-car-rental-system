<?php 
    include('connect.php');
    session_name('admin_session');
    session_start();
    include('../components/navbar.php'); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Categories</title>

    <style>
        /* ---- YOUR EXISTING CSS (UNCHANGED) ---- */

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
        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

    <script>
        function getCategory(brand_id, selected_category)
        {
            var xhr = new XMLHttpRequest();
            xhr.open("GET","getCategory.php?brand_id=" + brand_id, true);
            xhr.onload = function(){
                document.getElementById("ddlcategory").innerHTML = this.responseText;
                // FIX 1: re-select the previously chosen category after options load
                if (selected_category) {
                    document.getElementById("ddlcategory").value = selected_category;
                }
            };
            xhr.send();
        }
    </script>
</head>

<body>

<!-- ===== MAIN PANEL CONTENT ===== -->
<div class="main-panel1">
    <div class="content mt-3">
        <div class="container mt-5">

            <?php
                $brandErr = $categoryErr = $modelErr = $descErr = $imgErr = "";
                $model_name = $model_desc = "";
                // FIX 1: remember selected brand & category across POST
                $sel_brand    = "";
                $sel_category = "";

                if (isset($_POST['insbrand'])) {

                    $brand_id    = trim($_POST['ddlbrand']);
                    $category_id = trim($_POST['ddlcategory']);
                    $model_name  = preg_replace('/\s+/', ' ', trim($_POST['model_name']));
                    $model_desc  = trim($_POST['model_desc']);

                    // FIX 1: keep the selected values so JS can restore them
                    $sel_brand    = $brand_id;
                    $sel_category = $category_id;

                    $valid = true;

                    /* ── BRAND ── */
                    if (empty($brand_id)) {
                        $brandErr = "Please select a brand.";
                        $valid = false;
                    }

                    /* ── CATEGORY ── */
                    if (empty($category_id)) {
                        $categoryErr = "Please select a category.";
                        $valid = false;
                    }

                    /* ── MODEL NAME ── */
                    if (empty($model_name)) {
                        $modelErr = "Model name is required.";
                        $valid = false;
                    } elseif (!preg_match("/^[a-zA-Z0-9\- ]+$/", $model_name)) {
                        $modelErr = "Only letters, numbers, spaces and '-' allowed.";
                        $valid = false;
                    } elseif (!preg_match("/[a-zA-Z]/", $model_name)) {
                        $modelErr = "Model name cannot be numbers only.";
                        $valid = false;
                    } elseif (strlen($model_name) < 3 || strlen($model_name) > 50) {
                        $modelErr = "Model name must be 3–50 characters.";
                        $valid = false;
                    }

                    /* ── DESCRIPTION ── */
                    if (empty($model_desc)) {
                        $descErr = "Description is required.";
                        $valid = false;
                    } elseif (!preg_match("/^[a-zA-Z0-9\- ]+$/", $model_desc)) {
                        $descErr = "Only letters, numbers, spaces and '-' allowed.";
                        $valid = false;
                    } elseif (strlen($model_desc) < 5 || strlen($model_desc) > 500) {
                        $descErr = "Description must be 5–500 characters.";
                        $valid = false;
                    }

                    /* ── IMAGE ── */
                    if ($_FILES['mdl_image']['error'] === UPLOAD_ERR_NO_FILE) {
                        $imgErr = "Please upload an image.";
                        $valid = false;
                    } elseif ($_FILES['mdl_image']['error'] !== UPLOAD_ERR_OK) {
                        $imgErr = "Image upload error. Please try again.";
                        $valid = false;
                    } else {
                        $file_name = $_FILES['mdl_image']['name'];
                        $file_size = $_FILES['mdl_image']['size'];
                        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed   = ['jpg', 'jpeg', 'png', 'webp'];

                        if (!in_array($ext, $allowed)) {
                            $imgErr = "Only JPG, PNG, WEBP files allowed.";
                            $valid = false;
                        } elseif ($file_size > 2 * 1024 * 1024) {
                            $imgErr = "Image must be under 2MB.";
                            $valid = false;
                        }
                    }

                    /* ── FIX 2: DUPLICATE MODEL NAME CHECK ── */
                    if ($valid) {
                        $model_name_escaped = mysqli_real_escape_string($con, $model_name);
                        $check = mysqli_query($con, "SELECT * FROM model_master WHERE model_name='$model_name_escaped' AND brand_id=$brand_id AND category_id=$category_id");
                        if (mysqli_num_rows($check) > 0) {
                            echo "<script>alert('Model already exists!');</script>";
                            $valid = false;
                        }
                    }

                    /* ── YOUR ORIGINAL INSERT LOGIC (UNCHANGED) ── */
                    if ($valid) {
                        $model_image = $_FILES['mdl_image']['name'];
                        $tmp = $_FILES['mdl_image']['tmp_name'];
                        $dst = "./images/model_images/" . $model_image;

                        if (!empty($model_name) && !empty($model_image)) {
                            if (move_uploaded_file($tmp, $dst)) {
                                $q = "INSERT INTO model_master VALUES ('', $brand_id, $category_id, '$model_name', '$model_desc', '$model_image', 1)";
                                if (mysqli_query($con, $q)) {
                                    echo "<div class='alert alert-success alert-dismissible fade show w-75 mx-auto text-center' role='alert'>
                                            Your Model Added Successfully!.. <a href='view_models.php'>View</a>
                                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                                        </div>";
                                    // FIX 1: reset selections after successful insert
                                    $sel_brand = $sel_category = "";
                                } else {
                                    echo "<div class='alert alert-danger'>Database error</div>";
                                }
                            } else {
                                echo "<div class='alert alert-warning'>Image upload failed</div>";
                            }
                        }
                    }
                }
            ?>
            <!-- CENTERED FORM (NOT FULL WIDTH) -->
            <div class="row justify-content-center">
    <div class="col-lg-8 col-md-10 col-sm-12">

        <div class="card form-card">
            <div class="card-body">
                <h4 class="text-truncate">
                    <i class="bi bi-plus-circle"></i> Insert Models
                </h4>
                <small class="d-block text-truncate">
                    Add models to your system
                </small>
            </div>

            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <!-- Brand & Category -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-truncate">Brand *</label>
                            <!-- FIX 1: pass sel_category to getCategory so it can restore the category selection -->
                            <select name="ddlbrand" id="ddlbrand" class="form-select" onchange="getCategory(this.value, '')">
                                <option value="">Select Brand</option>
                                <?php
                                    $q = mysqli_query($con, "SELECT * FROM brand_master WHERE is_active=1");
                                    while ($row = mysqli_fetch_assoc($q)) {
                                        // FIX 1: mark previously selected brand as selected
                                        $selected = ($row['brand_id'] == $sel_brand) ? "selected" : "";
                                        echo "<option value='".$row['brand_id']."' $selected>".$row['brand_name']."</option>";
                                    }
                                ?>
                            </select>
                            <span class="text-danger small d-block mt-1"><?php echo $brandErr; ?></span>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-truncate">Category *</label>
                            <select name="ddlcategory" class="form-select" id="ddlcategory">
                                <option value="">Select Category</option>
                            </select>
                            <span class="text-danger small d-block mt-1"><?php echo $categoryErr; ?></span>
                        </div>
                    </div>

                    <!-- Model Name & Image -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-truncate">Model Name *</label>
                            <input type="text" name="model_name" class="form-control"
                                value="<?php echo htmlspecialchars($model_name); ?>">
                            <span class="text-danger small d-block mt-1"><?php echo $modelErr; ?></span>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-truncate">Model Image *</label>
                            <input type="file" name="mdl_image" class="form-control" accept="image/*">
                            <span class="text-danger small d-block mt-1"><?php echo $imgErr; ?></span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-truncate">Model Description *</label>
                        <input type="text" name="model_desc" class="form-control"
                            value="<?php echo htmlspecialchars($model_desc); ?>">
                        <span class="text-danger small d-block mt-1"><?php echo $descErr; ?></span>
                    </div>

                    <!-- Buttons -->
                    <div class="text-center mt-4">
                        <button type="submit" name="insbrand" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle"></i> Save Model
                        </button>
                        <button type="reset" class="btn btn-secondary px-4">
                            Reset
                        </button>
                    </div>

                </form>
            </div>

            <div class="form-footer text-center text-truncate">
                All fields marked * are mandatory
            </div>
        </div>

    </div>
</div>

            <!-- END CENTERED FORM -->

        </div>
    </div>
</div>


<?php include('../components/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
    // FIX 1: On page load, if a brand was previously selected, reload its categories and re-select the saved category
    var savedBrand    = "<?php echo $sel_brand; ?>";
    var savedCategory = "<?php echo $sel_category; ?>";

    if (savedBrand) {
        getCategory(savedBrand, savedCategory);
    }
</script>

</body>
</html>