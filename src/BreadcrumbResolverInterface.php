<?php

namespace Lens\Bundle\SeoBundle;

use Lens\Bundle\SeoBundle\Attribute\Breadcrumb;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;

interface BreadcrumbResolverInterface
{
    public const SERVICE_TAG = 'lens_seo.breadcrumb.resolver';

    public function resolveBreadcrumb(Request $request, Breadcrumb $breadcrumb): void;
}
