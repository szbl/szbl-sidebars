<?php
/*
Plugin Name: Sizeable Select-a-Sidebar
Description: Select a sidebar (for now).
Author: theandystratton
Author URI: http://sizeableinteractive.com
Version: 0.1
*/

class Szbl_Sidebar_Selector
{
	public static $instance;
	public $post_types = array( 'page', 'post' );
	public $slug = 'szbl-sidebar-manager';

	public static function init()
	{
		if ( is_null( self::$instance ) )
			self::$instance = new Szbl_Sidebar_Selector();
		return self::$instance;
	}

	private function __construct()
	{
		add_action( 'init', array( $this, 'register' ), 999 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_filter( 'sidebars_widgets', array( $this, 'sidebars_widgets' ) );
	}

	public function admin_head()
	{
?>
<style type="text/css">
form.szbl-sidebar-form { margin: 1em 0; background: #fefefe; padding: 1px 1em; border: 1px solid #d7d7d7; border-radius: .3em; box-shadow: 0 0 2px rgba(0,0,0,.1);}
form.szbl-sidebar-form h3 { border-bottom: 1px solid #e7e7e7; padding-bottom: .5em; margin-bottom: 0; }
</style>
<?php
	}

	public function register()
	{
		$sidebars = get_option( 'szbl_sidebars' );
		if ( is_array( $sidebars ) & count( $sidebars ) > 0 )
		{
			foreach ( $sidebars as $sidebar )
			{
				register_sidebar( $sidebar );
			}
		}
	}

	public function admin_menu()
	{
		add_submenu_page( 'options-general.php', 'Sizeable Sidebars', 'Sizeable Sizebars', 'edit_users', $this->slug, array( $this, 'manager' ) );
	}

	public function manager()
	{
		$sidebars = get_option( 'szbl_sidebars' );

		if ( !is_array( $sidebars ) )
		{
			$sidebars = array();
		}

		// Edit or Delete a Sidebar
		if ( isset( $_GET['action'] ) )
		{
			switch ( $_GET['action'] ) 
			{
				case 'edit':
					return $this->edit_sidebar();
					break;

				case 'delete':
					if ( isset( $_GET['s'] ) && isset( $sidebars[ $_GET['s'] ] ) )
					{
						$name = $sidebars[ $_GET['s'] ]['name'];
						unset( $sidebars[ $_GET['s'] ] );
						update_option( 'szbl_sidebars', $sidebars );
						$success = 'The sidebar named ' . esc_html( $name ) . ' has been deleted.';
					}
					break;
			}
		}

		// Create a Sidebar
		if ( isset( $_POST['szbl-nonce'] ) && wp_verify_nonce( $_POST['szbl-nonce'], 'create-sidebar' ) )
		{
			$sidebar = stripslashes_deep( $_POST['szbl'] );
			$sidebar['id'] = 'szbl-' . sanitize_title( $sidebar['name'] );
			$sidebars[] = $sidebar;
			$sidebars = array_values( $sidebars );
			update_option( 'szbl_sidebars', $sidebars );
			$success = 'A new sidebar named ' . esc_html( $sidebar['name'] ) . ' has been added.';
		}

		$sidebars = get_option( 'szbl_sidebars' );
		if ( !is_array( $sidebars ) )
		{
			$sidebars = array();
		}
?>
<div class="wrap">

	<h2>Sizeable Sidebar(s)</h2>

	<p>
		Manage your sidebars below. You can create and edit sidebars. <strong>Be careful when editing,</strong> changing a sidebar's
		name within this plugin will cause you to lose that sidebar's widgets.
	</p>

	<?php if ( isset( $success ) ) : ?>
	<div class="message updated"><p><?php echo $success; ?></p></div>
	<?php endif; ?>

	<table class="widefat fixed">
	<thead>
	<tr>
		<th>Name</th>
		<th>ID</th>
		<th>Options</th>
	</tr>
	</thead>
	<tbody>
	
	<?php if ( count( $sidebars ) <= 0 ) : ?>
	
	<tr>
		<td colspan="2">
			You have no sidebars, create one below!
		</td>
	</tr>
	
	<?php else : foreach ( $sidebars as $idx => $sidebar ) : ?>
	<tr>
		<td>
			<a href="?page=<?php echo esc_attr( $this->slug ); ?>&amp;action=edit&amp;s=<?php echo $idx; ?>">
				<?php echo esc_html( $sidebar['name'] ); ?>
			</a>
		</td>
		<td><?php echo esc_html( $sidebar['id'] ); ?></td>
		<td>
			<a href="?page=<?php echo esc_attr( $this->slug ); ?>&amp;action=edit&amp;s=<?php echo $idx; ?>">Edit</a> | 
			<a href="?page=<?php echo esc_attr( $this->slug ); ?>&amp;action=delete&amp;s=<?php echo $idx; ?>" onclick="return confirm( 'Are you sure?' );">Delete</a>
		</td>
	</tr>
	<?php endforeach; endif; ?>

	</tbody>
	</table>

	<form method="post" action="" class="szbl-sidebar-form">
		<h3>Create New Sidebar</h3>
		<table class="form-table">
		<tr>
			<th>
				<label for="szbl-name">Sidebar Name:</label>
			</th>
			<td>
				<input type="text" id="szbl-name" name="szbl[name]" value="">
				<small><br>We'll create an ID based on this.</small>
			</td>
		</tr>
		<tr>
			<th>
				<label for="szbl-before-title">Before Title Markup:</label>
			</th>
			<td>
				<input type="text" id="szbl-before-title" class="code widefat" name="szbl[before_title]" value="<?php
					echo esc_attr( '<h3 class="headline-list">' );
				?>">
			</td>
		</tr>
		<tr>
			<th>
				<label for="szbl-after-title">After Title Markup:</label>
			</th>
			<td>
				<input type="text" id="szbl-after-title" class="code widefat" name="szbl[after_title]" value="<?php
					echo esc_attr( '</h3>' );
				?>">
				<br>
			</td>
		</tr>
		<tr>
			<th>
				<label for="szbl-before-widget">Before Widget Markup:</label>
			</th>
			<td>
				<input type="text" id="szbl-before-widget" class="code widefat" name="szbl[before_widget]" value="<?php
					echo esc_attr( '<div class="widget %2$s">' );
				?>">
			</td>
		</tr>
		<tr>
			<th>
				<label for="szbl-after-widget">After Widget Markup:</label>
			</th>
			<td>
				<input type="text" id="szbl-after-widget" class="code widefat" name="szbl[after_widget]" value="<?php
					echo esc_attr( '</div>' );
				?>">
			</td>
		</tr>
		</table>

		<p><input type="submit" class="button" value="Create Sidebar"></p>
		
		<?php wp_nonce_field( 'create-sidebar', 'szbl-nonce' ); ?>

	</form>

</div>
<?php
	}

	public function edit_sidebar()
	{
		$sidebars = get_option( 'szbl_sidebars' );

		if ( !is_array( $sidebars ) || !isset( $sidebars[ $_GET['s'] ] ) )
		{
			wp_die( '<div class="message error"><p>Invalid Sidebar Selected</p></div>' );
		}

		// CREATE A SIDEBAR
		if ( isset( $_POST['szbl-nonce'] ) && wp_verify_nonce( $_POST['szbl-nonce'], 'update-sidebar' ) )
		{
			$sidebar = stripslashes_deep( $_POST['szbl'] );
			$sidebar['id'] = 'szbl-' . sanitize_title( $sidebar['name'] );
			$sidebars[ $_GET['s'] ] = $sidebar;
			update_option( 'szbl_sidebars', $sidebars );
			$success = 'This sidebar has been updated.';
		}

		$sidebars = get_option( 'szbl_sidebars' );
		if ( !is_array( $sidebars ) )
		{
			$sidebars = array();
		}

		$sidebar = $sidebars[ $_GET['s'] ];
?>
<div class="wrap">

	<h2>Sizeable Sidebars: Edit Sidebar</h2>
	<p>
		<a href="?page=<?php echo esc_attr( $this->slug ); ?>">&larr; Manage all sidebars</a>
	</p>
	<?php if ( isset( $success ) ) : ?>
	<div class="message updated"><p><?php echo $success; ?></p></div>
	<?php endif; ?>

	<form method="post" action="" class="szbl-sidebar-form">
		<table class="form-table">
		<tr>
			<th>
				<label for="szbl-name">Sidebar Name:</label>
			</th>
			<td>
				<input type="text" id="szbl-name" name="szbl[name]" value="<?php
					echo esc_attr( $sidebar['name'] );
				?>">
				<small>
					<br>
					<strong>BE CAREFUL:</strong><br>
					Changing this value will delete this sidebar and create a new sidebar, removing all widgets currently attached to this sidebar. 
				</small>
			</td>
		</tr>
		<tr>
			<th>
				<label for="szbl-before-title">Before Title Markup:</label>
			</th>
			<td>
				<input type="text" id="szbl-before-title" class="code widefat" name="szbl[before_title]" value="<?php
					echo esc_attr( $sidebar['before_title'] );
				?>">
			</td>
		</tr>
		<tr>
			<th>
				<label for="szbl-after-title">After Title Markup:</label>
			</th>
			<td>
				<input type="text" id="szbl-after-title" class="code widefat" name="szbl[after_title]" value="<?php
					echo esc_attr( $sidebar['after_title'] );
				?>">
				<br>
			</td>
		</tr>
		<tr>
			<th>
				<label for="szbl-before-widget">Before Widget Markup:</label>
			</th>
			<td>
				<input type="text" id="szbl-before-widget" class="code widefat" name="szbl[before_widget]" value="<?php
					echo esc_attr( $sidebar['before_widget'] );
				?>">
			</td>
		</tr>
		<tr>
			<th>
				<label for="szbl-after-widget">After Widget Markup:</label>
			</th>
			<td>
				<input type="text" id="szbl-after-widget" class="code widefat" name="szbl[after_widget]" value="<?php
					echo esc_attr( $sidebar['after_widget'] );
				?>">
			</td>
		</tr>
		</table>

		<p><input type="submit" class="button" value="Update Sidebar"></p>
		
		<?php wp_nonce_field( 'update-sidebar', 'szbl-nonce' ); ?>

	</form>
<?php
	}

	public function add_meta_boxes()
	{
		$post_types = apply_filters( 'szbl_sidebar_selector_types', $this->post_types );
		foreach ( $post_types as $post_type )
		{
			add_meta_box( 'szbl-sidebar-selector', 'Select a Sidebar', array( $this, 'meta' ), $post_type, 'side', 'default' );
		}
	}

	public function meta()
	{
		global $wp_registered_sidebars;

		$sidebar = get_post_meta( get_the_ID(), 'szbl_sidebar', true );
?>

	<?php do_action( 'szbl_sas_before_dropdown' ); ?>

	<select name="szbl[sidebar]" id="szbl-sas-sidebar">
		
		<option value="">Template Default</option>

		<?php foreach ( $wp_registered_sidebars as $wp_sidebar ) : ?>

		<option value="<?php echo esc_attr( $wp_sidebar['id'] ); ?>"<?php
			selected( $wp_sidebar['id'], $sidebar );
		?>><?php echo esc_html( $wp_sidebar['name'] ); ?></option>

		<?php endforeach; ?>

	</select>

	<?php do_action( 'szbl_sas_after_dropdown' ); ?>

<?php
	}

	public function save_post( $post_id ) 
	{
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( !isset( $_POST['post_type'] ) || !in_array( $_POST['post_type'], apply_filters( 'szbl_sidebar_selector_types', $this->post_types ) ) )
			return;

		if ( !current_user_can( 'edit_post', $post_id ) )
			return;

		if ( !isset( $_POST['szbl']['sidebar'] ) )
		{
			delete_post_meta( $post_id, 'szbl_sidebar' );
		}
		else 
		{
			update_post_meta( $post_id, 'szbl_sidebar', $_POST['szbl']['sidebar'] );
		}
	}

	/*
	 * If there is a custom sidebar, replace all existing sidebar widgets with these.
	 */
	public function sidebars_widgets( $widgets )
	{
		// don't wipe out widgets in admin
		if ( is_admin() )
			return $widgets;

		if ( $sidebar = get_post_meta( get_the_ID(), 'szbl_sidebar', true ) )
		{
			foreach ( $widgets as $k => $v )
			{
				if ( !in_array( $k, array( $sidebar, 'wp_inactive_widgets' ) ) )
				{
					$widgets[ $k ] = $widgets[ $sidebar ];
				}
			}
		}
		return $widgets;
	}

}

/*
 * Wrapper function/template tags for convenience
 */
function szbl_get_sidebar_name( $fallback = 'Default' )
{
	if ( $sidebar = get_post_meta( get_the_ID(), 'szbl_sidebar', true ) ) 
		return $sidebar;

	return $fallback;
}

function szbl_get_sidebar_id( $fallback = 'Default' )
{
	return szbl_get_sidebar_name( $fallback );
}

function szbl_dynamic_sidebar( $default = 'Default' )
{

}

Szbl_Sidebar_Selector::init();