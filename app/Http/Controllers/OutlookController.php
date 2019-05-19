<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Http\GraphResponse;
use Microsoft\Graph\Model;
use Illuminate\Support\Facades\Storage;

class OutlookController extends Controller
{
    public function mail()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $tokenCache = new \App\TokenStore\TokenCache;

        $graph = new Graph();
        $graph->setAccessToken($tokenCache->getAccessToken());

        $me = $graph->createRequest('GET', '/me')
            ->setReturnType(GraphResponse::class)
            ->execute();

        $getMessagesUrl = '/me/messages?';
        $getMessagesUrl .= http_build_query([
            "\$select" => "subject,from,receivedDateTime,hasAttachments,attachments",
            "\$orderby" => "receivedDateTime DESC",
            "\$expand" => "attachments",
            "\$top" => "30"
        ]);
        $messages = $graph->createRequest('GET', $getMessagesUrl)
            ->setReturnType(Model\Message::class)
            ->execute();

        foreach ($messages as $key => $Message) {
            if ($Message->getHasAttachments()) {
                $attachments = $Message->getAttachments();
                foreach ($attachments as $key => $attachment) {
                    $content = base64_decode($attachment['contentBytes']);
                    Storage::disk('ftp')->put('/dev/dev/saia/temporal/outlook/' . $attachment['name'], $content);
                }
            }
        }

        echo '<pre>';
        var_dump('---------------------');
        echo '</pre>';
        exit;

        return view('mail', array(
            'messages' => $messages
        ));
    }
}
