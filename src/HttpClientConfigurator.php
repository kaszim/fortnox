<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace FAPI\Fortnox;

use Http\Client\HttpClient;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Http\Message\Authentication;
use Http\Message\UriFactory;
use Http\Client\Common\Plugin;

/**
 * Configure an HTTP client.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @internal This class should not be used outside of the API Client, it is not part of the BC promise.
 */
final class HttpClientConfigurator
{
    /**
     * @var string
     */
    private $endpoint = 'https://api.fortnox.se';

    /**
     * @var string
     */
    private $clientId;


    /**
     * @var string
     */
    private $clientSecret;


    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var UriFactory
     */
    private $uriFactory;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var Plugin[]
     */
    private $prependPlugins = [];

    /**
     * @var Plugin[]
     */
    private $appendPlugins = [];

    /**
     * @param HttpClient|null $httpClient
     * @param UriFactory|null $uriFactory
     */
    public function __construct(HttpClient $httpClient = null, UriFactory $uriFactory = null)
    {
        $this->httpClient = $httpClient ?? HttpClientDiscovery::find();
        $this->uriFactory = $uriFactory ?? UriFactoryDiscovery::find();
    }

    /**
     * @return HttpClient
     */
    public function createConfiguredClient(): HttpClient
    {
        $plugins = $this->prependPlugins;

        $plugins[] = new Plugin\AddHostPlugin($this->uriFactory->createUri($this->endpoint));
        $plugins[] = new Plugin\HeaderDefaultsPlugin([
            'User-Agent' => 'FriendsOfApi/fortnox (https://github.com/FriendsOfApi/fortnox)',
        ]);

        $extraHeaders = [];
        if (null !== $this->clientId) {
            $extraHeaders['Client-Id']= $this->clientId;
        }

        if (null !== $this->clientSecret) {
            $extraHeaders['Client-Secret']= $this->clientSecret;
        }

        if (null !== $this->accessToken) {
            $extraHeaders['Access-Token']= $this->accessToken;
        }

        if (!empty($extraHeaders)) {
            $plugins[] = new Plugin\HeaderDefaultsPlugin($extraHeaders);
        }

        return new PluginClient($this->httpClient, array_merge($plugins, $this->appendPlugins));
    }

    /**
     * @param string $endpoint
     *
     * @return HttpClientConfigurator
     */
    public function setEndpoint(string $endpoint): HttpClientConfigurator
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function setClientId(string $clientId): HttpClientConfigurator
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function setClientSecret(string $clientSecret): HttpClientConfigurator
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function setAccessToken(string $accessToken): HttpClientConfigurator
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @param Plugin $plugin
     *
     * @return HttpClientConfigurator
     */
    public function appendPlugin(Plugin ...$plugin): HttpClientConfigurator
    {
        foreach ($plugin as $p) {
            $this->appendPlugins[] = $p;
        }

        return $this;
    }

    /**
     * @param Plugin $plugin
     *
     * @return HttpClientConfigurator
     */
    public function prependPlugin(Plugin ...$plugin): HttpClientConfigurator
    {
        $plugin = array_reverse($plugin);
        foreach ($plugin as $p) {
            array_unshift($this->prependPlugins, $p);
        }

        return $this;
    }
}
