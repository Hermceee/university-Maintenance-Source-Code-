function togglePassword(fieldId) {
    const passwordFields = document.querySelectorAll(`#${fieldId}`);
    passwordFields.forEach(field => {
        field.type = field.type === 'password' ? 'text' : 'password';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const passwordToggleCheckbox = document.getElementById('password_toggle');
    passwordToggleCheckbox.addEventListener('change', function() {
        const passwordFields = document.querySelectorAll('#password, #password_confirm');
        passwordFields.forEach(field => {
            field.type = this.checked ? 'text' : 'password';
        });
    });
});
