# Helastel: backend developer
## Задание

Разработчика попросили получить данные из REST-API стороннего сервиса.
Данные необходимо было кешировать, ошибки логировать. Разработчик с задачей справился, ниже предоставлен его код.

Проведите максимально подробный Code Review. Необходимо написать, с чем вы не согласны и почему.
Исправьте обозначенные ошибки, предоставив свой вариант кода.

```php
//php code

<?php

namespace src\Integration;

class DataProvider
{
    private $host;
    private $user;
    private $password;

    /**
     * @param $host
     * @param $user
     * @param $password
     */
    public function __construct($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function get(array $request)
    {
        // returns a response from external service
    }
}


<?php

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;

class DecoratorManager extends DataProvider
{
    public $cache;
    public $logger;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param CacheItemPoolInterface $cache
     */
    public function __construct($host, $user, $password, CacheItemPoolInterface $cache)
    {
        parent::__construct($host, $user, $password);
        $this->cache = $cache;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(array $input)
    {
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = parent::get($input);

            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+1 day')
                );

            return $result;
        } catch (Exception $e) {
            $this->logger->critical('Error');
        }

        return [];
    }

    public function getCacheKey(array $input)
    {
        return json_encode($input);
    }
}

```


## Рефакторинг:

1) Класс DataProvider надо переименовать, так как его задача взаимодействовать с API, что не отражается в названии
2) Входящие данные можно вынести в interface DataProviderInterface
3) Для параметров класса DataProvider нужно задать тип
4) Необходимо передать экземпляр класса DataProvider через конструктор, а сейчас от него идет наследование
5) $cache и $logger должны устанавливаться через конструктор и применяться внутри класса
6) Название класса DecoratorManager не самое подходящее, больше подходит DataManager
7) public function __construct($host, $user, $password, CacheItemPoolInterface $cache) - плохой вариант, тут не должно быть исходных данных для взаимодействовия с API, надо вынести их внутрь провайдера
8) public function setLogger(LoggerInterface $logger) - обязательная зависимость, ее надо перенести в конструктор
9) {@inheritdoc} - каким образом имеет место быть в коде? он же не переопределяется и ничего не делает
10) Нарушается PSR
11) Кэш не работает, потому что данные не сохраняются и используется не ключ, а весь объект json, да и если применять return json_encode($input), то где алгоритм хэширования, md5, например
12) Надо сохранить кэш таким образом $this->cache->save($cacheItem) после того, как значение будет установлено
13) return json_encode($input) - такая запись некорректна, так ключ содержит недопустимые символы
