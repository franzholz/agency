<?php

declare(strict_types=1);

namespace JambageCom\Agency\Controller;


/**
 * This file is part of the "Franz Holzinger" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Franz Holzinger <franz@ttproducts.de>, jambage.com
 */

/**
 * FrontendUserController
 */
class FrontendUserController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * frontendUserRepository
     *
     * @var \JambageCom\Agency\Domain\Repository\FrontendUserRepository
     */
    protected $frontendUserRepository = null;

    /**
     * @param \JambageCom\Agency\Domain\Repository\FrontendUserRepository $frontendUserRepository
     */
    public function injectFrontendUserRepository(\JambageCom\Agency\Domain\Repository\FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    /**
     * action index
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function indexAction(): \Psr\Http\Message\ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * action show
     *
     * @param \JambageCom\Agency\Domain\Model\FrontendUser $frontendUser
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function showAction(\JambageCom\Agency\Domain\Model\FrontendUser $frontendUser): \Psr\Http\Message\ResponseInterface
    {
        $this->view->assign('frontendUser', $frontendUser);
        return $this->htmlResponse();
    }

    /**
     * action new
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function newAction(): \Psr\Http\Message\ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * action create
     *
     * @param \JambageCom\Agency\Domain\Model\FrontendUser $newFrontendUser
     */
    public function createAction(\JambageCom\Agency\Domain\Model\FrontendUser $newFrontendUser)
    {
        $this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See https://docs.typo3.org/p/friendsoftypo3/extension-builder/master/en-us/User/Index.html', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
        $this->frontendUserRepository->add($newFrontendUser);
        $this->redirect('list');
    }

    /**
     * action edit
     *
     * @param \JambageCom\Agency\Domain\Model\FrontendUser $frontendUser
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("frontendUser")
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function editAction(\JambageCom\Agency\Domain\Model\FrontendUser $frontendUser): \Psr\Http\Message\ResponseInterface
    {
        $this->view->assign('frontendUser', $frontendUser);
        return $this->htmlResponse();
    }

    /**
     * action update
     *
     * @param \JambageCom\Agency\Domain\Model\FrontendUser $frontendUser
     */
    public function updateAction(\JambageCom\Agency\Domain\Model\FrontendUser $frontendUser)
    {
        $this->addFlashMessage('The object was updated. Please be aware that this action is publicly accessible unless you implement an access check. See https://docs.typo3.org/p/friendsoftypo3/extension-builder/master/en-us/User/Index.html', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
        $this->frontendUserRepository->update($frontendUser);
        $this->redirect('list');
    }

    /**
     * action delete
     *
     * @param \JambageCom\Agency\Domain\Model\FrontendUser $frontendUser
     */
    public function deleteAction(\JambageCom\Agency\Domain\Model\FrontendUser $frontendUser)
    {
        $this->addFlashMessage('The object was deleted. Please be aware that this action is publicly accessible unless you implement an access check. See https://docs.typo3.org/p/friendsoftypo3/extension-builder/master/en-us/User/Index.html', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
        $this->frontendUserRepository->remove($frontendUser);
        $this->redirect('list');
    }

    /**
     * action
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function Action(): \Psr\Http\Message\ResponseInterface
    {
        return $this->htmlResponse();
    }
}
