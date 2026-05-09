<title>View Brands</title>
<?php
include('connect.php');
session_name('admin_session');
session_start();
include('../components/navbar.php');
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>
    .page-content {
        min-height: calc(100vh - 70px);
        padding: 2rem 1rem;
    }

    .card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 1.5rem;
    }

    .card-header h4 {
        color: white;
        margin: 0;
    }

    tr:has(.btn-success) {
        opacity: 0.5;
        filter: grayscale(80%);
        transition: opacity 0.3s ease, filter 0.3s ease;
    }

    tr:has(.btn-success):hover {
        opacity: 0.7;
    }

    .table img {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }

    .table img:hover {
        transform: scale(1.1);
    }

    .btn-sm {
        padding: 0.4rem 0.8rem;
    }

    .dt-buttons {
        margin-bottom: 1rem;
    }

    .dt-button {
        background: #667eea !important;
        border: none !important;
        color: white !important;
        border-radius: 6px !important;
        padding: 0.5rem 1rem !important;
        margin-right: 0.5rem !important;
    }

    .dt-button:hover {
        background: #764ba2 !important;
    }

    tfoot select {
        width: 100%;
        padding: 4px;
    }

    .filter-row th {
        padding: 6px;
    }

    .filter-row select {
        width: 100%;
    }

</style>

<div class="container">
    <div class="page-content">
        <div class="card w-100">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-list me-2"></i>Brands Management</h4>
            </div>
        <div class="card-body">
            <table id="brandsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Brand Name</th>
                        <th>Logo</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                <?php
                $q = mysqli_query($con, "SELECT * FROM brand_master");
                while ($row = mysqli_fetch_assoc($q)) {
                ?>
                    <tr>
                        <td><?= $row['brand_id']; ?></td>
                        <td><?= $row['brand_name']; ?></td>
                        <td>
                            <img src="./images/brand_images/<?= $row['brand_logo']; ?>" width="60">
                        </td>
                        <td class="text-center">
                            <?php if($row['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                    <span class="badge bg-secondary">Disable</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_brand.php?id=<?= $row['brand_id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fa fa-edit"></i>
                            </a>

                            <a href="delete_brand.php?id=<?= $row['brand_id']; ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this brand?')">
                                <i class="fa fa-trash"></i>
                            </a>

                            <?php if ($row['is_active']) { ?>
                                <a href="toggle_brand.php?id=<?= $row['brand_id']; ?>&status=0"
                                   class="btn btn-sm btn-warning"
                                   title="Disable"
                                   onclick="return confirm('Are you sure you want to Disable Brand?')"
                                   >
                                    <i class="fa fa-ban"></i>
                                </a>
                            <?php } else { ?>
                                <a href="toggle_brand.php?id=<?= $row['brand_id']; ?>&status=1"
                                   class="btn btn-sm btn-success"
                                   title="Activate"
                                   onclick="return confirm('Are you sure you want to Active Brand?')"
                                   >
                                    <i class="fa fa-check"></i>
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include('../components/footer.php'); ?>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>

<!-- Export dependencies -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<!-- Export buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function () {
    $('#brandsTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                text: '<i class="fas fa-copy"></i> Copy'
            },
            {
                extend: 'csv',
                text: '<i class="fas fa-file-csv"></i> CSV'
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF'
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print'
            }
        ],
        columnDefs: [
            { orderable: false, targets: 4 }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search brands..."
        }
    });
});
</script>