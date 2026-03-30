<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig\Tests\Unit;

use OpenCoreEMR\ModuleConfig\GlobalsRegistrar;
use OpenCoreEMR\ModuleConfig\GlobalsSectionDescriptor;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Services\Globals\GlobalsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;

class GlobalsRegistrarTest extends TestCase
{
    private GlobalsSectionDescriptor $descriptor; // @phpstan-ignore property.uninitialized (setUp)
    private ParameterBag $globalsBag; // @phpstan-ignore property.uninitialized (setUp)

    protected function setUp(): void
    {
        $this->descriptor = new GlobalsSectionDescriptor(
            sectionName: 'OpenCoreEMR Test Module',
            moduleDirName: 'oce-module-test',
            enableKey: 'oce_test_module_enabled',
            settingsDescription: 'Configure test module options.',
        );
        $this->globalsBag = new ParameterBag(['webroot' => '/openemr']);
    }

    public function testRegisterAddsListener(): void
    {
        $dispatcher = new EventDispatcher();
        $registrar = new GlobalsRegistrar($this->globalsBag);
        $registrar->register($dispatcher, $this->descriptor);

        $this->assertTrue($dispatcher->hasListeners(GlobalsInitializedEvent::EVENT_HANDLE));
    }

    protected function tearDown(): void
    {
        // Clean up global state written by GlobalsService::save()
        // phpcs:ignore Squiz.PHP.GlobalKeyword.NotAllowed
        global $GLOBALS_METADATA;
        $GLOBALS_METADATA = null;
    }

    /**
     * Dispatch the event and return the captured globals metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    private function dispatchAndCapture(): array
    {
        $dispatcher = new EventDispatcher();
        $registrar = new GlobalsRegistrar($this->globalsBag);
        $registrar->register($dispatcher, $this->descriptor);

        $globalsService = new GlobalsService([], [], []);
        $event = new GlobalsInitializedEvent($globalsService);
        $dispatcher->dispatch($event, GlobalsInitializedEvent::EVENT_HANDLE);

        // GlobalsService::save() writes to global vars — use that to capture state
        $globalsService->save();
        // phpcs:ignore Squiz.PHP.GlobalKeyword.NotAllowed
        global $GLOBALS_METADATA;
        /** @var array<string, array<string, mixed>> $metadata */
        $metadata = $GLOBALS_METADATA ?? [];
        return $metadata;
    }

    public function testCreatesSectionWithEnableToggle(): void
    {
        $metadata = $this->dispatchAndCapture();

        $this->assertArrayHasKey('OpenCoreEMR Test Module', $metadata);

        $section = $metadata['OpenCoreEMR Test Module'];
        $this->assertArrayHasKey('oce_test_module_enabled', $section);

        /** @var list<mixed> $enableEntry */
        $enableEntry = $section['oce_test_module_enabled'];
        $this->assertSame('bool', $enableEntry[1]);
        $this->assertSame('0', $enableEntry[2]);
    }

    public function testCreatesSectionWithSettingsLink(): void
    {
        $metadata = $this->dispatchAndCapture();

        $this->assertArrayHasKey('OpenCoreEMR Test Module', $metadata);
        $section = $metadata['OpenCoreEMR Test Module'];
        $this->assertArrayHasKey('oce_test_module_enabled_settings_link', $section);

        /** @var list<mixed> $linkEntry */
        $linkEntry = $section['oce_test_module_enabled_settings_link'];
        $this->assertSame('html_display_section', $linkEntry[1]);

        /** @var array<string, callable(): string> $options */
        $options = $linkEntry[4];
        $this->assertArrayHasKey('render_callback', $options);

        $html = ($options['render_callback'])();
        $this->assertStringContainsString('oce-module-test', $html);
        $this->assertStringContainsString('/openemr/interface/modules/custom_modules/', $html);
        $this->assertStringContainsString('settings.php', $html);
        $this->assertStringContainsString('Configure test module options.', $html);
    }
}
