<?php
namespace Bookly\Lib\Base;

use Bookly\Lib;

/**
 * Class Plugin
 * @package Bookly\Lib\Base
 */
abstract class Plugin
{
    /******************************************************************************************************************
     * Protected properties                                                                                           *
     ******************************************************************************************************************/

    /**
     * Prefix for options and metas.
     *
     * @staticvar string
     */
    protected static $prefix;

    /**
     * Plugin title.
     *
     * @staticvar string
     */
    protected static $title;

    /**
     * Plugin version.
     *
     * @staticvar string
     */
    protected static $version;

    /**
     * Plugin slug.
     *
     * @staticvar string
     */
    protected static $slug;

    /**
     * Path to plugin directory.
     *
     * @staticvar string
     */
    protected static $directory;

    /**
     * Path to plugin main file.
     *
     * @staticvar string
     */
    protected static $main_file;

    /**
     * Plugin basename.
     *
     * @staticvar string
     */
    protected static $basename;

    /**
     * Plugin text domain.
     *
     * @staticvar string
     */
    protected static $text_domain;

    /**
     * Root namespace of plugin classes.
     *
     * @staticvar string
     */
    protected static $root_namespace;

    /******************************************************************************************************************
     * Private properties                                                                                             *
     ******************************************************************************************************************/

    /**
     * Array of plugin classes for objects.
     *
     * @var static[]
     */
    private static $plugin_classes = array();

    /******************************************************************************************************************
     * Public methods                                                                                                 *
     ******************************************************************************************************************/

    /**
     * Start Bookly plugin.
     */
    public static function run()
    {
        static::registerHooks();
        static::initUpdateChecker();
        // Run updates.
        $updater_class = static::getRootNamespace() . '\Lib\Updater';
        $updater = new $updater_class();
        $updater->run();
    }

    /**
     * Activate plugin.
     *
     * @param bool $network_wide
     */
    public static function activate( $network_wide )
    {
        if ( $network_wide && has_action( 'bookly_plugin_activate' ) ) {
            do_action( 'bookly_plugin_activate', static::getSlug() );
        } else {
            $installer_class = static::getRootNamespace() . '\Lib\Installer';
            $installer = new $installer_class();
            $installer->install();
        }
    }

    /**
     * Deactivate plugin.
     *
     * @param bool $network_wide
     */
    public static function deactivate( $network_wide )
    {
        if ( $network_wide && has_action( 'bookly_plugin_deactivate' ) ) {
            do_action( 'bookly_plugin_deactivate', static::getSlug() );
        } else {
            unload_textdomain( 'bookly' );
        }
    }

    /**
     * Uninstall plugin.
     *
     * @param string|bool $network_wide
     */
    public static function uninstall( $network_wide )
    {
        if ( $network_wide !== false && has_action( 'bookly_plugin_uninstall' ) ) {
            do_action( 'bookly_plugin_uninstall', static::getSlug() );
        } else {
            $installer_class = static::getRootNamespace() . '\Lib\Installer';
            $installer = new $installer_class();
            $installer->uninstall();
        }
    }

    /**
     * Check if plugin is enabled (applicable to add-ons).
     *
     * @return bool
     */
    public static function enabled()
    {
        return get_option( static::getPrefix() . 'enabled' ) == 1;
    }

    /**
     * Enable plugin (applicable to add-ons).
     */
    public static function enable()
    {
        update_option( static::getPrefix() . 'enabled', 1 );
    }

    /**
     * Disable plugin (applicable to add-ons).
     */
    public static function disable()
    {
        update_option( static::getPrefix() . 'enabled', 0 );
    }

    /**
     * Get prefix.
     *
     * @return mixed
     */
    public static function getPrefix()
    {
        if ( static::$prefix === null ) {
            static::$prefix = str_replace( array( '-addon', '-' ), array( '', '_' ), static::getSlug() ) . '_';
        }

        return static::$prefix;
    }

    /**
     * Get plugin title.
     *
     * @return string
     */
    public static function getTitle()
    {
        if ( static::$title === null ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_data = get_plugin_data( static::getMainFile() );
            static::$version     = $plugin_data['Version'];
            static::$title       = $plugin_data['Name'];
            static::$text_domain = $plugin_data['TextDomain'];
        }

        return static::$title;
    }

    /**
     * Get plugin version.
     *
     * @return string
     */
    public static function getVersion()
    {
        if ( static::$version === null ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_data = get_plugin_data( static::getMainFile() );
            static::$version     = $plugin_data['Version'];
            static::$title       = $plugin_data['Name'];
            static::$text_domain = $plugin_data['TextDomain'];
        }

        return static::$version;
    }

    /**
     * Get plugin slug.
     *
     * @return string
     */
    public static function getSlug()
    {
        if ( static::$slug === null ) {
            static::$slug = basename( static::getDirectory() );
        }

        return static::$slug;
    }

