<?php

namespace C2M87\Core\Model;

use DateTime;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * Profiler class.
 */
class Profiler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Profiler default date format
     */
    const DATE_FORMAT = DateTime::RFC822;

    /**
     * @var string
     */
    protected $taskName;

    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    /**
     * @var StopwatchEvent
     */
    protected $eventData;

    /**
     * Profiler constructor.
     *
     * @param string|null $profilingTaskName
     */
    public function __construct(string $profilingTaskName = null)
    {
        if (empty($profilingTaskName)) {
            $profilingTaskName = uniqid('profiler_');
        }
        $this->taskName  = $profilingTaskName;
        $this->stopwatch = new Stopwatch();
    }

    /**
     * Starts profiling capture.
     */
    public function start()
    {
        $this->stopwatch->start($this->taskName);
    }

    /**
     * Stops profiling capture and stores result.
     *
     * @param bool $logProfilingInfo
     */
    public function stop(bool $logProfilingInfo = false)
    {
        $this->eventData = $this->stopwatch->stop($this->taskName);
        if ($logProfilingInfo && !empty($this->logger)) {
            $this->logger->info(sprintf('%s', $this));
        }
    }

    /**
     * Gets the elapsed time.
     *
     * @return float|int
     */
    public function getElapsedTime()
    {
        $this->checkForEmptyData();

        return $this->eventData->getDuration();
    }

    /**
     * Get peak memory usage (Bytes).
     *
     * @return float|int
     */
    public function getPeakMemoryUsage()
    {
        $this->checkForEmptyData();

        return $this->eventData->getMemory();
    }

    /**
     * Get peak memory usage pretty print.
     *
     * @return string
     */
    public function getPrettyMemoryUsage()
    {
        $memInKb = $this->getPeakMemoryUsage();

        return $this->formatBytes($memInKb);
    }

    /**
     * Gets end time.
     *
     * @return DateTime
     */
    public function getDuration()
    {
        $this->checkForEmptyData();

        $eventDateTime = (int) ($this->eventData->getOrigin() / 1000);
        $dateTime      = new DateTime('@' . $eventDateTime);

        return $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->checkForEmptyData();

        $dateTimeEnd = $this->getDuration()->format(self::DATE_FORMAT);
        $timeSpent   = $this->getElapsedTime();
        $peakMemory  = $this->getPrettyMemoryUsage();

        $message = "Task finished at: $dateTimeEnd\n";
        $message .= "Time spent: $timeSpent ms. Memory used: $peakMemory.";

        return $message;
    }

    /**
     * Format bytes
     *
     * @param float $bytes
     * @param int   $precision
     * @return string
     */
    protected function formatBytes($bytes, $precision = 3)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $bytes = ((float) $bytes) / pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Returns the stop watch data.
     *
     * @return StopwatchEvent
     */
    protected function getEventData()
    {
        $this->checkForEmptyData();

        return $this->eventData;
    }

    /**
     * Checks for empty event data.
     *
     * @throws InvalidArgumentException
     */
    private function checkForEmptyData()
    {
        if (empty($this->eventData)) {
            throw new InvalidArgumentException('In order to check for any data you must stop the profiler first.');
        }
    }
}