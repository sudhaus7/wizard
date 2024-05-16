<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Enumeration;

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * @todo php:>=8.1 Change to default enumeration handling
 */
final class CreatorStatus extends Enumeration
{
    public const __default = self::STATUS_NOT_READY;
    public const STATUS_EDIT = 0;
    public const STATUS_NOT_READY = 5;
    public const STATUS_READY = 10;
    public const STATUS_PROCESSING = 15;
    public const STATUS_FAILED = 17;
    public const STATUS_DONE = 20;
}
