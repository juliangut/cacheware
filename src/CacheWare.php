<?php
/**
 * CacheWare (https://github.com/juliangut/cacheware)
 * PSR7 cache headers management middleware
 *
 * @license BSD-3-Clause
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

namespace Jgut\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PHP cache headers management middleware.
 */
class CacheWare
{
    const CACHE_EXPIRED = 'Thu, 19 Nov 1981 08:52:00 GMT';

    /**
     * @var array
     */
    protected $settings;

    /**
     * Middleware constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        $this->settings = array_merge(
            $this->getCacheParams(),
            $settings
        );
    }

    /**
     * Retrieve default cache parameters.
     *
     * @return array
     */
    protected function getCacheParams()
    {
        return [
            'limiter' => session_cache_limiter(),
            'expire' => session_cache_expire() ?: 180,
        ];
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // Prevent headers from being automatically sent to client
        ini_set('session.use_trans_sid', false);
        ini_set('session.use_cookies', false);
        ini_set('session.use_only_cookies', true);
        ini_set('session.use_strict_mode', false);
        ini_set('session.cache_limiter', '');

        return $next($request, $this->respondWithCacheHeaders($response));
    }

    /**
     * Add corresponding cache headers to response.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function respondWithCacheHeaders(ResponseInterface $response)
    {
        switch ($this->settings['limiter']) {
            case 'public':
                return $this->respondWithPublicCache($response);

            case 'private':
                return $this->respondWithPrivateCache($response);

            case 'private_no_expire':
            case 'private-no-expire':
                return $this->respondWithPrivateNoExpireCache($response);

            case 'nocache':
            case 'no-cache':
                return $this->respondWithNoCacheCache($response);
        }

        return $response;
    }

    /**
     * Add public cache headers to response.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function respondWithPublicCache(ResponseInterface $response)
    {
        $maxAge = $this->settings['expire'] ? (int) $this->settings['expire'] * 60 : 0;

        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $expireDate = clone $currentDate;
        $expireDate->modify('+' . $maxAge . ' seconds');

        return $response
            ->withAddedHeader(
                'Expires',
                sprintf('expires=%s; max-age=%s', $expireDate->format('D, d M Y H:i:s T'), $maxAge)
            )
            ->withAddedHeader('Cache-Control', sprintf('public, max-age=%s', $maxAge))
            ->withAddedHeader('Last-Modified', $currentDate->format('D, d M Y H:i:s T'));
    }

    /**
     * Add private cache headers to response.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function respondWithPrivateCache(ResponseInterface $response)
    {
        return $this->respondWithPrivateNoExpireCache(
            $response->withAddedHeader('Expires', self::CACHE_EXPIRED)
        );
    }

    /**
     * Add private-no-expire cache headers to response.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function respondWithPrivateNoExpireCache(ResponseInterface $response)
    {
        $maxAge = $this->settings['expire'] ? (int) $this->settings['expire'] * 60 : 0;
        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));

        return $response
            ->withAddedHeader('Cache-Control', sprintf('private, max-age=%1$s, pre-check=%1$s', $maxAge))
            ->withAddedHeader('Last-Modified', $currentDate->format('D, d M Y H:i:s T'));
    }

    /**
     * Add no-cache cache headers to response.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function respondWithNoCacheCache(ResponseInterface $response)
    {
        return $response
            ->withAddedHeader('Expires', self::CACHE_EXPIRED)
            ->withAddedHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
            ->withAddedHeader('Pragma', 'no-cache');
    }
}
