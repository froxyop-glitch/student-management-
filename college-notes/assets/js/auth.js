/**
 * College Notes Management System - Auth JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Password Visibility Toggle
    const toggleBtns = document.querySelectorAll('.toggle-password-btn');
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = btn.querySelector('i');

            if (targetInput) {
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    if (icon) {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    }
                } else {
                    targetInput.type = 'password';
                    if (icon) {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            }
        });
    });

    // 2. Client-side Form Password Match Validation
    const authForms = document.querySelectorAll('.validate-auth-form');
    authForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const pwd = form.querySelector('input[name="password"]');
            const confirmPwd = form.querySelector('input[name="confirm_password"]');

            if (pwd && confirmPwd && pwd.value !== confirmPwd.value) {
                e.preventDefault();
                alert('Passwords do not match! Please check and try again.');
                confirmPwd.focus();
            }
        });
    });
});
