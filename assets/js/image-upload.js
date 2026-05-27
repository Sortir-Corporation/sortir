 document.addEventListener("DOMContentLoaded", function() {
    // Target the Symfony-generated input via the class we passed in Twig
    const fileInput = document.querySelector('.hidden-symfony-file-input');
    const uploadLabel = document.getElementById('uploadLabel');
    const actionText = uploadLabel.querySelector('.action-text');

    if (fileInput && uploadLabel) {
    fileInput.addEventListener('change', function(event) {
    if (event.target.files.length > 0) {
    // Extract the file name
    const fileName = event.target.files[0].name;

    // Apply the green glass styling
    uploadLabel.classList.add('image-uploaded');

    // Update the text
    actionText.textContent = fileName;
} else {
    // Revert to original state if canceled
    uploadLabel.classList.remove('image-uploaded');
    actionText.textContent = "Choisir une image";
}
});
}
});
