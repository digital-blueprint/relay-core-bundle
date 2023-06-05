<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Auth;

use Dbp\Relay\CoreBundle\API\UserSessionProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class ProxyAuthenticator extends AbstractAuthenticator
{
    /**
     * @var AuthenticatorInterface[]
     */
    private $authenticators = [];

    /**
     * @var UserSession
     */
    private $userSession;

    /**
     * @var UserSessionProviderInterface|null
     */
    private $userSessionProvider;

    public function __construct(UserSession $userSession)
    {
        $this->userSession = $userSession;
    }

    public function addAuthenticator(AuthenticatorInterface $sub)
    {
        $this->authenticators[] = $sub;
    }

    private function getAuthenticator(Request $request): ?AuthenticatorInterface
    {
        foreach ($this->authenticators as $authenticator) {
            $supports = $authenticator->supports($request);
            if ($supports === null) {
                throw new AuthenticationException('Lazy authenticators not supported atm');
            }
            if ($supports === true) {
                return $authenticator;
            }
        }

        return null;
    }

    public function supports(Request $request): ?bool
    {
        return $this->getAuthenticator($request) !== null;
    }

    public function authenticate(Request $request): Passport
    {
        $authenticator = $this->getAuthenticator($request);
        if ($authenticator === null) {
            throw new AuthenticationException('no suitable authenticator found for request');
        }

        $passport = $authenticator->authenticate($request);

        $this->userSessionProvider = $passport->getAttribute('relay_user_session_provider');
        if ($this->userSessionProvider === null) {
            throw new AuthenticationException('failed to get user session provider for current authenticator');
        }

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->userSession->setProvider($this->userSessionProvider);

        return $this->getAuthenticator($request)->onAuthenticationSuccess($request, $token, $firewallName);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->userSession->setProvider(null);

        return $this->getAuthenticator($request)->onAuthenticationFailure($request, $exception);
    }
}
