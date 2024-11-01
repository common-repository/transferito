<?php

namespace Transferito\Models\Settings;

use Transferito\Models\Core\Api as TransferitoAPI;

class Telemetry
{
    private $api;

    public function __construct()
    {
        if (current_user_can('activate_plugins')) {
            $this->api = new TransferitoAPI();
        }
    }

    private function getUUID()
    {
        $uuid = get_transient('transferito_telemetry_uuid');

        if (!$uuid) {
            $userId = $this->createUUID();
            set_transient('transferito_telemetry_uuid', $userId);
        } else {
            $userId = $uuid;
        }

        return $userId;
    }

    private function createUUID()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $siteUrl = site_url();

        return hash('md5', "{$ip}_{$siteUrl}");
    }

    private function getUserProperties()
    {
        $userStatus = get_transient('transferito_user_status');
        return [
            'Cohort' => !$userStatus ? 'FREE' : $userStatus
        ];
    }

    public function pushEvent($event, array $eventProperties)
    {
        return $this->api->pushTelemetry([
            'userId'            => $this->getUUID(),
            'event'             => $event,
            'eventProperties'   => $eventProperties,
            'userProperties'    => $this->getUserProperties()
        ]);
    }


}
