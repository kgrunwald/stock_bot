<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            LogoutEvent::class => 'handleEvent'
        ];
    }

    public function handleEvent(LogoutEvent $event)
    {
        $res = new JsonResponse(['message' => 'logged out']);
        $event->setResponse($res);
    }
}