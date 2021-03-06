<?php

namespace vjolenz\OtpAuth;

use vjolenz\OtpAuth\Exceptions\NegativePasswordLengthException;
use vjolenz\OtpAuth\Exceptions\NegativeWindowSizeException;
use vjolenz\OtpAuth\Exceptions\UnsuitableHashingAlgorithmException;

class HotpAuthenticator implements OtpAuthenticatorInterface
{
    /**
     * @var string Key to be used in hash creation
     */
    protected $secret;

    /**
     * @var string Hashing algorithm to be used in hash creation
     */
    protected $algorithm = 'sha1';

    /**
     * @var int Accepting previous nth and next nth passwords
     */
    protected $windowSize = 1;

    /**
     * @var int Length of the password that will generated
     */
    protected $passwordLength = 6;

    /**
     * Generate one-time password using given moving factor.
     *
     * Generation is adapted from Java implementation in the HOTP RFC
     *
     * @see https://tools.ietf.org/html/rfc4226#page-32
     *
     * @param $movingFactor int a value that changes on a per use basis.
     *
     * @return string generated one-time password
     */
    public function generatePassword(int $movingFactor = 1): string
    {
        $binaryMovingFactor = pack('N*', 0, $movingFactor);

        $hash = hash_hmac($this->algorithm, $binaryMovingFactor, $this->secret, true);

        $hashDecimalArray = unpack('C*', $hash);

        // Array should be re-indexed
        // Since unpack returns array with index starting from 1
        $hashDecimalArray = array_values($hashDecimalArray);

        $offset = $hashDecimalArray[count($hashDecimalArray) - 1] & 0xF;

        $number = ($hashDecimalArray[$offset] & 0x7F) << 24 |
            ($hashDecimalArray[$offset + 1] & 0xFF) << 16 |
            ($hashDecimalArray[$offset + 2] & 0xFF) << 8 |
            ($hashDecimalArray[$offset + 3] & 0xFF);

        $password = $number % pow(10, $this->passwordLength);

        return str_pad($password, $this->passwordLength, '0', STR_PAD_LEFT);
    }

    /**
     * Verify one-time password using given moving factor.
     *
     * @param int|string $password
     * @param int        $movingFactor
     *
     * @return bool
     */
    public function verifyPassword($password, int $movingFactor = 1): bool
    {
        return $this->isPasswordInGivenWindow($password, $movingFactor, $this->windowSize);
    }

    /**
     * Check if password is in given window.
     *
     * @param $password
     * @param int $movingFactor
     * @param int $windowSize
     *
     * @return bool
     */
    protected function isPasswordInGivenWindow($password, int $movingFactor, int $windowSize): bool
    {
        for ($i = $movingFactor - $windowSize; $i <= $movingFactor + $windowSize; $i++) {
            if ($this->generatePassword($i) == $password) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * @param string $algorithm
     *
     * @throws \vjolenz\OtpAuth\Exceptions\UnsuitableHashingAlgorithmException
     */
    public function setAlgorithm(string $algorithm): void
    {
        if (!in_array($algorithm, hash_hmac_algos())) {
            throw new UnsuitableHashingAlgorithmException();
        }
        $this->algorithm = $algorithm;
    }

    /**
     * @return int
     */
    public function getPasswordLength(): int
    {
        return $this->passwordLength;
    }

    /**
     * @param int $passwordLength
     *
     * @throws \vjolenz\OtpAuth\Exceptions\NegativePasswordLengthException
     */
    public function setPasswordLength(int $passwordLength): void
    {
        if ($passwordLength < 1) {
            throw new NegativePasswordLengthException();
        }
        $this->passwordLength = $passwordLength;
    }

    /**
     * @return int
     */
    public function getWindowSize(): int
    {
        return $this->windowSize;
    }

    /**
     * @param int $windowSize
     *
     * @throws \vjolenz\OtpAuth\Exceptions\NegativeWindowSizeException
     */
    public function setWindowSize(int $windowSize): void
    {
        if ($windowSize < 0) {
            throw new NegativeWindowSizeException();
        }
        $this->windowSize = $windowSize;
    }
}
