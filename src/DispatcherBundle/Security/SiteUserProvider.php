<?php
/**
 * User: Danilo G. Zutin
 * Date: 04.08.15
 * Time: 10:31
 */

namespace DispatcherBundle\Security;

use DispatcherBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;


class SiteUserProvider implements UserProviderInterface
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function loadUserByUsername($username)
    {
        // make a call to your database here to search for the user
        $user = $this
            ->em
            ->getRepository('DispatcherBundle:User')
            ->findOneBy(array('username' => $username));

        // pretend it returns an array on success, false if there is no user
        if ($user) {
            return $user;
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'DispatcherBundle\Entity\User';
    }
}