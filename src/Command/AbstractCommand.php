<?php

namespace C2M87\Core\Command;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use C2M87\Core\Model\Profiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractCommand
 */
abstract class AbstractCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use LockableTrait;

    /**
     * Exit codes
     */
    const EXIT_CODE_OK            = 0;
    const EXIT_CODE_ERROR_GENERIC = 2;

    /**
     * An instance of InputInterface for reading from the console
     *
     * @var InputInterface
     */
    protected $input;

    /**
     * An instance of OutputInterface for writing to the console
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var string
     */
    protected static $defaultName = 'app:abstract-command';

    // TODO: Use https://symfony.com/doc/3.4/components/console/events.html for running messages.

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            exit(0);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
    }

    /**
     * Wraps the message into the <info> tag and allow any number of args
     * to be passed to vsprintf()
     *
     * The first argument will be the msg format and all other arguments
     * will be passed as an array to vsprintf()
     */
    public function info(/*...*/)
    {
        $args = func_get_args();
        $msg  = array_shift($args);

        $this->writeln(vsprintf($msg, $args), 'info');
    }

    /**
     * Wraps the message into the <error> tag and output to console.
     *
     * The first argument will be the msg format and all other arguments
     * will be passed as an array to vsprintf()
     */
    public function error(/*...*/)
    {
        $args = func_get_args();
        $msg  = array_shift($args);

        $this->writeln(vsprintf($msg, $args), 'error');
    }

    /**
     * Wraps the message into the <comment> tag and output to console.
     *
     * The first argument will be the msg format and all other arguments
     * will be passed as an array to vsprintf()
     */
    public function warning(/*...*/)
    {
        $args = func_get_args();
        $msg  = array_shift($args);

        $this->writeln(vsprintf($msg, $args), 'comment');
    }

    /**
     * Writes a line to output
     *
     * @param string $msg
     * @param string $type
     */
    protected function writeln($msg, $type = 'info')
    {
        $this->getOutput()->writeln("<{$type}>{$msg}</{$type}>");
    }

    /**
     * Gets an instance of OutputInterface for writing to the console
     *
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $commandName    = $this->getName();
        $this->profiler = new Profiler($commandName);
        $this->profiler->setLogger($this->logger);
        $this->profiler->start();

        $result = parent::run($input, $output);

        $this->profiler->stop(true);
        $message = sprintf("%s", $this->profiler);

        $this->info($message);

        return $result;
    }

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     * @required
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}