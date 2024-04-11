<?php

namespace Lens\Bundle\SeoBundle\StructuredData\Resolver;

use Lens\Bundle\SeoBundle\BreadcrumbFactory;
use Lens\Bundle\SeoBundle\StructuredData\StructuredDataResolverInterface;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\Type;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class BreadcrumbResolver implements StructuredDataResolverInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private BreadcrumbFactory $breadcrumbs,
    ) {
    }

    public function resolve(): ?Type
    {
        $breadcrumbs = $this->breadcrumbs->get();
        if (empty($breadcrumbs)) {
            return null;
        }

        $listItems = [];
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $url = $this->urlGenerator->generate(
                $breadcrumb->routeName,
                $breadcrumb->routeParameters,
                UrlGeneratorInterface::ABSOLUTE_URL,
            );

            $listItems[] = Schema::listItem()
                ->position($index + 1)
                ->name($breadcrumb->title)
                ->item(Schema::thing()->identifier($url));
        }

        return Schema::breadcrumbList()
            ->itemListElement($listItems);
    }
}
