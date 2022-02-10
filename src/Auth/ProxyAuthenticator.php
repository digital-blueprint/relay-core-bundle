<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Auth;

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
    private $authenticators;

    public function __construct()
    {
        $this->authenticators = [];
    }

    public function addAuthenticator(AuthenticatorInterface $sub)
    {
        $this->authenticators[] = $sub;
    }

    private function getAuthenticator(Request $request): ?AuthenticatorInterface
    {
        foreach ($this->authenticators as $auth) {
            $supports = $auth->supports($request);
            if ($supports === null) {
                throw new \RuntimeException('Lazy authenticators not supported atm');
            }
            if ($supports === true) {
                return $auth;
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
        $auth = $this->getAuthenticator($request);
        assert($auth !== null);

        return $auth->authenticate($request);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $auth = $this->getAuthenticator($request);
        assert($auth !== null);

        return $auth->onAuthenticationSuccess($request, $token, $firewallName);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $auth = $this->getAuthenticator($request);
        assert($auth !== null);

        return $auth->onAuthenticationFailure($request, $exception);
    }
}
