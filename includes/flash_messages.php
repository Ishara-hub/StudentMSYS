<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center">
        <i class="fas fa-check-circle me-2"></i>
        <div><?= $_SESSION['success'] ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center">
        <i class="fas fa-exclamation-circle me-2"></i>
        <div><?= $_SESSION['error'] ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>