<?php declare(strict_types=1);

namespace Sas\BlogModule\Page\Search;

use Shopware\Core\Content\Category\Exception\CategoryNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\HttpFoundation\Request;

class BlogSearchPageLoader
{
    private const DEFAULT_LIMIT = 24;

    private const DEFAULT_PAGE = 1;

    private GenericPageLoaderInterface $genericLoader;

    private EntityRepository $blogRepository;

    public function __construct(
        GenericPageLoaderInterface $genericLoader,
        EntityRepository $blogRepository
    ) {
        $this->genericLoader = $genericLoader;
        $this->blogRepository = $blogRepository;
    }

    /**
     * Loads the blog search page.
     * It gets the blog search results from the blog repository and passes them to the generic page loader.
     *
     * @param  Request                          $request
     * @param  SalesChannelContext              $context
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws MissingRequestParameterException
     * @return BlogSearchPage
     */
    public function load(Request $request, SalesChannelContext $context): BlogSearchPage
    {
        if (!$request->query->has('search')) {
            throw new MissingRequestParameterException('search');
        }

        $page = $this->genericLoader->load($request, $context);

        /** @var BlogSearchPage $page */
        $page = BlogSearchPage::createFrom($page);

        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->setRobots('noindex,follow');
        }

        $criteria = $this->createCriteria($request);

        $result = $this->blogRepository->search($criteria, $context->getContext());
        $page->setListing($result);

        $page->setSearchTerm(
            (string)$request->query->get('search')
        );

        return $page;
    }

    /**
     * Create a criteria for the blog listing.
     * It gets limit and page from the request.
     * It calculates the offset.
     * It sets the search term.
     *
     * @param  Request  $request
     * @return Criteria
     */
    private function createCriteria(Request $request): Criteria
    {
        $term = $request->query->get('search');
        $limit = $this->getLimit($request);
        $page = $this->getPage($request);
        $offset = ($page - 1) * $limit;

        $criteria = new Criteria();
        $criteria->setTerm($term);
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->setTitle('blog-search-page');
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
        $criteria->addAssociations([
                                       'blogCategories',
                                   ]);
        return $criteria;
    }

    /**
     * Get the limit from the request.
     * It also gets value from the POST request.
     *
     * @param  Request $request
     * @return int
     */
    private function getLimit(Request $request): int
    {
        $limit = $request->query->getInt('limit', 0);

        if ($request->isMethod(Request::METHOD_POST)) {
            $limit = $request->request->getInt('limit', $limit);
        }

        return $limit <= 0 ? self::DEFAULT_LIMIT : $limit;
    }

    /**
     * Get the page from the request.
     * It also gets value from the POST request.
     *
     * @param  Request $request
     * @return int
     */
    private function getPage(Request $request): int
    {
        $page = $request->query->getInt('p', 1);

        if ($request->isMethod(Request::METHOD_POST)) {
            $page = $request->request->getInt('p', $page);
        }

        return $page <= 0 ? self::DEFAULT_PAGE : $page;
    }
}
