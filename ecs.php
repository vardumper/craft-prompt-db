<?php

declare(strict_types=1);

use craft\ecs\SetList;
use Jelix\Version\Parser;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->parallel();
    $ecsConfig->paths([
        __DIR__ . '/src',
        __FILE__,
    ]);

    if (file_exists(__DIR__ . '/../../bootstrap.php')) {
        defined('CRAFT_BASE_PATH') or define('CRAFT_BASE_PATH', realpath(__DIR__ . '/../../'));
    }

    if (!defined('CRAFT_BASE_PATH')) {
        // if we're not in a craft project, use craft 4 rules (eg when in a github action runner)
        $ecsConfig->sets([SetList::CRAFT_CMS_4]);
        return;
    }

    // apply ecs rules based on craft version
    $lockOrJson = file_exists(CRAFT_BASE_PATH . '/composer.lock') ? CRAFT_BASE_PATH . '/composer.lock' : CRAFT_BASE_PATH . '/composer.json';
    $dependencies = json_decode(file_get_contents($lockOrJson), true);

    $craftVersion = '4.0.0.';
    if (isset($dependencies['packages'])) {
        $craft = array_values(array_filter($dependencies['packages'], function ($item) {
            return $item['name'] === 'craftcms/cms';
        }));
        $craftVersion = $craft[0]['version'];
    } elseif (isset($dependencies['require'])) {
        $craftVersion = $dependencies['require']['craftcms/cms'];
    }

    $version = Parser::parse($craftVersion);
    match ($version->getMajor()) {
        3 => $ecsConfig->sets([SetList::CRAFT_CMS_3]),
        4 => $ecsConfig->sets([SetList::CRAFT_CMS_4]),
        default => $ecsConfig->sets([SetList::CRAFT_CMS_4]),
    };
};
