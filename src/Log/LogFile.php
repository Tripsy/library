<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Log;

class LogFile extends Logger
{
    private string $pathLogs;

    public function __construct(string $pathLogs)
    {
        $this->pathLogs = $pathLogs;
    }

    /**
     * Write to log file
     *
     * @param string $label
     * @param string $message
     * @param array $context
     * @return void
     * @throws LogException
     */
    public function record(string $label, string $message, array $context): void
    {
        $content = date('Y-m-d H:i') . ' .::. ';

        if (empty($context['file']) === false) {
            $content .= $context['file'];

            unset($context['file']);
        }

        if (empty($context['line']) === false) {
            $content .= ' ~ ' . $context['line'];

            unset($context['line']);
        }

        $content .= PHP_EOL . $message;

        if (empty($context) === false) {
            $content .= PHP_EOL . json_encode($context);
        }

        $file_path = $this->getFilePath($label);
        @$file_stream = fopen($file_path, 'a'); // 'a' -> place pointer at end of file

        if (empty($file_stream)) {
            throw new LogException('Cannot open the file for write (eg: ' . $file_path . ')');
        }

        //write to end of file
        fwrite($file_stream, $content . PHP_EOL . PHP_EOL);

        //close file stream
        fclose($file_stream);
    }

    /**
     * Get log file path; Create log file if it does not exist
     *
     * @param string $file_name
     * @return string
     * @throws LogException
     */
    private function getFilePath(string $file_name): string
    {
        $file_name = strtolower($file_name) . '.log';
        $file_path = $this->pathLogs . '/' . $file_name;

        //Create log file if it doesn't exist.
        if (!file_exists($file_path)) {
            @$res = fopen($file_path, 'w');

            if (empty($res)) {
                throw new LogException('Cannot read / create file (eg: ' . $file_path . ')');
            }
        }

        return $file_path;
    }
}
