<?php namespace App\Http\Controllers;

use App, Input;
use App\Message;
use App\Sender;
use Illuminate\Http\Request;
use Log, SMS;
use Parse\ParseException;
use Parse\ParseObject;
use Pusher;

class TwilioController extends Controller {

	protected $sender;
	protected $message;

	public function __construct(Sender $sender, Message $message)
	{
		$this->sender = $sender;
		$this->message = $message;
	}

	public function receive()
	{
		Log::info('Webhook activation from Twilio');

	    $incoming = SMS::receive();
	    // Get the sender's number.
	    $phoneNumber = $incoming->from();
	    // Get the sender's message
	    $message = $incoming->message();
	    // Get the unique Twilio ID for the message
	    $twillioId = $incoming->id();

	    $sender = $this->sender->getSenderByPhoneNumber($phoneNumber);

	    if (! $sender) {
	    	$sender = $this->sender->createNewParseSender($phoneNumber);
	    }

    	$this->message->storeMessage(
    		$phoneNumber,
    		$message,
    		$twillioId,
    		$sender['parse_object_id']
    	);

  //   	App::bind('Pusher', function($app) {
		// 	$keys = $this->app['config']->get('services.pusher');

		//     return new \Pusher($keys['public'], $keys['secret'], $keys['app_id']);
		// });

    	App::make('Pusher')->trigger('magic-channel', $sender['parse_object_id'], [
			'phone_number' => $phoneNumber,
    		'message' 	   => $message
		]);
	}

	public function enter(Request $request)
	{
		// $request->headers->set('content-type','application/json');

	    // Get the recipient's number.
	    $phoneNumber = 'admin';

	    // Get the sender's message
	    $message = $request->get('message');

	    // Get the unique Parse Object ID of the recipient
	    $parseObjectId = $request->get('parseSenderObjectId');

	    // Get the unique Twilio ID for the message
	    $twillioId = '';

	    $this->message->sendMessage($message, $request->get('phoneNumber'));

    	$this->message->storeMessage(
    		$phoneNumber,
    		$message,
    		$twillioId,
    		$parseObjectId
    	);
	}

}
