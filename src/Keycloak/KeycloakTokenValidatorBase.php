<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

abstract class KeycloakTokenValidatorBase
{
    /**
     * Validates the token and returns the parsed token.
     *
     * @return array the token
     *
     * @throws TokenValidationException
     */
    abstract public function validate(string $accessToken): array;

    /**
     * Verifies that the token was created for the given audience.
     * If not then throws TokenValidationException.
     *
     * @param array  $jwt      The access token
     * @param string $audience The audience string
     *
     * @throws TokenValidationException
     */
    public static function checkAudience(array $jwt, string $audience): void
    {
        $value = $jwt['aud'] ?? [];

        if (\is_string($value)) {
            if ($value !== $audience) {
                throw new TokenValidationException('Bad audience');
            }
        } elseif (\is_array($value)) {
            if (!\in_array($audience, $value, true)) {
                throw new TokenValidationException('Bad audience');
            }
        } else {
            throw new TokenValidationException('Bad audience');
        }
    }
}
