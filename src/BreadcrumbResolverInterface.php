<?php

declare(strict_types=1);

namespace Lens\Bundle\SeoBundle;

use Lens\Bundle\SeoBundle\Attribute\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;

interface BreadcrumbResolverInterface
{
    public const BREADCRUMB_RESOLVER_SERVICE_TAG = 'lens_seo.breadcrumb.resolver';

    /**
     * @param Request $request The current (main) request instance.
     * @param Breadcrumb $breadcrumb The breadcrumb attribute instance that called the resolver.
     * @param array $breadcrumbs The list of breadcrumbs append your resolved one(s) to this in whatever manner you like.
     *
     * @return void
     */
    public function resolveBreadcrumb(Request $request, Breadcrumb $breadcrumb, array &$breadcrumbs): void;
}
