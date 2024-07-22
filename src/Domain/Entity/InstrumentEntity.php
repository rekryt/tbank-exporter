<?php

namespace TBank\Domain\Entity;

final class InstrumentEntity {
    /**
     * @param string $figi FIGI-идентификатор инструмента.
     * @param string $ticker Тикер инструмента.
     * @param string $classCode Класс-код инструмента.
     * @param string $isin ISIN-идентификатор инструмента.
     * @param float $lot Лотность инструмента. Возможно совершение операций только на количества ценной бумаги, кратные параметру lot.
     * @param string $currency Валюта расчётов.
     * @param bool $shortEnabledFlag Признак доступности для операций в шорт.
     * @param string $name Название инструмента.
     * @param string $exchange Tорговая площадка (секция биржи).
     * @param string $countryOfRisk Код страны риска — то есть страны, в которой компания ведёт основной бизнес.
     * @param string $countryOfRiskName Наименование страны риска — то есть страны, в которой компания ведёт основной бизнес.
     * @param string $instrumentType Тип инструмента.
     * @param string $tradingStatus Текущий режим торгов инструмента.
     * @param bool $otcFlag Признак внебиржевой ценной бумаги.
     * @param bool $buyAvailableFlag Признак доступности для покупки.
     * @param bool $sellAvailableFlag Признак доступности для продажи.
     * @param AmountEntity $minPriceIncrement Шаг цены.
     * @param bool $apiTradeAvailableFlag Возможность торговать инструментом через API.
     * @param string $uid Уникальный идентификатор инструмента.
     * @param string $realExchange Реальная площадка исполнения расчётов (биржа).
     * @param string $positionUid Уникальный идентификатор позиции инструмента.
     * @param string $assetUid Уникальный идентификатор актива.
     * @param bool $forIisFlag Признак доступности для ИИС.
     * @param bool $forQualInvestorFlag Флаг, отображающий доступность торговли инструментом только для квалифицированных инвесторов.
     * @param bool $weekendFlag Флаг, отображающий доступность торговли инструментом по выходным.
     * @param bool $blockedTcaFlag Флаг заблокированного ТКС.
     * @param string $instrumentKind Тип инструмента.
     * @param string $first1minCandleDate Дата первой минутной свечи.
     * @param string $first1dayCandleDate Дата первой дневной свечи.
     * @param BrandEntity $brand Информация о бренде.
     */
    public function __construct(
        public string $figi,
        public string $ticker,
        public string $classCode,
        public string $isin,
        public float $lot,
        public string $currency,
        public bool $shortEnabledFlag,
        public string $name,
        public string $exchange,
        public string $countryOfRisk,
        public string $countryOfRiskName,
        public string $instrumentType,
        public string $tradingStatus,
        public bool $otcFlag,
        public bool $buyAvailableFlag,
        public bool $sellAvailableFlag,
        public AmountEntity $minPriceIncrement,
        public bool $apiTradeAvailableFlag,
        public string $uid,
        public string $realExchange,
        public string $positionUid,
        public string $assetUid,
        public bool $forIisFlag,
        public bool $forQualInvestorFlag,
        public bool $weekendFlag,
        public bool $blockedTcaFlag,
        public string $instrumentKind,
        public string $first1minCandleDate,
        public string $first1dayCandleDate,
        public BrandEntity $brand
    ) {
    }
}
