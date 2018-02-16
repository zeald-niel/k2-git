<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class Staff
 * @package Bookly\Lib\Entities
 */
class Staff extends Lib\Base\Entity
{
    protected static $table = 'ab_staff';

    protected static $schema = array(
        'id'                 => array( 'format' => '%d' ),
        'wp_user_id'         => array( 'format' => '%d' ),
        'attachment_id'      => array( 'format' => '%d' ),
        'full_name'          => array( 'format' => '%s' ),
        'email'              => array( 'format' => '%s' ),
        'phone'              => array( 'format' => '%s' ),
        'google_data'        => array( 'format' => '%s' ),
        'google_calendar_id' => array( 'format' => '%s' ),
        'info'               => array( 'format' => '%s' ),
        'visibility'         => array( 'format' => '%s', 'default' => 'public' ),
        'position'           => array( 'format' => '%d', 'default' => 9999 ),
    );

    protected static $cache = array();

    public function save()
    {
        $is_new = ! $this->get( 'id' );

        if ( $is_new && $this->get( 'wp_user_id' ) ) {
            $user = get_user_by( 'id', $this->get( 'wp_user_id' ) );
            if ( $user ) {
                $this->set( 'email', $user->get( 'user_email' ) );
            }
        }

        $return = parent::save();
        if ( $this->isLoaded() ) {
            // Register string for translate in WPML.
            do_action( 'wpml_register_single_string', 'bookly', 'staff_' . $this->get( 'id' ), $this->get( 'full_name' ) );
            do_action( 'wpml_register_single_string', 'bookly', 'staff_' . $this->get( 'id' ) . '_info', $this->get( 'info' ) );
        }
        if ( $is_new ) {
            // Schedule items.
            $staff_id = $this->get( 'id' );
            foreach ( array( 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' ) as $day_index => $week_day ) {
                $item = new StaffScheduleItem();
                $item->set( 'staff_id',  $staff_id )
                    ->set( 'day_index',  $day_index + 1  )
                    ->set( 'start_time', get_option( 'bookly_bh_' . $week_day . '_start' ) ?: null )
                    ->set( 'end_time',   get_option( 'bookly_bh_' . $week_day . '_end' ) ?: null )
                    ->save();
            }

            // Create holidays for staff
            $this->wpdb->query( sprintf(
                'INSERT INTO `' . Holiday::getTableName(). '` (`parent_id`, `staff_id`, `date`, `repeat_event`)
                SELECT `id`, %d, `date`, `repeat_event` FROM `' . Holiday::getTableName() . '` WHERE `staff_id` IS NULL',
                $staff_id
            ) );
        }

        return $return;
    }

    /**
     * Get schedule items of staff member.
     *
     * @return StaffScheduleItem[]
     */
    public function getScheduleItems()
    {
        $start_of_week = (int) get_option( 'start_of_week' );
        // Start of week affects the sorting.
        // If it is 0(Sun) then the result should be 1,2,3,4,5,6,7.
        // If it is 1(Mon) then the result should be 2,3,4,5,6,7,1.
        // If it is 2(Tue) then the result should be 3,4,5,6,7,1,2. Etc.
        return StaffScheduleItem::query()
            ->where( 'staff_id',  $this->get( 'id' ) )
            ->sortBy( "IF(r.day_index + 10 - {$start_of_week} > 10, r.day_index + 10 - {$start_of_week}, 16 + r.day_index)" )
            ->indexBy( 'day_index' )
            ->find();
    }

    /**
     * Get StaffService entities associated with this staff member.
     *
     * @return StaffService[]
     */
    public function getStaffServices()
    {
        $result = array();

        if ( $this->get( 'id' ) ) {
            $staff_services = StaffService::query( 'ss' )
                ->select( 'ss.*, s.title, s.duration, s.price AS service_price, s.color, s.capacity AS service_capacity' )
                ->leftJoin( 'Service', 's', 's.id = ss.service_id' )
                ->where( 'ss.staff_id', $this->get( 'id' ) )
                ->where( 's.type', Service::TYPE_SIMPLE )
                ->fetchArray();

            foreach ( $staff_services as $data ) {
                $ss = new StaffService( $data );

                // Inject Service entity.
                $ss->service      = new Service();
                $data['id']       = $data['service_id'];
                $data['price']    = $data['service_price'];
                $data['capacity'] = $data['service_capacity'];
                $ss->service->setFields( $data, true );

                $result[] = $ss;
            }
        }

        return $result;
    }

    /**
     * Check whether staff is on holiday on given day.
     *
     * @param \DateTime $day
     * @return bool
     */
    public function isOnHoliday( \DateTime $day )
    {
        $query = Holiday::query()
            ->whereRaw( '( DATE_FORMAT( date, %s ) = %s AND repeat_event = 1 ) OR date = %s', array( '%m-%d', $day->format( 'm-d' ), $day->format( 'Y-m-d' ) ) )
            ->whereRaw( 'staff_id = %d OR staff_id IS NULL', array( $this->get( 'id' ) ) )
            ->limit( 1 );
        $rows = $query->execute( Lib\Query::HYDRATE_NONE );

        return $rows != 0;
    }

    /**
     * Delete staff member.
     */
    public function delete()
    {
        if ( $this->get( 'google_data' ) ) {
            $google = new Lib\Google();
            $google->loadByStaff( $this );
            $google->logout();
        }

        parent::delete();
    }

    public function getName()
    {
        return Lib\Utils\Common::getTranslatedString( 'staff_' . $this->get( 'id' ), $this->get( 'full_name' ) );
    }

    public function getInfo()
    {
        return Lib\Utils\Common::getTranslatedString( 'staff_' . $this->get( 'id' ) . '_info', $this->get( 'info' ) );
    }

}
