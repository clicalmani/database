<?php

declare(strict_types=1);

use Clicalmani\Core\Events\CoreEventDispatcher;
use Clicalmani\Core\Maker\Application;
use Clicalmani\Core\Providers\ContainerServiceProvider;
use Clicalmani\Core\Providers\LogServiceProvider;
use Clicalmani\Core\Providers\ValidationServiceProvider;
use Symfony\Component\DependencyInjection\Definition;

require_once __DIR__ . '/../vendor/autoload.php';

// Convention du framework : Response n'écrit pas sur stdout et ne ferme pas les
// buffers ouverts par PHPUnit.
define('TEST_ENV', true);

// Les helpers du framework (app(), config(), env(), database_path(), ...) ne sont
// jamais chargés par l'autoload PSR-4.
require_once dirname(__DIR__) . '/vendor/clicalmani/foundation/src/helpers.php';

// Racine applicative des tests : config/database.php et database_path() en dépendent.
$app = Application::getInstance(__DIR__);

// Les tests s'exécutent sur une base réelle : la connexion est décrite par
// tests/config/database.php, qui lit DB_CONNECTION, DB_HOST, DB_NAME, etc.
$app->config->set('database', require __DIR__ . '/config/database.php');

// Amorçage minimal, sans kernel HTTP ni console : le conteneur DI cœur fournit
// config, logger et database.
(new ContainerServiceProvider)->boot();

// DBQueryBuilder dispatche chaque requête via event() : sans ce service, tous
// les appels au query builder échouent. Aucun listener à découvrir en test.
$builder = ContainerServiceProvider::get();
$builder->setDefinition('events.discovery', (new Definition(CoreEventDispatcher::class))->setSynthetic(true)->setPublic(true));
$builder->set('events.discovery', new CoreEventDispatcher);

// Registre des règles de validation utilisé par Validator (Entity::migrate(), ...).
(new ValidationServiceProvider)->boot();

// Hors debug, logger()->error() appelle exit() et tue PHPUnit : on force le mode
// debug pour que la branche lève une exception à la place.
(new \ReflectionProperty(LogServiceProvider::class, 'is_debug_mode'))->setValue(null, true);
