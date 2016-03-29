<?php

namespace SwiftmailerTranslator\Test;

use PHPUnit_Framework_TestCase;
use Swift_Message;
use Swift_Events_SendEvent;
use SwiftmailerTranslator\TranslateDecoratorPlugin;

/**
 * @coversDefaultClass CL\Swiftmailer\TranslateDecoratorPlugin
 */
class TranslateDecoratorPluginTest extends PHPUnit_Framework_TestCase {
    private $_message;
    private $_event;
    private $_decorator;
    private $_subject;
    private $_body;
    private $_replacements;
    private $_translations;

    public function setUp() {

        $this->_subject  = 'Important message for {FirstName} {LastName}';
        $this->_body = 'Hello {FirstName}, we want to inform you about new product that we offer.';

        $this->_replacements = [
            'example@abv.bg' => ['{FirstName}' => 'Иван', '{LastName}' => 'Петров'],
            'example@mail.bg' =>['{FirstName}' => 'Наташа', '{LastName}' => 'Романова', 'lang' => 'ru'],
            'example@example.com' =>['{FirstName}' => 'Jon', '{LastName}' => 'Doe'],];
        $this->_translations = [
            'bg' => ['subject' => 'Важно съобщение за {FirstName} {LastName}',
                     'body'=>'Здравейте {FirstName}, искаме да ви се информираме за новия продукт който предлагаме'],
            'ru'=>['subject' => 'Важное сообщение {FirstName} {LastName}',
                   'body'=>'Здравствуйте {FirstName}, мы хотим получить информацию о новом продукте, который мы предлагаем.']];

        $this->_decorator = new TranslateDecoratorPlugin($this->_translations, $this->_replacements);
        $this->_message = Swift_Message::newInstance();
        $this->_message->setSubject($this->_subject)
            ->setBody($this->_body);
        /** @var  $event  Swift_Events_SendEvent*/
        $this->_event = $this->getMockBuilder('Swift_Events_SendEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->_event->expects($this->any())
            ->method('getMessage')
            ->will($this->returnValue($this->_message));
    }

    /**
     * Check is message translated correctly
     * @covers ::beforeSendPerformed
     * @covers ::__construct
     */
    public function testTranslatingMessage() {
        /**
         * Test with defined user language
         */
        $this->_message->setTo('example@mail.bg');
        $this->_decorator->beforeSendPerformed($this->_event);

        $this->assertEquals('Важное сообщение Наташа Романова', $this->_message->getSubject());
        $this->assertEquals('Здравствуйте Наташа, мы хотим получить информацию о новом продукте, который мы предлагаем.', $this->_message->getBody());

        $this->_decorator->sendPerformed($this->_event);

        /**
         * Test without defined user language
         * translate will be based on email domain.
         */
        $this->_message->setTo('example@abv.bg');
        $this->_decorator->beforeSendPerformed($this->_event);

        $this->assertEquals('Важно съобщение за Иван Петров', $this->_message->getSubject());
        $this->assertEquals('Здравейте Иван, искаме да ви се информираме за новия продукт който предлагаме', $this->_message->getBody());

        $this->_decorator->sendPerformed($this->_event);

        /**
         * Test without defined user language
         * translate will be based on email domain.
         * If translate not found must be used default message and subject
         */

        $this->_message->setTo('example@example.com');
        $this->_decorator->beforeSendPerformed($this->_event);

        $this->assertEquals('Important message for Jon Doe', $this->_message->getSubject());
        $this->assertEquals('Hello Jon, we want to inform you about new product that we offer.', $this->_message->getBody());

        $this->_decorator->sendPerformed($this->_event);
    }

    /**
     * Check is message restored back to its original state
     * @covers ::sendPerformed
     * @covers ::__construct
     */
    public function testResetMessage() {


        /**
         * Test with defined user language
         */
        $this->_message->setTo('example@mail.bg');
        $this->_decorator->beforeSendPerformed($this->_event);
        $this->_decorator->sendPerformed($this->_event);

        $this->assertEquals($this->_subject, $this->_message->getSubject());
        $this->assertEquals($this->_body, $this->_message->getBody());

        /**
         * Test without defined user language
         * translate will be based on email domain.
         */
        $this->_message->setTo('example@abv.bg');
        $this->_decorator->beforeSendPerformed($this->_event);
        $this->_decorator->sendPerformed($this->_event);

        $this->assertEquals($this->_subject, $this->_message->getSubject());
        $this->assertEquals($this->_body, $this->_message->getBody());


        /**
         * Test without defined user language
         * translate will be based on email domain.
         * If translate not found must be used default message and subject
         */
        $this->_message->setTo('example@example.com');
        $this->_decorator->beforeSendPerformed($this->_event);
        $this->_decorator->sendPerformed($this->_event);

        $this->assertEquals($this->_subject, $this->_message->getSubject());
        $this->assertEquals($this->_body, $this->_message->getBody());
    }

    /**
     * Check is given custom key work correctly
     * @covers ::beforeSendPerformed
     * @covers ::__construct
     *
     */
    public function testCustomKey() {

        $replacements = ['example@mail.bg' => ['{FirstName}' => 'Наташа', '{LastName}' => 'Романова', 'testKey' => 'ru']];

        $this->_decorator = new TranslateDecoratorPlugin($this->_translations, $replacements, 'testKey');
        $this->_message->setTo('example@mail.bg');
        $this->_decorator->beforeSendPerformed($this->_event);

        $this->assertEquals('Важное сообщение Наташа Романова', $this->_message->getSubject());
        $this->assertEquals('Здравствуйте Наташа, мы хотим получить информацию о новом продукте, который мы предлагаем.', $this->_message->getBody());

        $this->_decorator->sendPerformed($this->_event);
    }
}
