# Экспортер котировок для Prometheus
Экспортер котировок финансовых инструментов из TBank (Tinkoff Bank) InvestAPI

Особенности:
- Получение и экспорт для Prometheus котировки финансовых инструментов в реальном времени для заданного набора тикеров
- Использует метки для каждого тикера финансового инструмента
- Использует неблокирующий цикл событий (event loop) "Revolt", веб-сервер и клиент amphp для обеспечения высокой производительности

Область применения:
- Отслеживание цен на финансовые инструменты Московской фондовой биржи (MOEX), Санкт-Петербургской биржи (SPBEX) и вторичных рынках в реальном времени для экспорта данных в Prometheus
- Оповещения в Prometheus для уведомлений о значительных изменениях
- Сравнение производительности различных финансовых инструментов с помощью метрик и графиков в Grafana

## Установка
```shell
git clone git@github.com:rekryt/tbank-exporter.git .
cp .env.example .env
# configure API_TOKEN in .env
# nano .env
```

## Настройка
```dotenv
API_TOKEN=""
API_TICKERS="GOLD:BBG00V9V16J8|AKGD:BBG014M8NBM4|TGLD:TCS10A101X50|SBGD:BBG019HZM0H0|GAZP:BBG004730RP0|XAU:BBG0013HGFZ7|GLDRUB_TOM:BBG000VJ5YR4|TMOS:BBG333333333"
```
#### API_TOKEN
- [Документация](https://russianinvestments.github.io/investAPI/token/) о получении токена.
- - Перейдите в [настройки профиля Т-Инвестиции](https://www.tbank.ru/invest/settings/) и авторизуйтесь в системе, если это требуется. Функция Подтверждение сделок кодом должна быть отключена.
- - Выпустите токен T-Invest API для биржи и/или песочницы. Рекомендуется оставить права доступа "только чтение".
- - Скопируйте токен и сохраните его в .env файле. Токен отображается только один раз, просмотреть его позже не получится. Вы можете выпускать неограниченное количество токенов.
- - Срок жизни токена — три месяца с даты последнего использования.

#### API_TICKERS
- Укажите список финансовых инструментов в формате "TICKER:FIGI|TICKER:FIGI|TICKER:FIGI"

## Запуск вручную
```shell
composer install
php index.php
```

## Запуск через docker compose
```shell
docker network create web
docker compose build
docker compose up -d
```

### Использование
Экспортер запустит веб-сервер на порту 8080 и будет экспортировать метрики по адресу /metrics.
Текущая цена каждого финансового инструмента будет экспортироваться с меткой ticker
```text
# HELP price price
# TYPE price gauge
price{ticker="GAZP"} 125.23
price{ticker="XAU"} 2474.34
price{ticker="GOLD"} 1.7645
price{ticker="TGLD"} 8.89
price{ticker="AKGD"} 160.74
price{ticker="GLDRUB_TOM"} 6982.4
price{ticker="SBGD"} 21.105
price{ticker="TMOS"} 6.27
# HELP order_counts order_counts
# TYPE order_counts gauge
order_counts{ticker="GOLD"} 1
# HELP order_totals order_totals
# TYPE order_totals gauge
order_totals{ticker="GOLD"} 1.7645
# HELP portfolio portfolio
# TYPE portfolio gauge
portfolio{label="totalAmountShares"} 1547.2695
portfolio{label="totalAmountBonds"} 0
portfolio{label="totalAmountEtf"} 1.7645
portfolio{label="totalAmountCurrencies"} 9708.12
portfolio{label="expectedYield"} -15513.6
# HELP positions_count positions_count
# TYPE positions_count gauge
positions_count{ticker="GOLD"} 1
positions_count{ticker="RUB000UTSTOM"} 9708.12
# HELP positions_price positions_price
# TYPE positions_price gauge
positions_price{ticker="GOLD"} 1.7645
positions_price{ticker="RUB000UTSTOM"} 1
```

### Настройка prometheus.yaml
```yaml
# A scrape configuration containing exactly one endpoint to scrape.
scrape_configs:
  - job_name: 'tbank'
    scrape_interval: 5s
    honor_labels: true
    static_configs:
      - targets: ['tbank-exporter-app-1:8080']
```
где "tbank-exporter-app-1" сетевой адрес сервиса

### Настройка grafana
В корне проекта есть [пример JSON модели](grafana.json) dashboard-а.

### Полезные ссылки
- [Т-банк](https://www.tbank.ru/)
- - [Документация по API](https://www.tbank.ru/invest/open-api/)
- - [Получение токена доступа к T-invest API](https://russianinvestments.github.io/investAPI/token/)
- - [REST API получение данных по инструменту](https://russianinvestments.github.io/investAPI/swagger-ui/#/InstrumentsService/InstrumentsService_FindInstrument)
- - [Websocket API получение сообщений](https://russianinvestments.github.io/investAPI/ws/) - gRPC streaming Т-Инвестиции
- [Prometheus](https://prometheus.io/)
- [AMPHP](https://amphp.org/)
- - [http server](https://github.com/amphp/http-server)
- - [http client](https://github.com/amphp/http-client)
- - [ws client](https://github.com/amphp/websocket-client)
- [Revolt](https://revolt.run/)

### License
The MIT License (MIT). Please see [LICENSE](./LICENSE) for more information.