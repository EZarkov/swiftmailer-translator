Swiftmailer Translator
======================

Extend decorator plugin with option to modify subject and body with given translated templates

Installation
------------

Install via composer

```
composer require clippings/swiftmailer-translator
```

Usage
-----
```php
 $users = [['email' => 'example@abv.bg', 'firstName' => 'Иван', 'lastName' => 'Петров'],
           ['email' => 'example@mail.bg', 'firstName' => 'Наташа', 'lastName' => 'Романова', 'lang' => 'ru'],
           ['email' => 'example@example.com', 'firstName' => 'Jon', 'lastName' => 'Doe']];
 $replacements = [];
 
 foreach ($users as $user) {
     $replacements[$user['email']] = ['{FirstName}' => $user['firstName'], '{LastName}' => $user['lastName']];
        if (isset($user['lang'])){
            $replacements[$user['email']]['lang'] = $user['lang'];
            }
        }
        
 $translates = ['bg' => ['subject' => 'Важно съобщение за {FirstName} {LastName}',
                    'body'=>'Здравейте {FirstName}, искаме да ви се информираме за новия продукт който предлагаме'],
                'ru'=>['subject' => 'Важное сообщение {FirstName} {LastName}',
                    'body'=>'Здравствуйте {FirstName}, мы хотим получить информацию о новом продукте, который мы предлагаем.']];
                    
$transport = Swift_SmtpTransport::newInstance('smtp.example.com', 465, 'ssl')->setUsername('username')->setPassword('password');
$mailer = Swift_Mailer::newInstance($transport);
$decorator = new Swift_Plugins_TranslateDecoratorPlugin($translates, $replacements);
$mailer->registerPlugin($decorator);

$message = Swift_Message::newInstance()
	->setSubject('Important message for  {FirstName}')
	->setBody(
		'Hello {FirstName}, we want to  inform you about new product that we offer.');

$failedRecipients = [];
foreach ($users as $address => $name) {
	if (is_int($address)) {
		$message->setTo($name['email']);
	} else {
		$message->setTo(array($address['email'] => $name));
	}
	$mailer->send($message, $failedRecipients);
} 
```
License
-------

Copyright (c) 2016, root Developed by Evstati Zarkov

Under BSD-3-Clause license, read LICENSE file.
