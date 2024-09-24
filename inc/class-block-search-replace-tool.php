<?php

/**
 * Block Seach Replace Tool
 * 
 * @package Block Search Replace Tool Class
 * @author  David Ballarin Prunera
 * @link    https://ballarinconsulting.com/acerca
 */


/**
 * Documentation on creating an options page:
 * https://codex.wordpress.org/Creating_Options_Pages
 */


class BlockSearchReplaceTool {

    const DEFAULT_OPTIONS = [
        'action' => 'none',
        'search_text' => '',
        'replace_text' => '',
    ];

    public $options_name;
    private $options;
    private $styles;
    private $plugin_uri;

    public function __construct( $plugin_uri ) {

        $this->options_name = 'blocksrtool_options';
        $this->options = get_option( $this->options_name, self::DEFAULT_OPTIONS );
        $this->styles = get_option( 'blocksrtool_styles_options', [] );
        $this->plugin_uri = $plugin_uri;
        add_action( 'admin_init', array( $this, 'settings_init' ) );
        add_action( 'admin_menu', array( $this, 'add_page' ) );
        
    }

    /**
     * Adds page menu item inside tools
     * with a function callback to render it
     */
    public function add_page() {
        add_submenu_page(
            'tools.php',
            __( 'Block Search Replace Tool', 'block-search-replace-tool' ),
            __( 'Block Search Replace', 'block-search-replace-tool' ),
            'manage_options',
            'block-search-replace-tool',
            array( $this, 'render_page' )
        );
    }

    /**
     * Checks capabilities and renders the admin page
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if( get_current_screen()->base == 'tools_page_blocksrtool' ) {
            add_filter( 'admin_footer_text', function($text) {
                return '<span id="footer-thankyou">' . $text . 
                ' And thank you for using the <a href="'.$this->plugin_uri.
                '">Block Search Replace</a> plugin.</span>';
            }, 10, 1 );
        }
        settings_errors( 'blocksrtool_messages' );
        ?>
        <div class="wrap blocksrtool">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form id="blocksrtool_form" method="post" action="options.php">
                <?php settings_fields( 'block-search-replace-tool' ); ?>
                <input id="action" type="hidden" name="blocksrtool_options[action]" value="">
                <div class="content-wrap">
                    <div class="content-item">
                        <div class="form-wrap">
                            <?php do_settings_sections( 'block-search-replace-tool' ); ?>
                        </div>
                    </div>
                    <div class="content-item results">
                        <?php $this->render_results($this->options); ?>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Registers setting, sections and field
     */
    public function settings_init() {

        register_setting( 
            'block-search-replace-tool', 
            $this->options_name,
            array( $this, 'sanitize' )
        );
        add_settings_section(
            'search',
            '',
            '', //array ( $this, 'render_search_section' ),
            'block-search-replace-tool'
        );
        add_settings_field(
            'search_text',
            __( 'Piece of content to search', 'block-search-replace-tool' ),
            array( $this, 'render_search_field' ),
            'block-search-replace-tool',
            'search',
            array(
                'label_for' => 'search_text',
                'class' => 'search'
            )
        );
        if( !false ) {
        add_settings_section(
            'replace',
            __( 'WARNING: Do not use the replace function unless you have a backup of your content.', 
                'block-search-replace-tool' ),
            '', //array ( $this, 'render_replace_section' ),
            'block-search-replace-tool'
        );
        add_settings_field(
            'replace_text',
            __( 'Piece of content to replace', 'block-search-replace-tool' ),
            array( $this, 'render_replace_field' ),
            'block-search-replace-tool',
            'replace',
            array(
                'label_for' => 'replace_text',
                'class' => 'replace'
            )
        );
        }
    }

