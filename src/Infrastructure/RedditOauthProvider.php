<?php
declare(strict_types=1);

namespace App\Infrastructure;

use League\OAuth2\Client\Provider\GenericProvider;

class RedditOauthProvider extends GenericProvider
{
    public function getHeaders($token = null): array
    {
        // Pattern: <platform>:<app ID>:<version string> (by /u/<reddit username>)
        $headers = [
            'User-Agent' => 'web:saxopholis:1.0 (by /u/saxopholis)',
        ];
        // We have to use HTTP Basic Auth when requesting an access token
        if (!$token) {
            $auth = base64_encode("{$this->clientId}:{$this->clientSecret}");
            $headers['Authorization'] = 'Basic ' . $auth;
        }
        return array_merge(parent::getHeaders($token), $headers);
    }
}
