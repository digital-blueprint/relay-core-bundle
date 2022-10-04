<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Extends the UserInterface by a hasPrivilege method, which allows the usage of privileges in privilege expression.
 */
class UserExtender implements UserInterface
{
    /** @var UserInterface */
    private $user;

    /** @var array */
    private $privileges;

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    public function __construct(UserInterface $user, array $privileges)
    {
        $this->user = $user;
        $this->privileges = $privileges;
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function hasRole(string $roleName): bool
    {
        return $this->user->hasRole($roleName);
    }

    public function getAttribute(string $attributeName)
    {
        return $this->user->getAttribute($attributeName);
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
            'user' => $this,
            'subject' => $subject,
        ]);
    }
}
