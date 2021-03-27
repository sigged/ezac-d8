<?php


namespace Drupal\ezac\Util;

use Drupal;

class EzacMail {


  /**
   * The email.validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Send a simple email to specified recipient.
   *
   * @param string $module
   * @param string $key
   * @param string $to
   * @param string $message_subject
   * @param string $message_body
   */
  static function mail(string $module, string $key, string $to, string $message_subject, string $message_body) {
    $messenger = Drupal::messenger();

    $from = 'webmaster@ezac.nl';
    //$from = \Drupal::state()->get('system_mail', $my_email);
    $langcode = 'nl';
    $params = [
      'headers' => [
        'Bcc' => $from, // for mail logging
        'Reply-to' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
      ],
      'from' => $from,
      'sender' => $from,
      'subject' => $message_subject,
      'body' => $message_body,
    ];
    $reply = $from;
    $send = TRUE;

    // Finally, call MailManager::mail() to send the mail.
    $result = \Drupal::service('plugin.manager.mail')
      ->mail($module, $key, $to, $langcode, $params, $reply, $send);
    if ($result['result'] == TRUE) {
      $messenger->addMessage('Bericht is verzonden');
    }
    else {
      // This condition is also logged to the 'mail' logger channel by the
      // default PhpMail mailsystem.
      $messenger->addError('Bericht is niet verzonden.');
    }

  }

}