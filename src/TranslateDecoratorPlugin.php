<?php

namespace CL\Swiftmailer;
use Swift_Events_SendEvent;
use Swift_Plugins_DecoratorPlugin;

/**
 * Allows customization and translation of Messages on-the-fly.
 *
 * @author    Evstati Zarkov <evstati.zarkov@gmail.com>
 * @copyright 2016, root
 * @license   http://spdx.org/licenses/BSD-3-Clause
 * @version   0.12
 *
 * @see   Swift_Plugins_DecoratorPlugin
 *
 */
class TranslateDecoratorPlugin extends Swift_Plugins_DecoratorPlugin {
    private $_translations;
    private $_msgTranslated = false;
    private $_defaultSubject;
    private $_defaultBody;
    private $_languageKey;

    /**
     * TranslateDecoratorPlugin constructor.
     * Sets translates, replacements and custom key for translations .
     *
     * Translations should be of the form:
     * <code>
     * $translations = array(
     *  "bg" => array("subject" => "translation for subject", "body" => "translation for body" ),
     *  "ru" => array("subject" => "translation for subject", "body" => "translation for body" ),
     * )
     * </code>
     *
     * @see   Swift_Plugins_DecoratorPlugin
     * When using an array for $replacements and language for translation is defined , it should be of the form:
     * <code>
     * $replacements = array("address1@domain.tld" => array("{a}" => "b", "{c}" => "d", 'lang' => 'bg'));
     * </code>
     * or
     * <code>
     * $replacements = array("address2@domain.tld" => array("{a}" => "x", "{c}" => "y" $languageKey => 'ru'));
     * </code>
     *
     * @param        $translations
     * @param        mixed $replacements Array or Swift_Plugins_Decorator_Replacements
     * @param string $languageKey
     */
    public function __construct($translations, $replacements, $languageKey = 'lang') {
        $this->setTranslations($translations);
        $this->_languageKey = $languageKey;
        parent::__construct($replacements);
    }

    /**
     * Set translations
     * @param $translations
     */
    public function setTranslations($translations) {
        $this->_translations = $translations;
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     * If language for translation is not set will be used email domain.
     * If translation is not set will be used default message
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt) {
        $langIsFound = false;

        $message = $evt->getMessage();

        $to = array_keys($message->getTo());
        $address = array_shift($to);
        if (isset($this->getReplacementsFor($address)[$this->_languageKey])) {
            $lang = $this->getReplacementsFor($address)[$this->_languageKey];
            $langIsFound = true;
        }

        if (!$langIsFound) {
            list($user, $mailDomain) = explode('@', $address);
            unset($user);
            $mailDomainParts = explode('.', $mailDomain);
            $lang = $mailDomainParts[count($mailDomainParts) - 1];
        }

        if ($this->_isTranslated($lang)) {

            if ($this->_getTranslateFor($lang, 'subject')) {
                $subjectTranslated = $this->_getTranslateFor($lang, 'subject');
                $this->_setDefaultSubject($message->getSubject());
                $message->setSubject($subjectTranslated);
                $this->_msgTranslated = true;
            }

            if ($this->_getTranslateFor($lang, 'body')) {
                $bodyReplaced = $this->_getTranslateFor($lang, 'body');
                $this->_setDefaultBody($message->getBody());
                $message->setBody($bodyReplaced);
                $this->_msgTranslated = true;
            }
        }

        parent::beforeSendPerformed($evt);
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function sendPerformed(Swift_Events_SendEvent $evt) {
        parent::sendPerformed($evt);
        $this->_setMessageToDefault($evt->getMessage());
    }

    /**
     * Get translation for message boy or subject if is set
     * @param $lang
     * @param $type
     *
     * @return bool
     */
    private function _getTranslateFor($lang, $type) {
        if (isset($this->_translations[$lang][$type])) {
            return $this->_translations[$lang][$type];
        }

        return false;
    }

    /**
     * Check is translation set
     *
     * @param $lang
     *
     * @return bool
     */
    private function _isTranslated($lang) {
        if (isset($this->_translations[$lang])) {
            return true;
        }

        return false;
    }

    /**
     * Restore a changed message back to its original state
     *
     * @param $message Swift_Mime_Message
     */
    private function _setMessageToDefault($message) {
        if ($this->_msgTranslated) {
            $message->setSubject($this->_defaultSubject);
            $message->setBody($this->_defaultBody);
        }
    }

    /**
     * Set default body of message
     * @param string $defaultBody
     */
    private function _setDefaultBody($defaultBody) {
        if ($this->_defaultBody != $defaultBody) {
            $this->_defaultBody = $defaultBody;
        }
    }

    /**
     * Set default subject of message
     * @param string $defaultSubject
     */
    private function _setDefaultSubject($defaultSubject) {
        if ($this->_defaultSubject != $defaultSubject) {
            $this->_defaultSubject = $defaultSubject;
        }
    }
}
