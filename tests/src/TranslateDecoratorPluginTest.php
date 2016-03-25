<?php

namespace CL\Swiftmailer\Test;

use PHPUnit_Framework_TestCase;
use Swift_Message;
use Swift_Events_SendEvent;
use CL\Swiftmailer\TranslateDecoratorPlugin;

/**
 * @coversDefaultClass CL\Swiftmailer\TranslateDecoratorPlugin
 */
class TranslateDecoratorPluginTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers ::beforeSendPerformed
     * @covers ::sendPerformed
     * @covers ::__construct
     */
    public function testTranslateDecorator()
    {

        $subject = 'Important message for  {FirstName} {LastName}';
        $body = 'Hello {FirstName}, we want to  inform you about new product that we offer.';

        $users = [
            ['email' => 'example@abv.bg', 'firstName' => 'Иван', 'lastName' => 'Петров'],
            ['email' => 'example@mail.bg', 'firstName' => 'Наташа', 'lastName' => 'Романова', 'lang' => 'ru'],
            ['email' => 'example@example.com', 'firstName' => 'Jon', 'lastName' => 'Doe'],

        ];
        $replacements = array();
        foreach ($users as $user) {
            $replacements[$user['email']] = ['{FirstName}' => $user['firstName'], '{LastName}' => $user['lastName']];
            if (isset($user['lang'])){
                $replacements[$user['email']]['lang'] = $user['lang'];
            }

        }
        $translates = [
            'bg' => ['subject' => 'Важно съобщение за {FirstName} {LastName}',
                     'body'=>'Здравейте {FirstName}, искаме да ви се информираме за новия продукт който предлагаме'],
            'ru'=>['subject' => 'Важное сообщение {FirstName} {LastName}',
                   'body'=>'Здравствуйте {FirstName}, мы хотим получить информацию о новом продукте, который мы предлагаем.']
        ];

        $decorator = new TranslateDecoratorPlugin($translates, $replacements);
        $message = Swift_Message::newInstance();

        $message->setSubject($subject)
            ->setBody($body);

        /**
         * Test with defined user language
         */

        $message->setTo('example@mail.bg');
        $sendEvent = $this->createSendEvent($message);
        $decorator->beforeSendPerformed($sendEvent);
        $message->getBody();
        $this->assertEquals('Важное сообщение Наташа Романова',  $message->getSubject());
        $this->assertEquals('Здравствуйте Наташа, мы хотим получить информацию о новом продукте, который мы предлагаем.',  $message->getBody());

        $decorator->sendPerformed($sendEvent);
        $this->assertEquals($subject,  $message->getSubject());
        $this->assertEquals($body,  $message->getBody());


        /**
         * Test without defined user language
         * translate will be based on email domain.
         */


        $message->setTo('example@abv.bg');
        $sendEvent = $this->createSendEvent($message);
        $decorator->beforeSendPerformed($sendEvent);
        $message->getBody();
        $this->assertEquals('Важно съобщение за Иван Петров',  $message->getSubject());
        $this->assertEquals('Здравейте Иван, искаме да ви се информираме за новия продукт който предлагаме',  $message->getBody());

        $decorator->sendPerformed($sendEvent);
        $this->assertEquals($subject,  $message->getSubject());
        $this->assertEquals($body,  $message->getBody());


        /**
         * Test without defined user language
         * translate will be based on email domain.
         * If translate not found must be used default message and subject
         */


        $message->setTo('example@example.com');
        $sendEvent = $this->createSendEvent($message);
        $decorator->beforeSendPerformed($sendEvent);
        $message->getBody();
        $this->assertEquals('Important message for  Jon Doe',  $message->getSubject());
        $this->assertEquals('Hello Jon, we want to  inform you about new product that we offer.',  $message->getBody());

        $decorator->sendPerformed($sendEvent);
        $this->assertEquals($subject,  $message->getSubject());
        $this->assertEquals($body,  $message->getBody());
    }

    /**
     * @param $message
     *
     * @return Swift_Events_SendEvent
     */
    public function createSendEvent($message)
    {
        /** @var  $event  Swift_Events_SendEvent*/
        $event = $this->getMockBuilder('Swift_Events_SendEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getMessage')
            ->will($this->returnValue($message));
        return $event;
    }
}
