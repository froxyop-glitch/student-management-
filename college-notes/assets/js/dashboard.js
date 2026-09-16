/**
 * College Notes Management System - Dashboard JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. File Upload Size & Extension Validation
    const fileInputs = document.querySelectorAll('input[type="file"].validate-file');
    fileInputs.forEach(input => {
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;

            const maxSize = 20 * 1024 * 1024; // 20 MB
            const allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
            const fileName = file.name;
            const ext = fileName.split('.').pop().toLowerCase();

            if (!allowedExts.includes(ext)) {
                alert(`Invalid file type (.${ext}). Only PDF, DOC, DOCX, PPT, PPTX, and TXT files are allowed.`);
                input.value = '';
                return;
            }

            if (file.size > maxSize) {
                alert(`File size (${(file.size / (1024 * 1024)).toFixed(2)} MB) exceeds the maximum limit of 20MB.`);
                input.value = '';
                return;
            }

            // Display file info if target element exists
            const infoDisplay = document.getElementById('selectedFileInfo');
            if (infoDisplay) {
                infoDisplay.textContent = `Selected: ${fileName} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
                infoDisplay.classList.remove('d-none');
            }
        });
    });

    // 2. Auto submit filter forms on dropdown change
    const autoFilters = document.querySelectorAll('.auto-submit-filter');
    autoFilters.forEach(select => {
        select.addEventListener('change', () => {
            select.closest('form').submit();
        });
    });
});
