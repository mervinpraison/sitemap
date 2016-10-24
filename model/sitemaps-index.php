<?php



Class IndexSitemap {

	private $stylesheet = '';

	function __construct() {
		add_action( 'init', array($this, 'inits') );	
		add_action( 'template_redirect', array($this,'redirects') );
		add_action( 'seo_request_sitemap_index', array($this,'request_sitemap_index')  );
		add_action( 'transition_post_status', array($this,'status_change', 10, 3 ));
		add_filter( 'redirect_canonical', array($this,'canonicals') );
		$this->stylesheet = '<?xml-stylesheet type="text/xsl" href="'.SITEMAP_PLUGIN_URL.'view/css/xml-sitemap-xsl.php"?>';

	}

	function inits() {
		global $wp_rewrite;
		$GLOBALS['wp']->add_query_var( 'sitemaps' );
		$GLOBALS['wp']->add_query_var( 'sitemaps_n' );
		add_rewrite_rule( 'sitemaps\.xml$', 'index.php?sitemaps=1', 'top' );
		add_rewrite_rule( '([^/]+?)-sitemaps([0-9]+)?\.xml$', 'index.php?sitemaps=$matches[1]&sitemaps_n=$matches[2]', 'top' );
		add_rewrite_rule( 'sitemaps([0-9]+)?\.xml$', 'index.php?sitemaps=1&sitemaps_n=$matches[1]', 'top' );
		$wp_rewrite->flush_rules();
	}

	function redirects() {
		global $Build;
		$type = get_query_var( 'sitemaps' );
		if ( empty( $type ) )
			return;
		$Build->build_sitemaps( $type );
		$this->output();
		die();
	}

	function canonicals( $redirect ) {
		$sitemap = get_query_var( 'sitemaps' );
		if ( ! empty( $sitemap ) )
			return false;
		return $redirect;
	}

	function request_sitemap_index() {
		$base = $GLOBALS['wp_rewrite']->using_index_permalinks() ? 'index.php/' : '';
		$url = home_url( $base . 'sitemap_index.xml' );
		wp_remote_get( $url );
	}

	function status_change( $new_status, $old_status, $post ) {
		if ( $new_status != 'publish' )
			return;
	
		global $PingSearchEngines;
		wp_cache_delete( 'latestmodifieddate:gmt:post_type' . $post->post_type, 'sitemap_time' );
		wp_cache_delete( 'latestmodifieddate:gmt:term' . $post->term_id, 'sitemap_time' );
		//$options = get_mervin_options();
		//if ( isset($options['post_types-'.$post->post_type.'-not_in_sitemap']) && $options['post_types-'.$post->post_type.'-not_in_sitemap'] )
		//	return;
		if ( WP_CACHE )
			wp_schedule_single_event( time()+(60), 'request_sitemap_index' );
		//if ( seo_get_value( 'sitemap-include', $post->ID ) != 'never' )
		$PingSearchEngines->ping_searchengines();
	}

	

	function add_sitemap () {
		do_action ('add_sitemap');
	}

	function new_sitemap(){
		echo "<sitemap><loc>http://localhost/aah/post-sitemaps1.xml</loc><lastmod>2016-10-03 16:31:26</lastmod></sitemap>";
	}
	//add_action('add_sitemap', 'new_sitemap');

	function add_roots_sitemap($string){
		$string .= '<sitemap><loc>http://localhost/aah/post-sitemaps1.xml</loc><lastmod>2016-10-03 16:31:26</lastmod></sitemap>';
		return $string; 
	}
	//add_filter('add_root_sitemap', 'add_roots_sitemap');


	function sitemap_change_url_test($url){
		global $query_variable;	
		$url = $url.$query_variable.'/';
		return $url; 
	}


	function output() {
		// Prevent the search engines from indexing the XML Sitemap.
		header( 'X-Robots-Tag: noindex, follow', true );
		
		header( 'Content-Type: text/xml' );
		global $stylesheet;
		global $Build;
		echo '<?xml version="1.0" encoding="'.get_bloginfo('charset').'"?>';
		echo $this->stylesheet;
		//echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		echo $Build->build_sitemaps();
		echo $this->add_sitemap();
		//echo '</sitemapindex>';
		echo "\n" . '<!-- XML Sitemap generated by Praison SEO -->';
	}
}

?>