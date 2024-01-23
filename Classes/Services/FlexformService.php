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

namespace SUDHAUS7\Sudhaus7Wizard\Services;

final class FlexformService
{
    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    public static function flatten(array $data): array
    {
        $tmp = $data['data'];
        $data = [];
        foreach ($tmp as $a) {
            foreach ($a as $aa) {
                //$data = array_merge($data,$aa);
                foreach ($aa as $name => $value) {
                    if (isset($value['vDEF'])) {
                        $data[ $name ] = $value['vDEF'];
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @param array<array-key, mixed> $data
     * @deprecated
     * @interal
     * @return array<array-key, mixed>
     */
    public static function blowup(array $data): array
    {
        $ret = [];

        foreach ($data as $k => $v) {
            $ret['sDEF']['lDEF'][$k]['vDEF'] = $v;
        }
        return ['data' => $ret];
    }
}
