<?php
declare(strict_types=1);

namespace Netlogix\UserManagement\Controller;

use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
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
	 * @Flow\Inject
	 * @var PolicyService
	 */
	protected $policyService;

	public function indexAction(): void
	{
		$users = [];
		foreach ($this->userRepository->findAll() as $user) {
			$users[$this->persistenceManager->getIdentifierByObject($user)] = $user;
		}

		uasort($users, static function(User $a, User $b): int {
			if ($a->isActive() !== $b->isActive()) {
				return $b->isActive() <=> $a->isActive();
			}
			if ($a->getName()->getLastName() !== $b->getName()->getLastName()) {
				return $a->getName()->getLastName() <=> $b->getName()->getLastName();
			}

			return $a->getName()->getFirstName() <=> $b->getName()->getFirstName();
		});

		$roles = $this->policyService->getRoles();
		usort($roles, static function(Role $a, Role $b): int {
			return $a->getName() <=> $b->getName();
		});

		$this->view->assign('currentUser', $this->neosUserService->getCurrentUser());
		$this->view->assign('users', $users);
		$this->view->assign('roles', $roles);
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
