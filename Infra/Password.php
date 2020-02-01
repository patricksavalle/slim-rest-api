<?php /** @noinspection PhpUnused */

declare(strict_types = 1);

namespace SlimRestApi\Infra;

class Password
{
    const ALPHA = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const ALNUM = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const URL = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&\'()*+,;=';
    const NUMERIC = '0123456789';
    const NOZERO = '123456789';

    /**
     * Creates the password hash we store in the database
     * @param string $password
     * @param int $work_factor
     * @return string
     */
    public static function hash(string $password, int $work_factor = 12): string
    {
        // on a i7/3Ghz, hasing at factor 12 takes 0.25s per password
        // times increase progressively, 20 will take a minute or so per hash
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $work_factor]);
    }

    /**
     * Checks the user supplied password against the hash we stored
     * @param string $password
     * @param string $stored_hash
     * @return bool
     */
    public static function check(string $password, string $stored_hash): bool
    {
        return password_verify($password, $stored_hash);
    }

    public static function randomString(string $alphabet, int $len = 8): string
    {
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= substr($alphabet, mt_rand(0, strlen($alphabet) - 1), 1);
        }
        return $str;
    }

    public static function randomMD5(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(16, $cstrong));
    }

    public static function randomSHA1(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(20, $cstrong));
    }
}