    /**
     * Add messages according to input parameters
     */
    public function sanitize( $input ) {
        if( isset( $input['action'] ) && $input['action'] == 'Search' ) {
            if ( isset( $input['search_text'] ) && $input['search_text'] != '' ) {
                $this->add_message( 'search_ok' );
            } else {
                $this->add_message( 'no_search_string' );
            }
            $input['replace_text'] = '';
        } elseif (isset( $input['action'] ) && $input['action'] == 'Replace' ) {
            if ( isset( $input['replace_text'] ) && $input['replace_text'] != '' ) {
                if ( $input['replace_text'] != $input['search_text'] ) {
                    $this->add_message( 'replace_ok' );
                } else {
                    $this->add_message( 'equal_search_replace_strings' );
                }
            } else {
                $this->add_message( 'no_replace_string' );
            }
        }
        //@TODO use html_entity_decode()¿?
        return $input;
    }

    /**
     * Search or replace results are rendered deending on the action performed
     */
    public function render_results( $options ) {
        $results = [];
        if( isset( $options['action'] ) && $options['action'] == 'Search' ) {
            if ( isset( $options['search_text'] ) && $options['search_text'] != '' ) {
                $results = $this->search_query( $this->options['search_text'] );
                if ( count( $results ) > 0 ) {
                    ?>
                    <h4><?php esc_html_e( 'Search results', 'block-search-replace-tool' ); ?></h4>
                    <?php
                    $this->render_results_table( $results );
                } else {
                    printf( esc_html__( 'No search results', 'block-search-replace-tool' ) );
                }
            }
        } elseif (isset( $options['action'] ) && $options['action'] == 'Replace' ) {
            if ( isset( $options['replace_text'] ) && $options['replace_text'] != '' ) {
                if ( $options['replace_text'] != $options['search_text'] ) {
                    $results_replace = $this->replace_query( );
                    $replaced = $this->search_query( $options['replace_text'] );
                    if ( count( $replaced ) > 0 ) {
                        ?>
                        <h4><?php esc_html_e( 'Replacement results', 'block-search-replace-tool' ); ?></h4>
                        <?php
                        $this->render_results_table( $replaced );
                    } else {
                        printf( esc_html__( 'No replace results', 'block-search-replace-tool' ) );
                    }
                }
            }
        }
    }

    public function render_search_section( $args ) {
        ?>
        <h4><?php esc_html_e( 'Search parameters', 'block-search-replace-tool' ); ?></h4>
        <?php
    }

    public function render_replace_section( $args ) {
        ?>
        <h4><?php esc_html_e( 'Replace parameters', 'block-search-replace-tool' ); ?></h4>
        <?php
    }

    public function render_search_field( $args ) {
        /**
         * @TODO make the search and replace field a testarea so that
         * content with more than one line may be searched and replaced
         */
        ?>
        <input 
            style="width:100%" 
            type="text" 
            class="<?php echo esc_attr( $args['class'] ); ?>" 
            id="<?php echo esc_attr( $args['label_for'] ); ?>" 
            name="<?php echo esc_html( $this->options_name ); ?>[<?php echo esc_html( esc_attr( $args['label_for'] ) ); ?>]" 
            value="<?php echo esc_html( htmlentities( $this->options['search_text'] ) ); ?>">
        <h4><?php esc_html_e( 'Block settings helper buttons:', 'block-search-replace-tool' ); ?></h5>
        <div class="helper-buttons" style="margin-top: 16px;">
            <?php
                $this->do_button( __( 'spacings', 'block-search-replace-tool' ), 'spacings' );
                $this->do_button( __( 'spacers', 'block-search-replace-tool' ), 'spacers' );
                $this->do_button( __( 'fontSizes', 'block-search-replace-tool' ), 'fontsizes' );
                $this->do_button( __( 'colors', 'block-search-replace-tool' ), 'colors' );
            ?>
        </div>
        <h4><?php esc_html_e( 'Block style variation helper buttons:', 'block-search-replace-tool' ); ?>
            <span class="normal-weight">(<?php printf(
	            /* translators: 1: bloc style variation finder admin page url */
	            esc_html__( 'see the <a href="%1$s">Block style finder</a> page', 'sahmi' ),
                esc_html(admin_url( 'tools.php?page=blocksvfinder' ))
            );?>)</span></h4>
        <div class="helper-buttons" style="margin-top: 16px;">
            <?php
                foreach( $this->styles as $style ) {
                    $this->do_button( $style, 'is-style' );
                }
            ?>
        </div>
        <?php 
        $this->do_submit_button( 'search', 'primary', __( 'Search', 'block-search-replace-tool' ) );
    }

