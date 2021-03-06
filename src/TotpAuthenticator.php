<?php

namespace vjolenz\OtpAuth;

use vjolenz\OtpAuth\Exceptions\NegativeIntervalException;

class TotpAuthenticator extends HotpAuthenticator
{
    /**
     * @var int Indicates how many seconds a password is valid for
     */
    private $interval = 30;

    /**
     * Generate one-time password on the basis of timestamp.
     *
     * @param int|null $timestamp
     *
     * @return string generated password
     */
    public function generatePassword(int $timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        return parent::generatePassword(floor($timestamp / $this->interval));
    }

    /**
     * Verify one-time password on the basis of timestamp.
     *
     * @param int      $password
     * @param int|null $timestamp
     *
     * @return bool
     */
    public function verifyPassword($password, int $timestamp = null): bool
    {
        return $this->isPasswordInGivenWindow($password, $timestamp, $this->interval * $this->windowSize);
    }

    /**
     * @return int
     */
    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * @param int $interval
     *
     * @throws \vjolenz\OtpAuth\Exceptions\NegativeIntervalException
     */
    public function setInterval(int $interval): void
    {
        if ($interval < 0) {
            throw new NegativeIntervalException();
        }
        $this->interval = $interval;
    }
}
