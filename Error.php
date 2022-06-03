<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use DateTime;
use Tripsy\Library\Api\ApiClientCurl;
use Tripsy\Library\Api\ApiException;
use Tripsy\Library\Api\ApiRequest;
use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Exceptions\SystemException;

class Error
{
    /**
     * @var Language
     */
    private Language $language;

    /**
     * @var array
     */
    private array $vars = [];

    /**
     * @var bool
     */
    private bool $lastRuleError = false;

    /**
     * @var array
     */
    private array $error = [];

    /**
     * @param Language $language
     */
    public function __construct(Language $language)
    {
        //vars
        $this->language = $language;
    }

    /**
     * Return first error found
     *
     * @param bool $is_html
     * @return string
     * @throws SystemException
     */
    public function first(bool $is_html = true): string
    {
        if (empty($this->error)) {
            return '';
        }

        $first = reset($this->error);

        $first['text'] = $this->transliterate($first['text']);

        if (empty($first['vars']) === false) {
            $first['text'] = $this->replaceVars($first['text'], $first['vars']);
        }

        if ($is_html === false) {
            return $first['text'];
        }

        $first['text'] = $this->wrapText(
            $first['text'],
            $first['type']
        );

        return $this->wrapError($first['text'], $first['type']);
    }

    /**
     * Return all errors as an array
     *
     * @return array
     * @throws SystemException
     */
    public function raw(): array
    {
        return array_map(function ($v) {
            $v['text'] = $this->transliterate($v['text']);

            if (empty($v['vars']) === false) {
                $v['text'] = $this->replaceVars($v['text'], $v['vars']);
            }

            return $v['text'];
        }, $this->error);
    }

    /**
     * Return all errors as html
     *
     * @param string $type
     * @return string
     * @throws SystemException
     */
    public function all(string $type = 'none'): string
    {
        if (empty($this->error)) {
            return '';
        }

        $content = array_reduce($this->error, function ($return, $v) {
            $v['text'] = $this->transliterate($v['text']);

            if (empty($v['vars']) === false) {
                $v['text'] = $this->replaceVars($v['text'], $v['vars']);
            }

            return $return . $this->wrapText($v['text'], $v['type']);
        });

        return $this->wrapError($content, $type);
    }

    /**
     * Return all errors in separate lists based on type
     *
     * @return string
     * @throws SystemException
     */
    public function group(): string
    {
        if (empty($this->error)) {
            return '';
        }

        $group = [];

        foreach ($this->error as $e) {
            $group[$e['type']][] = $e;
        }

        $output = '';

        foreach ($group as $type => $errors) {
            $content = array_reduce($errors, function ($return, $v) {
                $v['text'] = $this->transliterate($v['text']);

                if (empty($v['vars']) === false) {
                    $v['text'] = $this->replaceVars($v['text'], $v['vars']);
                }

                return $return . $this->wrapText($v['text'], $v['type']);
            });

            $output .= $this->wrapError($content, $type);
        }

        return $output;
    }

    /**
     * Set vars to be replaced in the error text
     *
     * @param array $vars
     * @return $this
     */
    public function setVars(array $vars): self
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * Set pair key / value to be replaced in the error text
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    private function pushVars(string $key, string $value): void
    {
        $this->vars[$key] = $value;
    }

    /**
     * Return existing vars set for replace in the error text
     *
     * @return array
     */
    private function getVars(): array
    {
        return $this->vars;
    }

    /**
     * Reset existing vars set for replace in the error text
     * Note: run everytime on $this->add()
     *
     * @return void
     */
    private function resetVars(): void
    {
        $this->vars = [];
    }

    /**
     * Replace vars in the error text
     *
     * @param string $string
     * @param array $array
     * @return string
     */
    private function replaceVars(string $string, array $array): string
    {
        if ($array > 0) {
            $array_keys = array_keys($array);

            foreach ($array_keys as &$v) {
                $v = '{' . $v . '}';
            }

            $string = str_replace($array_keys, $array, $string);
        }

        return $string;
    }

