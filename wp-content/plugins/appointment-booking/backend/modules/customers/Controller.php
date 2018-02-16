<?php
namespace Bookly\Backend\Modules\Customers;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Customers
 */
class Controller extends Lib\Base\Controller
{
    const page_slug = 'bookly-customers';

    protected function getPermissions()
    {
        return array(
            'executeSaveCustomer' => 'user',
        );
    }

    public function index()
    {
        if ( $this->hasParameter( 'import-customers' ) ) {
            $this->importCustomers();
        }

        $this->enqueueStyles( array(
            'backend'  => array( 'bootstrap/css/bootstrap-theme.min.css', ),
            'frontend' => array( 'css/ladda.min.css', ),
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/datatables.min.js' => array( 'jquery' ),
            ),
            'frontend' => array(
                'js/spin.min.js' => array( 'jquery' ),
                'js/ladda.min.js' => array( 'jquery' ),
            ),
            'module' => array(
                'js/customers.js' => array( 'bookly-datatables.min.js', 'bookly-ng-customer_dialog.js' ),
            ),
        ) );

        wp_localize_script( 'bookly-customers.js', 'BooklyL10n', array(
            'edit'            => __( 'Edit', 'bookly' ),
            'are_you_sure'    => __( 'Are you sure?', 'bookly' ),
            'wp_users'        => get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name' ) ),
            'zeroRecords'     => __( 'No customers found.', 'bookly' ),
            'processing'      => __( 'Processing...', 'bookly' ),
            'edit_customer'   => __( 'Edit customer', 'bookly' ),
            'new_customer'    => __( 'New customer', 'bookly' ),
            'create_customer' => __( 'Create customer', 'bookly' ),
            'save'            => __( 'Save', 'bookly' ),
            'search'          => __( 'Quick search customer', 'bookly' ),
        ) );

