<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\component;

class Mail
{
    private $to = array();
    private $subject = null;
    private $message = null;
    private $headers = array();
    private $wrap = 78;
    private $params = null;
    private $uniqID = null;
    private $attachments = array();
    private $encoding = 'utf-8';

    public function __construct()
    {
        $this->setType('text');
        $this->uniqID = $this->getUniqueId();
    }

    public function setType($type = 'text')
    {
        $type = strtolower($type);
        switch ($type) {
            case 'html':
                $this->addHeader('MIME-Version', '1.0', 'MIME');
                $this->addHeader('Content-Type', 'text/html; charset="' . $this->encoding . '"', 'TYPE');
                break;
            case 'text':
                $this->addHeader('MIME-Version', '1.0', 'MIME');
                $this->addHeader('Content-Type', 'text/plain; charset="' . $this->encoding . '"', 'TYPE');
                break;
        }
    }

    public function addHeader($type, $value, $key = null)
    {
        if (empty($key))
            $this->headers[] = $type . ': ' . $value;
        else
            $this->headers[$key] = $type . ': ' . $value;
    }

    private function getUniqueId()
    {
        return md5(uniqid(time()));
    }

    public static function sendText($to, $from, $message, $subject = null)
    {
        $mail = new Mail();
        if (is_array($to)) {
            foreach ($to as $_to) {
                $mail->setTo($_to);
            }
        } else {
            $mail->setTo($to);
        }
        if (is_array($from)) {
            $mail->setFrom($from['email'], $from['name']);
        } else {
            $mail->setFrom($from);
        }
        if (!empty($subject)) $mail->setSubject($subject);
        $mail->setMessage($message);
        $mail->setType('text');
        return $mail->send();
    }

    public function setTo($email, $name = null)
    {
        $this->to[] = $this->getEmailForHeader($email, $name);
    }

    private function getEmailForHeader($email, $name = null)
    {
        $email = (string)$email;
        $name = (string)$name;
        $email = $this->filterEmail($email);
        if (empty($name)) return $email;

        $name = $this->encodeUTF8($this->filterName($name));
        return '"' . $name . '" <' . $email . '>';
    }

    private function filterEmail($email)
    {
        $email = (string)$email;
        return filter_var(
            $this->filter($email),
            FILTER_SANITIZE_EMAIL
        );
    }

    private function filter($input)
    {
        $rule = array(
            "\r" => '',
            "\n" => '',
            "\t" => '',
            '"' => '',
            ',' => '',
            '<' => '',
            '>' => ''
        );
        return strtr($input, $rule);
    }

    private function encodeUTF8($string)
    {
        if (!$this->checkUtf8()) return $string;

        $string = trim($string);
        $text = explode(' ', $string);
        $result = array();
        foreach ($text as $word) {
            $result[] = $this->stringToUTF8($word);
        }
        $space = $this->stringToUTF8(' ');
        return implode($space, $result);
    }

    private function checkUtf8()
    {
        $encoding = strtolower($this->encoding);
        return ($encoding == 'utf-8') ? true : false;

    }

    private function stringToUTF8($string)
    {
        if (!$this->checkUtf8()) return $string;
        return "=?UTF-8?B?" . base64_encode($string) . "?=";
    }

    private function filterName($name)
    {
        $name = (string)$name;
        $name = $this->filter(filter_var(
            $name,
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        ));
        return trim($name);
    }

    public function setFrom($email, $name = null)
    {
        $this->addHeaderEmail('From', $email, $name);
    }

    public function addHeaderEmail($type, $email = null, $name = null)
    {
        $value = $this->getEmailForHeader($email, $name);
        $this->addHeader($type, $value);
    }

    public function setSubject($subject)
    {
        $this->subject = $this->stringToUTF8($this->filterOther($subject));
    }

    private function filterOther($value)
    {
        $value = (string)$value;
        return filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
    }

    public function setMessage($message)
    {
        $message = str_replace(array("\n", "\0"), array("\r\n", ''), $message);
        $this->message = $message;
    }

    public function send()
    {
        if (empty($this->to)) return false;

        $to = join(', ', $this->to);

        if (!empty($this->attachments)) {
            $message = $this->assembleAttachment();
        } else {
            $message = wordwrap($this->message, $this->wrap, "\r\n");
        }

        $headers = '';
        if (!empty($this->headers)) $headers = join(PHP_EOL, $this->headers);

        return mail($to, $this->subject, $message, $headers, $this->params);
    }

    private function assembleAttachment()
    {
        $this->addHeader('MIME-Version', '1.0', 'MIME');
        $this->addHeader("Content-Type", "multipart/mixed; boundary=\"{$this->uniqID}\"");
        $body = array();
        $body[] = "This is a multi-part message in MIME format.";
        $body[] = "--{$this->_uid}";
        $body[] = 'Content-type:text/html; charset="' . $this->encoding . '"';
        $body[] = "Content-Transfer-Encoding: 8bit";
        $body[] = "";
        $body[] = $this->message;
        $body[] = "";
        $body[] = "--{$this->uniqID}";
        foreach ($this->attachments as $attachment) {
            $body[] = $this->getAttachmentMimeTemplate($attachment);
        }
        return implode(PHP_EOL, $body);
    }

    private function getAttachmentMimeTemplate($attachment)
    {
        $filename = $attachment['filename'];
        $data = $attachment['data'];
        $head[] = "Content-Type: application/octet-stream; name=\"{$filename}\"";
        $head[] = "Content-Transfer-Encoding: base64";
        $head[] = "Content-Disposition: attachment; filename=\"{$filename}\"";
        $head[] = "";
        $head[] = $data;
        $head[] = "";
        $head[] = "--{$this->uniqID}";
        return implode(PHP_EOL, $head);
    }

    public static function sendHtml($to, $from, $message, $subject = null)
    {
        $mail = new Mail();
        if (is_array($to)) {
            foreach ($to as $_to) {
                $mail->setTo($_to);
            }
        } else {
            $mail->setTo($to);
        }
        if (is_array($from)) {
            $mail->setFrom($from['email'], $from['name']);
        } else {
            $mail->setFrom($from);
        }
        if (!empty($subject)) $mail->setSubject($subject);
        $mail->setMessage($message);
        $mail->setType('html');
        return $mail->send();
    }

    public function setEncoding($type)
    {
        $this->encoding = $type;
    }

    public function setReplyTo($email, $name = null)
    {
        $this->addHeaderEmail('Reply-To', $email, $name);
    }

    public function setCc($email, $name = null)
    {
        $this->addHeaderEmail('Cc', $email, $name);
    }

    public function setBcc($email, $name = null)
    {
        $this->addHeaderEmail('Bcc', $email, $name);
    }

    public function setWrap($wrap = 78)
    {
        if ($wrap < 1) {
            $wrap = 78;
        }
        $this->wrap = $wrap;
    }

    public function setParameters($additional)
    {
        $this->params = $additional;
    }

    public function addAttachment($path, $filename = null)
    {
        if (file_exists($path)) {
            $filename = empty($filename) ? basename($path) : $filename;
            $this->attachments[] = array(
                'path' => $path,
                'filename' => $filename,
                'data' => function ($path) {
                    $filesize = filesize($path);
                    $handle = fopen($path, "r");
                    $attachment = fread($handle, $filesize);
                    fclose($handle);
                    return chunk_split(base64_encode($attachment));
                }
            );
        }
    }
}