    /**
     * Add message to error list
     *
     * @param string $text
     * @param string $type
     * @param array $vars
     * @return void
     */
    public function add(string $text, string $type = 'danger', array $vars = []): void
    {
        $this->ruleErrorTrue();
        $vars = $vars ?: $this->getVars();

        $this->resetVars();

        $this->error[] = [
            'text' => $text,
            'type' => $type,
            'vars' => $vars,
        ];
    }

    /**
     * @param Session $session
     * @param string $pattern
     * @param string $case
     * @param string $message
     * @param string $type
     *
     * @return void
     */
    public function setSessionMessage(Session $session, string $pattern, string $case, string $message, string $type = 'info'): void
    {
        //vars
        $key = $pattern . '.' . $case . '.' . $type;

        //session
        $session->set($key, $message);
    }

    /**
     * @param Session $session
     * @param string $pattern
     * @param string $case
     *
     * @return void
     */
    public function readSessionMessage(Session $session, string $pattern, string $case): void
    {
        $arr_message_type = ['info', 'warning'];

        foreach ($arr_message_type as $type) {
            $key = $pattern . '.' . $case . '.' . $type;

            if ($session->has($key)) {
                $this->add($session->get($key), $type);

                $session->remove($key);
            }
        }
    }

    /**
     * Convert a key to text
     *
     * @param string $string
     *
     * @return string
     * @throws SystemException
     */
    private function transliterate(string $string): string
    {
        return $this->language->get($string, $string);
    }

    /**
     * @param string $message
     * @param string $type
     * @return string
     */
    private function wrapText(string $message, string $type = 'none'): string
    {
        switch ($type) {
            case 'danger':
            case 'warning':
                $message = '<i class="fas fa-exclamation-triangle"></i> ' . $message;
                break;

            case 'success':
                $message = '<i class="fas fa-check-circle"></i> ' . $message;
                break;

            case 'info':
                $message = '<i class="fas fa-info-circle"></i> ' . $message;
                break;
        }

        return '<div class="alert-msg alert-' . $type . '">' . $message . '</div>';
    }

    /**
     * @param string $content
     * @param string $type
     *
     * @return string
     */
    private function wrapError(string $content, string $type = 'none'): string
    {
        return '<div class="alert-wrap alert-' . $type . '">' . $content . '</div>';
    }

    /**
     * Set lastRuleError flag to true
     *
     * @return void
     */
    private function ruleErrorTrue(): void
    {
        $this->lastRuleError = true;
    }

    /**
     * Set lastRuleError flag to false
     *
     * @return void
     */
    private function ruleErrorFalse(): void
    {
        $this->lastRuleError = false;
    }

    /**
     * Run callback if last checked rule had no error
     *
     * @param callable $callback
     * @return $this
     */
    public function ruleCallback(callable $callback): self
    {
        if ($this->lastRuleError === false) {
            call_user_func($callback);
        }

        return $this;
    }

    /**
     * Return true if last checked rule had error
     *
     * @return bool
     */
    public function ruleError(): bool
    {
        return $this->lastRuleError;
    }

    /**
     * Return true if error IS present
     *
     * @return bool
     */
    public function exist(): bool
    {
        if ($this->error) {
            return true;
        }

        return false;
    }

    /**
     * Return true if error IS NOT present
     *
     * @return bool
     */
    public function none(): bool
    {
        if ($this->error) {
            return false;
        }

        return true;
    }

    /**
     * Add message to error list if `expr` is true
     *
     * @param bool $expr
     * @param string $msg
     * @return $this
     */
    public function is_true(bool $expr, string $msg): self
    {
        $this->ruleErrorFalse();

        if ($expr === true) {
            $this->add($msg);
        }

        return $this;
    }

