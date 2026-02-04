        </main>
    </div>
    
    <!-- Notification Modal -->
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Notifications</h3>
                <span class="close" id="closeNotificationModal">&times;</span>
            </div>
            <div class="modal-body" id="notificationList">
                <!-- Notifications will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Action</h3>
                <span class="close" id="closeConfirmModal">&times;</span>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Are you sure you want to perform this action?</p>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelAction">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmAction">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
    
    <footer class="main-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Inventory Management System - University Project</p>
            <p>Developed with PHP, MySQL & JavaScript</p>
        </div>
    </footer>
</body>
</html>