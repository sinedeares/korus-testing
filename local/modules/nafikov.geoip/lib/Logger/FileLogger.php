<?php

namespace Nafikov\GeoIp\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class FileLogger implements LoggerInterface
{
    private string $logFile;
    private string $minLevel;

    private const LEVELS = [
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 1,
        LogLevel::NOTICE => 2,
        LogLevel::WARNING => 3,
        LogLevel::ERROR => 4,
        LogLevel::CRITICAL => 5,
        LogLevel::ALERT => 6,
        LogLevel::EMERGENCY => 7,
    ];

    public function __construct(string $logFile, string $minLevel = LogLevel::INFO)
    {
        $this->logFile = $logFile;
        $this->minLevel = $minLevel;

        $dir = dirname(($logFile));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        // Проверяем, нужно ли логировать этот уровень
        if (!$this->shouldLog($level)) {
            return;
        }

        // Интерполяция сообщения с контекстом
        // Например: "User {username} logged in" + ['username' => 'John'] = "User John logged in"
        $message = $this->interpolate($message, $context);

        // Формируем строку лога
        $timestamp = date('Y-m-d H:i:s');
        $levelStr = strtoupper($level);

        $logLine = sprintf(
            "[%s] %s: %s",
            $timestamp,
            $levelStr,
            $message
        );

        // Добавляем контекст если есть дополнительные данные
        if (!empty($context)) {
            $logLine .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $logLine .= PHP_EOL;

        // Записываем в файл
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    private function shouldLog(string $level): bool
    {
        $currentLevel = self::LEVELS[$level] ?? 0;
        $minLevel = self::LEVELS[$this->minLevel] ?? 0;

        return $currentLevel >= $minLevel;
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }
}