        $this->render( 'index' );
    }

    /**
     * Get list of customers.
     */
    public function executeGetCustomers()
    {
        global $wpdb;

        $columns = $this->getParameter( 'columns' );
        $order   = $this->getParameter( 'order' );
        $filter  = $this->getParameter( 'filter' );

        $query = Lib\Entities\Customer::query( 'c' );

        $total = $query->count();

        $query
            ->select( 'SQL_CALC_FOUND_ROWS c.*,
                (
                    SELECT MAX(a.start_date) FROM ' . Lib\Entities\Appointment::getTableName() . ' a
                        LEFT JOIN ' . Lib\Entities\CustomerAppointment::getTableName() . ' ca ON ca.appointment_id = a.id
                            WHERE ca.customer_id = c.id
                ) AS last_appointment,
                (
                    SELECT COUNT(DISTINCT ca.appointment_id) FROM ' . Lib\Entities\CustomerAppointment::getTableName() . ' ca
                        WHERE ca.customer_id = c.id
                ) AS total_appointments,
                (
                    SELECT SUM(p.total) FROM ' . Lib\Entities\Payment::getTableName() . ' p
                        WHERE p.id IN (
                            SELECT DISTINCT ca.payment_id FROM ' . Lib\Entities\CustomerAppointment::getTableName() . ' ca
                                WHERE ca.customer_id = c.id
                        )
                ) AS payments,
                wpu.display_name AS wp_user' )
            ->tableJoin( $wpdb->users, 'wpu', 'wpu.ID = c.wp_user_id' )
            ->groupBy( 'c.id' );

        if ( $filter != '' ) {
            $search_value = Lib\Query::escape( $filter );
            $query
                ->whereLike( 'c.name', "%{$search_value}%" )
                ->whereLike( 'c.phone', "%{$search_value}%", 'OR' )
                ->whereLike( 'c.email', "%{$search_value}%", 'OR' );
        }

        foreach ( $order as $sort_by ) {
            $query->sortBy( str_replace( '.', '_', $columns[ $sort_by['column'] ]['data'] ) )
                ->order( $sort_by['dir'] == 'desc' ? Lib\Query::ORDER_DESCENDING : Lib\Query::ORDER_ASCENDING );
        }

        $query->limit( $this->getParameter( 'length' ) )->offset( $this->getParameter( 'start' ) );

        $data = array();
        foreach ( $query->fetchArray() as $row ) {
            $data[] = array(
                'id'                 => $row['id'],
                'name'               => $row['name'],
                'wp_user'            => $row['wp_user'],
                'wp_user_id'         => $row['wp_user_id'],
                'phone'              => $row['phone'],
                'email'              => $row['email'],
                'notes'              => $row['notes'],
                'birthday'           => $row['birthday'],
                'last_appointment'   => $row['last_appointment'] ? Lib\Utils\DateTime::formatDateTime( $row['last_appointment'] ) : '',
                'total_appointments' => $row['total_appointments'],
                'payments'           => Lib\Utils\Common::formatPrice( $row['payments'] ),
            );
        }

        wp_send_json( array(
            'draw'            => ( int ) $this->getParameter( 'draw' ),
            'recordsTotal'    => $total,
            'recordsFiltered' => ( int ) $wpdb->get_var( 'SELECT FOUND_ROWS()' ),
            'data'            => $data,
        ) );
    }

    /**
     * Create or edit a customer.
     */
    public function executeSaveCustomer()
    {
        $response = array();
        $form = new Forms\Customer();

        do {
            if ( $this->getParameter( 'name' ) !== '' ) {
                $params = $this->getPostParameters();
                if ( ! $params['wp_user_id'] ) {
                    $params['wp_user_id'] = null;
                }
                if ( ! $params['birthday'] ) {
                    $params['birthday'] = null;
                }
                $form->bind( $params );
                /** @var Lib\Entities\Customer $customer */
                $customer = $form->save();
                if ( $customer ) {
                    $response['success']  = true;
                    $response['customer'] = array(
                        'id'         => $customer->get( 'id' ),
                        'wp_user_id' => $customer->get( 'wp_user_id' ),
                        'name'       => $customer->get( 'name' ),
                        'phone'      => $customer->get( 'phone' ),
                        'email'      => $customer->get( 'email' ),
                        'notes'      => $customer->get( 'notes' ),
                        'birthday'   => $customer->get( 'birthday' ),
                    );
                    break;
                }
            }
            $response['success'] = false;
            $response['errors']  = array( 'name' => array( 'required' ) );
        } while ( 0 );

        wp_send_json( $response );
    }

    /**
     * Import customers from CSV.
     */
    private function importCustomers()
    {
        @ini_set( 'auto_detect_line_endings', true );

        $file = fopen( $_FILES['import_customers_file']['tmp_name'], 'r' );
        while ( $line = fgetcsv( $file, null, $this->getParameter( 'import_customers_delimiter' ) ) ) {
            if ( $line[0] != '' ) {
                $customer = new Lib\Entities\Customer();
                $customer->set( 'name', $line[0] );
                if ( isset( $line[1] ) ) {
                    $customer->set( 'phone', $line[1] );
                }
                if ( isset( $line[2] ) ) {
                    $customer->set( 'email', $line[2] );
                }
                if ( isset( $line[3] ) && $line[3] != '' ) {
                    $dob = date_create( $line[3] );
                    if ( $dob !== false ) {
                        $customer->set( 'birthday', $dob->format( 'Y-m-d' ) );
                    }
                }
                $customer->save();
            }
        }
    }

    /**
     * Delete customers.
     */
    public function executeDeleteCustomers()
    {
        foreach ( $this->getParameter( 'data', array() ) as $id ) {
            $customer = new Lib\Entities\Customer();
            $customer->load( $id );
            $customer->deleteWithWPUser( (bool) $this->getParameter( 'with_wp_user' ) );
        }
        wp_send_json_success();
    }

    /**
     * Export Customers to CSV
     */
    public function executeExportCustomers()
    {
        global $wpdb;
        $delimiter = $this->getParameter( 'export_customers_delimiter', ',' );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=Customers.csv' );

        $titles = array(
            'name'     => \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_name' ),
            'wp_user'  => __( 'User', 'bookly' ),
            'phone'    => \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_phone' ),
            'email'    => \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_email' ),
            'notes'    => __( 'Notes', 'bookly' ),
            'last_appointment'   => __( 'Last appointment', 'bookly' ),
            'total_appointments' => __( 'Total appointments', 'bookly' ),
            'payments' => __( 'Payments', 'bookly' ),
            'birthday' => __( 'Date of birth', 'bookly' ),
        );
        $header = array();
        $column = array();

        foreach ( $this->getParameter( 'exp' ) as $key => $value ) {
            $header[] = $titles[ $key ];
            $column[] = $key;
        }

        $output = fopen( 'php://output', 'w' );
        fwrite( $output, pack( 'CCC', 0xef, 0xbb, 0xbf ) );
        fputcsv( $output, $header, $delimiter );

        $rows = Lib\Entities\Customer::query( 'c' )
            ->select( 'c.*, MAX(a.start_date) AS last_appointment,
                COUNT(a.id) AS total_appointments,
                COALESCE(SUM(p.total),0) AS payments,
                wpu.display_name AS wp_user' )
            ->leftJoin( 'CustomerAppointment', 'ca', 'ca.customer_id = c.id' )
            ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
            ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
            ->tableJoin( $wpdb->users, 'wpu', 'wpu.ID = c.wp_user_id' )
            ->groupBy( 'c.id' )
            ->fetchArray();

        foreach ( $rows as $row ) {
            $row_data = array_fill( 0, count( $column ), '' );
            foreach ( $row as $key => $value ) {
                $pos = array_search( $key, $column );
                if ( $pos !== false ) {
                    $row_data[ $pos ] = $value;
                }
            }
            fputcsv( $output, $row_data, $delimiter );
        }

        fclose( $output );

        exit;
    }
}