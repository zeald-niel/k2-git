<?php
namespace Bookly\Lib\Base;

/**
 * Class Installer
 * @package Bookly\Lib\Base
 */
abstract class Installer extends Schema
{
    protected $options = array();

    /******************************************************************************************************************
     * Public methods                                                                                                 *
     ******************************************************************************************************************/

    /**
     * Install.
     */
    public function install()
    {
        $plugin_class = Plugin::getPluginFor( $this );
        $data_loaded_option_name = $plugin_class::getPrefix() . 'data_loaded';

        // Create tables and load data if it hasn't been loaded yet.
        if ( ! get_option( $data_loaded_option_name ) ) {
            $this->createTables();
            $this->loadData();
        }

        update_option( $data_loaded_option_name, '1' );
    }

    /**
     * Uninstall.
     */
    public function uninstall()
    {
        $this->removeData();
        $this->dropPluginTables();
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Create tables.
     */
    public function createTables()
    {

    }

    /**
     * Drop tables (@see \Bookly\Backend\Modules\Debug\Controller ).
     */
    public function dropTables()
    {
        $this->dropPluginTables();
    }

    /**
     * Load data.
     */
    public function loadData()
    {
        // Add default options.
        $plugin_class  = Plugin::getPluginFor( $this );
        $plugin_prefix = $plugin_class::getPrefix();
        add_option( $plugin_prefix . 'data_loaded', '0' );
        add_option( $plugin_prefix . 'db_version',  $plugin_class::getVersion() );
        add_option( $plugin_prefix . 'installation_time', time() );
        add_option( $plugin_prefix . 'grace_start', time() + 2 * WEEK_IN_SECONDS );
        add_option( $plugin_class::getPurchaseCodeOption(), '' );
        if ( Plugin::getPrefix() != 'bookly_' ) {
            add_option( $plugin_prefix . 'enabled', '1' );
        }

        // Add plugin options.
        foreach ( $this->options as $name => $value ) {
            add_option( $name, $value );
            if ( strpos( $name, 'bookly_l10n_' ) === 0 ) {
                do_action( 'wpml_register_single_string', 'bookly', $name, $value );
            }
        }
    }

    /**
     * Remove data.
     */
    public function removeData()
    {
        // Remove options.
        foreach ( $this->options as $name => $value ) {
            delete_option( $name );
        }
        $plugin_class  = Plugin::getPluginFor( $this );
        $plugin_prefix = $plugin_class::getPrefix();
        delete_option( $plugin_prefix . 'data_loaded' );
        delete_option( $plugin_prefix . 'db_version' );
        delete_option( $plugin_prefix . 'installation_time' );
        delete_option( $plugin_prefix . 'grace_start' );
        delete_option( $plugin_prefix . 'enabled' );
        delete_option( $plugin_class::getPurchaseCodeOption() );
    }

}
