<?php

namespace Tests\Feature;

use Tests\TestCase;

class GuaranteeRefundFrontendContractsTest extends TestCase
{
    public function test_contract_termination_warning_does_not_submit_discounts_or_use_native_dialogs(): void
    {
        $component = file_get_contents(resource_path('views/components/contratos.blade.php'));

        $this->assertStringContainsString('terminacion-warning', $component);
        $this->assertStringContainsString('Al terminar el contrato se fijará la fecha de término', $component);
        $this->assertStringContainsString('devolución de garantía quedará pendiente', $component);
        $this->assertStringContainsString('30 días', $component);
        $this->assertStringContainsString('cobros proporcionales de renta', $component);
        $this->assertStringContainsString('body: JSON.stringify({})', $component);
        $this->assertStringNotContainsString('collectTerminationDiscounts(preview)', $component);
        $this->assertStringNotContainsString('alert(', $component);
        $this->assertStringNotContainsString('confirm(', $component);
        $this->assertStringNotContainsString('prompt(', $component);
    }

    public function test_dashboard_cliente_and_propiedad_route_guarantee_refunds_to_refund_modal(): void
    {
        $refundPartials = file_get_contents(resource_path('views/components/guarantee-refund-modal.blade.php'))
            . file_get_contents(resource_path('views/components/guarantee-refund-scripts.blade.php'));

        foreach ([
            resource_path('views/dashboard/index.blade.php'),
            resource_path('views/cliente.blade.php'),
            resource_path('views/propiedad.blade.php'),
        ] as $path) {
            $view = file_get_contents($path) . $refundPartials;

            $this->assertStringContainsString('is_guarantee_refund', $view, $path);
            $this->assertStringContainsString('openGuaranteeRefundModal(cobro)', $view, $path);
            $this->assertStringContainsString('modalGarantiaRefund', $view, $path);
            $this->assertStringContainsString('Plazo restante', $view, $path);
            $this->assertStringContainsString("'/api/cobros/' + cobro.id + '/devolver-garantia'", $view, $path);
            $this->assertStringContainsString('window.showElLoading(btn)', $view, $path);
            $this->assertStringContainsString('window.hideElLoading(btn)', $view, $path);
            $this->assertStringNotContainsString('alert(', $view, $path);
            $this->assertStringNotContainsString('confirm(', $view, $path);
            $this->assertStringNotContainsString('prompt(', $view, $path);
        }
    }

    public function test_server_rendered_ficha_pendientes_include_guarantee_refund_metadata_helper(): void
    {
        foreach ([
            app_path('Http/Controllers/Vistas/FichaClienteController.php'),
            app_path('Http/Controllers/Vistas/FichaPropiedadController.php'),
        ] as $path) {
            $controller = file_get_contents($path);

            $this->assertStringContainsString('GarantiaRefundMetadata::forCobro($cobro)', $controller, $path);
        }
    }
}
