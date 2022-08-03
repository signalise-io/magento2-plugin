<?php

declare(strict_types=1);

namespace Signalise\Plugin\Model\Config;

class RegisteredEvents
{
    public function getRegisteredEvents()
    {
        $eventsXML = file_get_contents(
            str_replace('Model/Config', '', __DIR__) . 'etc/events.xml'
        );

        preg_match_all('/<event name="([^\s]+)">/', $eventsXML, $events);

       return $events[1];
    }
}
