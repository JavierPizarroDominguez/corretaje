<aside id="sidebar" class="sidebar">
    <div class="d-flex align-items-center gap-2 px-3 py-3">
        <span class="brand-icon"><i class="ti ti-building-skyscraper"></i></span>
        <span class="brand-text">InApp</span>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('dashboard') }}">
                <i class="ti ti-home"></i>
                <span class="nav-text">Inicio</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('administracion.create') }}">
                <i class="ti ti-building-plus"></i>
                <span class="nav-text">Agregar administración</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link disabled" href="#">
                <i class="ti ti-receipt"></i>
                <span class="nav-text">Subir Cartola Bancaria</span>
            </a>
        </li>
    </ul>
</aside>