<script>
    // Themed file picker (label.file-pick): once a file is chosen the button
    // shows its name; clearing the choice puts the original text back.
    document.addEventListener('change', function (event) {
        var input = event.target;
        if (!input.matches || !input.matches('.file-pick input[type="file"]')) {
            return;
        }

        var pick = input.closest('.file-pick');
        var text = pick.querySelector('.file-pick-text');
        if (!text.dataset.empty) {
            text.dataset.empty = text.textContent;
        }

        var chosen = input.files && input.files.length > 0;
        text.textContent = chosen ? input.files[0].name : text.dataset.empty;
        pick.classList.toggle('file-pick-chosen', chosen);
    });
</script>
