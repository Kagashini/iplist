<?php

namespace OpenCCK\App\Controller;

use Amp\ByteStream\BufferException;
use Amp\Http\Server\Request;

use OpenCCK\App\Service\IPListService;
use OpenCCK\Domain\Entity\Site;
use OpenCCK\Infrastructure\API\App;

use Monolog\Logger;
use Throwable;

abstract class AbstractIPListController extends AbstractController {
    protected Logger $logger;
    protected IPListService $service;

    /**
     * @param Request $request
     * @param array $headers
     * @throws BufferException
     * @throws Throwable
     */
    public function __construct(protected Request $request, protected array $headers = []) {
        parent::__construct($request, $this->headers);

        $this->logger = App::getLogger();
        $this->service = IPListService::getInstance();
    }

    /**
     * @return string
     */
    abstract public function getBody(): string;

    /**
     * @return array<string, Site>
     */
    protected function getSites(): array {
        $wildcard = !!($this->request->getQueryParameter('wildcard') ?? '');
        return array_map(static function (Site $siteEntity) use ($wildcard) {
            $site = clone $siteEntity;
            $site->domains = $siteEntity->getDomains($wildcard);
            return $site;
        }, $this->service->sites);
    }
}
