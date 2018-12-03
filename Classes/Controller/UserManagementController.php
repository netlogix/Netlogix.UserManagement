<?php
namespace Netlogix\UserManagement\Controller;

/*
 * This file is part of the Netlogix.UserManagement package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Repository\UserRepository;
use Netlogix\UserManagement\Domain\Service\UserService;

class UserManagementController extends AbstractModuleController
{

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = [
		'html' => TemplateView::class,
		'json' => JsonView::class,
	];

	/**
	 * @Flow\Inject
	 * @var UserRepository
	 */
	protected $userRepository;

	/**
	 * @Flow\Inject
	 * @var UserService
	 */
	protected $userService;

	/**
	 * @Flow\Inject
	 * @var \Neos\Neos\Domain\Service\UserService
	 */
	protected $neosUserService;

	/**
	 * @Flow\Inject
	 * @var SecurityContext
	 */
	protected $securityContext;

	/**
	 * @return void
	 */
	public function indexAction()
	{
		$users = [];
		foreach ($this->userRepository->findAll() as $user) {
			$users[$this->persistenceManager->getIdentifierByObject($user)] = $user;
		}

		uasort($users, function(User $a, User $b) {
			if ($a->isActive() !== $b->isActive()) {
				return $b->isActive() <=> $a->isActive();
			}

			return $a->getName()->getFullName() <=> $b->getName()->getFullName();
		});

		$this->view->assign('currentUser', $this->neosUserService->getCurrentUser());
		$this->view->assign('users', $users);
		$this->view->assign('csrfToken', $this->securityContext->getCsrfProtectionToken());

		$uriBuilder = clone $this->uriBuilder;
		$uriBuilder->setFormat('json');
		$this->view->assign('routes', [
			'activateUser' => $uriBuilder->uriFor('activateUser', [], 'UserManagement', 'Netlogix.UserManagement'),
			'deactivateUser' => $uriBuilder->uriFor('deactivateUser', [], 'UserManagement', 'Netlogix.UserManagement'),
			'activateUsersByRole' => $uriBuilder->uriFor('activateUsersByRole', [], 'UserManagement', 'Netlogix.UserManagement'),
			'deactivateUsersByRole' => $uriBuilder->uriFor('deactivateUsersByRole', [], 'UserManagement', 'Netlogix.UserManagement'),
		]);
	}

	/**
	 * @param User $user
	 */
	public function activateUserAction(User $user)
	{
		$this->userService->activateUser($user);
	}

	/**
	 * @param User $user
	 */
	public function deactivateUserAction(User $user)
	{
		$this->userService->deactivateUser($user);
	}

	/**
	 * @param string $roleIdentifier
	 */
	public function activateUsersByRoleAction(string $roleIdentifier)
	{
		$this->userService->activateUsersByRole($roleIdentifier);
	}

	/**
	 * @param string $roleIdentifier
	 */
	public function deactivateUsersByRoleAction(string $roleIdentifier)
	{
		$this->userService->deactivateUsersByRole($roleIdentifier);
	}
}
