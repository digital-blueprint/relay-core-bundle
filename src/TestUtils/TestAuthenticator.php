<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TestAuthenticator extends AbstractAuthenticator
{
    /** @var TestUser */
    private $user;

    /** @var string */
    private $token;

    /** @var TestUserSession */
    private $userSession;

    public function __construct(TestUserSession $userSession)
    {
        $this->userSession = $userSession;
    }

    public function setToken(?string $token)
    {
        $this->token = $token;
    }

    public function setUser(TestUser $user)
    {
        $this->user = $user;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): PassportInterface
    {
        assert($this->user !== null);

        if ($this->token === null) {
            throw new BadCredentialsException('Invalid token');
        } else {
            $auth = $request->headers->get('Authorization', '');
            if ($auth === '') {
                throw new BadCredentialsException('Token is not present in the request headers');
            }
            $token = trim(preg_replace('/^(?:\s+)?Bearer\s/', '', $auth));

            if ($token !== $this->token) {
                throw new BadCredentialsException('Invalid token');
            }
        }

        $passport = new SelfValidatingPassport(new UserBadge($this->token, function ($token) {
            return $this->user;
        }));

        $passport->setAttribute('relay_user_session_provider', new TestUserSessionProvider($this->user->getUserIdentifier()));

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->userSession->setIdentifier($this->user->getUserIdentifier());
        $this->userSession->setRoles($this->user->getRoles());
        $this->userSession->setIsAuthenticated(true);

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
    }
}
