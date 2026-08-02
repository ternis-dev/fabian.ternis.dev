document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('upload_form');
    const imageInput = document.getElementById('upload_image');
    const imagePreview = document.getElementById('upload_preview_img');
    const usernameInput = document.getElementById('upload_username');
    const usernamePreview = document.getElementById('upload_preview_username');
    const descriptionInput = document.getElementById('upload_description');
    const descriptionPreview = document.getElementById('upload_preview_description');
    const createdAtPreview = document.getElementById('upload_preview_created_at');
    const submitBtn = document.getElementById('upload_submit_btn');

    let currentObjectUrl = null;

    const updateDefaultText = () => {
        usernamePreview.textContent = usernameInput.value.trim() || 'Anonymous';
        descriptionPreview.textContent = descriptionInput.value.trim() || 'No description provided.';
        createdAtPreview.textContent = new Date().toLocaleDateString();
    };

    updateDefaultText();

    imageInput.addEventListener('change', (e) => {
        const file = e.target.files[0];

        if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = null;
        }

        if (file) {
            currentObjectUrl = URL.createObjectURL(file);
            imagePreview.src = currentObjectUrl;
            imagePreview.style.display = 'block';
        } else {
            imagePreview.src = '';
            imagePreview.style.display = 'none';
        }
    });

    usernameInput.addEventListener('input', () => {
        usernamePreview.textContent = usernameInput.value.trim() || 'Anonymous';
    });

    descriptionInput.addEventListener('input', () => {
        descriptionPreview.textContent = descriptionInput.value.trim() || 'No description provided.';
    });
});

function onUploadTurnstileSuccess(token) {
    const submitBtn = document.getElementById('upload_submit_btn');
    if (submitBtn) submitBtn.disabled = false;
}

function onUploadTurnstileError() {
    const submitBtn = document.getElementById('upload_submit_btn');
    if (submitBtn) submitBtn.disabled = true;
}