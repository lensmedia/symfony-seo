<?php

namespace Lens\Bundle\SeoBundle\Event;

use Lens\Bundle\SeoBundle\StructuredData\StructuredDataBuilder;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

readonly class AppendStructuredDataToResponse
{
    public function __construct(
        private StructuredDataBuilder $structuredDataBuilder,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if ($response->isRedirection() || !$response->isSuccessful()) {
            return;
        }

        $responseContent = $response->getContent();
        $bodyClose = strrpos($responseContent, '</body>');
        if ($bodyClose === false) {
            return;
        }

        $structuredData = $this->structuredDataBuilder->toScript();

        $content = substr_replace($responseContent, $structuredData, $bodyClose, 0);
        $response->setContent($content);
    }
}
