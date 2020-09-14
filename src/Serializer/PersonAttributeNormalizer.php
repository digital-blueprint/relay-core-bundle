<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Serializer;

use DBP\API\CoreBundle\Entity\Person;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class PersonAttributeNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'LDAP_PERSON_ATTRIBUTE_NORMALIZER_CURRENT_USER_ALREADY_CALLED';

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        // set the group "Person:current-user" for the current user
        if ($this->isCurrentUser($object)) {
            $context['groups'][] = 'Person:current-user';
        }

        $context[self::ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        // Make sure we're not called twice
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Person;
    }

    /**
     * @param Person $object
     */
    private function isCurrentUser($object): bool
    {
        $user = $this->security->getUser();

        return $user ? $user->getUsername() === $object->getIdentifier() : false;
    }
}
