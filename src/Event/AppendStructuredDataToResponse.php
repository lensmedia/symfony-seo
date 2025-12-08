<?php

namespace Lens\Bundle\SeoBundle\Event;

use Lens\Bundle\SeoBundle\StructuredData\StructuredDataBuilder;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class AppendStructuredDataToResponse
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
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

        $event = new BeforeAppendStructuredDataToResponseEvent($this->structuredDataBuilder, $response);
        $this->eventDispatcher->dispatch($event);

        $structuredData = $this->structuredDataBuilder->toScript();

        $content = substr_replace($responseContent, $structuredData, $bodyClose, 0);
        $response->setContent($content);

        $event = new AfterAppendStructuredDataToResponseEvent($this->structuredDataBuilder, $response);
        $this->eventDispatcher->dispatch($event);
    }
}
