<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Authorization implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const PRIVILEGE_NAME_ATTRIBUTE = 'name';
    public const PRIVILEGE_EXPRESSION_ATTRIBUTE = 'expression';

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var array */
    private $privileges;

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        $this->privileges = [];
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function addPrivileges(array $privileges)
    {
        foreach ($privileges as $privilege) {
            $this->privileges[$privilege[self::PRIVILEGE_NAME_ATTRIBUTE]] = $privilege[self::PRIVILEGE_EXPRESSION_ATTRIBUTE];
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function hasPrivilege(string $privilegeName, $subject = null): bool
    {
        $privilegeExpression = $this->privileges[$privilegeName] ?? null;
        if ($privilegeExpression === null) {
            throw new AuthorizationException(sprintf('privilege \'%s\' undefined', $privilegeName), AuthorizationException::PRIVILEGE_UNDEFINED);
        }

        return $this->expressionLanguage->evaluate($privilegeExpression, [
            'user' => $this->getCurrentUser(),
            'subject' => $subject,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function hasRole(string $roleName): bool
    {
        return $this->getCurrentUser()->hasRole($roleName);
    }

    /**
     * @return mixed|null
     *
     * @throws AuthorizationException
     */
    public function getAttribute(string $attributeName)
    {
        return $this->getCurrentUser()->getAttribute($attributeName);
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user instanceof UserInterface) {
            return $user;
        } else {
            throw new AuthorizationException('current user not available', AuthorizationException::USER_NOT_AVAILABLE);
        }
    }
}
