<?php

namespace Lens\Bundle\SeoBundle;

use Lens\Bundle\SeoBundle\Attribute\Meta;
use Symfony\Component\HttpFoundation\Request;

interface MetaFallbackInterface
{
    public function fallback(Request $request): ?Meta;
}
