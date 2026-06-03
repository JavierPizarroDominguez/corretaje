window.editarCampo = function editarCampo(tdId, btnId, formId, inputId) {
    //Capturar elementos
    const td = document.getElementById(tdId);
    const btn = document.getElementById(btnId);
    const form = document.getElementById(formId);
    const input = document.getElementById(inputId);
    //Cambiar visibilidad
    td.style.display = 'none';
    btn.style.display = 'none';
    form.style.display = 'table-cell';
    input.focus();
    // Detectar click fuera
    document.addEventListener('click', function cerrarFormulario(event) {
        const clickDentroFormulario = form.contains(event.target);
        const clickBotonEditar = btn.contains(event.target);
        if (!clickDentroFormulario && !clickBotonEditar) {
            form.style.display = 'none';
            td.style.display = 'table-cell';
            btn.style.display = 'table-cell';
            document.removeEventListener('click', cerrarFormulario);
        }
    });
}