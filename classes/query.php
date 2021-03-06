<?php

class BEA_PVC_Query {
	/**
	 * Register hooks
	 */
	public function __construct() {
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ), 10, 1 );
		add_filter( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ), 15, 1 );
		add_filter( 'posts_join', array( __CLASS__, 'posts_join' ), 10, 2 );
		add_filter( 'posts_orderby', array( __CLASS__, 'posts_orderby' ), 10, 2 );
		add_filter( 'posts_where', array( __CLASS__, 'posts_where' ), 10, 2 );
	}

	/**
	 * Add new query vars for allow time interval
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'views_interval';

		return $vars;
	}

	/**
	 * Parse query, check if ordery by is by VIEWS
	 *
	 * @param WP_Query $query
	 */
	public static function pre_get_posts( $query ) {
		if ( $query->get( 'orderby' ) == 'views' ) {
			$query->bea_pvc = true;
			$query->set( 'orderby', 'none' );
			$query->set( 'views_interval', BEA_PVC_Plugin::_get_db_interval( $query->get( 'views_interval' ) ) );
		}
	}

	/**
	 * Make SQL where with custom table
	 *
	 * @param string $posts_where
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	public static function posts_where( $posts_where = '', $query = null ) {
		if ( isset( $query->bea_pvc ) && $query->bea_pvc == true ) {
			switch ( $query->get( 'views_interval' ) ) {
				case 'year_counter' :
					$posts_where .= " AND YEAR(pvc.year_date) = YEAR(CURDATE())";
					break;
				case 'month_counter' :
					$posts_where .= " AND MONTH(pvc.month_date) = MONTH(CURDATE()) AND YEAR(pvc.month_date) = YEAR(CURDATE())";
					break;
				case 'day_counter' :
					$posts_where .= " AND DAY(pvc.day_date) = DAY(CURDATE()) ";
					break;
				case 'week_counter' :
					$posts_where .= " AND YEARWEEK(pvc.week_date, 1) = YEARWEEK(CURDATE(), 1)";
					break;
			}
		}

		return $posts_where;
	}

	/**
	 * Make SQL join with custom table
	 *
	 * @global type $wpdb
	 *
	 * @param string $join_sql
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	public static function posts_join( $join_sql = '', $query = null ) {
		global $wpdb;

		if ( isset( $query->bea_pvc ) && $query->bea_pvc == true ) {
			$join_sql .= " LEFT JOIN $wpdb->post_views_counter AS pvc ON $wpdb->posts.ID = pvc.post_id ";
		}

		return $join_sql;
	}

	/**
	 * Make QL order by on custom fields of custom table !
	 *
	 * @global type $wpdb
	 *
	 * @param string $order_sql
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	public static function posts_orderby( $order_sql = '', $query = null ) {
		/**
		 * @var $wpdb wpdb;
		 */
		global $wpdb;

		if ( isset( $query->bea_pvc ) && $query->bea_pvc == true ) {
			$order_sql = " pvc." . $query->get( 'views_interval' ) . ' ' . $query->get( 'order' ) . ", $wpdb->posts.post_date DESC ";
		}

		return $order_sql;
	}

}