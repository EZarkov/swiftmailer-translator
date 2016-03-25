<?php

namespace CL\Swiftmailer;

/**
 *
 * @author    Evstati Zarkov <evstati.zarkov@gmail.com>
 * @copyright 2016, root
 * @license   http://spdx.org/licenses/BSD-3-Clause
 * @version 0.11
 */
class Swift_Plugins_TranslateDecoratorPlugin extends Swift_Plugins_DecoratorPlugin {
    private $_translates;
    private $_msgTranslated = false;
    private $_defaultSubject;
    private $_defaultBody;

    public function __construct($translates, $replacements) {
        $this->setTranslates($translates);
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
		if (isset($this->getReplacementsFor($address)['lang']) ) {
            $lang = $this->getReplacementsFor($address)['lang'];
			$langIsFound = true;
		}

        if (!$langIsFound) {
            list($user, $mailDomain) = explode('@', $address);
            unset($user);
            $mailDomainParts = explode('.', $mailDomain);

            $lang = $mailDomainParts[count($mailDomainParts) - 1];
        }

        if ($this->_isTranslated($lang)) {

            if ($subjectTranslated = $this->_getTranslateFor($lang, 'subject')) {
                /** @var  $header Swift_Mime_Header */
                $this->setDefaultSubject($message->getSubject());
                $message->setSubject($subjectTranslated);
                $this->_msgTranslated = true;
            }


            if ($bodyReplaced = $this->_getTranslateFor($lang, 'body')) {
                $this->setDefaultBody($message->getBody());
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
            foreach ($message->getHeaders()->getAll() as $header) {

                if ($header->getFieldName() == 'Subject') {
                    $this->setDefaultSubject($header->getFieldBodyModel());
                    $header->setFieldBodyModel($this->_defaultSubject);
                }
            }

            $message->setBody($this->_defaultBody);
        }


    }

    /**
     * @param mixed $defaultBody
     */
    private function setDefaultBody($defaultBody) {
        if ($this->_defaultBody != $defaultBody) {
            $this->_defaultBody = $defaultBody;
        }
    }

    /**
     * @param mixed $defaultSubject
     */
    public function setDefaultSubject($defaultSubject) {
        if ($this->_defaultSubject != $defaultSubject) {
            $this->_defaultSubject = $defaultSubject;
        }
    }
}
