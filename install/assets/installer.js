// Installer JavaScript - Handles AJAX calls for database testing and SQL import

// Step 1: Database Connection Test
document.addEventListener('DOMContentLoaded', function() {
    const dbForm = document.getElementById('dbForm');
    const testBtn = document.getElementById('testBtn');
    const nextBtn = document.getElementById('nextBtn');
    const testResult = document.getElementById('testResult');
    const testSpinner = document.getElementById('testSpinner');
    const testText = document.getElementById('testText');
    
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            const formData = new FormData(dbForm);
            
            // Show loading state
            testSpinner.style.display = 'inline-block';
            testText.textContent = 'Testing...';
            testBtn.disabled = true;
            testResult.innerHTML = '';
            
            fetch('ajax_test_connection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                testSpinner.style.display = 'none';
                testText.textContent = 'Test Connection';
                testBtn.disabled = false;
                
                if (data.success) {
                    testResult.innerHTML = '<div class="alert alert-success">✓ ' + data.message + '</div>';
                    if (data.warning) {
                        testResult.innerHTML += '<div class="alert alert-warning">⚠️ ' + data.warning + '</div>';
                    }
                    nextBtn.disabled = false;
                    
                    // Store credentials for form submission
                    dbForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        window.location.href = 'step2.php';
                    });
                } else {
                    testResult.innerHTML = '<div class="alert alert-error">✗ ' + data.message + '</div>';
                    nextBtn.disabled = true;
                }
            })
            .catch(error => {
                testSpinner.style.display = 'none';
                testText.textContent = 'Test Connection';
                testBtn.disabled = false;
                testResult.innerHTML = '<div class="alert alert-error">✗ Connection test failed: ' + error.message + '</div>';
                nextBtn.disabled = true;
            });
        });
    }
    
    // Step 2: SQL Import
    const importForm = document.getElementById('importForm');
    const importBtn = document.getElementById('importBtn');
    const importResult = document.getElementById('importResult');
    const importSpinner = document.getElementById('importSpinner');
    const importText = document.getElementById('importText');
    const progressSection = document.getElementById('progressSection');
    
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(importForm);
            const fileInput = document.getElementById('sql_file');
            
            if (fileInput.files.length === 0) {
                importResult.innerHTML = '<div class="alert alert-error">Please select a .sql file to upload.</div>';
                return;
            }
            
            // Show loading state
            importSpinner.style.display = 'inline-block';
            importText.textContent = 'Importing...';
            importBtn.disabled = true;
            progressSection.style.display = 'block';
            importResult.innerHTML = '';
            
            fetch('ajax_import_sql.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                importSpinner.style.display = 'none';
                importText.textContent = 'Import Database';
                importBtn.disabled = false;
                progressSection.style.display = 'none';
                
                if (data.success) {
                    importResult.innerHTML = '<div class="alert alert-success">✓ ' + data.message + '</div>';
                    
                    // Auto-redirect to step 3 after a short delay
                    setTimeout(function() {
                        window.location.href = 'step3.php';
                    }, 1500);
                } else {
                    importResult.innerHTML = '<div class="alert alert-error">✗ ' + data.message + '</div>';
                }
            })
            .catch(error => {
                importSpinner.style.display = 'none';
                importText.textContent = 'Import Database';
                importBtn.disabled = false;
                progressSection.style.display = 'none';
                importResult.innerHTML = '<div class="alert alert-error">✗ Import failed: ' + error.message + '</div>';
            });
        });
    }
});