    public function render_replace_field( $args ) {
        ?>
        <input 
            style="width:100%" 
            type="text" 
            class="<?php echo esc_attr( $args['class'] ); ?>" 
            id="<?php echo esc_attr( $args['label_for'] ); ?>" 
            name="<?php echo esc_html( $this->options_name ); ?>[<?php echo esc_attr( $args['label_for'] ); ?>]" 
            value="<?php echo esc_html( htmlentities( $this->options['replace_text'] ) ); ?>">
        <?php
        $this->do_submit_button( 'search', 'secondary', __( 'Replace', 'block-search-replace-tool' ) );
    }

    public function render_results_table( $results ) {
        printf(
            '<table><tr><th>ID</th><th>post_type</th><th>post_name</th><th>post_title</th><th>view</th></tr>'
        );
        foreach($results as $result) {
            printf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                esc_html( $result->ID ), 
                esc_html( $result->post_type ), 
                esc_html( $result->post_name ), 
                '<a href="'.esc_html( get_edit_post_link($result->ID) ) . '">' . esc_html( $result->post_title ) . '</a>',
                '<a target="_blank" href="'.esc_html( get_permalink($result->ID) ) . '">'.esc_html( $result->post_title ) . '</a>',
            );
        }
        printf(
            '</table>'
        );
    }

    public function search_query( $search_string ) {
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_type, post_name, post_title
                FROM {$wpdb->posts} 
                WHERE `post_type` <> 'revision'
                AND `post_content` LIKE %s",
                '%' . $search_string . '%'
            )
        );
        return $results;
    }

    public function replace_query( ) {
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "UPDATE {$wpdb->posts} 
                SET post_content = REPLACE( post_content, %s, %s)
                WHERE `post_type` <> 'revision'",
                $this->options['search_text'], 
                $this->options['replace_text']
            )
        );
        return $results;
    }

    public function do_submit_button($name, $type, $value) {
        printf(
            '<p class="submit"><input onclick="setAction(\'%s\' )" id="%s" class="button button-%s" type="submit" name="%s" value="%s"></p>',
            esc_html( $value ),
            esc_html( $name ),
            esc_html( $type ),
            esc_html( $name ),
            esc_html( $value )
        );
    }

    public function do_button($name, $type) {
        switch($type) {
            case 'spacings':
                $search_text = 'preset|spacing|';
                break;
            case 'spacers':
                $search_text = '<!-- wp:spacer ';
                break;
            case 'fontsizes':
                $search_text = 'fontSize';
                break;
            case 'colors':
                $search_text = 'preset|color|';
                break;
            case 'is-style':
                $search_text = '' . $type . '-' . $name . '';
                break;
            default:
                $search_text = '';
        }
        printf(
            '<button type="button" onclick="setSearch(\'%s\' )">%s</button>',
            esc_html( $search_text ),
            esc_html( $name )
        );
    }

    public function add_message( $message_code ) {
        switch ($message_code) {
            case 'search_ok':
                $message = __( 'Search done', 'block-search-replace-tool' );
                $message_type = 'update';
                break;
            case 'replace_ok':
                $message = __( 'Replacement done', 'block-search-replace-tool' );
                $message_type = 'update';
                break;
            case 'no_search_string':
                $message = __( 'No search string has been provided', 'block-search-replace-tool' );
                $message_type = 'error';
                break;
            case 'no_replace_string';
                $message = __( 'No replace string has been provided', 'block-search-replace-tool' );
                $message_type = 'error';
                break;
            case 'equal_search_replace_strings':
                $message = __( 'Replace string must be different from search string', 'block-search-replace-tool' );
                $message_type = 'error';
                break;
            default:
                $message = __( 'Unknown error', 'block-search-replace-tool' );
                $message_type = 'error';
        }
        add_settings_error( 
            'blocksrtool_messages',
            'blocksrtool_message',
            $message,
            $message_type
        );
    }
}
