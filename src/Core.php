<?php

namespace Simplon\Core;

use Psr\Http\Message\ResponseInterface;
use Relay\RelayBuilder;
use Simplon\Core\Storage\SessionStorage;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Class Core
 * @package Simplon\Core
 */
class Core
{
    const BODY_CHUNKSIZE = 2048;

    /**
     * @return Core
     */
    public function withSession(): self
    {
        SessionStorage::initSession();

        return $this;
    }

    /**
     * @return Core
     */
    public function withErrorHandler(): self
    {
        $handler = new PrettyPageHandler();

        if (getenv('APP_MODULE') === 'api')
        {
            $handler = new JsonResponseHandler();
        }

        if (getenv('APP_ENV') === 'production')
        {
            // work in progress ...
            $handler = function ($exception, $inspector, $run)
            {
            };
        }

        $whoops = new Run();
        $whoops->pushHandler($handler);
        $whoops->register();

        return $this;
    }

    /**
     * @param array $middleware
     *
     * @return void
     */
    public function run(array $middleware)
    {
        $relay = (new RelayBuilder())->newInstance($middleware);

        /** @var Response $response */
        $response = $relay(ServerRequestFactory::fromGlobals(), new Response());

        $this->response($response);
    }

    /**
     * Taken from SLIM framework: https://github.com/slimphp/Slim/blob/3.x/Slim/App.php#L354
     *
     * @param ResponseInterface $response
     *
     * @return void
     */
    private function response(ResponseInterface $response)
    {
        if (!headers_sent())
        {
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            foreach ($response->getHeaders() as $name => $values)
            {
                foreach ($values as $value)
                {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        if (!$this->isEmptyResponse($response))
        {
            $body = $response->getBody();

            if ($body->isSeekable())
            {
                $body->rewind();
            }

            $contentLength = $response->getHeaderLine('Content-Length');

            if (!$contentLength)
            {
                $contentLength = $body->getSize();
            }

            if (isset($contentLength))
            {
                $amountToRead = $contentLength;

                while ($amountToRead > 0 && !$body->eof())
                {
                    $data = $body->read(min(self::BODY_CHUNKSIZE, $amountToRead));

                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL)
                    {
                        break;
                    }
                }
            }
            else
            {
                while (!$body->eof())
                {
                    echo $body->read(self::BODY_CHUNKSIZE);

                    if (connection_status() != CONNECTION_NORMAL)
                    {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Taken from SLIM framework: https://github.com/slimphp/Slim/blob/3.x/Slim/App.php#L573
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    private function isEmptyResponse(ResponseInterface $response): bool
    {
        return in_array($response->getStatusCode(), [204, 205, 304]);
    }
}