    /**
     * Add message to error list if `expr` is false
     *
     * @param bool $expr
     * @param string $msg
     *
     * @return $this
     */
    public function is_false(bool $expr, string $msg): self
    {
        $this->ruleErrorFalse();

        if ($expr === false) {
            $this->add($msg);
        }

        return $this;
    }

    /**
     * Add message to error list if `var` is empty
     *
     * @param string $var
     * @param string $msg
     * @return $this
     */
    public function is_empty(string $var, string $msg): self
    {
        $this->ruleErrorFalse();

        if (empty($var)) {
            $this->add($msg);
        }

        return $this;
    }

    /**
     * Check if $var_1 & $var_2 have same value
     *
     * @param string $var_1
     * @param string $var_2
     * @param string $msg
     * @return $this
     */
    public function is_match(string $var_1, string $var_2, string $msg): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value_1', $var_1);
        $this->pushVars('value_2', $var_2);

        if ($var_1 != $var_2) {
            $this->add($msg);
        }

        $this->resetVars();

        return $this;
    }

    /**
     * Check $var by regex $format
     *
     * @param string $var
     * @param string $format
     * @param string $msg *
     * @return $this
     */
    public function is_format(string $var, string $format, string $msg): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value', $var);

        if (!preg_match($format, $var)) {
            $this->add($msg);
        }

        $this->resetVars();

        return $this;
    }

    /**
     * Check if $var contains provided $invalid chars
     *
     * @param string $var
     * @param string $invalid
     * @param string $msg
     * @return $this
     */
    public function is_invalid_char(string $var, string $invalid, string $msg): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value', $var);
        $this->pushVars('char', $invalid);

        if (array_intersect(str_split($var), str_split($invalid))) {
            $this->add($msg);
        }

        $this->resetVars();

        return $this;
    }

    /**
     * Check $min length of the $var
     *
     * @param string $var
     * @param int $min
     * @param string $msg
     * @return $this
     */
    public function is_length_min(string $var, int $min, string $msg): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value', $var);
        $this->pushVars('min_length', $min);

        if (strlen($var) < $min) {
            $this->add($msg);
        }

        $this->resetVars();

        return $this;
    }

    /**
     * Check $max length of the $var
     *
     * @param string $var
     * @param int $max
     * @param string $msg
     * @return $this
     */
    public function is_length_max(string $var, int $max, string $msg): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value', $var);
        $this->pushVars('max_length', $max);

        if (strlen($var) > $max) {
            $this->add($msg);
        }

        $this->resetVars();

        return $this;
    }

    /**
     * Check $min / $max $var length
     *
     * @param string $var
     * @param int $min
     * @param int $max
     * @param string $msg
     * @return $this
     */
    public function is_length(string $var, int $min, int $max, string $msg): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value', $var);
        $this->pushVars('min_length', $min);
        $this->pushVars('max_length', $max);

        if (strlen($var) < $min || strlen($var) > $max) {
            $this->add($msg);
        }

        $this->resetVars();

        return $this;
    }

    /**
     * Check string if is a valid float number
     *
     * @param string $var
     * @param string $msg
     * @param int|null $min
     * @param int|null $max
     * @return $this
     */
    public function is_number(string $var, string $msg, int $min = null, int $max = null): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value', $var);

        if (!preg_match('~^\d+(.\d+)?$~', $var)) {
            $this->add($msg);

            return $this;
        }

        if (!is_null($min) && $var < $min) {
            $this->add($msg);

            return $this;
        }

        if (!is_null($max) && $var > $max) {
            $this->add($msg);

            return $this;
        }

        $this->resetVars();

        return $this;
    }

    /**
     * Check if string is date based on the provided $format
     *
     * @param string $var
     * @param string $msg
     * @param string $format
     * @return $this
     */
    public function is_date(string $var, string $msg, string $format = 'Y-m-d'): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value', $var);

        $d = DateTime::createFromFormat($format, $var);

        if (!$d || $d->format($format) != $var) {
            $this->add($msg);
        }

        $this->resetVars();

        return $this;
    }

    /**
     * Check url format
     *
     * @param string $var
     * @param string $msg
     * @return $this
     */
    public function is_url(string $var, string $msg): self
    {
        $this->is_format($var, '/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $msg);

        return $this;
    }

    /**
     * Check if url exist based on http response code
     *
     * @param $var
     * @param $msg
     * @return $this
     */
    public function url_exist($var, $msg): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value', $var);

        if ($response = @get_headers($var)) {
            if (strpos($response[0], '404 Not Found') !== false) {
                $this->add($msg);
            }
        } else {
            $this->add($msg);
        }

        $this->resetVars();

        return $this;
    }

    /**
     * Check string is in $array
     *
     * @param string $var
     * @param array $array
     * @param string $msg
     * @return $this
     */
    public function in_array(string $var, array $array, string $msg): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value', $var);

        if (in_array($var, $array) === false) {
            $this->add($msg);
        }

        $this->resetVars();

        return $this;
    }

    /**
     * Check if string is email
     *
     * @param string $var
     * @param string $msg
     * @return $this
     */
    public function is_email(string $var, string $msg): self
    {
        $this->is_format($var, '/^[a-zA-Z0-9_\-.]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-.]+$/', $msg);
        //$this->is_format($var, '/^[a-zA-Z0-9_\-.]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/', $msg);

        return $this;
    }

    /**
     * Check birthdate
     *
     * @param string $var
     * @param string $msg
     * @param int $min_age
     * @return $this
     */
    public function is_valid_birthdate(string $var, string $msg, int $min_age = 1): self
    {
        $this->ruleErrorFalse();

        $this->pushVars('value', $var);

        $format = 'Y-m-d';
        $d = DateTime::createFromFormat($format, $var);

        if ($d && $d->format($format) == $var) {
            $c = new DateTime();
            $years = $c->diff($d)->format('%y');

            if ($years < $min_age) {
                $this->add($msg);
            }
        } else {
            $this->add($msg);
        }

        $this->resetVars();

        return $this;
    }

    /**
     * CSRF verification
     *
     * @param Session $session
     * @param string $case
     * @param string $msg
     * @return $this
     * @throws ConfigException
     */
    public function csrf(Session $session, string $case, string $msg): self
    {
        $this->ruleErrorFalse();

        $this->is_match(
            Request::post('csrf_' . $case), //source -> post
            $session->get('csrf.' . $case), //source -> session (note: generated via csrf_input)
            $msg
        );

        return $this;
    }

    /**
     * CAPTCHA verification
     *
     * @param Config $cfg
     * @param Session|null $session
     * @param $case
     * @param $msg
     * @return $this
     * @throws ApiException
     * @throws ConfigException
     */
    public function captcha(Config $cfg, ?Session $session, $case, $msg): self
    {
        $this->ruleErrorFalse();

        $mode = (string)$cfg->get($case . '.captcha');

        switch ($mode) {
            case 'recaptcha':
                $settings = $cfg->get('captcha.recaptcha');

                $apiRequest = (new ApiRequest())
                    ->setMethod('POST')
                    ->setUri($settings['verify_uri'])
                    ->setBody([
                        'secret' => $settings['secret_key'],
                        'response' => Request::post('g-recaptcha-response')
                    ]);

                $apiClient = new ApiClientCurl($apiRequest);
                $apiResponse = $apiClient->send();

                if ($apiResponse->isSuccessStatusCode()) {
                    $res = $apiResponse->getBody();
                    $res = json_decode($res);

                    if ($res->success === false) {
                        $this->add($msg);
                    }
                } else {
                    throw new ApiException('Api request error', 0, null, $apiResponse);
                }
                break;

            case 'standard':
                $this->is_match(
                    Request::post('captcha_' . $case), //source -> post
                    $session->get('captcha'), //source -> session (note: generated via captcha image)
                    $msg
                );
                break;
        }

        return $this;
    }
}
