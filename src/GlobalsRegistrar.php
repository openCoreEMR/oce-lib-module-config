<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig;

use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Services\Globals\GlobalSetting;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Register a module's globals section: enable/disable toggle and settings page link.
 *
 * Eliminates the boilerplate every OCE module copy-pastes in its Bootstrap class.
 *
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc. <https://www.opencoreemr.com>
 */
class GlobalsRegistrar
{
    public function __construct(
        private readonly ParameterBag $globalsBag,
    ) {
    }

    /**
     * Subscribe to GlobalsInitializedEvent and register the module's globals section.
     */
    public function register(
        EventDispatcherInterface $eventDispatcher,
        GlobalsSectionDescriptor $descriptor,
    ): void {
        $globalsBag = $this->globalsBag;

        $eventDispatcher->addListener(
            GlobalsInitializedEvent::EVENT_HANDLE,
            static function (GlobalsInitializedEvent $event) use ($descriptor, $globalsBag): void {
                $service = $event->getGlobalsService();

                $service->createSection($descriptor->sectionName);

                // Enable/disable toggle
                $enableSetting = new GlobalSetting(
                    sprintf(xlt('Enable %s'), $descriptor->sectionName),
                    GlobalSetting::DATA_TYPE_BOOL,
                    '0',
                    sprintf(xlt('Enable or disable the %s module'), $descriptor->sectionName),
                );
                $service->appendToSection(
                    $descriptor->sectionName,
                    $descriptor->enableKey,
                    $enableSetting,
                );

                // Settings page link
                $webroot = $globalsBag->getString('webroot');
                $settingsPath = $webroot
                    . '/interface/modules/custom_modules/' . $descriptor->moduleDirName
                    . '/public/settings.php';

                $linkSetting = new GlobalSetting(
                    xlt('Module Settings'),
                    GlobalSetting::DATA_TYPE_HTML_DISPLAY_SECTION,
                    '',
                    xlt('Link to the module settings page'),
                );
                $linkSetting->addFieldOption(
                    GlobalSetting::DATA_TYPE_OPTION_RENDER_CALLBACK,
                    static function () use ($settingsPath, $descriptor): string {
                        $url = attr($settingsPath);
                        $label = xlt('Open Module Settings');
                        $description = xlt($descriptor->settingsDescription);
                        return <<<HTML
                            <p>{$description}</p>
                            <a href="{$url}" class="btn btn-secondary btn-sm"
                               onclick="top.restoreSession()">{$label}</a>
                            HTML;
                    },
                );
                $service->appendToSection(
                    $descriptor->sectionName,
                    $descriptor->enableKey . '_settings_link',
                    $linkSetting,
                );
            },
        );
    }
}
