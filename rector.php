<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
    ]);

    // Define what rule sets will be applied
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_85,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
    ]);

    // Skip some rules for Laravel compatibility
    $rectorConfig->skip([
        // Skip inline constructor as it may break Laravel dependency injection
        InlineConstructorDefaultToPropertyRector::class,
    ]);

    // Import short classes
    $rectorConfig->importShortClasses(false);

    // Import names
    $rectorConfig->importNames();
};
