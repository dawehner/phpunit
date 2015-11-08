<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

/**
 * Windows utility for PHP sub-processes.
 *
 * @since Class available since Release 3.5.12
 */
class PHPUnit_Util_PHP_Windows extends PHPUnit_Util_PHP_Default
{

    /**
     * {@inheritdoc}
     *
     * Reading from STDOUT or STDERR hangs forever on Windows if the output is
     * too large.
     *
     * @see https://bugs.php.net/bug.php?id=51800
     */
    public function runJob($job, array $settings = [])
    {
        if (false === $stdout_handle = tmpfile()) {
            throw new PHPUnit_Framework_Exception(
                'A temporary file could not be created; verify that your TEMP environment variable is writable'
            );
        }

        $process = proc_open(
            $this->getCommand($settings),
            [
                0 => ['pipe', 'r'],
                1 => $stdout_handle,
                2 => ['pipe', 'w']
            ],
            $pipes
        );

        if (!is_resource($process)) {
            throw new PHPUnit_Framework_Exception(
                'Unable to spawn worker process'
            );
        }

        $this->process($pipes[0], $job);
        fclose($pipes[0]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);

        rewind($stdout_handle);
        $stdout = stream_get_contents($stdout_handle);
        fclose($stdout_handle);

        $this->cleanup();

        return ['stdout' => $stdout, 'stderr' => $stderr];
    }
}
