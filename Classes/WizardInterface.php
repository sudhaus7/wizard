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

namespace SUDHAUS7\Sudhaus7Wizard;

interface WizardInterface
{
    /**
     * @return array
     */
    public static function getConfig();

    /**
     * @param array $data
     * @return bool
     */
    public static function checkConfig($data);

    /**
     * @return array
     */
    public function getTemplateUser();

    /**
     * @return array
     */
    public function getTemplateGroup();

    /**
     * @return array
     */
    public function getMediaBaseDir();

    public function finalize(Create &$pObj);
}
