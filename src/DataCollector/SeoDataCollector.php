<?php

namespace Lens\Bundle\SeoBundle\DataCollector;

use Lens\Bundle\SeoBundle\Attribute\Breadcrumb;
use Lens\Bundle\SeoBundle\Attribute\Meta;
use Lens\Bundle\SeoBundle\BreadcrumbFactory;
use Lens\Bundle\SeoBundle\MetaFactory;
use Lens\Bundle\SeoBundle\StructuredData\StructuredDataBuilder;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SeoDataCollector extends AbstractDataCollector
{
    public function __construct(
        private readonly MetaFactory $metaFactory,
        private readonly BreadcrumbFactory $breadcrumbFactory,
        private readonly StructuredDataBuilder $structuredDataBuilder
    ) {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = [
            'meta' => $this->metaFactory->get(),
            'breadcrumbs' => $this->breadcrumbFactory->get(),
            'structuredData' => $this->structuredDataBuilder->toArray(),
        ];
    }

    public function meta(): ?Meta
    {
        return $this->data['meta'];
    }

    /**
     * @return Breadcrumb[]
     */
    public function breadcrumbs(): array
    {
        return $this->data['breadcrumbs'];
    }

    public function structuredData(): array
    {
        return $this->data['structuredData'];
    }
}
