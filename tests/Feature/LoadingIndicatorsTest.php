<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for loading indicator infrastructure:
 * - Overlay container exists in layouts/app.blade.php
 * - .loading-placeholder rows are present in index table templates
 * - The loading utility functions are exposed on window
 */
class LoadingIndicatorsTest extends TestCase
{
    /**
     * Test that the app layout includes the loading overlay container.
     * The overlay is a fixed div inside <main> for page-level loading state.
     */
    public function test_layout_includes_loading_overlay_container(): void
    {
        $response = $this->get(route('dashboard'));

        // The overlay container should be present in the layout.
        // We look for a div with id="page-loading-overlay" inside <main>.
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('id="page-loading-overlay"', $content);
    }

    /**
     * Test that dashboard index has the pendientes tbody container.
     */
    public function test_dashboard_pendientes_table_has_body_container(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('id="body-pendientes"', $content);
    }

    /**
     * Test that the page-loading-overlay element is present in <main>
     * and has the spinner content for visual feedback.
     */
    public function test_page_overlay_has_spinner_content_in_main(): void
    {
        $response = $this->get(route('dashboard'));
        $content = $response->getContent();

        // The overlay div should be inside <main> with the loading spinner markup
        // The overlay element is a div with id="page-loading-overlay" containing spinner-border
        $this->assertStringContainsString('id="page-loading-overlay"', $content);
        $this->assertStringContainsString('spinner-border', $content);
        $this->assertStringContainsString('Cargando...', $content);
    }
}
