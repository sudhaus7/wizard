<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 project.
 *
 * @author Frank Berger <fberger@sudhaus7.de>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use TYPO3\CMS\Core\Information\Typo3Version;

return static function (ContainerConfigurator $containerConfigurator): void {
    $majorVersion = (new Typo3Version())->getMajorVersion();
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $services->load(
        sprintf('SUDHAUS7\\Sudhaus7Wizard\\Core%d\\', $majorVersion),
        sprintf(__DIR__ . '/../Core%d/', $majorVersion),
    );
};
