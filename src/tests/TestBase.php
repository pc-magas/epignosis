<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class TestBase extends TestCase 
{  
    public function tearDown():void
    {
        // Reset time mocks
        \Carbon\Carbon::setTestNow();
    }
    /**
     * Mocked email for just providing a dummy object
     *
     * @return \Symfony\Component\Mailer\MailerInterface
     */
    public function dummyMail():\Symfony\Component\Mailer\MailerInterface
    {
        return $this->createMock(\Symfony\Component\Mailer\MailerInterface::class);
    }

}