    /**
     * Get path to plugin directory.
     *
     * @return string
     */
    public static function getDirectory()
    {
        if ( static::$directory === null ) {
            $reflector = new \ReflectionClass( get_called_class() );
            static::$directory = dirname( dirname( $reflector->getFileName() ) );
        }

        return static::$directory;
    }

    /**
     * Get path to plugin main file.
     *
     * @return string
     */
    public static function getMainFile()
    {
        if ( static::$main_file === null ) {
            static::$main_file = static::getDirectory() . '/main.php';
        }

        return static::$main_file;
    }

    /**
     * Get plugin basename.
     *
     * @return string
     */
    public static function getBasename()
    {
        if ( static::$basename === null ) {
            static::$basename = plugin_basename( static::getMainFile() );
        }

        return static::$basename;
    }

    /**
     * Get plugin text domain.
     *
     * @return string
     */
    public static function getTextDomain()
    {
        if ( static::$text_domain === null ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_data = get_plugin_data( static::getMainFile() );
            static::$version     = $plugin_data['Version'];
            static::$title       = $plugin_data['Name'];
            static::$text_domain = $plugin_data['TextDomain'];
        }

        return static::$text_domain;
    }

    /**
     * Get root namespace of called class.
     *
     * @return string
     */
    public static function getRootNamespace()
    {
        if ( static::$root_namespace === null ) {
            $called_class = get_called_class();
            static::$root_namespace = substr( $called_class, 0, strpos( $called_class, '\\' ) );
        }

        return static::$root_namespace;
    }

    /**
     * Get entity classes.
     *
     * @return Lib\Base\Entity[]
     */
    public static function getEntityClasses()
    {
        $result = array();

        $dir = static::getDirectory() . '/lib/entities';
        if ( is_dir( $dir ) ) {
            foreach ( scandir( $dir ) as $filename ) {
                if ( $filename == '.' || $filename == '..' ) {
                    continue;
                }
                $result[] = static::getRootNamespace() . '\Lib\Entities\\' . basename( $filename, '.php' );
            }
        }

        return $result;
    }

    /**
     * Get plugin purchase code option name.
     *
     * @return string
     */
    public static function getPurchaseCodeOption()
    {
        return static::getPrefix() . 'envato_purchase_code';
    }

    /**
     * Get plugin purchase code.
     *
     * @param int $blog_id
     * @return string
     */
    public static function getPurchaseCode( $blog_id = null )
    {
        $option = static::getPurchaseCodeOption();

        return $blog_id ? get_blog_option( $blog_id, $option ) : get_option( $option );
    }

    /**
     * Update plugin purchase code.
     *
     * @param string $value
     * @param int    $blog_id
     */
    public static function updatePurchaseCode( $value, $blog_id = null )
    {
        $option = static::getPurchaseCodeOption();

        if ( $blog_id ) {
            update_blog_option( $blog_id, $option, $value );
        } else {
            update_option( $option, $value );
        }
    }

    /**
     * Get plugin installation time.
     *
     * @return int
     */
    public static function getInstallationTime()
    {
        return get_option( static::getPrefix() . 'installation_time' );
    }

    /**
     * Check whether the plugin is network active.
     *
     * @return bool
     */
    public static function isNetworkActive()
    {
        return is_plugin_active_for_network( static::getBasename() );
    }

    /**
     * Get plugin class for given object.
     *
     * @param $object
     * @return static
     */
    public static function getPluginFor( $object )
    {
        $class = get_class( $object );

        if ( ! isset ( self::$plugin_classes[ $class ] ) ) {
            self::$plugin_classes[ $class ] = substr( $class, 0, strpos( $class, '\\' ) ) . '\Lib\Plugin';
        }

        return self::$plugin_classes[ $class ];
    }

    /******************************************************************************************************************
     * Protected methods                                                                                              *
     ******************************************************************************************************************/

