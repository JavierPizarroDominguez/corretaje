<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'InApp')</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tabler Icons -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.35.0/dist/tabler-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Styles -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div id="overlay" class="overlay"></div>
    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm mobile-toggle-btn">
        <i class="ti ti-menu-2"></i>
    </button>
    @include('layouts.partials.sidebar')
    <main id="content" class="content">
        {{-- Page-level loading overlay: visible by default, hidden on DOMContentLoaded --}}
        <div id="page-loading-overlay">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;border-width:.3em;"></div>
                <p class="mt-3 text-muted mb-0">Cargando...</p>
            </div>
        </div>
        <script>
        (function() {
          var overlay = document.getElementById('page-loading-overlay');
          if (!overlay) return;
          var timer = setTimeout(function() {
            if (overlay) overlay.style.display = 'none';
          }, 200);
          document.addEventListener('DOMContentLoaded', function() {
            clearTimeout(timer);
            if (overlay) overlay.remove();
          }, { once: true });
        })();
        </script>
        @yield('content')
    </main>
    <!-- MODAL DE MENSAJES -->
    <div class="modal fade" id="flashModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" id="flashHeader">
                    <h5 class="modal-title" id="flashTitle">Mensaje</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="flashBody">
                </div>
            </div>
        </div>
    </div>
    <!-- MODAL GLOBAL REUTILIZABLE -->
    <div class="modal fade" id="modalPrincipal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" id="modalPrincipalDialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPrincipalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalPrincipalBody"></div>
            </div>
        </div>
    </div>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
   
    <!-- Editar Campo -->
    <script src="{{ asset('js/editarCampo.js') }}"></script>
    <!-- Buscador -->
    <script src="{{ asset('js/buscador.js') }}?v=2"></script>
    <!-- Alertas success y error -->
    <script>
        window.flashData = {
            success: @json(session('success')),
            error: @json($errors->any() ? $errors->first() : session('error'))
        };
    </script>
    <script src="{{ asset('js/alertas.js') }}"></script>
    <!-- MODAL GLOBAL REUTILIZABLE -->
    <script>
        window.abrirModal = function ({
            titulo = '',
            vista = '',
            size = 'modal-lg',
            cliente_id = null
        }) {
            const sourceDiv = document.getElementById(vista);
            const body = document.getElementById('modalPrincipalBody');
            const savedHTML = sourceDiv.innerHTML;

            body.innerHTML = '';
            for (let child of sourceDiv.children) {
                body.appendChild(child.cloneNode(true));
            }
            sourceDiv.innerHTML = '';

            // CUSTOM: copy cliente_id into modal hidden inputs
            if (cliente_id) {
                const modalClienteId = body.querySelector('#modal-cliente-id');
                if (modalClienteId) modalClienteId.value = cliente_id;
                const formClienteId = body.querySelector('#input-create-cliente-id');
                if (formClienteId) formClienteId.value = cliente_id;
            }

            document.getElementById('modalPrincipalTitle').innerHTML = titulo;
            const dialog = document.getElementById('modalPrincipalDialog');
            dialog.className = 'modal-dialog ' + size;

            const modalEl = document.getElementById('modalPrincipal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            modalEl.addEventListener('hidden.bs.modal', function () {
                body.innerHTML = '';
                sourceDiv.innerHTML = savedHTML;
            }, { once: true });
        }
    </script>
    <!-- SCRIPTS -->
    @stack('scripts')
</body>
</html>