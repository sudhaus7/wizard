<?php

/*
 * This file is part of the TYPO3 project.
 * (c) 2022 B-Factor GmbH
 *          Sudhaus7
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 * The TYPO3 project - inspiring people to share!
 * @copyright 2022 B-Factor GmbH https://b-factor.de/
 * @author Frank Berger <fberger@b-factor.de>
 * @author Daniel Simon <dsimon@b-factor.de>
 */

namespace SUDHAUS7\Sudhaus7Wizard;

/**
 * Created by PhpStorm.
 * User: frank
 * Date: 30/06/16
 * Time: 18:20
 */
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
