<?php declare(strict_types=1);

namespace BlogModule\Tests\Core\Content\Sitemap\Provider;

use Doctrine\DBAL\Connection;
use BlogModule\Tests\Fakes\FakeEntityRepository;
use BlogModule\Tests\Traits\ContextTrait;
use PHPUnit\Framework\TestCase;
use Sas\BlogModule\Content\Blog\BlogEntriesCollection;
use Sas\BlogModule\Content\Blog\BlogEntriesDefinition;
use Sas\BlogModule\Content\Blog\BlogEntriesEntity;
use Sas\BlogModule\Core\Content\Sitemap\Provider\BlogUrlProvider;
use Shopware\Core\Content\Sitemap\Struct\UrlResult;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class BlogUrlProviderTest extends TestCase
{
    use ContextTrait;

    private EntityRepository $blogRepository;

    private Connection $connection;

    private BlogUrlProvider $blogUrlProvider;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->blogRepository = new FakeEntityRepository(new BlogEntriesDefinition());
        $this->connection = $this->createMock(Connection::class);

        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->blogUrlProvider = new BlogUrlProvider(
            $this->blogRepository,
            $this->connection
        );
    }

    /**
     * This test verifies that method getUrls correctly returns
     * an instance of UrlResult with empty urls.
     *
     * @return void
     */
    public function testGetUrlsWithEmptyBlogEntries(): void
    {
        $limit = 10;
        $searchResults = $this->createEmptyBlogSearchResults();
        $this->blogRepository->entitySearchResults = [$searchResults];

        $actualResult = $this->blogUrlProvider->getUrls($this->salesChannelContext, $limit);

        static::assertInstanceOf(UrlResult::class, $actualResult);
        static::assertSame([], $actualResult->getUrls());
    }

    /**
     * This test verifies that method getUrls correctly returns
     * an instance of UrlResult with one blog url that is built
     * from given article id.
     *
     * @return void
     */
    public function testGetUrls(): void
    {
        $limit = 10;
        $articleId = '12345678901234567890123456789012';
        $searchResults = $this->createBlogSearchResults($articleId);
        $this->blogRepository->entitySearchResults = [$searchResults];
        $this->connection->method('fetchAll')->willReturn([
            [ 'foreign_key' => $articleId, 'seo_path_info' => 'blog/blog-entry' ],
        ]);

        $actualResult = $this->blogUrlProvider->getUrls($this->salesChannelContext, $limit);

        static::assertInstanceOf(UrlResult::class, $actualResult);
        static::assertNotEmpty($actualResult->getUrls());
        static::assertSame($articleId, $actualResult->getUrls()[0]->getIdentifier());
    }

    /**
     * Create an empty search result for blog entries.
     *
     * @return EntitySearchResult
     */
    private function createEmptyBlogSearchResults(): EntitySearchResult
    {
        return $this->createConfiguredMock(EntitySearchResult::class, []);
    }

    /**
     * Create a search result for blog entries with given article id.
     *
     * @param string $articleId
     * @return EntitySearchResult
     */
    private function createBlogSearchResults(string $articleId): EntitySearchResult
    {
        $blogEntity = $this->createConfiguredMock(BlogEntriesEntity::class, [
            'getId' => $articleId,
        ]);

        return new EntitySearchResult(
            BlogEntriesDefinition::ENTITY_NAME,
            1,
            new BlogEntriesCollection([$blogEntity]),
            null,
            new Criteria(),
            $this->getContext($this)
        );
    }
}
