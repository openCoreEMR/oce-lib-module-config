<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig;

/**
 * Parameters for registering a module's globals section (enable toggle + settings link).
 *
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc. <https://www.opencoreemr.com>
 */
final readonly class GlobalsSectionDescriptor
{
    /**
     * @param string $sectionName Display name for the globals section (e.g. "OpenCoreEMR Sinch Conversations")
     * @param string $moduleDirName Module directory name under custom_modules/ (e.g. "oce-module-sinch-conversations")
     * @param string $enableKey Globals key for the enable/disable toggle (e.g. "oce_sinch_conversations_enabled")
     * @param string $settingsDescription Help text shown above the settings link
     */
    public function __construct(
        public string $sectionName,
        public string $moduleDirName,
        public string $enableKey,
        public string $settingsDescription,
    ) {
    }
}
