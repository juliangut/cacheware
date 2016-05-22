<?php
/**
 * CacheWare (https://github.com/juliangut/cacheware)
 * PSR7 cache headers management middleware
 *
 * @license BSD-3-Clause
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

namespace Jgut\Middleware\Cacheware\Tests;

use Jgut\Middleware\CacheWare;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * PHP cache headers management middleware test class.
 */
class CacheWareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Zend\Diactoros\Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->request = ServerRequestFactory::fromGlobals();
        $this->response = new Response;
        $this->callback = function ($request, $response) {
            return $response;
        };
    }

    public function testPublicCache()
    {
        $middleware = new CacheWare(['limiter' => 'public']);

        /* @var Response $response */
        $response = $middleware($this->request, $this->response, $this->callback);

        self::assertTrue($response->hasHeader('Expires'));
        self::assertTrue($response->hasHeader('Cache-Control'));
        self::assertSame(0, strpos($response->getHeaderLine('Cache-Control'), 'public'));
        self::assertTrue($response->hasHeader('Last-Modified'));
    }

    public function testPrivateCache()
    {
        $middleware = new CacheWare(['limiter' => 'private']);

        /* @var Response $response */
        $response = $middleware($this->request, $this->response, $this->callback);

        self::assertTrue($response->hasHeader('Expires'));
        self::assertEquals(CacheWare::CACHE_EXPIRED, $response->getHeaderLine('Expires'));
        self::assertTrue($response->hasHeader('Cache-Control'));
        self::assertSame(0, strpos($response->getHeaderLine('Cache-Control'), 'private'));
        self::assertTrue($response->hasHeader('Last-Modified'));
    }

    public function testPrivateNoExpireCache()
    {
        $middleware = new CacheWare(['limiter' => 'private_no_expire']);

        /* @var Response $response */
        $response = $middleware($this->request, $this->response, $this->callback);

        self::assertFalse($response->hasHeader('Expires'));
        self::assertTrue($response->hasHeader('Cache-Control'));
        self::assertSame(0, strpos($response->getHeaderLine('Cache-Control'), 'private'));
        self::assertTrue($response->hasHeader('Last-Modified'));
    }

    public function testNoCacheCache()
    {
        $middleware = new CacheWare(['limiter' => 'nocache']);

        /* @var Response $response */
        $response = $middleware($this->request, $this->response, $this->callback);

        self::assertTrue($response->hasHeader('Expires'));
        self::assertEquals(CacheWare::CACHE_EXPIRED, $response->getHeaderLine('Expires'));
        self::assertTrue($response->hasHeader('Cache-Control'));
        self::assertEquals(
            'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            $response->getHeaderLine('Cache-Control')
        );
        self::assertTrue($response->hasHeader('Pragma'));
        self::assertEquals('no-cache', $response->getHeaderLine('Pragma'));
    }

    public function testNoCache()
    {
        $middleware = new CacheWare(['limiter' => null]);

        /* @var Response $response */
        $response = $middleware($this->request, $this->response, $this->callback);

        self::assertFalse($response->hasHeader('Expires'));
        self::assertFalse($response->hasHeader('Cache-Control'));
        self::assertFalse($response->hasHeader('Last-Modified'));
        self::assertFalse($response->hasHeader('Pragma'));
    }
}
