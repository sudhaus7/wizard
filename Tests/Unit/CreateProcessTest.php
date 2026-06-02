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

namespace SUDHAUS7\Sudhaus7Wizard\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CreateProcessTest extends UnitTestCase
{
    protected CreateProcess $create_process;

    protected function setUp(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $this->create_process = new CreateProcess($eventDispatcher);
        $this->create_process->pageMap = [
            1 => 10,
            2 => 20,
            3 => 30,
        ];
        $this->create_process->contentMap = [
            'pages' => $this->create_process->pageMap,
            'tt_content' => $this->create_process->pageMap,
            'sys_category' => [
                1 => 11,
                2 => 21,
                3 => 31,
            ],
            'sys_file' => $this->create_process->pageMap,
        ];

        parent::setUp();
    }

    #[Test]
    public function getTranslateUidTranslatesUidForPages(): void
    {
        self::assertEquals(10, $this->create_process->getTranslateUid('pages', 1));
    }

    #[Test]
    public function getTranslateUidTranslatesUidReturnsOriginalUidIfNotInTranslationTable(): void
    {
        self::assertEquals(100, $this->create_process->getTranslateUid('tt_content', 100));
    }

    #[Test]
    public function getTranslateUidTranslatesUidReturnsOriginalTablePrefixAndUidIfNotInTranslationTable(): void
    {
        self::assertEquals('tt_content_100', $this->create_process->getTranslateUid('tt_content', 'tt_content_100'));
    }

    #[Test]
    public function getTranslateUidTranslatesUidForTtContent(): void
    {
        self::assertEquals(10, $this->create_process->getTranslateUid('tt_content', 1));
    }

    #[Test]
    public function getTranslateUidTranslatesUidForTtContentWithTablePrefix(): void
    {
        self::assertEquals('tt_content_10', $this->create_process->getTranslateUid('tt_content', 'tt_content_1'));
    }

    #[Test]
    public function getTranslateUidTranslatesUidForTtContentWithDifferentTablePrefix(): void
    {
        self::assertEquals('pages_10', $this->create_process->getTranslateUid('tt_content', 'pages_1'));
    }

    #[Test]
    public function getTranslateIDlistTranslatesListOfUids(): void
    {
        self::assertEquals('10,20,30', $this->create_process->translateIDlist('tt_content', '1,2,3'));
    }

    #[Test]
    public function getTranslateIDlistTranslatesListOfTablePrefixedUids(): void
    {
        self::assertEquals('pages_10,pages_20,pages_30', $this->create_process->translateIDlist('pages', 'pages_1,pages_2,pages_3'));
    }

    #[Test]
    public function getTranslateIDlistTranslatesListOfMixedTablePrefixedUids(): void
    {
        self::assertEquals('tt_content_10,sys_category_21,pages_30', $this->create_process->translateIDlist('pages', 'tt_content_1,sys_category_2,pages_3'));
    }

    #[Test]
    public function getTranslateT3LinkStringTranslatesLinkString(): void
    {
        self::assertEquals('t3://page?uid=10', $this->create_process->translateT3LinkString('t3://page?uid=1'));
        self::assertEquals('t3://file?uid=10', $this->create_process->translateT3LinkString('t3://file?uid=1'));
    }

    #[Test]
    public function getTranslateT3LinkStringTranslatesPageAndAnchor(): void
    {
        self::assertEquals('t3://page?uid=10#20', $this->create_process->translateT3LinkString('t3://page?uid=1#2'));
    }

    #[Test]
    public function getTranslateT3LinkStringTranslatesClassicLinkString(): void
    {
        self::assertEquals('file:10', $this->create_process->translateT3LinkString('file:1'));
    }
    //t3://page?uid=6#212 _blank cssclass "My great link"

    #[Test]
    public function getTranslateTypolinkStringTranslatesWizardGeneratedLinkConfig(): void
    {
        self::assertEquals('t3://page?uid=10#20 _blank cssclass My great link', $this->create_process->translateTypolinkString('t3://page?uid=1#2 _blank cssclass "My great link"'));
    }

    #[Test]
    public function getTranslateUidReverseReversesAtranslatedUid(): void
    {
        self::assertEquals(1, $this->create_process->getTranslateUidReverse('pages', 10));
        self::assertEquals(1, $this->create_process->getTranslateUidReverse('tt_content', 10));
    }
}
