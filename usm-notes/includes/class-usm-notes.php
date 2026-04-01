<?php
/**
 * Clasa principală a pluginului USM Notes.
 *
 * @package USM_Notes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class USM_Notes {

    /**
     * Înregistrează toate hook-urile necesare.
     */
    public function init() {
        // Înregistrare CPT și taxonomie
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'init', [ $this, 'register_taxonomy' ] );

        // Metabox pentru data de reamintire
        add_action( 'add_meta_boxes', [ $this, 'add_due_date_meta_box' ] );
        add_action( 'save_post_note', [ $this, 'save_due_date_meta' ], 10, 2 );

        // Validare înainte de salvare (mesaj de eroare)
        add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );

        // Coloană personalizată în lista adminului
        add_filter( 'manage_note_posts_columns', [ $this, 'add_due_date_column' ] );
        add_action( 'manage_note_posts_custom_column', [ $this, 'render_due_date_column' ], 10, 2 );
        add_filter( 'manage_edit-note_sortable_columns', [ $this, 'sortable_due_date_column' ] );

        // Shortcode
        add_shortcode( 'usm_notes', [ $this, 'render_shortcode' ] );

        // Stiluri frontend
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
    }

    // =========================================================================
    // PASUL 3: Înregistrare Custom Post Type
    // =========================================================================

    /**
     * Înregistrează tipul de postare personalizat „Notiță" (note).
     */
    public function register_post_type() {
        $labels = [
            'name'               => __( 'Notițe', 'usm-notes' ),
            'singular_name'      => __( 'Notiță', 'usm-notes' ),
            'menu_name'          => __( 'Notițe', 'usm-notes' ),
            'add_new'            => __( 'Adaugă notiță', 'usm-notes' ),
            'add_new_item'       => __( 'Adaugă notiță nouă', 'usm-notes' ),
            'edit_item'          => __( 'Editează notița', 'usm-notes' ),
            'new_item'           => __( 'Notiță nouă', 'usm-notes' ),
            'view_item'          => __( 'Vizualizează notița', 'usm-notes' ),
            'search_items'       => __( 'Caută notițe', 'usm-notes' ),
            'not_found'          => __( 'Nu au fost găsite notițe.', 'usm-notes' ),
            'not_found_in_trash' => __( 'Nu există notițe în coș.', 'usm-notes' ),
            'all_items'          => __( 'Toate notițele', 'usm-notes' ),
        ];

        $args = [
            'labels'      => $labels,
            'public'      => true,
            'has_archive' => true,
            'menu_icon'   => 'dashicons-sticky',
            'supports'    => [ 'title', 'editor', 'author', 'thumbnail' ],
            'rewrite'     => [ 'slug' => 'notes' ],
            'show_in_rest' => true, // Compatibilitate Gutenberg
        ];

        register_post_type( 'note', $args );
    }

    // =========================================================================
    // PASUL 4: Înregistrare taxonomie personalizată
    // =========================================================================

    /**
     * Înregistrează taxonomia „Prioritate" (priority) pentru CPT note.
     */
    public function register_taxonomy() {
        $labels = [
            'name'              => __( 'Priorități', 'usm-notes' ),
            'singular_name'     => __( 'Prioritate', 'usm-notes' ),
            'search_items'      => __( 'Caută priorități', 'usm-notes' ),
            'all_items'         => __( 'Toate prioritățile', 'usm-notes' ),
            'parent_item'       => __( 'Prioritate părinte', 'usm-notes' ),
            'parent_item_colon' => __( 'Prioritate părinte:', 'usm-notes' ),
            'edit_item'         => __( 'Editează prioritatea', 'usm-notes' ),
            'update_item'       => __( 'Actualizează prioritatea', 'usm-notes' ),
            'add_new_item'      => __( 'Adaugă prioritate nouă', 'usm-notes' ),
            'new_item_name'     => __( 'Prioritate nouă', 'usm-notes' ),
            'menu_name'         => __( 'Priorități', 'usm-notes' ),
        ];

        $args = [
            'labels'            => $labels,
            'hierarchical'      => true,   // Comportament ca și categoriile
            'public'            => true,
            'show_admin_column' => true,   // Afișează coloana în lista admin
            'rewrite'           => [ 'slug' => 'priority' ],
            'show_in_rest'      => true,
        ];

        register_taxonomy( 'priority', [ 'note' ], $args );
    }

    // =========================================================================
    // PASUL 5: Metabox pentru data de reamintire
    // =========================================================================

    /**
     * Adaugă metaboxul în editorul CPT note.
     */
    public function add_due_date_meta_box() {
        add_meta_box(
            'usm_note_due_date',
            __( 'Data de reamintire', 'usm-notes' ),
            [ $this, 'render_due_date_meta_box' ],
            'note',
            'side',
            'high'
        );
    }

    /**
     * Randează conținutul metaboxului.
     *
     * @param WP_Post $post Postarea curentă.
     */
    public function render_due_date_meta_box( $post ) {
        // Generăm nonce pentru securitate
        wp_nonce_field( 'usm_save_note_due_date', 'usm_note_due_date_nonce' );

        $due_date = get_post_meta( $post->ID, '_usm_note_due_date', true );
        $today    = date( 'Y-m-d' );

        ?>
        <p>
            <label for="usm_note_due_date">
                <strong><?php esc_html_e( 'Selectează data *', 'usm-notes' ); ?></strong>
            </label>
        </p>
        <p>
            <input
                type="date"
                id="usm_note_due_date"
                name="usm_note_due_date"
                value="<?php echo esc_attr( $due_date ); ?>"
                min="<?php echo esc_attr( $today ); ?>"
                style="width:100%;"
                required
            />
        </p>
        <p style="color:#666;font-size:11px;">
            <?php esc_html_e( 'Data nu poate fi în trecut. Câmp obligatoriu.', 'usm-notes' ); ?>
        </p>
        <?php
    }

    /**
     * Salvează valoarea datei de reamintire.
     *
     * @param int     $post_id ID-ul postării.
     * @param WP_Post $post    Obiectul postare.
     */
    public function save_due_date_meta( $post_id, $post ) {
        // 1. Verificare nonce
        if (
            ! isset( $_POST['usm_note_due_date_nonce'] ) ||
            ! wp_verify_nonce( $_POST['usm_note_due_date_nonce'], 'usm_save_note_due_date' )
        ) {
            return;
        }

        // 2. Evităm salvarea la autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // 3. Verificare permisiuni
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // 4. Câmpul este obligatoriu
        if ( empty( $_POST['usm_note_due_date'] ) ) {
            // Salvăm un flag de eroare și prevenim publicarea
            set_transient( 'usm_note_error_' . $post_id, 'due_date_empty', 30 );
            // Revenim la ciornă dacă utilizatorul a publicat
            if ( $post->post_status === 'publish' ) {
                remove_action( 'save_post_note', [ $this, 'save_due_date_meta' ], 10 );
                wp_update_post( [
                    'ID'          => $post_id,
                    'post_status' => 'draft',
                ] );
                add_action( 'save_post_note', [ $this, 'save_due_date_meta' ], 10, 2 );
            }
            return;
        }

        // 5. Validare: data nu poate fi în trecut
        $due_date = sanitize_text_field( $_POST['usm_note_due_date'] );
        $today    = date( 'Y-m-d' );

        if ( $due_date < $today ) {
            set_transient( 'usm_note_error_' . $post_id, 'due_date_past', 30 );
            if ( $post->post_status === 'publish' ) {
                remove_action( 'save_post_note', [ $this, 'save_due_date_meta' ], 10 );
                wp_update_post( [
                    'ID'          => $post_id,
                    'post_status' => 'draft',
                ] );
                add_action( 'save_post_note', [ $this, 'save_due_date_meta' ], 10, 2 );
            }
            return;
        }

        // 6. Salvăm data validă
        update_post_meta( $post_id, '_usm_note_due_date', $due_date );
    }

    /**
     * Afișează mesaje de eroare în admin după salvare eșuată.
     */
    public function display_admin_notices() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'note' ) {
            return;
        }

        $post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
        if ( ! $post_id ) {
            return;
        }

        $error = get_transient( 'usm_note_error_' . $post_id );

        if ( $error === 'due_date_empty' ) {
            delete_transient( 'usm_note_error_' . $post_id );
            echo '<div class="notice notice-error"><p>';
            esc_html_e( 'Eroare: Câmpul „Data de reamintire" este obligatoriu. Notița a fost salvată ca ciornă.', 'usm-notes' );
            echo '</p></div>';
        }

        if ( $error === 'due_date_past' ) {
            delete_transient( 'usm_note_error_' . $post_id );
            echo '<div class="notice notice-error"><p>';
            esc_html_e( 'Eroare: Data de reamintire nu poate fi în trecut. Notița a fost salvată ca ciornă.', 'usm-notes' );
            echo '</p></div>';
        }
    }

    // =========================================================================
    // Coloană personalizată în lista admin
    // =========================================================================

    /**
     * Adaugă coloana „Due Date" în lista postărilor CPT note.
     *
     * @param  array $columns Coloanele existente.
     * @return array
     */
    public function add_due_date_column( $columns ) {
        // Inserăm coloana înainte de „date"
        $new_columns = [];
        foreach ( $columns as $key => $label ) {
            if ( $key === 'date' ) {
                $new_columns['usm_due_date'] = __( 'Data reamintire', 'usm-notes' );
            }
            $new_columns[ $key ] = $label;
        }
        return $new_columns;
    }

    /**
     * Randează valoarea coloanei personalizate.
     *
     * @param string $column  Numele coloanei.
     * @param int    $post_id ID-ul postării.
     */
    public function render_due_date_column( $column, $post_id ) {
        if ( $column === 'usm_due_date' ) {
            $due_date = get_post_meta( $post_id, '_usm_note_due_date', true );
            if ( $due_date ) {
                $today    = date( 'Y-m-d' );
                $is_past  = $due_date < $today;
                $color    = $is_past ? 'color:#c0392b;' : 'color:#27ae60;';
                echo '<span style="' . esc_attr( $color ) . 'font-weight:bold;">';
                echo esc_html( $due_date );
                echo '</span>';
            } else {
                echo '<span style="color:#999;">—</span>';
            }
        }
    }

    /**
     * Face coloana sortabilă.
     *
     * @param  array $columns
     * @return array
     */
    public function sortable_due_date_column( $columns ) {
        $columns['usm_due_date'] = 'usm_due_date';
        return $columns;
    }

    // =========================================================================
    // PASUL 6: Shortcode [usm_notes]
    // =========================================================================

    /**
     * Procesează shortcode-ul [usm_notes priority="X" before_date="YYYY-MM-DD"].
     *
     * @param  array $atts Atributele shortcode-ului.
     * @return string HTML generat.
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts(
            [
                'priority'    => '',
                'before_date' => '',
            ],
            $atts,
            'usm_notes'
        );

        $args = [
            'post_type'      => 'note',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value',
            'meta_key'       => '_usm_note_due_date',
            'order'          => 'ASC',
        ];

        // Filtru după prioritate
        if ( ! empty( $atts['priority'] ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'priority',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $atts['priority'] ),
                ],
            ];
        }

        // Filtru după dată (before_date)
        if ( ! empty( $atts['before_date'] ) ) {
            $args['meta_query'] = [
                [
                    'key'     => '_usm_note_due_date',
                    'value'   => sanitize_text_field( $atts['before_date'] ),
                    'compare' => '<=',
                    'type'    => 'DATE',
                ],
            ];
        }

        $query = new WP_Query( $args );

        ob_start();

        if ( $query->have_posts() ) {
            echo '<div class="usm-notes-list">';

            while ( $query->have_posts() ) {
                $query->the_post();
                $due_date   = get_post_meta( get_the_ID(), '_usm_note_due_date', true );
                $priorities = get_the_terms( get_the_ID(), 'priority' );
                $priority   = ( $priorities && ! is_wp_error( $priorities ) )
                    ? esc_html( $priorities[0]->name )
                    : __( 'Neprecizată', 'usm-notes' );

                // Clasa CSS bazată pe prioritate
                $priority_slug = ( $priorities && ! is_wp_error( $priorities ) )
                    ? sanitize_html_class( $priorities[0]->slug )
                    : 'none';

                ?>
                <article class="usm-note usm-priority-<?php echo $priority_slug; ?>">
                    <div class="usm-note__header">
                        <h3 class="usm-note__title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <span class="usm-note__badge usm-badge-<?php echo $priority_slug; ?>">
                            <?php echo $priority; ?>
                        </span>
                    </div>
                    <div class="usm-note__meta">
                        <?php if ( $due_date ) : ?>
                            <span class="usm-note__due">
                                📅 <?php echo esc_html( $due_date ); ?>
                            </span>
                        <?php endif; ?>
                        <span class="usm-note__author">
                            ✍️ <?php the_author(); ?>
                        </span>
                    </div>
                    <div class="usm-note__excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    <a class="usm-note__link" href="<?php the_permalink(); ?>">
                        <?php esc_html_e( 'Citește mai mult →', 'usm-notes' ); ?>
                    </a>
                </article>
                <?php
            }

            echo '</div>';
        } else {
            echo '<p class="usm-notes-empty">';
            esc_html_e( 'Nu există notițe cu parametrii specificați.', 'usm-notes' );
            echo '</p>';
        }

        wp_reset_postdata();

        return ob_get_clean();
    }

    // =========================================================================
    // Stiluri CSS frontend
    // =========================================================================

    /**
     * Înregistrează și încarcă fișierul CSS pentru frontend.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'usm-notes-style',
            USM_NOTES_URL . 'assets/css/usm-notes.css',
            [],
            USM_NOTES_VERSION,
            'all'
        );
    }
}
