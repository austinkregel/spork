<?php

declare(strict_types=1);

use App\Http\Controllers\Dav\DavController;
use App\Http\Middleware\PrepareDavRequest;
use Illuminate\Support\Facades\Route;

$davMethods = [
    'GET',
    'POST',
    'PUT',
    'DELETE',
    'HEAD',
    'OPTIONS',
    'PATCH',
    'PROPFIND',
    'PROPPATCH',
    'MKCOL',
    'COPY',
    'MOVE',
    'LOCK',
    'UNLOCK',
    'REPORT',
    'MKCALENDAR',
    'ACL',
];

Route::middleware([PrepareDavRequest::class])
    ->match($davMethods, '/dav/{any?}', DavController::class)
    ->where('any', '.*')
    ->name('dav.handle');

Route::redirect('/.well-known/carddav', '/dav/', 301);
Route::redirect('/.well-known/caldav', '/dav/', 301);
