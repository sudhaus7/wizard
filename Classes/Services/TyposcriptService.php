<?php

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

namespace SUDHAUS7\Sudhaus7Wizard\Services;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class TyposcriptService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const INDENT = '  ';

    /**
     * @return array<array-key, mixed>
     */
    public static function parse(string $s):array
    {
        /** @var  TypoScriptParser $oTSparser */
        $oTSparser = GeneralUtility::makeInstance(TypoScriptParser::class);
        $oTSparser->parse($s);
        return $oTSparser->setup;
    }

    /**
     * @param array<array-key, mixed> $a
     */
    public static function fold(array $a, int $i = 0, string $keys = ''): string
    {
        $c = '';
        foreach ($a as $k => $v) {
            if (is_array($v)) {
                if (count($v) > 1) {
                    $c .= "\n" . str_repeat(self::INDENT, $i) . $keys . substr($k, 0, -1) . ' {';
                    $c .= str_repeat(self::INDENT, $i) . self::fold($v, $i + 1);
                    $c .= str_repeat(self::INDENT, $i) . '}';
                } else {
                    $c .= str_repeat(self::INDENT, $i) . self::fold($v, $i, $keys . $k);
                }
            } elseif (empty($keys)) {
                $test = explode("\n", $v);
                if (count($test) > 1) {
                    $c .= "\n" . str_repeat(self::INDENT, $i) . $k . ' ( ' . "\n" . $v . "\n)";
                } else {
                    $c .= "\n" . str_repeat(self::INDENT, $i) . $k . ' = ' . $v;
                }
            } else {
                //$c .= "\n".str_repeat(self::INDENT, $i).$keys.$k.' = '.$v;
                $test = explode("\n", $v);
                if (count($test) > 1) {
                    $c .= "\n" . str_repeat(self::INDENT, $i) . $keys . $k . ' ( ' . "\n" . $v . "\n)";
                } else {
                    $c .= "\n" . str_repeat(self::INDENT, $i) . $keys . $k . ' = ' . $v;
                }
            }
        }
        return $c . "\n";
    }

    /**
     * @param array<array-key, mixed> $a
     * @return array<array-key, mixed>
     */
    public static function cleanArray(array $a): array
    {
        foreach ($a as $k => $v) {
            if (str_ends_with($k, '.')) {
                $k = substr($k, 0, -1);
            }
            $a[$k] = is_array($v) ? self::cleanArray($v) : $v;
            unset($a[$k . '.']);
        }
        return $a;
    }
}
