<?php
declare(strict_types=1);

namespace Netlogix\UserManagement\Controller;

use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Repository\UserRepository;
use Netlogix\UserManagement\Domain\Service\UserService;
use Neos\Neos\Domain\Service\UserService as NeosUserService;

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
	 * @var NeosUserService
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

	public function activateUserAction(User $user): void
	{
		$this->userService->activateUser($user);

		$this->addFlashMessage('User "%s" was activated!', '', Message::SEVERITY_OK, [$user->getLabel()]);
	}

	public function deactivateUserAction(User $user): void
	{
		$this->userService->deactivateUser($user);

		$this->addFlashMessage('User "%s" was deactivated!', '', Message::SEVERITY_OK, [$user->getLabel()]);
	}

	public function activateUsersByRoleAction(string $roleIdentifier): void
	{
		$this->userService->activateUsersByRole($roleIdentifier);

		$this->addFlashMessage('Users with Role "%s" were activated!', '', Message::SEVERITY_OK, [$roleIdentifier]);
	}

	public function deactivateUsersByRoleAction(string $roleIdentifier): void
	{
		$this->userService->deactivateUsersByRole($roleIdentifier);

		$this->addFlashMessage('Users with Role "%s" were deactivated!', '', Message::SEVERITY_OK, [$roleIdentifier]);
	}
}
