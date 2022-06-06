<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Ds\Map;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Exceptions\EmailException;
use Tripsy\Library\Standard\ObjectTools;
use Tripsy\Library\Standard\StringTools;

class Emailer
{
    private Map $config;

    private string $layout = '';
    private Address $from;
    private Address $replyTo;
    private array $to;
    private array $cc = [];
    private array $bcc = [];
    private string $content;
    private string $subject;
    private array $vars = [];
    private array $attachment = [];
    private array $embed = [];
    

    /**
     * @throws ConfigException
     */
    public function __construct(array $settings)
    {
        $this->config = ObjectTools::data($settings, [
            'from.email' => 'string',
            'from.name' => 'string',
            'smtp.host' => 'string',
            'smtp.port' => 'int',
            'smtp.username' => 'string',
            'smtp.password' => 'string',
            'debug' => 'bool',
        ]);        
        
        $this->setFrom($this->config->get('from.email'), $this->config->get('from.name'));
        $this->setReplyTo($this->config->get('from.email'), $this->config->get('from.name'));
    }

    public function setLayout(string $path): self
    {
        $this->layout = $path;

        return $this;
    }

    private function getLayout(): string
    {
        return $this->layout;
    }

    public function setFrom(string $email, string $name = ''): self
    {
        $this->from = new Address($email, $name);

        return $this;
    }

    private function getFrom(): Address
    {
        return $this->from;
    }

    public function setReplyTo(string $email, string $name = ''): self
    {
        $this->replyTo = new Address($email, $name);

        return $this;
    }

    private function getReplyTo(): Address
    {
        return $this->replyTo;
    }

    public function addTo(string $email, string $name = ''): self
    {
        $this->to[] = new Address($email, $name);

        return $this;
    }

    private function getTo(): array
    {
        return $this->to;
    }

    public function addCc(string $email, string $name = ''): self
    {
        $this->cc[] = new Address($email, $name);

        return $this;
    }

    private function getCc(): array
    {
        return $this->cc;
    }

    public function addBcc(string $email, string $name = ''): self
    {
        $this->bcc[] = new Address($email, $name);

        return $this;
    }

    private function getBcc(): array
    {
        return $this->bcc;
    }

    public function setSubject(string $string): self
    {
        $this->subject = $string;

        return $this;
    }

    private function getSubject(): string
    {
        return StringTools::interpolate($this->subject, $this->getVars());
    }

    public function setContent(string $string): self
    {
        $this->content = $string;

        return $this;
    }

    public function buildContent(string $template, array $data): self
    {
        $this->content = template($template, 'file_absolute')
            ->assign($data)
            ->parse();

        return $this;
    }

    private function getContent(): string
    {
        $layout_path = $this->getLayout();

        if (empty($layout_path) === true) {
            return $this->content;
        }

        if (file_exists($this->getLayout()) === false) {
            throw new EmailException('The layout template <strong>' . $this->getLayout() . '</strong> does not exist');
        }

        return template($this->getLayout(), 'file_absolute')
            ->assign('email_subject', $this->getSubject())
            ->assign('email_content', $this->content)
            ->assign($this->getVars())
            ->parse();
    }

    public function setVars(array $data): self
    {
        $this->vars = array_merge($this->vars, $data);

        return $this;
    }

    private function getVars(): array
    {
        return $this->vars;
    }

    public function addAttach(array $data): self
    {
        $this->attachment[] = array_merge($this->attachment, $data);

        return $this;
    }

    private function getAttach(): array
    {
        return $this->attachment;
    }

    public function addEmbed(array $data): self
    {
        $this->embed[] = array_merge($this->embed, $data);

        return $this;
    }

    private function getEmbed(): array
    {
        return $this->embed;
    }

    private function getSmtpDsn(): string //https://symfony.com/doc/4.4/reference/configuration/swiftmailer.html
    {
        $host = $this->config->get('smtp.host');
        $port = $this->config->get('smtp.port');
        $username = urlencode($this->config->get('smtp.username'));
        $password = urlencode($this->config->get('smtp.password'));
        $disable_delivery = $this->config->get('debug');

        return 'smtp://' . $username . ':' . $password . '@' . $host . ':' . $port . '/?timeout=60&encryption=ssl&auth_mode=login&disable_delivery=' . $disable_delivery;
    }

    /**
     * @return void
     * @throws EmailException
     */
    public function send(): void
    {
        $transport = Transport::fromDsn($this->getSmtpDsn());
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from($this->getFrom())
            ->to(...$this->getTo())
            ->cc(...$this->getCc())
            ->bcc(...$this->getBcc())
            ->replyTo($this->getReplyTo())
            ->subject($this->getSubject())
            ->html($this->getContent());

        if ($attachments = $this->getAttach()) {
            foreach ($attachments as $attachment) {
                if ($attachment['string']) {
                    $email->attach($attachment['source'], $attachment['name']);
                } else {
                    $email->attachFromPath($attachment['source'], $attachment['name']);
                }
            }
        }

        if ($embeds = $this->getEmbed()) {
            foreach ($embeds as $embed) {
                if ($embed['string']) {
                    $email->embed($embed['source'], $embed['name']);
                } else {
                    $email->embedFromPath($embed['source'], $embed['name']);
                }
            }
        }

        try {
            //$mailer->send($email);
            throw new EmailException('Do not send the email');
        } catch (TransportExceptionInterface $e) {
            throw new EmailException($e->getMessage());
        }
    }
}
