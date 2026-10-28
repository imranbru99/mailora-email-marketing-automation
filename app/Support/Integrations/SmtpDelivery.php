<?php

namespace App\Support\Integrations;

use App\Contracts\MailDelivery;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class SmtpDelivery implements MailDelivery
{
    /** @param array{to:string,from:string,name:string,subject:string,html:string,text:string,headers:array<string,string>,message_id:string} $message */
    public function send(array $message): void
    {
        if (config('platform.sandbox') || config('mail.default') !== 'smtp') {
            throw new \LogicException('An explicit SMTP configuration is required.');
        }
        Mail::mailer('smtp')->html($message['html'], function (Message $mail) use ($message) {
            $mail->to($message['to'])->from($message['from'], $message['name'])->subject($message['subject']);
            $email = $mail->getSymfonyMessage();
            $email->text($message['text']);
            $headers = $email->getHeaders();
            $headers->addIdHeader('Message-ID', $message['message_id']);
            foreach ($message['headers'] as $name => $value) {
                $headers->addTextHeader($name, $value);
            }
        });
    }
}
