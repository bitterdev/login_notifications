<?php

namespace LoginNotifications\Provider;

use Concrete\Core\Application\Application;
use Concrete\Core\Asset\AssetInterface;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\Http\Request;
use Concrete\Core\Mail\Service;
use Concrete\Core\User\Event\User;
use Concrete\Core\User\UserInfo;
use Concrete\Core\View\View;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServiceProvider extends Provider
{
    protected EventDispatcherInterface $eventDispatcher;
    protected Connection $db;
    protected Service $mailService;
    protected Request $request;

    public function __construct(
        Application              $app,
        EventDispatcherInterface $eventDispatcher,
        Connection               $db,
        Service                  $mailService,
        Request                  $request
    )
    {
        parent::__construct($app);

        $this->eventDispatcher = $eventDispatcher;
        $this->db = $db;
        $this->mailService = $mailService;
        $this->request = $request;
    }

    public function register()
    {
        $this->registerAssets();
        $this->registerEventHandlers();
    }

    protected function registerAssets()
    {
        $al = AssetList::getInstance();
        $al->register("javascript", "login-notifications", "js/login-notifications.js", ["position" => AssetInterface::ASSET_POSITION_FOOTER], "login_notifications");
    }

    protected function registerEventHandlers()
    {
        $this->eventDispatcher->addListener("on_before_render", function () {
            $v = View::getInstance();
            $v->requireAsset("javascript", "login-notifications");
        });

        $this->eventDispatcher->addListener("on_user_login", function ($event) {
            /** @var User $event */

            $user = $event->getUserObject();
            $userInfo = $user->getUserInfoObject();

            if ($userInfo instanceof UserInfo) {
                $uID = $user->getUserID();
                $ip = null;

                $headerKeys = [
                    'HTTP_X_FORWARDED_FOR',
                    'HTTP_X_REAL_IP',
                    'HTTP_CLIENT_IP',
                    'HTTP_X_FORWARDED',
                    'HTTP_X_CLUSTER_CLIENT_IP',
                    'HTTP_FORWARDED_FOR',
                    'HTTP_FORWARDED',
                    'REMOTE_ADDR'
                ];

                foreach ($headerKeys as $headerKey) {
                    if ($this->request->server->has($headerKey) &&
                        filter_var($this->request->server->get($headerKey), FILTER_VALIDATE_IP)) {
                        $ip = $this->request->server->get($headerKey);
                    }
                }

                $userAgent = $this->request->server->get("HTTP_USER_AGENT");
                $fingerprint = $this->request->cookies->get("fingerprint");

                if ($fingerprint === null) {
                    $fingerprint = sha1($ip . $userAgent);
                }

                /** @noinspection SqlDialectInspection */
                /** @noinspection SqlNoDataSourceInspection */
                $existing = (int)$this->db->fetchOne("SELECT COUNT(*) FROM UserLoginNotifications WHERE fingerprint = ?", [$fingerprint]) > 0;

                if (!$existing) {
                    $this->db->insert("UserLoginNotifications", [
                        "fingerprint" => $fingerprint,
                        "userAgent" => $userAgent,
                        "uID" => $uID,
                        "ip" => $ip,
                        "loginAt" => date('Y-m-d H:i:s')
                    ]);

                    if (filter_var($userInfo->getUserEmail(), FILTER_VALIDATE_EMAIL)) {
                        $this->mailService->to($userInfo->getUserEmail());
                        $this->mailService->addParameter('fingerprint', $fingerprint);
                        $this->mailService->addParameter('ip', $ip);
                        $this->mailService->addParameter('userAgent', $userAgent);
                        $this->mailService->addParameter('user', $user);
                        $this->mailService->load('login_notification', 'login_notifications');
                        $this->mailService->sendMail();
                    }
                }
            }
        });
    }
}