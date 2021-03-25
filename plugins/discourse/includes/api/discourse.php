<?php

/** @var \crisp\core\PluginAPI $this */
/** @var \crisp\core\PluginAPI self */
$EnvFile = parse_ini_file(__DIR__ . "/../../.env");
$Error = false;
$Message = "";


if (empty($EnvFile["DISCOURSE_WEBHOOK_SECRET"])) {
    $Error[] = "DISCOURSE_WEBHOOK_SECRET is not set in .env file";
}

if (empty($EnvFile["DISCOURSE_HOSTNAME"])) {
    $Error[] = "DISCOURSE_HOSTNAME is not set in .env file";
}

if (empty($EnvFile["DISCOURSE_API_KEY"])) {
    $Error[] = "DISCOURSE_API_KEY is not set in .env file";
}


if (array_key_exists('HTTP_X_DISCOURSE_EVENT_SIGNATURE', $_SERVER) && $_SERVER["HTTP_X_DISCOURSE_EVENT"] == "post_created" && !$Error) {
    $PayLoadRaw = file_get_contents('php://input');
    $PayLoadHash = substr($_SERVER['HTTP_X_DISCOURSE_EVENT_SIGNATURE'], 7);
    $PayLoad = json_decode($PayLoadRaw);




    if (hash_hmac('sha256', $PayLoadRaw, $EnvFile["DISCOURSE_WEBHOOK_SECRET"]) == $PayLoadHash) {
        if (preg_match_all(\crisp\api\Config::get("plugin_discourse_service_regex"), $PayLoad->post->raw) > 0 && ($PayLoad->post->primary_group_name == "Team" || $PayLoad->post->primary_group_name == "curators")) {

            $responses = [];

            $Discourse = new \pnoeric\DiscourseAPI($EnvFile["DISCOURSE_HOSTNAME"], $EnvFile["DISCOURSE_API_KEY"]);


            if (\crisp\api\Phoenix::serviceExistsByNamePG($PayLoad->post->topic_title)) {
                $Service = \crisp\api\Phoenix::getServiceByNamePG($PayLoad->post->topic_title);


                $String = "A curator has added the service, below are the links to your service!\n\nService: https://edit.tosdr.org/services/" . $Service["id"];

                $Documents = crisp\api\Phoenix::getDocumentsByServicePG($Service["id"]);

                if (count($Documents) > 0) {
                    $String .= "\n\nDocuments:\n";
                    foreach ($Documents as $Document) {
                        $String .= $Document["name"] . " - https://edit.tosdr.org/documents/" . $Document["id"] . "\n";
                    }
                }

                $responses[] = $Discourse->createPost($String, $PayLoad->post->topic_id, "system", new DateTime());
            }

            $responses[] = $Discourse->closeTopic($PayLoad->post->topic_id);
            $responses[] = $Discourse->addTagsToTopic(["service-added"], "-/" . $PayLoad->post->topic_id);



            $Message = "OK";
        } else {

            if ($PayLoad->post->post_number == 1) {

                if (\crisp\api\Phoenix::serviceExistsByNamePG($PayLoad->post->topic_title)) {
                    $Discourse = new \pnoeric\DiscourseAPI($EnvFile["DISCOURSE_HOSTNAME"], $EnvFile["DISCOURSE_API_KEY"]);

                    $Service = \crisp\api\Phoenix::getServiceByNamePG($PayLoad->post->topic_title);

                    $Documents = crisp\api\Phoenix::getDocumentsByServicePG($Service["id"]);

                    $String = "Hello!\n\nIt seems that your service already is in our System!\n\nService: https://edit.tosdr.org/services/" . $Service["id"];

                    if (count($Documents) > 0) {
                        $String .= "\n\nDocuments:\n";
                        foreach ($Documents as $Document) {
                            $String .= $Document["name"] . " - https://edit.tosdr.org/documents/" . $Document["id"] . "\n";
                        }
                    }


                    $responses[] = $Discourse->createPost($String, $PayLoad->post->topic_id, "system", new DateTime());
                    $responses[] = $Discourse->closeTopic($PayLoad->post->topic_id);
                    $responses[] = $Discourse->addTagsToTopic(["service-duplicate"], "-/" . $PayLoad->post->topic_id);
                    $Message = "Success!";
                } else {
                    $responses = [];

                    $Discourse = new \pnoeric\DiscourseAPI($EnvFile["DISCOURSE_HOSTNAME"], $EnvFile["DISCOURSE_API_KEY"]);

                    $Service = \crisp\api\Phoenix::getServiceByNamePG($PayLoad->post->topic_title);

                    $responses[] = $Discourse->createPost("Hello!\nThanks for contributing to ToS;DR!\n\nA curator will soon add the documents to our database.\n\nService Link: https://edit.tosdr.org/services/" . $Service["id"], $PayLoad->post->topic_id, "system", new DateTime());
                    $Message = "Success!";
                }
            } else {
                $Error[] = "No match";
            }
        }
    } else {
        $Error[] = "Failed to verify payload";
        $Message = "Failed to authenticate webhook request!";
    }
} else {
    if (!array_key_exists('HTTP_X_DISCOURSE_EVENT_SIGNATURE', $_SERVER)) {
        $Error[] = "HTTP_X_DISCOURSE_EVENT_SIGNATURE missing";
    }
    if (!$_SERVER["HTTP_X_DISCOURSE_EVENT"] == "post_created") {
        $Error[] = "HTTP_X_DISCOURSE_EVENT is not post_created";
    }
    $Message = "Not a valid webhook request!";
}


$this->response($Error, $Message, []);
