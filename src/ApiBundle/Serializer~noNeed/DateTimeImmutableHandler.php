<?php

namespace ApiBundle\Serializer;


use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;

class DateTimeImmutableHandler implements SubscribingHandlerInterface
{
    /**
     * @var string
     */
    private $format;

    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format'    => 'json',
                'type'      => 'DateTimeImmutable',
                'method'    => 'serializeDateTimeImmutableToJson',
            ]
        ];
    }

    /**
     * @param string $format
     */
    public function __construct($format = DATE_ISO8601)
    {
        $this->format = $format;
    }

    public function serializeDateTimeImmutableToJson(
        JsonSerializationVisitor $visitor,
        \DateTimeImmutable $date,
        array $type,
        Context $context
    ) {
        return $visitor->visitString($date->format($this->format), $type, $context);
    }
}