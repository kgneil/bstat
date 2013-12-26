<?php
class bStat
{
	private $admin   = FALSE;
	public  $db      = FALSE;
	public  $id_base = 'bstat';
	private $options = FALSE;
	public  $version = 1;

	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ) );
	} // END __construct

	public function init()
	{
		wp_register_script( $this->id_base, plugins_url( plugin_basename( __DIR__ ) ) . '/js/bstat.js', array( 'jquery' ), $this->version, TRUE );

		if( is_admin() )
		{
			$this->admin();
		}
		else
		{
			add_action( 'template_redirect', array( $this, 'template_redirect' ), 15 );
			wp_enqueue_script( $this->id_base );
		}
	} // END init

	// a singleton for the admin object
	public function admin()
	{
		if ( ! $this->admin )
		{
			require_once __DIR__ . '/class-bstat-admin.php';
			$this->admin = new bStat_Admin();
		}

		return $this->admin;
	} // END admin

	// a singleton for the db object
	public function db()
	{
		if ( ! $this->db )
		{
			// the db abstract is required for all DB classes
			require_once __DIR__ . '/class-bstat-db.php';

			// @TODO: this needs to load the DB interface based on the config; for now, just wpdb is supported
			require_once __DIR__ . '/class-bstat-db-wpdb.php';
			$this->db = new bStat_Db_Wpdb();
		}

		return $this->db;
	} // END db

	public function options()
	{
		if ( ! $this->options )
		{
			$this->options = (object) apply_filters( 
				'go_config',
				array(
					'endpoint' => admin_url( '/admin-ajax.php?action=' . $this->id_base ),
					'db' => 'bStat_Db_Wpdb',
					'secret' => $this->version,
					'session_duration' => 1800, // 30 minutes in seconds
				),
				$this->id_base
			);
		}

		return $this->options;
	} // END options

	public function template_redirect()
	{
		wp_localize_script( $this->id_base, $this->id_base, $this->wp_localize_script() );
	} // END template_redirect

	public function wp_localize_script()
	{
		global $wpdb;
		$details = array(
			'post'       => apply_filters( 'bstat_post_id', ( is_singular() ? get_queried_object_id() : FALSE ) ),
			'blog'       => ( isset( $wpdb->blogid ) ? $wpdb->blogid : 1 ),
			'endpoint'   => $this->options()->endpoint,
		);
		$details['signature'] = $this->get_signature( $details );

		return $details;
	}

	public function get_signature( $details )
	{
		return md5( (int) $details['post'] . (int) $details['blog'] . (string) $this->options()->secret );
	}

	public function validate_signature( $details )
	{
		if ( ! isset( $details['signature'], $details['post'], $details['blog'] ) )
		{
			return FALSE;
		}

		return $this->get_signature( $details ) == $details['signature'];
	}

	public function get_session()
	{
		// get or start a session
		if ( isset( $_COOKIE[ $this->id_base ]['session'] ) )
		{
			$session = $_COOKIE[ $this->id_base ]['session'];
		}
		else
		{
			$session = md5( microtime() . $this->options()->secret );
		}

		// set or update the cookie to expire in 30 minutes or so (configurable)
		setcookie(
			$this->get_field_name( 'session' ),
			$session,
			time() + $this->options()->session_length,
			SITECOOKIEPATH, // WordPress-provided constant
			COOKIE_DOMAIN // WordPress-provided constant
		);

		return $session;
	}
}

function bstat()
{
	global $bstat;

	if ( ! $bstat )
	{
		$bstat = new bStat;
	}

	return $bstat;
} // end bstat