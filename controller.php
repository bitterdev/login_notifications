<?php

namespace Concrete\Package\LoginNotifications;

use Concrete\Core\Package\Package;
use LoginNotifications\Provider\ServiceProvider;

class Controller extends Package
{
    protected string $pkgHandle = 'login_notifications';
    protected string $pkgVersion = '0.0.3';
    protected $appVersionRequired = '9.0.0';
    protected $pkgAutoloaderRegistries = [
        'src/LoginNotifications' => 'LoginNotifications',
    ];

    public function getPackageName(): string
    {
        return t('Login Notifications');
    }

    public function getPackageDescription(): string
    {
        return t('Get notified when a user logs in from a new device for improved account security.');
    }

    public function on_start()
    {
        /** @var ServiceProvider $serviceProvider */
        /** @noinspection PhpUnhandledExceptionInspection */
        $serviceProvider = $this->app->make(ServiceProvider::class);
        $serviceProvider->register();
    }
}


