<?php

namespace DiceRoller;

class InputHandler
{
    public const INPUT_TRIGGER = '/roll';
    public const HELP_COMMAND = 'help';

    private $rawPostContents;
    private $postContents;
    private $command;
    private $initiator;
    private $roomId;

    public function __construct()
    {
        $this->rawPostContents = file_get_contents('php://input');
        $this->postContents = htmlentities(strip_tags($this->rawPostContents), ENT_QUOTES);

        if ($this->wasPostReceived()) {
            $json = json_decode($this->rawPostContents, true); // $json contains event, item, and webhook id
            $item = $json['item']; // $item contains message and room
            $room = $item['room']; // $room contains id and name
            $this->roomId = $room['id']; // the room id to post the response to
            $message = $item['message']; // $message contains date, from, id, mentions, message (typed text), and type
            $this->initiator = $message['from']['mention_name']; // the mention name of the user who triggered the interaction
            $fullCommandText = $message['message']; // $fullCommandText is the complete message that triggered the interaction
            $this->command = $fullCommandText;
            if (strpos($this->command, self::INPUT_TRIGGER . ' ') === 0) { // if the message starts with the trigger
                $this->command = explode(self::INPUT_TRIGGER . ' ', $this->command, 2)[1]; // store the message without the trigger
            } else {
                $this->postContents = false; // throw out commands that do not start with the trigger
            }
            // accept various help command versions
//            if ($this->command == 'h' || $this->command == '-h' || $this->command == '--h'
//                || $this->command == 'help' || $this->command == '-help' || $this->command == '--help') {
//                $this->command = self::HELP_COMMAND;
//            }
        }
    }

    public function wasPostReceived()
    {
        return $this->postContents !== false && !empty($this->postContents);
    }

    public function postContents()
    {
        return $this->postContents;
    }

    public function command()
    {
        return $this->command;
    }

    public function initiator()
    {
        return $this->initiator;
    }

    public function initiatorPrefix()
    {
        if (trim($this->initiator) === '') {
            return '@' . $this->initiator . ' ';
        }
        return '';
    }

    public function roomId()
    {
        return $this->roomId;
    }
}