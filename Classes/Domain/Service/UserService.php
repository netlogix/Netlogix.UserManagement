<?php
declare(strict_types=1);

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

    public const INACTIVE_TIMESTAMP = '2000-01-01 00:00:00';

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var AccountRepository
     */
    protected $accountRepository;

    /**
     * @var Context
     */
    protected $securityContext;

    public function __construct(
        UserRepository $userRepository,
        AccountRepository $accountRepository,
        Context $securityContext
    ) {
        $this->userRepository = $userRepository;
        $this->accountRepository = $accountRepository;
        $this->securityContext = $securityContext;
    }

    public function activateUser(User $user): void
    {
        $this->setUserActive($user, true);
    }

    public function deactivateUser(User $user): void
    {
        $this->setUserActive($user, false);
    }

    public function activateUsersByRole(string $roleIdentifier): void
    {
        $this->setUserActiveByRole($roleIdentifier, true);
    }

    public function deactivateUsersByRole(string $roleIdentifier): void
    {
        $this->setUserActiveByRole($roleIdentifier, false);
    }

    protected function setUserActiveByRole(string $roleIdentifier, bool $active): void
    {
        /** @var User $user */
        foreach ($this->userRepository->findAll() as $user) {
            $accountsWithRoles = array_filter($user->getAccounts()->toArray(),
                function (Account $account) use ($roleIdentifier) {
                    return in_array($roleIdentifier, array_map(function (Role $role) {
                        return $role->getIdentifier();
                    }, $account->getRoles()));
                });

            if (!empty($accountsWithRoles)) {
                $this->setUserActive($user, $active);
            }
        }
    }

    protected function setUserActive(User $user, bool $active): void
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