    /**
     * Register hooks.
     * @todo Change to protected.
     */
    public static function registerHooks()
    {
        /** @var Plugin $plugin_class */
        $plugin_class = get_called_class();

        register_activation_hook( static::getMainFile(),   array( $plugin_class, 'activate' ) );
        register_deactivation_hook( static::getMainFile(), array( $plugin_class, 'deactivate' ) );
        register_uninstall_hook( static::getMainFile(),    array( $plugin_class, 'uninstall' ) );

        add_action( 'plugins_loaded', function () use ( $plugin_class ) {
            // l10n.
            load_plugin_textdomain( $plugin_class::getTextDomain(), false, $plugin_class::getSlug() . '/languages' );
        } );

        // Add handlers to Bookly filters.
        add_filter( 'bookly_plugins', function ( array $plugins ) use ( $plugin_class ) {
            $plugins[ $plugin_class::getSlug() ] = $plugin_class;

            return $plugins;
        } );

        if ( is_admin() ) {
            // Add handlers to Bookly actions.
            add_action( 'bookly_render_purchase_code', function ( $blog_id = null ) use ( $plugin_class ) {
                $purchase_code = $plugin_class::getPurchaseCode( $blog_id );

                printf(
                    '<div class="form-group"><label for="%2$s">%1$s %3$s:</label><input id="%2$s" class="purchase-code form-control" type="text" name="purchase_code[%2$s]" value="%4$s" /></div>',
                    $plugin_class::getTitle(),
                    $plugin_class::getPurchaseCodeOption(),
                    __( 'Purchase Code', 'bookly' ),
                    $purchase_code
                );
            }, 1, 1 );

            add_filter( 'bookly_save_purchase_codes', function ( $errors, $purchase_codes, $blog_id ) use ( $plugin_class ) {
                $option = $plugin_class::getPurchaseCodeOption();
                if ( array_key_exists( $option, (array) $purchase_codes ) ) {
                    $purchase_code = trim( $purchase_codes[ $option ] );
                    if ( $purchase_code == '' ) {
                        $plugin_class::updatePurchaseCode( '' );
                    } else {
                        $result = Lib\API::verifyPurchaseCode( $purchase_code, $plugin_class );
                        if ( $result['valid'] ) {
                            $plugin_class::updatePurchaseCode( $purchase_code, $blog_id );
                            $grace_notifications = get_option( 'bookly_grace_notifications' );
                            $grace_notifications['add-ons'] = '0';
                            if ( $blog_id ) {
                                update_blog_option( $blog_id, 'bookly_grace_notifications', $grace_notifications );
                            } else {
                                update_option( 'bookly_grace_notifications', $grace_notifications );
                            }
                        } else {
                            $errors[] = $result['error'];
                        }
                    }
                }

                return $errors;
            } , 10, 3 );
        }
        // For admin notices about SMS weekly summary and etc.
        if ( ! wp_next_scheduled( 'bookly_daily_routine' ) ) {
            wp_schedule_event( time(), 'daily', 'bookly_daily_routine' );
        }
    }

    /**
     * Init update checker.
     */
    protected static function initUpdateChecker()
    {
        include_once Lib\Plugin::getDirectory() . '/lib/utils/plugin-update-checker.php';

        $purchase_code = static::getPurchaseCode();
        add_filter( 'puc_manual_check_link-' . static::getSlug(), function () use ( $purchase_code ) {
            if ( $purchase_code != '' ) {
                return __( 'Check for updates', 'bookly' );
            }
        } );

        add_filter( 'puc_manual_check_message-' . static::getSlug(), function ( $message, $status ) {
            switch ( $status ) {
                case 'no_update':        return __( 'This plugin is up to date.', 'bookly' );
                case 'update_available': return __( 'A new version of this plugin is available.', 'bookly' );
                default:                 return sprintf( __( 'Unknown update checker status "%s"', 'bookly' ), htmlentities( $status ) );
            }
        }, 10, 2 );

        add_filter( 'puc_request_info_result-' . static::getSlug(), function ( $pluginInfo, $result ) {
            if ( $result instanceof \WP_Error ) {
                if ( get_option( 'bookly_api_server_error_time' ) == '0' ) {
                    update_option( 'bookly_api_server_error_time', current_time( 'timestamp' ) );
                }
            } elseif ( isset( $result['body'] ) ) {
                $response = json_decode( $result['body'], true );
                if ( isset( $response['options'] ) ) {
                    foreach ( $response['options'] as $option => $value ) {
                        $value !== null ? update_option( $option, $value ) : delete_option( $option );
                    }
                }
                update_option( 'bookly_api_server_error_time', '0' );
            }

            return $pluginInfo;
        }, 10, 2 );

        $plugin_version = static::getVersion();
        $plugin_slug    = static::getSlug();
        $purchase_code  = static::getPurchaseCode();
        add_filter( 'puc_request_info_query_args-' . static::getSlug(), function( $queryArgs ) use ( $plugin_version, $plugin_slug, $purchase_code ) {
            global $wp_version;

            $queryArgs['api']           = '1.1';
            $queryArgs['action']        = 'update';
            $queryArgs['plugin']        = $plugin_slug;
            $queryArgs['site_url']      = site_url();
            $queryArgs['versions']      = array( $plugin_version, 'wp' => $wp_version );
            $queryArgs['purchase_code'] = $purchase_code;
            unset ( $queryArgs['checking_for_updates'] );

            return $queryArgs;
        } );

        \PucFactory::buildUpdateChecker(
            'http://booking-wp-plugin.com/index.php',
            static::getMainFile(),
            static::getSlug(),
            24
        );
        if ( static::getPurchaseCode() == '' ) {
            $plugin_basename = static::getBasename();
            add_filter( 'plugin_row_meta', function ( $links, $plugin ) use ( $plugin_basename ) {
                if ( $plugin == $plugin_basename ) {
                    return array_merge(
                        $links,
                        array(
                            0 => '<span class="dashicons dashicons-info"></span> ' .
                                sprintf(
                                    __( 'To update - enter the <a href="%s">Purchase Code</a>', 'bookly' ),
                                    Lib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Settings\Controller::page_slug, array( 'tab' => 'purchase_code' ) )
                                ),
                        )
                    );
                }

                return $links;
            }, 10, 2 );
        }
    }

}