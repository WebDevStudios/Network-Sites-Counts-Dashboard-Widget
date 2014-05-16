<?php

class N_S_C_Data {

	public $args = array();

	/**
	 * Initialize the class
	 * @since 0.1.0
	 * @param array $args Arguments for count data retrieval
	 */
	function __construct( $args = array() ) {
		$this->args = ! empty( $args ) && is_array( $args ) ? $args : $_REQUEST;
		$this->args['status'] = isset( $this->args['status'] ) && in_array( $this->args['status'], array( 'publish','future','draft','pending','private','trash','auto-draft','inherit' ), true )
			? $this->args['status']
			: 'all';

		$this->args['post_type'] = isset( $this->args['post_type'] )
			? sanitize_text_field( $this->args['post_type'] )
			: 'post';

		$this->args['trans_id'] = $trans_id = __CLASS__.$this->args['status'].$this->args['post_type'];
	}

	/**
	 * Get post count for post type from args and status from args
	 * @since  0.1.0
	 * @return int   Posts count
	 */
	function get_post_count() {
		$counts = wp_count_posts( $this->args['post_type'] );
		if ( 'all' === $this->args['status'] )
			return $counts;

		return $counts->{ $this->args['status'] };
	}

	/**
	 * Retrieve counts data for all sites in a network (and store in a transient)
	 * @since  0.1.0
	 * @return array Array of sites count data
	 */
	function all_sites_post_count() {

		if ( $network_data = get_site_transient( __FUNCTION__ . $this->args['trans_id'] ) ) {
			return $network_data;
		}

		global $wpdb;
		$sites_in_network = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->blogs} WHERE  spam = '0'
			AND deleted = '0' AND archived = '0'
			ORDER BY registered DESC, 5", ARRAY_A
		) );

		if ( empty( $sites_in_network ) )
			return;

		$original_blog_id = get_current_blog_id();
		$network_data      = array();

		foreach ( $sites_in_network as $network_site ) {
			switch_to_blog( $network_site->blog_id );
			$network_data[ $network_site->blog_id .':'. $network_site->path ] = apply_filters( 'n_s_c_d_widget_data_stored_per_site', $this->get_post_count(), $network_site, $this );
		}
		switch_to_blog( $original_blog_id );

		set_site_transient( __FUNCTION__ . $this->args['trans_id'], $network_data, DAY_IN_SECONDS );

		return $network_data;
	}

	/**
	 * Retrieve arguments from the class initialization
	 * @since  0.1.0
	 * @param  string  $arg Argument to retrieve
	 * @return mixed        All args or specified arg
	 */
	public function args( $arg = '' ) {
		if ( $arg ) {
			return array_key_exists( $arg, $this->args ) ? $this->args[ $arg ] : false;
		}
		return $this->args;
	}
}