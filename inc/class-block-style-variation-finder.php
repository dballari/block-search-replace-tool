<?php

/**
 * Block Style Variation Finder
 * 
 * @package Block Style Variation Finder Class
 * @author  David Ballarin Prunera
 * @link    https://ballarinconsulting.com/acerca
 */


/**
 * Documentation on creating an options page:
 * https://codex.wordpress.org/Creating_Options_Pages
 */


class BlockStyleVariationFinder {
    
    public $all_registered_blocks;
    public $registered_block_styles;
    public $options_name;
    private $options;
    private $styles;
    private $plugin_uri;

    public function __construct( $plugin_uri ) {
        $this->options_name = 'blocksrtool_styles_options';
        $this->options = get_option( $this->options_name, [] );
        $this->styles = [];
        $this->plugin_uri = $plugin_uri;
        add_action( 'admin_menu', array( $this, 'add_page' ) );
        add_action( 'init', array( $this, 'initialize' ), 99);
    }

    public function initialize() {
        $this->all_registered_blocks = 
            WP_Block_Type_Registry::get_instance()->get_all_registered();
        $this->registered_block_styles = 
            WP_Block_Styles_Registry::get_instance()->get_all_registered();
    }

    public function add_page() {
        add_submenu_page(
            'tools.php',
            __( 'Block Style Variation Finder Tool', 'block-search-replace-tool' ),
            __( 'Block Style Finder', 'block-search-replace-tool' ),
            'manage_options',
            'blocksvfinder',
            array( $this, 'render_page' )
        );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if( get_current_screen()->base == 'tools_page_blocksvfinder' ) {
            add_filter( 'admin_footer_text', function($text) {
                return '<span id="footer-thankyou">' . $text . 
                ' And thank you for using the <a href="'.$this->plugin_uri.
                '">Block Search Replace</a> plugin.</span>';
            }, 10, 1 );
        }
        ?>
        <div class="wrap blocksrtool">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <div class="content-wrap">
                <div class="content-item core-registered-style-variations">
                    <?php $this->render_registered_blocks(); ?>
                </div>
                <div class="content-item theme-registered-style-variations">
                    <?php $this->render_registered_block_styles(); ?>
                </div>
            </div>
        </div>
        <?php
        update_option( $this->options_name, $this->styles );
    }
       
    function render_registered_blocks() {
        $this->render_table_header( 'Core' );
        foreach( $this->all_registered_blocks as $block) {
            if( !empty($block->styles) ) {
                printf(
                    '<tr><td>%s</td><td>%s</td>',
                    esc_html( $block->name ),
                    esc_html( $this->render_block_styles($block->styles ) )
                );
            }
        }
        $this->render_table_footer();
    }

    function render_block_styles( $styles ) {
        $rendered = '';
        foreach( $styles as $style ) {
            $rendered .= $style['name'] . ' // ';
            $this->add_style( $style['name'] );
        }
        return substr($rendered, 0, strlen($rendered) - 4);
    }

    function render_registered_block_styles( ) {
        $this->render_table_header( 'Theme' );
        foreach( $this->registered_block_styles as $blockkey => $styles ) {
                printf(
                    '<tr><td>%s</td><td>%s</td>',
                    esc_html( $blockkey ),
                    esc_html( $this->render_block_style_variations( $styles ) )
                );
        }
        $this->render_table_footer();
    }

    function render_block_style_variations( $styles ) {
        $rendered = '';
        foreach( $styles as $stylekey => $style) {
            $rendered .= $stylekey . ' // ';
            $this->add_style( $style['name'] );
        }
        return substr($rendered, 0, strlen($rendered) - 4);
    }

    function render_table_header( $type ) {
        printf(
            '<table><tr><th>Block name</th><th>%s registered styles</th></tr>',
            esc_html( $type )
        );
    }

    function render_table_footer() {
        printf(
            '</table>'
        );
    }

    function add_style( $value ) {
        if( !in_array( $value, $this->styles ) ) {
            $this->styles[] = $value;
        }
    }
}
