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
use Pepperfm\DonationalertsAuth\DonationalertsAuth;
use ReflectionClass;
use SocialiteProviders\Manager\OAuth2\User;

beforeEach(function () {
    $this->provider = new DonationalertsAuth(
        new Request(),
        'client-id',
        'client-secret',
        'https://example.com/callback'
    );
});

test('builds authorization url with expected query parameters', function () {
    $method = (new ReflectionClass($this->provider))->getMethod('getAuthUrl');
    $method->setAccessible(true);

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
    $method->setAccessible(true);

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
    $method->setAccessible(true);

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
    $method->setAccessible(true);

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
