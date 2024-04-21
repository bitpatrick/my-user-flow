<?php

abstract class OAuth
{
    protected string $client_id;
    protected string $client_secret;
    protected string $authorize_URL;
    protected string $access_token_URL;
    protected string $user_info_URL;
    protected string $redirect_URL;

    public function __construct(string $client_id, string $client_secret, string $authorize_URL, string $access_token_URL, string $user_info_URL, string $redirect_URL = '')
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->authorize_URL = $authorize_URL;
        $this->access_token_URL = $access_token_URL;
        $this->user_info_URL = $user_info_URL;
        $this->redirect_URL = $redirect_URL;
    }

    abstract public function getLoginPage(): string;

    abstract public function exchangeAuthCodeForToken(string $code): string;

    abstract public function getUserInfo($accessToken): array;
}
