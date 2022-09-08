<?php

declare(strict_types=1);

namespace JambageCom\Agency\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Test case
 *
 * @author Franz Holzinger <franz@ttproducts.de>
 */
class FrontendUserControllerTest extends UnitTestCase
{
    /**
     * @var \JambageCom\Agency\Controller\FrontendUserController|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder($this->buildAccessibleProxy(\JambageCom\Agency\Controller\FrontendUserController::class))
            ->onlyMethods(['redirect', 'forward', 'addFlashMessage'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function showActionAssignsTheGivenFrontendUserToView(): void
    {
        $frontendUser = new \JambageCom\Agency\Domain\Model\FrontendUser();

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $this->subject->_set('view', $view);
        $view->expects(self::once())->method('assign')->with('frontendUser', $frontendUser);

        $this->subject->showAction($frontendUser);
    }

    /**
     * @test
     */
    public function createActionAddsTheGivenFrontendUserToFrontendUserRepository(): void
    {
        $frontendUser = new \JambageCom\Agency\Domain\Model\FrontendUser();

        $frontendUserRepository = $this->getMockBuilder(\JambageCom\Agency\Domain\Repository\FrontendUserRepository::class)
            ->onlyMethods(['add'])
            ->disableOriginalConstructor()
            ->getMock();

        $frontendUserRepository->expects(self::once())->method('add')->with($frontendUser);
        $this->subject->_set('frontendUserRepository', $frontendUserRepository);

        $this->subject->createAction($frontendUser);
    }

    /**
     * @test
     */
    public function editActionAssignsTheGivenFrontendUserToView(): void
    {
        $frontendUser = new \JambageCom\Agency\Domain\Model\FrontendUser();

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $this->subject->_set('view', $view);
        $view->expects(self::once())->method('assign')->with('frontendUser', $frontendUser);

        $this->subject->editAction($frontendUser);
    }

    /**
     * @test
     */
    public function updateActionUpdatesTheGivenFrontendUserInFrontendUserRepository(): void
    {
        $frontendUser = new \JambageCom\Agency\Domain\Model\FrontendUser();

        $frontendUserRepository = $this->getMockBuilder(\JambageCom\Agency\Domain\Repository\FrontendUserRepository::class)
            ->onlyMethods(['update'])
            ->disableOriginalConstructor()
            ->getMock();

        $frontendUserRepository->expects(self::once())->method('update')->with($frontendUser);
        $this->subject->_set('frontendUserRepository', $frontendUserRepository);

        $this->subject->updateAction($frontendUser);
    }

    /**
     * @test
     */
    public function deleteActionRemovesTheGivenFrontendUserFromFrontendUserRepository(): void
    {
        $frontendUser = new \JambageCom\Agency\Domain\Model\FrontendUser();

        $frontendUserRepository = $this->getMockBuilder(\JambageCom\Agency\Domain\Repository\FrontendUserRepository::class)
            ->onlyMethods(['remove'])
            ->disableOriginalConstructor()
            ->getMock();

        $frontendUserRepository->expects(self::once())->method('remove')->with($frontendUser);
        $this->subject->_set('frontendUserRepository', $frontendUserRepository);

        $this->subject->deleteAction($frontendUser);
    }
}
