        </main> <!-- Close main-content -->

        <?php
        // Define SYSTEM_VERSION if not already defined
        if (!defined('SYSTEM_VERSION')) {
            define('SYSTEM_VERSION', '1.0.0'); // Replace '1.0.0' with your actual version
        }
        ?>

        <footer class="footer mt-auto py-3 bg-dark text-white">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <span class="text-muted">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="text-muted me-2">Version: <?= SYSTEM_VERSION ?></span>
                        <span id="footerClock" class="text-muted"></span>
                    </div>
                </div>
            </div>
        </footer>

        <!-- JavaScript Libraries -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
    </body>
</html>