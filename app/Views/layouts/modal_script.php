<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dialog = document.getElementById(btn.getAttribute('data-modal-open'));
                if (dialog && typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dialog = btn.closest('dialog');
                if (dialog) {
                    dialog.close();
                }
            });
        });

        document.querySelectorAll('dialog.modal').forEach(function (dialog) {
            dialog.addEventListener('click', function (e) {
                if (e.target === dialog) {
                    dialog.close();
                }
            });
        });
    });
</script>
