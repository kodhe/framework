<?php

namespace Kodhe\Library\Session\Drivers;

use Kodhe\Library\Session\Driver;
use Kodhe\Library\Session\HandlerInterface;
use Exception;
use Kodhe\Framework\Database\Query\Builder;

/**
 * CodeIgniter Session Database Driver
 */
class Database extends Driver implements HandlerInterface
{
    /**
     * DB object
     */
    protected Builder $_db;

    /**
     * Row exists flag
     */
    protected bool $row_exists = false;

    /**
     * Lock "driver" flag
     */
    protected ?string $platform = null;

    /**
     * Lock identifier
     */
    protected $lock = false;

    /**
     * Constructor
     */
    public function __construct(array &$params)
    {
        parent::__construct($params);

        $ci = kodhe();
        isset($ci->db) or $ci->load->database();
        $this->_db = $ci->db;

        if (! $this->_db instanceof Builder) {
            throw new Exception('Query Builder not enabled for the configured database.');
        }

        if ($this->_db->pconnect) {
            throw new Exception('Configured database connection is persistent.');
        }

        if ($this->_db->cache_on) {
            throw new Exception('Configured database connection has cache enabled.');
        }

        $db_driver = $this->_db->dbdriver . (empty($this->_db->subdriver) ? '' : '_' . $this->_db->subdriver);
        
        if (str_contains($db_driver, 'mysql')) {
            $this->platform = 'mysql';
        } elseif (in_array($db_driver, ['postgre', 'pdo_pgsql'], true)) {
            $this->platform = 'postgre';
        }

        // BC work-around for old 'sess_table_name' setting
        if (! isset($this->_config['save_path']) && ($this->_config['save_path'] = config_item('sess_table_name'))) {
            log_message('debug', 'Session: "sess_save_path" is empty; using BC fallback to "sess_table_name".');
        }
    }

    /**
     * Open session handler
     */
    public function open(string $save_path, string $name): bool
    {
        if (empty($this->_db->conn_id) && ! $this->_db->db_connect()) {
            return false;
        }

        $this->php5_validate_id();
        return true;
    }

    /**
     * Read session data
     */
    public function read(string $session_id): string|false
    {
        if ($this->getLock($session_id) === false) {
            return false;
        }

        $this->_db->reset_query();
        $this->_session_id = $session_id;

        $this->_db->select('data')
                  ->from($this->_config['save_path'])
                  ->where('id', $session_id);

        if ($this->_config['match_ip']) {
            $this->_db->where('ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
        }

        $result = $this->_db->get();
        if (! $result || ($row = $result->row()) === null) {
            $this->row_exists = false;
            $this->_fingerprint = md5('');
            return '';
        }

        $session_data = ($this->platform === 'postgre')
            ? base64_decode(rtrim($row->data))
            : $row->data;

        $this->_fingerprint = md5($session_data);
        $this->row_exists = true;
        
        return $session_data;
    }

    /**
     * Write session data
     */
    public function write(string $session_id, string $session_data): bool
    {
        $this->_db->reset_query();

        // Was the ID regenerated?
        if (isset($this->_session_id) && $session_id !== $this->_session_id) {
            if (! $this->releaseLock() || ! $this->getLock($session_id)) {
                return false;
            }
            $this->row_exists = false;
            $this->_session_id = $session_id;
        } elseif ($this->lock === false) {
            return false;
        }

        if ($this->row_exists === false) {
            $insert_data = [
                'id'         => $session_id,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'timestamp'  => time(),
                'data'       => $this->platform === 'postgre' 
                    ? base64_encode($session_data) 
                    : $session_data
            ];

            if ($this->_db->insert($this->_config['save_path'], $insert_data)) {
                $this->_fingerprint = md5($session_data);
                $this->row_exists = true;
                return true;
            }
            return false;
        }

        $this->_db->where('id', $session_id);
        if ($this->_config['match_ip']) {
            $this->_db->where('ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
        }

        $update_data = ['timestamp' => time()];
        if ($this->_fingerprint !== md5($session_data)) {
            $update_data['data'] = $this->platform === 'postgre'
                ? base64_encode($session_data)
                : $session_data;
        }

        if ($this->_db->update($this->_config['save_path'], $update_data)) {
            $this->_fingerprint = md5($session_data);
            return true;
        }

        return false;
    }

    /**
     * Close session handler
     */
    public function close(): bool
    {
        return $this->lock && ! $this->releaseLock() ? false : true;
    }

    /**
     * Destroy session
     */
    public function destroy(string $session_id): bool
    {
        if ($this->lock) {
            $this->_db->reset_query();
            $this->_db->where('id', $session_id);
            
            if ($this->_config['match_ip']) {
                $this->_db->where('ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
            }

            if (! $this->_db->delete($this->_config['save_path'])) {
                return false;
            }
        }

        if ($this->close() === true) {
            $this->_cookie_destroy();
            return true;
        }

        return false;
    }

    /**
     * Garbage collection
     */
    public function gc(int $maxlifetime): int|false
    {
        $this->_db->reset_query();
        $result = $this->_db->delete(
            $this->_config['save_path'],
            'timestamp < ' . (time() - $maxlifetime)
        );
        
        return $result ? $this->_db->affected_rows() : false;
    }

    /**
     * Validate session ID
     */
    public function validateSessionId(string $id): bool
    {
        $this->_db->reset_query();
        $this->_db->select('1')
                  ->from($this->_config['save_path'])
                  ->where('id', $id);
        
        if ($this->_config['match_ip']) {
            $this->_db->where('ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
        }
        
        $result = $this->_db->get();
        return $result && $result->row() !== null;
    }

    /**
     * Get lock
     */
    protected function getLock(string $session_id): bool
    {
        if ($this->platform === 'mysql') {
            $arg = md5($session_id . ($this->_config['match_ip'] ? '_' . ($_SERVER['REMOTE_ADDR'] ?? '') : ''));
            $result = $this->_db->query("SELECT GET_LOCK('" . $arg . "', 300) AS ci_session_lock")->row();
            
            if ($result && $result->ci_session_lock) {
                $this->lock = $arg;
                return true;
            }
            return false;
        }
        
        if ($this->platform === 'postgre') {
            $arg = "hashtext('" . $session_id . "')" . 
                   ($this->_config['match_ip'] ? ", hashtext('" . ($_SERVER['REMOTE_ADDR'] ?? '') . "')" : '');
            
            if ($this->_db->simple_query('SELECT pg_advisory_lock(' . $arg . ')')) {
                $this->lock = $arg;
                return true;
            }
            return false;
        }

        return parent::_get_lock($session_id);
    }

    /**
     * Release lock
     */
    protected function releaseLock(): bool
    {
        if (! $this->lock) {
            return true;
        }

        if ($this->platform === 'mysql') {
            $result = $this->_db->query("SELECT RELEASE_LOCK('" . $this->lock . "') AS ci_session_lock")->row();
            if ($result && $result->ci_session_lock) {
                $this->lock = false;
                return true;
            }
            return false;
        }
        
        if ($this->platform === 'postgre') {
            if ($this->_db->simple_query('SELECT pg_advisory_unlock(' . $this->lock . ')')) {
                $this->lock = false;
                return true;
            }
            return false;
        }

        return parent::_release_lock();
    }
}