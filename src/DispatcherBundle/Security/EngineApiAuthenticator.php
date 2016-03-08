<?php
/**
 * User: Danilo G. Zutin
 * Date: 06.11.15
 * Time: 13:06
 */
namespace DispatcherBundle\Security;

use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use DispatcherBundle\Entity\LabSession;

class EngineApiAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{

    public function createToken(Request $request, $providerKey)
    {
        // look for the X-apikey and Authorization headers
        $apiKey = $request->headers->get('X-apikey');
        $http_basic = $request->headers->get('Authorization');
        $credentials = array('apiKey' => $apiKey,
                             'http_basic' => $http_basic);

        if (($apiKey == null) || ($http_basic == null)) {
            throw new BadCredentialsException('No API key and/or Http basic credentials found');

        }

        return new PreAuthenticatedToken(
            'anon.',
            $credentials,
            $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if (!$userProvider instanceof ApiKeyUserProvider) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The user provider must be an instance of ApiKeyUserProvider (%s was given).',
                    get_class($userProvider)
                )
            );
        }

        $credentials = $token->getCredentials();
        $engine = $userProvider->getEngineIdForCredentials($credentials);

        if (!$engine) {
            throw new AuthenticationException(
                sprintf('API Key "%s" does not exist.', $credentials['apiKey'])
            );
        }


        return new PreAuthenticatedToken(
            $engine,
            $credentials['apiKey'],
            $providerKey,
            $engine->getRoles()
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new Response("Authentication Failed.", 401);
    }

}