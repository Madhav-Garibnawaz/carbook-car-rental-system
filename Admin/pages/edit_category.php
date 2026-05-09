<?php 
    include('connect.php');
    session_name('admin_session');
    session_start();
    include('../components/navbar.php'); 

    // Handle form submission
    $x = $_GET['id'] ?? 0;
    $message = '';
    $messageType = '';

    if (isset($_POST['editcategory'])) {
        $brand_id = mysqli_real_escape_string($con, $_POST['ddlbrand']);
        $category_name = mysqli_real_escape_string($con, $_POST['ct_name']);
        $category_image = $_FILES['ct_image']['name'];
        $tmp = $_FILES['ct_image']['tmp_name'];
        
        if (!empty($category_name) && !empty($brand_id)) {
            // If new image is uploaded
            if (!empty($category_image)) {
                $ext = strtolower(pathinfo($category_image, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($ext, $allowed)) {
                    $message = "Only image files are allowed (jpg, jpeg, png, gif, webp)";
                    $messageType = "warning";
                } else {
                    // Create unique filename
                    $new_filename = "category_" . time() . "_" . uniqid() . "." . $ext;
                    $dst = "./images/category_images/" . $new_filename;
                    
                    if (move_uploaded_file($tmp, $dst)) {
                        // Delete old image
                        $old_query = mysqli_query($con, "SELECT category_image FROM category_master WHERE category_id=$x");
                        $old_row = mysqli_fetch_assoc($old_query);
                        if ($old_row && file_exists("./images/category_images/" . $old_row['category_image'])) {
                            unlink("./images/category_images/" . $old_row['category_image']);
                        }
                        
                        $q = "UPDATE category_master SET brand_id=$brand_id, category_name='$category_name', category_image='$new_filename' WHERE category_id=$x";
                        
                        if (mysqli_query($con, $q)) {
                            $message = "Category updated successfully!";
                            $messageType = "success";
                            echo "<script>
                                setTimeout(() => {
                                    window.location.href = 'view_categories.php';
                                }, 1500);
                            </script>";
                        } else {
                            $message = "Database error: " . mysqli_error($con);
                            $messageType = "danger";
                        }
                    } else {
                        $message = "Image upload failed";
                        $messageType = "warning";
                    }
                }
            } else {
                // Update only name and brand, keep existing image
                $q = "UPDATE category_master SET brand_id=$brand_id, category_name='$category_name' WHERE category_id=$x";
                
                if (mysqli_query($con, $q)) {
                    $message = "Category updated successfully!";
                    $messageType = "success";
                    echo "<script>
                        setTimeout(() => {
                            window.location.href = 'view_categories.php';
                        }, 1500);
                    </script>";
                } else {
                    $message = "Database error: " . mysqli_error($con);
                    $messageType = "danger";
                }
            }
        } else {
            $message = "Category name and brand are required";
            $messageType = "warning";
        }
    }

    // Fetch category data
    $qry = mysqli_query($con, "SELECT * FROM category_master WHERE category_id=$x");
    $category = mysqli_fetch_array($qry);

    if (!$category) {
        echo "<script>alert('Category not found'); window.location.href='view_categories.php';</script>";
        exit();
    }
?>

    <div class="container">
        <div class="page-inner">
            <div class="page-header">
                <h3 class="fw-bold mb-3">Edit Category</h3>
                <ul class="breadcrumbs mb-3">
                    <li class="nav-home">
                        <a href="index.php"><i class="icon-home"></i></a>
                    </li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="view_categories.php">Categories</a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item">Edit Category</li>
                </ul>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <strong><?= $messageType === 'success' ? 'Success!' : 'Error!' ?></strong> <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-5 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-edit me-2"></i>Update Category Information
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="editCategoryForm">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="ddlbrand" class="form-label">
                                                Brand <span class="text-danger">*</span>
                                            </label>
                                            <select name="ddlbrand" id="ddlbrand" class="form-select" required>
                                                <option value="">Select Brand</option>
                                                <?php
                                                    $brand_query = mysqli_query($con, "SELECT * FROM brand_master WHERE is_active=1");
                                                    while ($brand_row = mysqli_fetch_assoc($brand_query)) {
                                                        $selected = ($brand_row['brand_id'] == $category['brand_id']) ? 'selected' : '';
                                                        echo "<option value='" . $brand_row['brand_id'] . "' $selected>" . htmlspecialchars($brand_row['brand_name']) . "</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="ct_name" class="form-label">
                                                Category Name <span class="text-danger">*</span>
                                            </label>
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                id="ct_name" 
                                                name="ct_name" 
                                                value="<?= htmlspecialchars($category['category_name']) ?>"
                                                placeholder="Enter category name"
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="ct_image" class="form-label">
                                                Category Image (Leave empty to keep current image)
                                            </label>
                                            <input 
                                                type="file" 
                                                class="form-control" 
                                                id="ct_image" 
                                                name="ct_image" 
                                                accept="image/*"
                                                onchange="previewImage(this)"
                                            />
                                            <small class="form-text text-muted">
                                                Accepted formats: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label">Current Image</label>
                                            <div class="d-flex align-items-center gap-3">
                                                <img 
                                                    src="./images/category_images/<?= $category['category_image'] ?>" 
                                                    alt="Current Image" 
                                                    id="currentImage"
                                                    class="img-thumbnail"
                                                    style="max-width: 150px; max-height: 150px; object-fit: contain;"
                                                />
                                                <div id="newImagePreview" style="display: none;">
                                                    <p class="mb-1 text-muted small">New Image Preview:</p>
                                                    <img 
                                                        id="previewImg" 
                                                        class="img-thumbnail"
                                                        style="max-width: 150px; max-height: 150px; object-fit: contain;"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-action">
                                    <button type="submit" name="editcategory" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Update Category
                                    </button>
                                    <a href="view_categories.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('newImagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Check file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Form validation
        document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
            const categoryName = document.getElementById('ct_name').value.trim();
            const brandId = document.getElementById('ddlbrand').value;
            
            if (categoryName === '' || brandId === '') {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
        });
    </script>

<?php include('../components/footer.php'); ?>