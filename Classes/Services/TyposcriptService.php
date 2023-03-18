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

class TyposcriptService implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    public static function parse($s)
    {
        /** @var  TypoScriptParser $oTSparser */
        $oTSparser = GeneralUtility::makeInstance(TypoScriptParser::class);
        $oTSparser->parse($s);
        return $oTSparser->setup;
    }

    public static function fold(array $a, $i=0, $keys=''): string
    {
        $c = '';
        foreach ($a as $k=>$v) {
            if (is_array($v)) {
                if (count($v) > 1) {
                    $c .= "\n" . str_repeat('  ', $i) . $keys . substr($k, 0, -1) . ' {';
                    $c .= str_repeat('  ', $i) . self::fold($v, $i+1);
                    $c .= str_repeat('  ', $i) . '}';
                } else {
                    $c .= str_repeat('  ', $i) . self::fold($v, $i, $keys . $k);
                }
            } elseif (empty($keys)) {
                $test = explode("\n", $v);
                if (count($test) > 1) {
                    $c .= "\n" . str_repeat('  ', $i) . $k . ' ( ' . "\n" . $v . "\n)";
                } else {
                    $c .= "\n" . str_repeat('  ', $i) . $k . ' = ' . $v;
                }
            } else {
                //$c .= "\n".str_repeat('  ', $i).$keys.$k.' = '.$v;
                $test = explode("\n", $v);
                if (count($test) > 1) {
                    $c .= "\n" . str_repeat('  ', $i) . $keys . $k . ' ( ' . "\n" . $v . "\n)";
                } else {
                    $c .= "\n" . str_repeat('  ', $i) . $keys . $k . ' = ' . $v;
                }
            }
        }
        return $c . "\n";
    }

    public static function cleanArray($a)
    {
        foreach ($a as $k => $v) {
            if (substr($k, -1, 1) == '.') {
                $k = substr($k, 0, -1);
            }
            $a[$k] = is_array($v) ? self::cleanArray($v) : $v;
            unset($a[$k . '.']);
        }
        return $a;
    }
}
