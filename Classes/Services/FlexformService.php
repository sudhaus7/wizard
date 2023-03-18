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

class FlexformService
{
    public static function flatten($data): array
    {
        $tmp = $data['data'];
        $data = [];
        foreach ($tmp as $a) {
            foreach ($a as $aa) {
                //$data = array_merge($data,$aa);
                foreach ($aa as $name => $value) {
                    $data[$name] = $value['vDEF'];
                }
            }
        }
        return $data;
    }

    public static function blowup($data)
    {
        $ret = [];

        foreach ($data as $k => $v) {
            $ret['sDEF']['lDEF'][$k]['vDEF'] = $v;
        }
        return ['data' => $ret];
    }
}
