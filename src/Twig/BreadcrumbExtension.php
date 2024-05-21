<?php

namespace Lens\Bundle\SeoBundle\Twig;

use Lens\Bundle\SeoBundle\BreadcrumbFactory;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class BreadcrumbExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly BreadcrumbFactory $breadcrumbFactory,
        private readonly bool $enabled = true,
        private readonly ?string $globalName = 'lens_seo_breadcrumbs',
    ) {
    }

    public function getGlobals(): array
    {
        if (!$this->enabled) {
            return [];
        }

        return [
            $this->globalName => $this->breadcrumbFactory->get(...),
        ];
    }
}
