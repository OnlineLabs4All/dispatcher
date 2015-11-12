<?php
/**
 * User: Danilo G. Zutin
 * Date: 12.11.15
 * Time: 12:19
 */

// src/DispatcherBundle/Security/ApiKeyUserProvider.php
namespace DispatcherBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityManager;
use DispatcherBundle\Entity\ExperimentEngine;

class ApiKeyUserProvider implements UserProviderInterface
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getEngineIdForCredentials($credentials)
    {
        // Look up the Experiment Engine based on the provided credentials
        $engine = $this->em
            ->getRepository('DispatcherBundle:ExperimentEngine')
            ->findOneBy(array('api_key' => $credentials['apiKey'],
                              'httpAuthentication' =>  $credentials['http_basic']));

        if ($engine){
            return $engine;
        }
        return null;
    }

    public function loadUserByUsername($username)
    {

    }

    public function refreshUser(UserInterface $engine)
    {
        if (!$engine instanceof ExperimentEngine) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($engine))
            );
        }

        return $this->loadUserByUsername($engine->getId());
    }

    public function supportsClass($class)
    {
        return 'DispatcherBundle\Entity\ExperimentEngine' === $class;
    }
}
