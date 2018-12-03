<?php
namespace Netlogix\UserManagement\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\Role;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Repository\UserRepository;

/**
 * @Flow\Scope("singleton")
 */
class UserService
{

	const INACTIVE_TIMESTAMP = '2000-01-01 00:00:00';

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var UserRepository
	 */
	protected $userRepository;

	/**
	 * @Flow\Inject
	 * @var AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @param User $user
	 */
	public function activateUser(User $user)
	{
		$this->setUserActive($user, true);
	}

	/**
	 * @param User $user
	 */
	public function deactivateUser(User $user)
	{
		$this->setUserActive($user, false);
	}

	/**
	 * @param string $roleIdentifier
	 */
	public function activateUsersByRole(string $roleIdentifier)
	{
		$this->setUserActiveByRole($roleIdentifier, true);
	}

	/**
	 * @param string $roleIdentifier
	 */
	public function deactivateUsersByRole(string $roleIdentifier)
	{
		$this->setUserActiveByRole($roleIdentifier, false);
	}

	/**
	 * @param string $roleIdentifier
	 * @param bool $active
	 */
	protected function setUserActiveByRole(string $roleIdentifier, $active)
	{
		/** @var User $user */
		foreach ($this->userRepository->findAll() as $user) {
			$accountsWithRoles = array_filter($user->getAccounts()->toArray(), function(Account $account) use ($roleIdentifier) {
				return in_array($roleIdentifier, array_map(function(Role $role) {
					return $role->getIdentifier();
				}, $account->getRoles()));
			});

			if (!empty($accountsWithRoles)) {
				$this->setUserActive($user, $active);
			}
		}
	}

	/**
	 * @param User $user
	 * @param bool $active
	 */
	protected function setUserActive(User $user, $active)
	{
		foreach ($user->getAccounts() as $account) {
			if ($this->securityContext->isInitialized() && $this->securityContext->getAccount() === $account) {
				continue;
			}

			$account->setExpirationDate($active ? null : new \DateTime(self::INACTIVE_TIMESTAMP));
			$this->accountRepository->update($account);
		}
	}

}
