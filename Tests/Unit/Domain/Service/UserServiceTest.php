<?php
declare(strict_types=1);

namespace Netlogix\UserManagement\Tests\Unit\Domain\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Repository\UserRepository;
use Netlogix\UserManagement\Domain\Service\UserService;

class UserServiceTest extends UnitTestCase
{

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var UserRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userRepository;

    /**
     * @var AccountRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $accountRepository;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $securityContext;

    protected function setUp(): void
    {
        $this->userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->accountRepository = $this->getMockBuilder(AccountRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userService = new UserService(
            $this->userRepository,
            $this->accountRepository,
            $this->securityContext
        );
    }

    /**
     * @test
     */
    public function activating_a_user_will_remove_the_expiration_date_from_their_accounts(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $account = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $account
            ->expects(self::exactly(2))
            ->method('setExpirationDate')
            ->with(null);

        $user
            ->expects(self::once())
            ->method('getAccounts')
            ->willReturn(new ArrayCollection([$account, $account]));

        $this
            ->accountRepository
            ->expects(self::exactly(2))
            ->method('update')
            ->with($account);

        $this->userService->activateUser($user);
    }

    /**
     * @test
     */
    public function deactivating_a_user_will_set_the_expiration_date_on_all_their_accounts(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $account = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $account
            ->expects(self::exactly(2))
            ->method('setExpirationDate')
            ->with(new \DateTime(UserService::INACTIVE_TIMESTAMP));

        $user
            ->expects(self::once())
            ->method('getAccounts')
            ->willReturn(new ArrayCollection([$account, $account]));

        $this
            ->accountRepository
            ->expects(self::exactly(2))
            ->method('update')
            ->with($account);

        $this->userService->deactivateUser($user);
    }

    /**
     * @test
     * @dataProvider provideModificationMethodsForSingleUser
     */
    public function the_currently_authenticated_user_wont_be_changed(string $method): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $account = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $account
            ->expects(self::never())
            ->method('setExpirationDate');

        $user
            ->expects(self::once())
            ->method('getAccounts')
            ->willReturn(new ArrayCollection([$account]));

        $this
            ->accountRepository
            ->expects(self::never())
            ->method('update')
            ->with($account);

        $this
            ->securityContext
            ->expects(self::once())
            ->method('isInitialized')
            ->willReturn(true);

        $this
            ->securityContext
            ->expects(self::once())
            ->method('getAccount')
            ->willReturn($account);

        $this->userService->{$method}($user);
    }

    public function provideModificationMethodsForSingleUser(): iterable
    {
        yield 'When activating user' => ['method' => 'activateUser'];
        yield 'When deactivating user' => ['method' => 'deactivateUser'];
    }

    /**
     * @test
     */
    public function users_can_be_activated_by_role(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $account = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $role = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();

        $role
            ->method('getIdentifier')
            ->willReturn('Netlogix.UserManagement:Foo');

        $account
            ->expects(self::exactly(4))
            ->method('setExpirationDate')
            ->with(null);

        $account
            ->expects(self::exactly(4))
            ->method('getRoles')
            ->willReturn([$role]);

        $user
            ->expects(self::exactly(4))
            ->method('getAccounts')
            ->willReturn(new ArrayCollection([$account, $account]));

        $this
            ->userRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn(new ArrayQueryResult([$user, $user]));

        $this
            ->accountRepository
            ->expects(self::exactly(4))
            ->method('update')
            ->with($account);

        $this->userService->activateUsersByRole('Netlogix.UserManagement:Foo');
    }

    /**
     * @test
     */
    public function users_can_be_deactivated_by_role(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $account = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $role = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();

        $role
            ->method('getIdentifier')
            ->willReturn('Netlogix.UserManagement:Foo');

        $account
            ->expects(self::exactly(4))
            ->method('setExpirationDate')
            ->with(new \DateTime(UserService::INACTIVE_TIMESTAMP));

        $account
            ->expects(self::exactly(4))
            ->method('getRoles')
            ->willReturn([$role]);

        $user
            ->expects(self::exactly(4))
            ->method('getAccounts')
            ->willReturn(new ArrayCollection([$account, $account]));

        $this
            ->userRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn(new ArrayQueryResult([$user, $user]));

        $this
            ->accountRepository
            ->expects(self::exactly(4))
            ->method('update')
            ->with($account);

        $this->userService->deactivateUsersByRole('Netlogix.UserManagement:Foo');
    }

    /**
     * @test
     * @dataProvider provideModificationMethodsForMultipleUsers
     */
    public function when_changing_users_by_role_only_users_with_accounts_with_the_given_role_are_affected(string $method): void
    {
        $fooRole = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fooRole
            ->method('getIdentifier')
            ->willReturn('Netlogix.UserManagement:Foo');

        $barRole = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();

        $barRole
            ->method('getIdentifier')
            ->willReturn('Netlogix.UserManagement:Bar');

        $accountA = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountA
            ->method('getRoles')
            ->willReturn([$fooRole]);

        $accountB = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountB
            ->method('getRoles')
            ->willReturn([$barRole]);


        $userA = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userA
            ->method('getAccounts')
            ->willReturn(new ArrayCollection([$accountA]));

        $userB = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userB
            ->method('getAccounts')
            ->willReturn(new ArrayCollection([$accountB]));

        $accountA
            ->expects(self::once())
            ->method('setExpirationDate');

        $accountB
            ->expects(self::never())
            ->method('setExpirationDate');

        $this
            ->userRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn(new ArrayQueryResult([$userA, $userB]));

        $this->userService->{$method}('Netlogix.UserManagement:Foo');
    }

    public function provideModificationMethodsForMultipleUsers(): iterable
    {
        yield 'When activating users' => ['method' => 'activateUsersByRole'];
        yield 'When deactivating users' => ['method' => 'deactivateUsersByRole'];
    }

    /**
     * @test
     * @dataProvider provideModificationMethodsForMultipleUsers
     */
    public function when_changing_users_by_role_the_currently_authenticated_user_is_not_changed(string $method): void
    {
        $fooRole = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fooRole
            ->method('getIdentifier')
            ->willReturn('Netlogix.UserManagement:Foo');

        $accountA = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountA
            ->method('getRoles')
            ->willReturn([$fooRole]);

        $accountB = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountB
            ->method('getRoles')
            ->willReturn([$fooRole]);

        $userA = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userA
            ->method('getAccounts')
            ->willReturn(new ArrayCollection([$accountA]));

        $userB = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userB
            ->method('getAccounts')
            ->willReturn(new ArrayCollection([$accountB]));

        $accountA
            ->expects(self::never())
            ->method('setExpirationDate');

        $accountB
            ->expects(self::once())
            ->method('setExpirationDate');

        $this
            ->userRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn(new ArrayQueryResult([$userA, $userB]));

        $this
            ->securityContext
            ->method('isInitialized')
            ->willReturn(true);

        $this
            ->securityContext
            ->method('getAccount')
            ->willReturn($accountA);

        $this->userService->{$method}('Netlogix.UserManagement:Foo');
    }

}
