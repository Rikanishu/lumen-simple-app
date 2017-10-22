<?php

namespace App\Console\Helper;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class InternalRequestClient
{
    /**
     * Visit the given URI with a JSON request.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return Response
     */
    public function json($method, $uri, array $data = [], array $headers = [])
    {
        $content = json_encode($data);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        return $this->doRequest(
            $method, $uri, [], [], [], $this->transformHeadersToServerVars($headers), $content
        );
    }

    public function doRequest($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $currentUri = $this->prepareUrlForRequest($uri);

        $symfonyRequest = SymfonyRequest::create(
            $currentUri, $method, $parameters,
            $cookies, $files, $server, $content
        );

        return App::prepareResponse(
            App::handle(Request::createFromBase($symfonyRequest))
        );
    }

    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        $prefix = 'HTTP_';

        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');

            if (! starts_with($name, $prefix) && $name != 'CONTENT_TYPE') {
                $name = $prefix.$name;
            }

            $server[$name] = $value;
        }

        return $server;
    }

    /**
     * Turn the given URI into a fully qualified URL.
     *
     * @param  string  $uri
     * @return string
     */
    protected function prepareUrlForRequest($uri)
    {
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }

        if (! Str::startsWith($uri, 'http')) {
            $uri = 'http://localhost/'.$uri;
        }

        return trim($uri, '/');
    }
}