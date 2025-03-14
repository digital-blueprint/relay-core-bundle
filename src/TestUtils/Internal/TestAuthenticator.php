<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils\Internal;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
class TestAuthenticator extends AbstractAuthenticator
{
    public const TEST_TOKEN = '42';
    public const TEST_AUTHORIZATION_HEADER = 'Bearer '.self::TEST_TOKEN;

    private ?TestUser $user = null;

    private ?string $token = null;

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    public function setUser(TestUser $user): void
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

    public function authenticate(Request $request): Passport
    {
        if ($this->user === null) {
            throw new \AssertionError('No user set. Make sure to create a new test client for each request, or call disableReboot() on the test client.');
        }

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

        $passport->setAttribute('relay_user_session_provider', new TestUserSessionProvider($this->user->isServiceAccount() ? null : $this->user->getUserIdentifier()));

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }
}
