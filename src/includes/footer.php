        <footer class="footer mt-auto py-3 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center">
                        <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> Management System. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Remove old Tailwind JS -->
        <!-- <script>
            // Basic mobile menu toggle
            const menuButton = document.getElementById('mobile-menu-button');
            // ... rest of old script ...
        </script> -->

        <!-- Add Bootstrap Bundle JS (includes Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

        <!-- Add any custom JS files below Bootstrap -->
        <!-- <script src="js/custom_script.js"></script> -->

        <?php
        // Close DB connection if open
        if (isset($conn)) {
            mysqli_close($conn);
        }
        ?>
    </body>
</html> 