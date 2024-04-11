<?php

namespace Lens\Bundle\SeoBundle\Twig;

use Lens\Bundle\SeoBundle\MetaFactory;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class MetaExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly MetaFactory $metaFactory,
        private readonly bool $enabled = true,
        private readonly ?string $globalName = 'lens_seo_meta',
    ) {
    }

    public function getGlobals(): array
    {
        if (!$this->enabled) {
            return [];
        }

        return [
            $this->globalName => $this->metaFactory->get(),
        ];
    }
}
