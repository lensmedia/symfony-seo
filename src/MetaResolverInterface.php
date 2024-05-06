<?php

namespace Lens\Bundle\SeoBundle;

use Lens\Bundle\SeoBundle\Attribute\Meta;
use Symfony\Component\HttpFoundation\Request;

interface MetaResolverInterface
{
    public const META_RESOLVER_SERVICE_TAG = 'lens_seo.meta.resolver';

    public function resolveMeta(Request $request, Meta $meta): void;
}
