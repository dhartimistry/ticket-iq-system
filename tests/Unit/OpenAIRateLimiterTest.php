<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Cache\RateLimiter;
use App\Http\Middleware\OpenAIRateLimiter;
use Illuminate\Http\Request;
use Closure;
use Symfony\Component\HttpFoundation\Response;
use Mockery;

class OpenAIRateLimiterTest extends TestCase
{
    protected $middleware;
    protected $request;
    protected $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = Mockery::mock(RateLimiter::class);
        $this->middleware = new OpenAIRateLimiter($this->rateLimiter);
        $this->request = Request::create('/', 'GET');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRateLimiterAllowsRequestsWithinLimit()
    {
        config(['openai.rate_limit' => 60]); // 60 requests per minute
        
        $this->rateLimiter->shouldReceive('tooManyAttempts')
            ->once()
            ->with('openai_api_127.0.0.1', 60)
            ->andReturn(false);

        $this->rateLimiter->shouldReceive('hit')
            ->once()
            ->with('openai_api_127.0.0.1', 60)
            ->andReturn(1);

        $called = false;
        $next = function ($request) use (&$called) {
            $called = true;
            return new Response();
        };

        $response = $this->middleware->handle($this->request, $next);

        $this->assertTrue($called);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testRateLimiterBlocksRequestsWhenLimitExceeded()
    {
        config(['openai.rate_limit' => 1]); // 1 request per minute

        $this->rateLimiter->shouldReceive('tooManyAttempts')
            ->once()
            ->with('openai_api_127.0.0.1', 1)
            ->andReturn(true);

        $this->rateLimiter->shouldReceive('availableIn')
            ->once()
            ->with('openai_api_127.0.0.1')
            ->andReturn(30);

        $response = $this->middleware->handle($this->request, function ($request) {
            $this->fail('Next middleware should not be called');
            return new Response();
        });

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertStringContainsString('too many', strtolower($response->getContent()));
    }

    public function testRateLimiterUsesConfiguredLimit()
    {
        $customLimit = 30;
        config(['openai.rate_limit' => $customLimit]);

        $this->rateLimiter->shouldReceive('tooManyAttempts')
            ->once()
            ->with('openai_api_127.0.0.1', $customLimit)
            ->andReturn(false);

        $this->rateLimiter->shouldReceive('hit')
            ->once()
            ->with('openai_api_127.0.0.1', 60)
            ->andReturn(1);

        $response = $this->middleware->handle($this->request, function ($request) {
            return new Response();
        });

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
