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
        function getCategory(brand_id)
        {
            var xhr = new XMLHttpRequest();
            xhr.open("GET","getCategory.php?brand_id=" + brand_id, true);
            xhr.onload = function(){
                document.getElementById("ddlcategory").innerHTML = this.responseText;
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
                $x = $_GET['id'];
                $model_q = mysqli_query($con, "SELECT * FROM model_master WHERE model_id=$x");
$model   = mysqli_fetch_assoc($model_q);

$current_brand    = $model['brand_id'];
$current_category = $model['category_id'];
$current_image    = $model['model_image'];
                if (isset($_POST['insbrand'])) {

    $brand_id    = $_POST['ddlbrand'];
    $category_id = $_POST['ddlcategory'];
    $model_name  = $_POST['model_name'];
    $model_desc  = $_POST['model_desc'];

    // IMAGE HANDLING
    if (!empty($_FILES['mdl_image']['name'])) {
        $model_image = $_FILES['mdl_image']['name'];
        move_uploaded_file(
            $_FILES['mdl_image']['tmp_name'],
            "./images/model_images/" . $model_image
        );
    } else {
        $model_image = $current_image; // keep old image
    }

    if (!empty($model_name)) {

        $q = "
            UPDATE model_master SET
                brand_id = $brand_id,
                category_id = $category_id,
                model_name = '$model_name',
                model_description = '$model_desc',
                model_image = '$model_image',
                is_active = 1
            WHERE model_id = $x
        ";

        if (mysqli_query($con, $q)) {
            echo "<script>
                setTimeout(() => {
                    window.location.href = 'view_models.php';
                }, 1500);
            </script>";
        } else {
            echo "<div class='alert alert-danger'>Database error</div>";
        }

    } else {
        echo "<div class='alert alert-warning'>Model name is required</div>";
    }
}

            ?>

            <!-- CENTERED FORM (NOT FULL WIDTH) -->
            <div class="row justify-content-center">
    <div class="col-lg-8 col-md-10 col-sm-12">

        <div class="card form-card">
            <div class="card-body">
                <h4 class="text-truncate">
                    <i class="bi bi-plus-circle"></i> Edit Models
                </h4>
                <small class="d-block text-truncate">
                    Add a categories to your system
                </small>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">

                    <!-- Brand & Category -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-truncate">Brand *</label>
                            <select name="ddlbrand"
        id="ddlbrand"
        class="form-select"
        onchange="getCategory(this.value)"
        required>

    <option value="">-- Select Brand --</option>

    <?php
    $brand_q = mysqli_query($con, "SELECT * FROM brand_master WHERE is_active=1");
    while ($b = mysqli_fetch_assoc($brand_q)) {
        $selected = ($b['brand_id'] == $current_brand) ? "selected" : "";
        echo "<option value='{$b['brand_id']}' $selected>{$b['brand_name']}</option>";
    }
    ?>
</select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-truncate">Category *</label>
                            <select name="ddlcategory"
        id="ddlcategory"
        class="form-select"
        required>

    <option value="">-- Select Category --</option>

    <?php
    $cat_q = mysqli_query($con, "
        SELECT * FROM category_master 
        WHERE brand_id = $current_brand
    ");

    while ($c = mysqli_fetch_assoc($cat_q)) {
        $selected = ($c['category_id'] == $current_category) ? "selected" : "";
        echo "<option value='{$c['category_id']}' $selected>{$c['category_name']}</option>";
    }
    ?>
</select>
                        </div>
                    </div>
                    <!-- Model Name -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-truncate">Model Name *</label>
                            <input type="text"
                                   name="model_name"
                                   class="form-control text-truncate"
                                   value = "<?php echo $model['model_name']; ?>"
                                   required>
                        </div>

                        <!-- Model Image -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-truncate">Model Image *</label>
                            <input type="file"
                                   name="mdl_image"
                                   class="form-control"
                                   accept="image/*">
                        </div>
                    </div>
                    <div class="mt-2">
    <label class="form-label fw-bold">Current Image</label><br>
    <img src="./images/model_images/<?php echo $current_image; ?>"
         alt="Model Image"
         class="img-thumbnail"
         style="max-width:150px; max-height:150px; object-fit:contain;">
</div>

                    <!-- Description (full width) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-truncate">Model Description *</label>
                        <input type="text"
                               name="model_desc"
                               class="form-control text-truncate"
                               value = "<?php echo $model['model_description']; ?>"
                               required>
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
</body>
</html>