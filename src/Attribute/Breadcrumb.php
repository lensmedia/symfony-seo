<?php

declare(strict_types=1);

namespace Lens\Bundle\SeoBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Breadcrumb
{
    public string $routeName;
    public array $routeParameters = [];

    public function __construct(
        public string|array|null $title = null,
        public ?string $parent = null,
        public ?string $resolver = null,
        /** Translate the title, does not work when a resolver is set */
        public bool $translate = false,
        /** Translation parameters/ other context */
        public array $context = [],
        /** Translation domain */
        public ?string $translationDomain = null,
    ) {
    }
}
