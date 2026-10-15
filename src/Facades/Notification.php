<?php
namespace Clicalmani\Database\Facades;

use Clicalmani\Core\Support\Facades\Facade;

/**
 * @method static \Clicalmani\Database\Notifications\AnonymousNotifiable route(string $channel, mixed $route)
 * @method static \Clicalmani\Database\Notifications\NotificationManager channel(string|array $channels)
 * @method static \Clicalmani\Database\Notifications\NotificationManager extend(string $name, callable $factory)
 * @method static \Clicalmani\Database\Notifications\NotificationManager middleware(string $channel, mixed $mw)
 * @method static \Clicalmani\Database\Notifications\NotificationManager globalMiddleware(mixed $mw)
 * @method static void send(object $event, object $notifiable)
 * @method static \Clicalmani\Database\Notifications\ChannelRegistry registry()
 * @method static \Clicalmani\Database\Notifications\NotificationManager flush()
 * @method static string[] channels()
 */
class Notification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'notification';
    }
}