<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class MikrotikService
{
    protected $host;
    protected $username;
    protected $password;
    protected $port;
    protected $socket;
    protected $connected = false;

    public function __construct($host = null, $username = null, $password = null, $port = 8728)
    {
        $this->host = $host ?? config('mikrotik.host');
        $this->username = $username ?? config('mikrotik.username');
        $this->password = $password ?? config('mikrotik.password');
        $this->port = $port;
    }

    /**
     * Connect to Mikrotik router
     *
     * @return bool
     */
    public function connect(): bool
    {
        try {
            $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);

            if (!$this->socket) {
                throw new \Exception("Cannot connect to {$this->host}:{$this->port} - {$errstr} ({$errno})");
            }

            // Login
            $this->write('/login');
            $response = $this->read();

            if (isset($response[0]['!trap'])) {
                throw new \Exception('Login failed: ' . ($response[0]['message'] ?? 'Unknown error'));
            }

            // Send credentials
            $this->write('/login', false, [
                '=name=' . $this->username,
                '=password=' . $this->password,
            ]);

            $response = $this->read();

            if (isset($response[0]['!trap'])) {
                throw new \Exception('Authentication failed');
            }

            $this->connected = true;

            Log::info('Connected to Mikrotik', ['host' => $this->host]);

            return true;

        } catch (\Exception $e) {
            Log::error('Mikrotik connection failed', [
                'host' => $this->host,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Disconnect from Mikrotik
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->connected = false;
            Log::info('Disconnected from Mikrotik', ['host' => $this->host]);
        }
    }

    /**
     * Suspend user (disable PPPoE secret)
     *
     * @param Customer $customer
     * @return bool
     */
    public function suspendUser(Customer $customer): bool
    {
        try {
            if (!$this->connected && !$this->connect()) {
                return false;
            }

            $username = $this->generateUsername($customer);

            // Find user
            $this->write('/ppp/secret/print', false, [
                '?name=' . $username,
            ]);

            $response = $this->read();

            if (empty($response) || isset($response[0]['!trap'])) {
                Log::warning('User not found in Mikrotik', ['username' => $username]);
                return false;
            }

            $userId = $response[0]['.id'];

            // Disable user
            $this->write('/ppp/secret/disable', false, [
                '=.id=' . $userId,
            ]);

            $response = $this->read();

            if (isset($response[0]['!trap'])) {
                throw new \Exception('Failed to disable user: ' . ($response[0]['message'] ?? 'Unknown error'));
            }

            // Disconnect active sessions
            $this->disconnectActiveSession($username);

            Log::info('Mikrotik user suspended', [
                'username' => $username,
                'customer_id' => $customer->id,
                'host' => $this->host,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to suspend Mikrotik user', [
                'customer_id' => $customer->id,
                'host' => $this->host,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Unsuspend user (enable PPPoE secret)
     *
     * @param Customer $customer
     * @return bool
     */
    public function unsuspendUser(Customer $customer): bool
    {
        try {
            if (!$this->connected && !$this->connect()) {
                return false;
            }

            $username = $this->generateUsername($customer);

            // Find user
            $this->write('/ppp/secret/print', false, [
                '?name=' . $username,
            ]);

            $response = $this->read();

            if (empty($response) || isset($response[0]['!trap'])) {
                Log::warning('User not found in Mikrotik', ['username' => $username]);
                return false;
            }

            $userId = $response[0]['.id'];

            // Enable user
            $this->write('/ppp/secret/enable', false, [
                '=.id=' . $userId,
            ]);

            $response = $this->read();

            if (isset($response[0]['!trap'])) {
                throw new \Exception('Failed to enable user: ' . ($response[0]['message'] ?? 'Unknown error'));
            }

            Log::info('Mikrotik user unsuspended', [
                'username' => $username,
                'customer_id' => $customer->id,
                'host' => $this->host,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to unsuspend Mikrotik user', [
                'customer_id' => $customer->id,
                'host' => $this->host,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create PPPoE user
     *
     * @param Customer $customer
     * @param string $password
     * @param string $profile
     * @return bool
     */
    public function createUser(Customer $customer, string $password, string $profile = 'default'): bool
    {
        try {
            if (!$this->connected && !$this->connect()) {
                return false;
            }

            $username = $this->generateUsername($customer);

            // Create PPPoE secret
            $this->write('/ppp/secret/add', false, [
                '=name=' . $username,
                '=password=' . $password,
                '=service=pppoe',
                '=profile=' . $profile,
                '=comment=Customer ID: ' . $customer->id,
            ]);

            $response = $this->read();

            if (isset($response[0]['!trap'])) {
                throw new \Exception('Failed to create user: ' . ($response[0]['message'] ?? 'Unknown error'));
            }

            Log::info('Mikrotik user created', [
                'username' => $username,
                'customer_id' => $customer->id,
                'host' => $this->host,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to create Mikrotik user', [
                'customer_id' => $customer->id,
                'host' => $this->host,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete user
     *
     * @param Customer $customer
     * @return bool
     */
    public function deleteUser(Customer $customer): bool
    {
        try {
            if (!$this->connected && !$this->connect()) {
                return false;
            }

            $username = $this->generateUsername($customer);

            // Find user
            $this->write('/ppp/secret/print', false, [
                '?name=' . $username,
            ]);

            $response = $this->read();

            if (empty($response) || isset($response[0]['!trap'])) {
                Log::warning('User not found in Mikrotik', ['username' => $username]);
                return false;
            }

            $userId = $response[0]['.id'];

            // Remove user
            $this->write('/ppp/secret/remove', false, [
                '=.id=' . $userId,
            ]);

            $response = $this->read();

            if (isset($response[0]['!trap'])) {
                throw new \Exception('Failed to delete user: ' . ($response[0]['message'] ?? 'Unknown error'));
            }

            Log::info('Mikrotik user deleted', [
                'username' => $username,
                'customer_id' => $customer->id,
                'host' => $this->host,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete Mikrotik user', [
                'customer_id' => $customer->id,
                'host' => $this->host,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get active sessions
     *
     * @param Customer $customer
     * @return array
     */
    public function getActiveSessions(Customer $customer): array
    {
        try {
            if (!$this->connected && !$this->connect()) {
                return [];
            }

            $username = $this->generateUsername($customer);

            $this->write('/ppp/active/print', false, [
                '?name=' . $username,
            ]);

            $response = $this->read();

            if (isset($response[0]['!trap'])) {
                return [];
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Failed to get active sessions', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Disconnect active session
     *
     * @param string $username
     * @return bool
     */
    private function disconnectActiveSession(string $username): bool
    {
        try {
            // Find active session
            $this->write('/ppp/active/print', false, [
                '?name=' . $username,
            ]);

            $response = $this->read();

            if (empty($response) || isset($response[0]['!trap'])) {
                return false;
            }

            foreach ($response as $session) {
                if (isset($session['.id'])) {
                    $this->write('/ppp/active/remove', false, [
                        '=.id=' . $session['.id'],
                    ]);
                    $this->read();
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to disconnect session', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Write command to Mikrotik
     *
     * @param string $command
     * @param bool $tag
     * @param array $params
     */
    private function write(string $command, bool $tag = true, array $params = []): void
    {
        $data = [];
        $data[] = $command;

        foreach ($params as $param) {
            $data[] = $param;
        }

        foreach ($data as $line) {
            $this->writeWord($line);
        }

        $this->writeWord('');
    }

    /**
     * Write word to socket
     *
     * @param string $word
     */
    private function writeWord(string $word): void
    {
        $len = strlen($word);

        if ($len < 0x80) {
            fwrite($this->socket, chr($len) . $word);
        } elseif ($len < 0x4000) {
            fwrite($this->socket, chr(($len >> 8) | 0x80) . chr($len) . $word);
        } elseif ($len < 0x200000) {
            fwrite($this->socket, chr(($len >> 16) | 0xC0) . chr($len >> 8) . chr($len) . $word);
        } elseif ($len < 0x10000000) {
            fwrite($this->socket, chr(($len >> 24) | 0xE0) . chr($len >> 16) . chr($len >> 8) . chr($len) . $word);
        } else {
            fwrite($this->socket, chr(0xF0) . chr($len >> 24) . chr($len >> 16) . chr($len >> 8) . chr($len) . $word);
        }
    }

    /**
     * Read response from Mikrotik
     *
     * @return array
     */
    private function read(): array
    {
        $response = [];
        $current = [];

        while (true) {
            $word = $this->readWord();

            if ($word === '') {
                if (!empty($current)) {
                    $response[] = $current;
                }
                break;
            }

            if (strpos($word, '=') !== false) {
                list($key, $value) = explode('=', $word, 2);
                $current[$key] = $value;
            } else {
                $current[$word] = true;
            }
        }

        return $response;
    }

    /**
     * Read word from socket
     *
     * @return string
     */
    private function readWord(): string
    {
        $len = $this->readLen();

        if ($len === 0) {
            return '';
        }

        $word = '';
        while (strlen($word) < $len) {
            $word .= fread($this->socket, $len - strlen($word));
        }

        return $word;
    }

    /**
     * Read length from socket
     *
     * @return int
     */
    private function readLen(): int
    {
        $byte = ord(fread($this->socket, 1));

        if (($byte & 0x80) == 0x00) {
            return $byte;
        } elseif (($byte & 0xC0) == 0x80) {
            return (($byte & ~0xC0) << 8) + ord(fread($this->socket, 1));
        } elseif (($byte & 0xE0) == 0xC0) {
            return (($byte & ~0xE0) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif (($byte & 0xF0) == 0xE0) {
            return (($byte & ~0xF0) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        } elseif (($byte & 0xF8) == 0xF0) {
            return (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }

        return 0;
    }

    /**
     * Generate username from customer
     *
     * @param Customer $customer
     * @return string
     */
    private function generateUsername(Customer $customer): string
    {
        return 'customer_' . $customer->id;
    }

    /**
     * Destructor - auto disconnect
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
