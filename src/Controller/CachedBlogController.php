<?php declare(strict_types=1);

namespace Sas\BlogModule\Controller;

use Shopware\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Handle Cache for BlogController
 *
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class CachedBlogController extends StorefrontController
{
    private BlogController $decorated;

    private CacheInterface $cache;

    private EntityCacheKeyGenerator $generator;

    /**
     * @var AbstractCacheTracer<Response>
     */
    private AbstractCacheTracer $tracer;

    public function __construct(
        BlogController $decorated,
        CacheInterface $cache,
        EntityCacheKeyGenerator $generator,
        AbstractCacheTracer $tracer
    ) {
        $this->decorated = $decorated;
        $this->cache = $cache;
        $this->generator = $generator;
        $this->tracer = $tracer;
    }

    public static function buildName(string $articleId): string
    {
        return 'sas-blog-detail-' . $articleId;
    }

    /**
     * @Route("/sas_blog/{articleId}", name="sas.frontend.blog.detail", methods={"GET"})
     */
    public function detailAction(string $articleId, Request $request, SalesChannelContext $context): Response
    {
        $key = $this->generateKey($articleId, $context);

        $value = $this->cache->get($key, function (ItemInterface $item) use ($articleId, $request, $context) {
            $response = $this->decorated->detailAction($articleId, $request, $context);

            $item->tag($this->generateTags($articleId));

            return CacheValueCompressor::compress($response);
        });

        return CacheValueCompressor::uncompress($value);
    }

    private function generateKey(string $articleId, SalesChannelContext $context): string
    {
        $parts = [
            $this->generator->getSalesChannelContextHash($context),
        ];

        return self::buildName($articleId) . '-' . md5(JsonFieldSerializer::encodeJson($parts));
    }

    /**
     * @return array<string>
     */
    private function generateTags(string $articleId): array
    {
        $tags = array_merge(
            $this->tracer->get(self::buildName($articleId)),
            [self::buildName($articleId)]
        );

        return array_unique(array_filter($tags));
    }
}
