<?php

/**
 * GitHub Implementation
 */
class GitHubOAuth extends OAuth
{

    public function getLoginPage(): string
    {
        $_SESSION['state'] = hash('sha256', microtime(TRUE) . rand() . $_SERVER['REMOTE_ADDR']);
        unset($_SESSION['access_token']);

        $params = [
            'client_id' => $this->client_id,
            'scope' => 'read:user',
            'state' => $_SESSION['state']
        ];

        $redirect_to = 'Location: ' . $this->authorize_URL . '?' . http_build_query($params);

        return $redirect_to;
    }

    public function exchangeAuthCodeForToken(string $code): string
    {
        $body = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code,
        ];

        $args = [
            'body' => $body,
            'headers' => ['Accept' => 'application/json'],
        ];

        $response = wp_remote_post($this->access_token_URL, $args);

        $body_response = wp_remote_retrieve_body($response);

        $array = json_decode($body_response, true);

        return $array['access_token'];
    }

    public function getUserInfo($accessToken): array
    {
        $response = wp_remote_get($this->user_info_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'User-Agent' => 'WordPress'
            ]
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
