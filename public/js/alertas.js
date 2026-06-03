document.addEventListener('DOMContentLoaded', function () {

    const success = window.flashData.success;
    const error = window.flashData.error;

    const message = success || error;
    const type = success ? 'success' : (error ? 'error' : null);

    if (!message) return;

    const modalEl = document.getElementById('flashModal');
    const modal = new bootstrap.Modal(modalEl);

    const header = document.getElementById('flashHeader');
    const title = document.getElementById('flashTitle');
    const body = document.getElementById('flashBody');

    body.innerText = message;

    if (type === 'success') {
        header.classList.add('bg-success', 'text-white');
        title.innerText = 'Éxito';
    }

    if (type === 'error') {
        header.classList.add('bg-danger', 'text-white');
        title.innerText = 'Error';
    }

    modal.show();
});