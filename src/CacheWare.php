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
    const CACHE_PUBLIC = 'limit';
    const CACHE_PRIVATE = 'private';
    const CACHE_PRIVATE_NO_EXPIRE = 'private_no_expire';
    const CACHE_NOCACHE = 'nocache';

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
        $this->settings = $settings;
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
        ini_set('session.use_cookies', true);
        ini_set('session.use_only_cookies', true);
        ini_set('session.use_strict_mode', false);
        ini_set('session.cache_limiter', '');

        $cacheSettings = array_merge($this->getCacheSettings(), $this->settings);

        $response = $next($request, $response);

        return $this->respondWithCacheHeaders($cacheSettings, $response);
    }

    /**
     * Retrieve default cache settings.
     *
     * @return array
     */
    protected function getCacheSettings()
    {
        return [
            'limiter' => session_cache_limiter(),
            'expire' => session_cache_expire() ?: 180,
        ];
    }

    /**
     * Add corresponding cache headers to response.
     *
     * @param array             $settings
     * @param ResponseInterface $response
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseInterface
     */
    protected function respondWithCacheHeaders(array $settings, ResponseInterface $response)
    {
        switch ($settings['limiter']) {
            case static::CACHE_PUBLIC:
                return $this->respondWithPublicCache($settings, $response);

            case static::CACHE_PRIVATE:
                return $this->respondWithPrivateCache($settings, $response);

            case static::CACHE_PRIVATE_NO_EXPIRE:
                return $this->respondWithPrivateNoExpireCache($settings, $response);

            case static::CACHE_NOCACHE:
                return $this->respondWithNoCacheCache($response);
        }

        return $response;
    }

    /**
     * Add public cache headers to response.
     *
     * @param array             $settings
     * @param ResponseInterface $response
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseInterface
     */
    protected function respondWithPublicCache(array $settings, ResponseInterface $response)
    {
        $maxAge = $settings['expire'] ? (int) $settings['expire'] * 60 : 0;

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
     * @param array             $settings
     * @param ResponseInterface $response
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseInterface
     */
    protected function respondWithPrivateCache(array $settings, ResponseInterface $response)
    {
        return $this->respondWithPrivateNoExpireCache(
            $settings,
            $response->withAddedHeader('Expires', static::CACHE_EXPIRED)
        );
    }

    /**
     * Add private-no-expire cache headers to response.
     *
     * @param array             $settings
     * @param ResponseInterface $response
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseInterface
     */
    protected function respondWithPrivateNoExpireCache(array $settings, ResponseInterface $response)
    {
        $maxAge = $settings['expire'] ? (int) $settings['expire'] * 60 : 0;
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
     * @throws \InvalidArgumentException
     *
     * @return ResponseInterface
     */
    protected function respondWithNoCacheCache(ResponseInterface $response)
    {
        return $response
            ->withAddedHeader('Expires', static::CACHE_EXPIRED)
            ->withAddedHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
            ->withAddedHeader('Pragma', 'no-cache');
    }
}
