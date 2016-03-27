<?php

namespace CL\Swiftmailer;
use Swift_Events_SendEvent;
use Swift_Plugins_DecoratorPlugin;

/**
 *
 * @author    Evstati Zarkov <evstati.zarkov@gmail.com>
 * @copyright 2016, root
 * @license   http://spdx.org/licenses/BSD-3-Clause
 * @version 0.11
 */
class TranslateDecoratorPlugin extends Swift_Plugins_DecoratorPlugin {
    private $_translates;
    private $_msgTranslated = false;
    private $_defaultSubject;
    private $_defaultBody;
    private $_languageKey;

    public function __construct($translates, $replacements, $languageKey = 'lang') {
        $this->setTranslates($translates);
        $this->_languageKey = $languageKey;
        parent::__construct($replacements);
    }


    public function setTranslates($translates) {
        $this->_translates = $translates;
    }

    /**
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

    private function _getTranslateFor($lang, $type) {
        if (isset($this->_translates[$lang][$type])) {
            return $this->_translates[$lang][$type];
        }

        return false;
    }

    private function _isTranslated($lang) {
        if (isset($this->_translates[$lang])) {
            return true;
        }

        return false;
    }

    /**
     * @param $message Swift_Mime_Message
     */
    private function _setMessageToDefault($message) {
        if ($this->_msgTranslated) {
            /** @var  $header Swift_Mime_Header */
            $message->setSubject($this->_defaultSubject);

            $message->setBody($this->_defaultBody);
        }


    }

    /**
     * @param mixed $defaultBody
     */
    private function _setDefaultBody($defaultBody) {
        if ($this->_defaultBody != $defaultBody) {
            $this->_defaultBody = $defaultBody;
        }
    }

    /**
     * @param mixed $defaultSubject
     */
    private function _setDefaultSubject($defaultSubject) {
        if ($this->_defaultSubject != $defaultSubject) {
            $this->_defaultSubject = $defaultSubject;
        }
    }
}
