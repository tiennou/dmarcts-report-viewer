<?php

class MIMEMail
{
    protected static function checkExtension()
    {
        if (!function_exists('mailparse_msg_create')) {
            throw new Exception("mailparse extension unavailable");
        }
    }

    public static function createFromFile($filename)
    {
        self::checkExtension();

        $fd = fopen($filename, "r");
        if ($fd === false) {
            throw new Exception("failed to open mail file \"{$filename}\"");
        }

        $msg = mailparse_msg_create();
        while (!feof($fd)) {
            $data = fread($fd, 2048);
            if (!mailparse_msg_parse($msg, $data)) {
                fclose($fd);
                throw new Exception("invalid mail message");
            }
        }

        return new self($msg, $fd);
    }

    public static function createFromData($data)
    {
        self::checkExtension();

        $msg = mailparse_msg_create();
        if (!mailparse_msg_parse($msg, $data)) {
            throw new Exception("invalid mail message");
        }

        return new self($msg, null, $data);
    }

    protected static function createFromMailParsePart($part, MIMEMail $parent)
    {
        if (!$part) {
            throw new Exception("invalid mime part");
        }

        $meta = mailparse_msg_get_part_data($part);
        $start = $meta['starting-pos'];
        $length = $meta['ending-pos'] + 1 - $start;
        if ($parent->fd)
        {
            fseek($parent->fd, $start);
            $data = fread($parent->fd, $length);
        }
        else
        {
            $data = substr($parent->data, $start, $length);
        }

        return self::createFromData($data);
    }

    protected $mail = null;
    protected $structure = null;
    protected $fd = null;
    protected $data = null;
    protected $meta = null;
    protected $part_id = 0;

    public $headers = array();

    protected function __construct($mail, $fd = null, $data = null)
    {
        if (!is_resource($mail))
        {
            throw new Exception('invalid argument passed to MIMEMail');
        }
        $this->mail = $mail;
        $this->structure = mailparse_msg_get_structure($mail);
        $this->meta = mailparse_msg_get_part_data($mail);
        $this->headers = $this->meta['headers'];
        $this->fd = $fd;
        $this->data = $data;

        array_shift($this->structure); // We *are* the first structure
    }

    function __destruct()
    {
        mailparse_msg_free($this->mail);
        if ($this->fd !== null)
        {
            fclose($this->fd);
        }
    }

    public function getBody()
    {
        $start = $this->meta['starting-pos-body'];
        $length = $this->meta['ending-pos-body'] - $start;
        if ($this->fd !== null) {
            fseek($this->fd, $start);
            $body = fread($this->fd, $length);
        }
        else
        {
            $body = substr($this->data, $start, $length);
        }
        return $body;
    }

    public function getPart()
    {
        $identifier = (isset($this->structure[$this->part_id]) ? $this->structure[$this->part_id++] : null);
        if ($identifier === null) return null;

        $part = mailparse_msg_get_part($this->mail, $identifier);

        $mail = self::createFromMailParsePart($part, $this);

        return $mail;
    }

    public function reset()
    {
        $this->part_id = 0;
    }
}
?>