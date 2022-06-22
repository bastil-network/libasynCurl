<?php

namespace libasynCurl\src\test;

use libasynCurl\Curl;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\InternetRequestResult;

class Example extends PluginBase
{
    public const BACKEND_URL = "http://localhost:8080";

    protected function onEnable(): void
    {
        Curl::register($this, self::BACKEND_URL, ["Authorization" => "Bearer keyboard-cat"]);
        Curl::getRequest("/", function (?InternetRequestResult $res): void {
            if (!$res) {
                $this->getLogger()->info("Nothing returned");
                return;
            }

            var_dump($res->getBody());
        });

        Curl::postRequest("/", ["username" => "my-username"], function (?InternetRequestResult $res): void {
            if (!$res) {
                $this->getLogger()->info("Nothing returned");
                return;
            }

            var_dump($res->getBody());
        });
    }
}