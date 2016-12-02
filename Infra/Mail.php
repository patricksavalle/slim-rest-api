<?php

/**
 * TRIPDRIVE.COM
 *
 * @link:       api.tripdrive.com
 * @copyright:  VCK TRAVEL BV, 2016
 * @author:     patrick@patricksavalle.com
 *
 * Note: use coding standards at http://www.php-fig.org/psr/
 */

declare(strict_types = 1);

namespace SlimRestApi\Infra;

require_once BASE_PATH . '/Infra/Ini.php';
require_once BASE_PATH . '/Infra/Singleton.php';

/**
 *  SMTP, instantiates a SMTP object and delegates all calls to this object.
 *  Singleton-pattern.
 *  We use PHPMailer, see: https://github.com/PHPMailer/PHPMailer
 */
final class Mail extends Singleton
{
    static protected $instance = null;

    static public function setSubject($subject)
    {
        static::setProperty('Subject', $subject);
    }

    static public function setBody(string $body)
    {
        static::setProperty('Body', $body);
    }

    static public function addAddress(string $address)
    {
        if (empty(Ini::get('mailgun_testmode_redirect_address')) or in_array(substr($address, strpos($address, '@') + 1), Ini::get('mailgun_testmode_allowed_domain'))) {
            parent::addAddress($address);
        } else {
            foreach (Ini::get('mailgun_testmode_redirect_address') as $redirect) {
                parent::addAddress($redirect);
            }
        }
    }

    static public function setAltBody(string $body)
    {
        static::setProperty('AltBody', $body);
    }

    static public function getErrorInfo()
    {
        return static::getProperty('ErrorInfo');
    }

    static protected function instance()
    {
        $mail = new \PHPMailer();
        $mail->isSMTP();
        $mail->Host = Ini::get('smtp_host');
        $mail->SMTPAuth = true;
        $mail->Username = Ini::get('smtp_login');
        $mail->Password = Ini::get('smtp_password');
        $mail->SMTPSecure = Ini::get('smtp_secure');
        $mail->Port = Ini::get('smtp_port');
        return $mail;
    }
}

