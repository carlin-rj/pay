<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Alipay;

use Closure;
use Yansongda\Pay\Contract\PluginInterface;
use Yansongda\Pay\Direction\NoHttpRequestDirection;
use Yansongda\Pay\Exception\ContainerException;
use Yansongda\Pay\Exception\Exception;
use Yansongda\Pay\Exception\InvalidConfigException;
use Yansongda\Pay\Exception\InvalidSignException;
use Yansongda\Pay\Exception\ServiceNotFoundException;
use Yansongda\Pay\Logger;
use Yansongda\Pay\Rocket;
use Yansongda\Supports\Collection;
use Yansongda\Supports\Str;

use function Yansongda\Pay\verify_alipay_sign;

class CallbackPlugin implements PluginInterface
{
    /**
     * @throws ContainerException
     * @throws InvalidConfigException
     * @throws ServiceNotFoundException
     * @throws InvalidSignException
     */
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        Logger::debug('[Alipay][CallbackPlugin] 插件开始装载', ['rocket' => $rocket]);

        $this->formatPayload($rocket);
        $sign = $rocket->getParams()['sign'] ?? false;

        if (!$sign) {
            throw new InvalidSignException(Exception::SIGN_EMPTY, 'Callback Empty Sign', $rocket->getParams());
        }

        verify_alipay_sign($rocket->getParams(), $this->getSignContent($rocket->getPayload()), $sign);

        $rocket->setDirection(NoHttpRequestDirection::class)
            ->setDestination($rocket->getPayload());

        Logger::info('[Alipay][CallbackPlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $next($rocket);
    }

    protected function formatPayload(Rocket $rocket): void
    {
        $payload = (new Collection($rocket->getParams()))
            ->filter(fn ($v, $k) => '' !== $v && !is_null($v) && 'sign' != $k && 'sign_type' != $k && !Str::startsWith($k, '_'));

        $rocket->setPayload($payload);
    }

    protected function getSignContent(Collection $payload): string
    {
        return $payload->sortKeys()->toString();
    }
}
