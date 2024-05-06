<?php

namespace Lens\Bundle\SeoBundle;

use Lens\Bundle\SeoBundle\Attribute\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;

interface BreadcrumbResolverInterface
{
    public const BREADCRUMB_RESOLVER_SERVICE_TAG = 'lens_seo.breadcrumb.resolver';

    public function resolveBreadcrumb(Request $request, Breadcrumb $breadcrumb): void;
}
