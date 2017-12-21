<?php

namespace DiceRoller;
class HipChatHelper
{
    public const MAX_MESSAGE_LENGTH = 10000; // https://www.hipchat.com/docs/apiv2/method/send_room_notification
    public const TARGET_DOMAIN = '';

    protected static function getPostURL($roomId)
    {
        $authToken = HipChatHelper::getAuthToken($roomId);
        if ($authToken) {
            return TARGET_DOMAIN . "/v2/room/$roomId/notification?auth_token=$authToken";
        }
        return false;
    }

    protected static function getAuthToken($roomId)
    {
        //TODO: store registered room/token pairs in DB and perform lookup here
        $registeredRooms = array('sampleRoomId'=>'sampleAuthToken');

        if (array_key_exists($roomId, $registeredRooms)) {
            //logToFile("Room recognized! Returning authtoken.");
            return $registeredRooms[$roomId];
        }
        //logToFile("Room \"$roomId\" not recognized!");
        return false;
    }

    public static function sendPost_Curl($post, $roomId)
    {
        //logToFile("Sending data \"".var_export($post, true)."\" to ".getPostURL());
        $ch = curl_init(HipChatHelper::getPostURL($roomId));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);
        curl_close($ch);
    }

    public static function generatePost($data, $messageColor = 'green')
    {
        return json_encode(array(
                'color' => $messageColor,
                'message' => $data,
                'notify' => false,
                'message_format' => 'html')
        );
    }
}