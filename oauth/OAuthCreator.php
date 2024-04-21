<?php

class OAuthCreator
{

    public static function createGitHubOAuth(string $redirect_URL = ''): OAuth
    {
        return new GitHubOAuth('73fc17305d4b74bf14c3', '97a7720d067c6bfebc05f3e7916379377e9b81c9', 'https://github.com/login/oauth/authorize', 'https://github.com/login/oauth/access_token', 'https://api.github.com/user', $redirect_URL);
    }

    public static function createOAuth(string $provider): OAuth
    {
        switch ($provider) {
            case 'github':
                // Crea e restituisce l'oggetto GitHubOAuth
                return self::createGitHubOAuth();

            default:
                // Lancia un'eccezione o gestisci il caso in cui il provider non è supportato
                throw new InvalidArgumentException("Provider not supported: " . $provider);
        }
    }
}
