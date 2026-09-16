<?php

namespace Lens\Bundle\SeoBundle\Event;

use Lens\Bundle\SeoBundle\StructuredData\StructuredDataBuilder;
use Lens\Bundle\SeoBundle\SupportsRequestTrait;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class AppendStructuredDataToResponse
{
    use SupportsRequestTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private StructuredDataBuilder $structuredDataBuilder,
        private array $urls = [],
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->supportsRequest($event->getRequest(), $this->urls)) {
            return;
        }

        $response = $event->getResponse();
        if ($response->isRedirection() || !$response->isSuccessful()) {
            return;
        }

        if (!str_contains($response->headers->get('Content-Type', ''), 'html')) {
            return;
        }

        $responseContent = $response->getContent();
        if (false === $responseContent) {
            return;
        }

        $bodyClose = strrpos($responseContent, '</body>');
        if ($bodyClose === false) {
            return;
        }

        $beforeEvent = new BeforeAppendStructuredDataToResponseEvent($this->structuredDataBuilder, $response);
        $this->eventDispatcher->dispatch($beforeEvent);

        if (empty($this->structuredDataBuilder->toArray()['@graph'])) {
            return;
        }

        $structuredData = $this->structuredDataBuilder->toScript();

        $content = substr_replace($responseContent, $structuredData, $bodyClose, 0);
        $response->setContent($content);

        $afterEvent = new AfterAppendStructuredDataToResponseEvent($this->structuredDataBuilder, $response);
        $this->eventDispatcher->dispatch($afterEvent);
    }
}
