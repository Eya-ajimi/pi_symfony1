<?php
// src/Service/TwilioSmsService.php
namespace App\Service;

use Twilio\Rest\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TwilioSmsService
{
    private $client;
    private $twilioNumber;

    public function __construct(ParameterBagInterface $params)
    {
        $accountSid = $params->get('TWILIO_ACCOUNT_SID');
        $authToken = $params->get('TWILIO_AUTH_TOKEN');
        $this->twilioNumber = $params->get('TWILIO_PHONE_NUMBER');
        
        $this->client = new Client($accountSid, $authToken);
    }

    public function sendSms(string $to, string $message): bool
    {
        try {
            // Format Tunisian number by adding +216 prefix
            if (strlen($to) === 8 && is_numeric($to)) {
                $to = '+216' . $to;
            }

            $this->client->messages->create(
                $to,
                [
                    'from' => $this->twilioNumber,
                    'body' => $message
                ]
            );
            
            return true;
        } catch (\Exception $e) {
            // Log error or handle it as needed
            return false;
        }
    }
}