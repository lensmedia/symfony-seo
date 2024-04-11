<?php

namespace Lens\Bundle\SeoBundle\Twig;

use Lens\Bundle\SeoBundle\StructuredData\StructuredDataBuilder;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class StructuredDataExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly StructuredDataBuilder $structuredDataBuilder,
        private readonly bool $enabled = true,
        private readonly ?string $globalName = 'lens_seo_structured_data',
    ) {
    }

    public function getGlobals(): array
    {
        if (!$this->enabled) {
            return [];
        }

        return [
            $this->globalName => $this->structuredDataBuilder,
        ];
    }
}
