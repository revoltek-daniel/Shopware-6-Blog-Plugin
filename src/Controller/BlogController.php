<?php declare(strict_types=1);

namespace Sas\BlogModule\Controller;

use Sas\BlogModule\Page\Blog\BlogPageLoader;
use Sas\BlogModule\Page\Search\BlogSearchPageLoader;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Cache\Annotation\HttpCache;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Shopware\Storefront\Page\Navigation\NavigationPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends StorefrontController
{
    private GenericPageLoaderInterface $genericPageLoader;

    private EntityRepository $blogRepository;

    private BlogPageLoader $blogPageLoader;

    private BlogSearchPageLoader $blogSearchPageLoader;

    public function __construct(
        GenericPageLoaderInterface $genericPageLoader,
        EntityRepository $blogRepository,
        BlogPageLoader $blogPageLoader,
        BlogSearchPageLoader $blogSearchPageLoader
    ) {
        $this->genericPageLoader = $genericPageLoader;
        $this->blogRepository = $blogRepository;
        $this->blogPageLoader = $blogPageLoader;
        $this->blogSearchPageLoader = $blogSearchPageLoader;
    }

    /**
     * @HttpCache()
     * @Route("/sas_blog/search", name="sas.frontend.blog.search", methods={"GET"}, defaults={"_routeScope"={"storefront"}})
     */
    public function search(Request $request, SalesChannelContext $context): Response
    {
        try {
            $page = $this->blogSearchPageLoader->load($request, $context);
        } catch (MissingRequestParameterException $missingRequestParameterException) {
            return $this->forwardToRoute('frontend.home.page');
        }

        return $this->renderStorefront('@Storefront/storefront/page/blog-search/index.html.twig', ['page' => $page]);
    }

    /**
     * @HttpCache()
     * @Route("/widgets/blog-search", name="widgets.blog.search.pagelet", methods={"GET", "POST"}, defaults={"XmlHttpRequest"=true, "_routeScope"={"storefront"}})
     *
     * @throws MissingRequestParameterException
     */
    public function ajax(Request $request, SalesChannelContext $context): Response
    {
        $request->request->set('no-aggregations', true);

        $page = $this->blogSearchPageLoader->load($request, $context);

        $response = $this->renderStorefront('@Storefront/storefront/page/blog-search/search-pagelet.html.twig', ['page' => $page]);
        $response->headers->set('x-robots-tag', 'noindex');

        return $response;
    }

    /**
     * @HttpCache()
     * @Route("/sas_blog/{articleId}", name="sas.frontend.blog.detail", methods={"GET"}, defaults={"_routeScope"={"storefront"}})
     */
    public function detailAction(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->blogPageLoader->load($request, $context);

        return $this->renderStorefront('@Storefront/storefront/page/content/index.html.twig', ['page' => $page]);
    }

    /**
     * @HttpCache()
     * @Route("/blog/rss", name="frontend.sas.blog.rss", methods={"GET"}, defaults={"_routeScope"={"storefront"}})
     */
    public function rss(Request $request, SalesChannelContext $context): Response
    {
        $criteria = new Criteria();

        $dateTime = new \DateTime();

        $criteria->addAssociations(['blogAuthor.salutation']);

        $criteria->addFilter(
            new EqualsFilter('active', true),
            new RangeFilter('publishedAt', [RangeFilter::LTE => $dateTime->format(\DATE_ATOM)])
        );

        $results = $this->blogRepository->search($criteria, $context->getContext())->getEntities();

        $page = $this->genericPageLoader->load($request, $context);
        $page = NavigationPage::createFrom($page);

        $response = $this->renderStorefront('@SasBlogModule/storefront/page/rss.html.twig', [
            'results' => $results,
            'page'    => $page,
        ]);
        $response->headers->set('Content-Type', 'application/xml; charset=utf-8');

        return $response;
    }
}
