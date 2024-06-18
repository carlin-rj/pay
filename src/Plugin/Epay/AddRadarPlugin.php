<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Epay;

use Closure;
use GuzzleHttp\Psr7\Request;
use Yansongda\Artful\Contract\PluginInterface;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\ServiceNotFoundException;
use Yansongda\Artful\Logger;
use Yansongda\Artful\Rocket;
use Yansongda\Supports\Collection;

use function Yansongda\Pay\get_epay_url;
use function Yansongda\Pay\get_provider_config;

class AddRadarPlugin implements PluginInterface
{
    /**
     * @throws ServiceNotFoundException
     * @throws ContainerException
     */
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        Logger::info('[epay][AddRadarPlugin] 插件开始装载', ['rocket' => $rocket]);

        $params = $rocket->getParams();
        $config = get_provider_config('epay', $params);
        $payload = $rocket->getPayload();

        $rocket->setRadar(new Request(
            strtoupper($params['_method'] ?? 'POST'),
            get_epay_url($config, $payload),
            $this->getHeaders(),
            $this->getBody($payload),
        ));

        Logger::info('[epay][AddRadarPlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $next($rocket);
    }

    protected function getMethod(array $params): string
    {
        return strtoupper($params['_method'] ?? 'POST');
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'text/html',
            'User-Agent' => 'yansongda/pay-v3',
        ];
    }

    protected function getBody(Collection $payload): string
    {
        $sign = $payload->get('sign');
        $signType = $payload->get('signType');
        $payload->forget('sign');
        $payload->forget('signType');
        $payload = $payload->sortKeys();
        $payload->set('sign', $sign);
        $payload->set('signType', $signType);

        return $payload->toString();
    }
}
