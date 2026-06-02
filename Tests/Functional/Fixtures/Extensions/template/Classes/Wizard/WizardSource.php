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

namespace SUDHAUS7\Sudhaus7Template\Wizard;

use SUDHAUS7\Sudhaus7Wizard\Services\RestWizardRequest;
use SUDHAUS7\Sudhaus7Wizard\Sources\RestWizardServerSource;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class WizardSource extends RestWizardServerSource
{
    public function getAPI(): RestWizardRequest
    {
        $api = GeneralUtility::makeInstance(RestWizardRequest::class);
        $api->setAPIHOST('https://ekbo.de/');
        $api->setAPIURL('wizard-server/index.php/');
        $api->setAPISHAREDSECRET('EJF9qmf_jdn6cac5gux');
        return $api;
    }
}
