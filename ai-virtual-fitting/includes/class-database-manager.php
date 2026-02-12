<?php
/**
 * Database Manager for AI Virtual Fitting Plugin
 */

if (!defined("ABSPATH")) {
    exit;
}

class AI_Virtual_Fitting_Database_Manager {
    const DB_VERSION = "1.1.0";
    
    private $credits_table;
    private $sessions_table;
    private $activity_log_table;
    
    public function __construct() {
        global $wpdb;
        $this->credits_table = $wpdb->prefix . "virtual_fitting_credits";
        $this->sessions_table = $wpdb->prefix . "virtual_fitting_sessions";
        $this->activity_log_table = $wpdb->prefix . "virtual_fitting_activity_log";
    }
    
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $credits_sql = "CREATE TABLE {$this->credits_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            credits_remaining int(11) NOT NULL DEFAULT 0,
            total_credits_purchased int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        $sessions_sql = "CREATE TABLE {$this->sessions_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            status enum(\"processing\",\"completed\",\"failed\") NOT NULL DEFAULT \"processing\",
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id)
        ) $charset_collate;";
        
        $activity_log_sql = "CREATE TABLE {$this->activity_log_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            user_email varchar(255) NOT NULL,
            user_name varchar(255) NOT NULL,
            action varchar(50) NOT NULL,
            product_id bigint(20) unsigned DEFAULT NULL,
            product_name varchar(255) DEFAULT NULL,
            status enum(\"success\",\"error\") NOT NULL,
            error_message text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            processing_time float DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at),
            KEY action (action)
        ) $charset_collate;";
        
        require_once(ABSPATH . "wp-admin/includes/upgrade.php");
        dbDelta($credits_sql);
        dbDelta($sessions_sql);
        dbDelta($activity_log_sql);
        update_option("ai_virtual_fitting_db_version", self::DB_VERSION);
        
        return $this->verify_tables_exist();
    }
    
    public function verify_tables_exist() {
        global $wpdb;
        $credits_exists = $wpdb->get_var("SHOW TABLES LIKE \"{$this->credits_table}\"") === $this->credits_table;
        $sessions_exists = $wpdb->get_var("SHOW TABLES LIKE \"{$this->sessions_table}\"") === $this->sessions_table;
        $activity_log_exists = $wpdb->get_var("SHOW TABLES LIKE \"{$this->activity_log_table}\"") === $this->activity_log_table;
        return $credits_exists && $sessions_exists && $activity_log_exists;
    }
    
    public function drop_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->sessions_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->credits_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->activity_log_table}");
        delete_option("ai_virtual_fitting_db_version");
        return true;
    }
    
    public function get_credits_table() {
        return $this->credits_table;
    }
    
    public function get_sessions_table() {
        return $this->sessions_table;
    }
    
    public function get_activity_log_table() {
        return $this->activity_log_table;
    }
}
