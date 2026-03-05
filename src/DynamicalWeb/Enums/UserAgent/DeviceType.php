<?php

    namespace DynamicalWeb\Enums\UserAgent;

    use DynamicalWeb\Interfaces\StringInterface;

    enum DeviceType: string implements StringInterface
    {
        case MOBILE = 'mobile';
        case TABLET = 'tablet';
        case DESKTOP = 'desktop';
        case BOT = 'bot';
        case UNKNOWN = 'unknown';

        /**
         * @inheritDoc
         */
        public function toString(): string
        {
            return $this->value;
        }
    }
