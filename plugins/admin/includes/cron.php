<?php

include_once __DIR__ . '/Phoenix.php';
include_once __DIR__ . '/PhoenixUser.php';
include_once __DIR__ . '/Users.php';


echo "Cron for admin plugin" . PHP_EOL;
switch ($_CRON["Data"]->name) {
    case "spam_by_comments":
        echo "Cleaning spam comments" . PHP_EOL;

        $PointComments = crisp\plugin\admin\Phoenix::fetchPointCommentsDistinctSpam();
        $User = new crisp\plugin\admin\PhoenixUser(null);

        $Array = [];
        $Banned = [];
        foreach ($PointComments as $Comment) {
            echo "Added $Comment[id] to Spam!" . PHP_EOL;
            $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "PointComment"));
            $User->UserID = $Comment["user_id"];
            $Banned[] = $User->deactivate();
            foreach (crisp\plugin\admin\Phoenix::fetchPointCommentsByUser($Comment["user_id"]) as $Comment) {
                echo "Added $Comment[id] to Spam!" . PHP_EOL;
                $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "PointComment"));
            }
        }



        $CaseComments = crisp\plugin\admin\Phoenix::fetchCaseCommentsDistinctSpam();

        foreach ($CaseComments as $Comment) {
            echo "Added $Comment[id] to Spam!" . PHP_EOL;
            $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "CaseComment"));
            $User->UserID = $Comment["user_id"];
            $Banned[] = $User->deactivate();
            foreach (crisp\plugin\admin\Phoenix::fetchCaseCommentsByUser($Comment["user_id"]) as $Comment) {
                echo "Added $Comment[id] to Spam!" . PHP_EOL;
                $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "CaseComment"));
            }
        }


        $TopicComments = crisp\plugin\admin\Phoenix::fetchTopicCommentsDistinctSpam();

        foreach ($TopicComments as $Comment) {
            echo "Added $Comment[id] to Spam!" . PHP_EOL;
            $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "TopicComment"));
            $User->UserID = $Comment["user_id"];
            $Banned[] = $User->deactivate();
            foreach (crisp\plugin\admin\Phoenix::fetchTopicCommentsByUser($Comment["user_id"]) as $Comment) {
                echo "Added $Comment[id] to Spam!" . PHP_EOL;
                $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "TopicComment"));
            }
        }


        $ServiceComments = crisp\plugin\admin\Phoenix::fetchServiceCommentsDistinctSpam();

        foreach ($ServiceComments as $Comment) {
            echo "Added $Comment[id] to Spam!" . PHP_EOL;
            $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "ServiceComment"));
            $User->UserID = $Comment["user_id"];
            $Banned[] = $User->deactivate();
            foreach (crisp\plugin\admin\Phoenix::fetchServiceCommentsByUser($Comment["user_id"]) as $Comment) {
                echo "Added $Comment[id] to Spam!" . PHP_EOL;
                $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "ServiceComment"));
            }
        }
        echo count($Array) . " comments marked as spam!" . PHP_EOL;
        echo count($Banned) . " banned users!" . PHP_EOL;

        break;
}
