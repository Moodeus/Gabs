<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-table-search]').forEach(function (input) {
            var table = document.getElementById(input.getAttribute('data-table-search'));
            if (!table) {
                return;
            }

            var rows = table.querySelectorAll('tbody tr:not([data-search-ignore])');

            input.addEventListener('input', function () {
                var q = input.value.trim().toLowerCase();
                rows.forEach(function (row) {
                    row.hidden = q !== '' && !row.textContent.toLowerCase().includes(q);
                });
            });
        });
    });
</script>
