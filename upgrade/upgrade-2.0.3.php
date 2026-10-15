<?php
/**
 * Upgrade to 2.0.3: refresh the .htaccess rule so that png / webp originals are protected as well.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_3($module)
{
    try {
        $module->removeHtaccessSection();
        $module->writeHtaccessSection();
    } catch (\Throwable $e) {
        // never block the upgrade because of .htaccess permissions
    }

    return true;
}
