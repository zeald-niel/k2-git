<?php
namespace Bookly\Lib;

use Bookly\Lib\Entities\StatsForms;
use Bookly\Lib\Entities\StatsSteps;

/**
 * Class Stats
 * Used to collect and work with anonymous stat
 * @package Bookly\Lib
 */
class Stats {
    /**
     * Add information about page with bookly form
     * @param string $url address of the page with form
     */
    public static function recordForm( $url )
    {
        global $wpdb;

        if ( get_option( 'bookly_gen_collect_stats' ) ) {
            $wpdb->query( $wpdb->prepare(
                'INSERT IGNORE INTO ' . StatsForms::getTableName() . '
                SET `url` = %s',
                $url
            ) );
        }
    }

    /**
     * Add information about some step of form visited
     * @param string $step step name
     */
    public static function recordStep( $step )
    {
        global $wpdb;

        if ( get_option( 'bookly_gen_collect_stats' ) ) {
            $wpdb->query( $wpdb->prepare(
                'INSERT INTO ' . StatsSteps::getTableName() . '
                SET
                    `step` = %s,
                    `count` = 1
                ON DUPLICATE KEY UPDATE
                    `count` = `count` + 1',
                $step
            ) );
        }
    }

    /**
     * Prepare data to send
     *
     * @hook puc_request_info_query_args-*
     *
     * @param array $queryArgs
     *
     * @return array
     */
    public static function sendDataFilter( array $queryArgs )
    {
        $stats_steps   = StatsSteps::query()->sortBy( 'step' )->whereNot( 'step', '0' )->find();
        $data['steps'] = array();
        foreach ( $stats_steps as $stat ) {
            $data['steps'][ $stat->get( 'step' ) ] = $stat->get( 'count' );
        }

        $stats_forms   = StatsForms::query()->sortBy( 'url' )->find();
        $data['forms'] = array();
        foreach ( $stats_forms as $stat ) {
            $data['forms'][] = $stat->get( 'url' );
        }

        $queryArgs['stat'] = urlencode( base64_encode( json_encode( $data ) ) );//do not remove urlencode, this param will not be encoded for some reason

        return $queryArgs;
    }

    /**
     * Process response after data was send, mark data as sent
     *
     * @hook puc_request_info_result-*
     *
     * @param \PluginInfo_3_0|null $pluginInfo
     * @param \WP_Error|array  $result The response or WP_Error on failure
     *
     * @return \PluginInfo_3_0|null
     */
    public static function responseAction( $pluginInfo, $result ) {
        if ( $result instanceof \WP_Error ) {

        } elseif ( isset( $result['body'] ) ) {
            $response = json_decode( $result['body'], true );
            if ( isset( $response['stat'] ) ) {
                $stat = json_decode( base64_decode( $response['stat'] ), true );
                if ( isset( $stat['steps'] ) ) {
                    foreach ( $stat['steps'] as $step => $count ) {
                        self::markStepProcessed( $step, $count );
                    }
                }
                if ( isset ( $stat['forms'] ) ) {
                    foreach ( $stat['forms'] as $url ) {
                        self::markFormUrlProcessed( $url );
                    }
                }
            }
        }

        return $pluginInfo;
    }

    /**
     * Marks stat about steps processed
     *
     * @param string $step
     * @param int $count
     */
    private static function markStepProcessed( $step, $count )
    {
        global $wpdb;

        $wpdb->query( $wpdb->prepare(
            'UPDATE ' . StatsSteps::getTableName() . '
            SET `count` = `count` - %d
            WHERE `step` = %s',
            array( $count, $step )
        ) );
    }

    /**
     * Mark stat about urls with forms processed
     * We can miss url that requests between the send data and response, but it's really unlikely and unimportant
     *
     * @param string $url
     */
    private static function markFormUrlProcessed( $url )
    {
        global $wpdb;

        $wpdb->query( $wpdb->prepare (
            'DELETE FROM ' . StatsForms::getTableName() . '
            WHERE `url` = %s',
            $url
        ) );
    }
}