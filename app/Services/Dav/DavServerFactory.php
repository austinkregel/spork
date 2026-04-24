<?php

declare(strict_types=1);

namespace App\Services\Dav;

use App\Services\Dav\Backends\EloquentCalDavBackend;
use App\Services\Dav\Backends\EloquentCardDavBackend;
use App\Services\Dav\Backends\SanctumTokenAuthBackend;
use App\Services\Dav\Backends\UserPrincipalBackend;
use Sabre\CalDAV\CalendarRoot;
use Sabre\CalDAV\Plugin as CalDavPlugin;
use Sabre\CalDAV\Schedule\Plugin as CalDavSchedulePlugin;
use Sabre\CardDAV\AddressBookRoot;
use Sabre\CardDAV\Plugin as CardDavPlugin;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use Sabre\DAV\Server;
use Sabre\DAV\Sync\Plugin as SyncPlugin;
use Sabre\DAVACL\Plugin as AclPlugin;
use Sabre\DAVACL\PrincipalCollection;

/**
 * Builds a fully wired Sabre DAV server. Each invocation makes a fresh
 * server (Sabre keeps per-request state on the instance).
 */
class DavServerFactory
{
    public function __construct(
        private readonly UserPrincipalBackend $principalBackend,
        private readonly SanctumTokenAuthBackend $authBackend,
        private readonly EloquentCardDavBackend $cardBackend,
        private readonly EloquentCalDavBackend $calendarBackend,
    ) {
    }

    public function make(string $baseUri = '/dav/'): Server
    {
        $tree = [
            new PrincipalCollection($this->principalBackend),
            new AddressBookRoot($this->principalBackend, $this->cardBackend),
            new CalendarRoot($this->principalBackend, $this->calendarBackend),
        ];

        $server = new Server($tree);
        $server->setBaseUri($baseUri);
        $server->debugExceptions = (bool) config('app.debug', false);

        $server->addPlugin(new AuthPlugin($this->authBackend));

        $aclPlugin = new AclPlugin;
        $aclPlugin->principalCollectionSet = ['principals'];
        $aclPlugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($aclPlugin);

        $server->addPlugin(new CalDavPlugin);
        $server->addPlugin(new CardDavPlugin);
        $server->addPlugin(new SyncPlugin);

        $schedulePlugin = new CalDavSchedulePlugin;
        $server->addPlugin($schedulePlugin);

        if (config('app.debug', false)) {
            $browser = new BrowserPlugin;
            $server->addPlugin($browser);
        }

        return $server;
    }
}
