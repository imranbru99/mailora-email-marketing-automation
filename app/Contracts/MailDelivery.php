<?php

namespace App\Contracts;

interface MailDelivery
{
    /** @param array{to:string,from:string,name:string,subject:string,html:string,text:string,headers:array<string,string>,message_id:string} $message */
    public function send(array $message): void;
}
