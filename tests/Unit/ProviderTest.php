<?php

declare(strict_types=1);

namespace Pepperfm\DonationalertsAuth\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\Token;
use Pepperfm\DonationalertsAuth\Provider;
use ReflectionClass;
use SocialiteProviders\Manager\OAuth2\User;

class ProviderTest extends Provider
{
    public array $fakeRefreshTokenResponse = [];

    public ?string $refreshTokenPassed = null;

    protected function getRefreshTokenResponse($refreshToken)
    {
        $this->refreshTokenPassed = $refreshToken;

        return $this->fakeRefreshTokenResponse;
    }
}

beforeEach(function () {
    $this->provider = new Provider(
        new Request(),
        'client-id',
        'client-secret',
        'https://example.com/callback'
    );
});

test('builds authorization url with expected query parameters', function () {
    $method = (new ReflectionClass($this->provider))->getMethod('getAuthUrl');

    $url = $method->invoke($this->provider, 'state-123');
    $parsed = parse_url($url);

    expect($parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'])
        ->toBe('https://www.donationalerts.com/oauth/authorize');

    parse_str($parsed['query'], $query);

    expect($query)->toEqual([
        'client_id' => 'client-id',
        'redirect_uri' => 'https://example.com/callback',
        'scope' => 'oauth-user-show',
        'response_type' => 'code',
        'state' => 'state-123',
    ]);
});

test('returns the token url', function () {
    $method = (new ReflectionClass($this->provider))->getMethod('getTokenUrl');

    expect($method->invoke($this->provider))
        ->toBe('https://www.donationalerts.com/oauth/token');
});

test('requests user info with a bearer token and returns the decoded payload', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['data' => ['id' => 10]])),
    ]);
    $history = [];
    $handler = HandlerStack::create($mock);
    $handler->push(Middleware::history($history));
    $client = new Client(['handler' => $handler]);

    $this->provider->setHttpClient($client);

    $method = (new ReflectionClass($this->provider))->getMethod('getUserByToken');

    $payload = $method->invoke($this->provider, 'test-token');

    expect($payload)->toEqual(['data' => ['id' => 10]])
        ->and($history)->toHaveCount(1);

    $request = $history[0]['request'];
    expect((string) $request->getUri())->toBe('https://www.donationalerts.com/api/v1/user/oauth')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer test-token')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json');
});

test('maps the user array to a socialite user object', function () {
    $method = (new ReflectionClass($this->provider))->getMethod('mapUserToObject');

    $user = $method->invoke($this->provider, [
        'data' => [
            'id' => 1,
            'code' => 'nickname',
            'name' => 'Full Name',
            'email' => 'user@example.com',
            'avatar' => 'https://example.com/avatar.png',
        ],
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->getId())->toBe(1)
        ->and($user->getNickname())->toBe('nickname')
        ->and($user->getName())->toBe('Full Name')
        ->and($user->getEmail())->toBe('user@example.com')
        ->and($user->getAvatar())->toBe('https://example.com/avatar.png')
        ->and($user->getRaw())->toEqual([
            'id' => 1,
            'code' => 'nickname',
            'name' => 'Full Name',
            'email' => 'user@example.com',
            'avatar' => 'https://example.com/avatar.png',
        ]);
});

test('returns a token object when refreshing without scopes in the response', function () {
    $provider = new ProviderTest(
        new Request(),
        'client-id',
        'client-secret',
        'https://example.com/callback'
    );
    $provider->fakeRefreshTokenResponse = [
        'access_token' => 'new-access',
        'refresh_token' => 'new-refresh',
        'expires_in' => 321,
    ];

    $token = $provider->refreshToken('refresh-token');

    expect($provider->refreshTokenPassed)->toBe('refresh-token')
        ->and($token)->toBeInstanceOf(Token::class)
        ->and($token->token)->toBe('new-access')
        ->and($token->refreshToken)->toBe('new-refresh')
        ->and($token->expiresIn)->toBe(321)
        ->and($token->approvedScopes)->toBe(['oauth-user-show']);
});
