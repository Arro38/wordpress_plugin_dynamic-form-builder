<?php

/**
 * Plugin Name: Dynamic Form Builder
 * Description: Un constructeur de formulaires dynamiques
 * Version: 1.0.0
 * Author: Arro38
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enregistrement du CPT pour les formulaires
function dfb_register_form_post_type()
{
    $labels = array(
        'name'               => 'Formulaires Dynamiques',
        'singular_name'      => 'Formulaire Dynamique',
        'menu_name'          => 'Formulaires Dynamiques',
        'add_new'            => 'Ajouter un formulaire',
        'add_new_item'       => 'Ajouter un nouveau formulaire',
        'edit_item'          => 'Modifier le formulaire',
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-format-aside',
        'supports'            => array('title', 'editor'),
        'rewrite'            => array('slug' => 'dynamic-form'),
    );

    register_post_type('dfb_form', $args);
}
add_action('init', 'dfb_register_form_post_type');

// Enregistrement des métadonnées pour les questions
function dfb_register_form_meta_boxes()
{
    add_meta_box(
        'dfb_questions',
        'Questions du formulaire',
        'dfb_questions_meta_box_callback',
        'dfb_form',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'dfb_register_form_meta_boxes');

// Enregistrement des scripts admin
function dfb_enqueue_admin_scripts($hook)
{
    global $post;

    if ($hook == 'post-new.php' || $hook == 'post.php') {
        if ('dfb_form' === $post->post_type) {
            wp_enqueue_script('dfb-admin-script', plugins_url('assets/js/admin-form.js', __FILE__), array('jquery'), '1.0.0', true);
            wp_enqueue_style('dfb-admin-style', plugins_url('assets/css/admin-form.css', __FILE__));
        }
    }
}
add_action('admin_enqueue_scripts', 'dfb_enqueue_admin_scripts');

// Callback pour l'affichage des champs de questions
function dfb_questions_meta_box_callback($post)
{
    wp_nonce_field('dfb_save_questions', 'dfb_questions_nonce');
    $questions = get_post_meta($post->ID, '_dfb_questions', true);
    if (!is_array($questions)) {
        $questions = array();
    }
?>
    <div id="dfb-questions-container">
        <div class="dfb-questions-list">
            <?php
            if (!empty($questions)) {
                foreach ($questions as $index => $question):
                    $answers = isset($question['answers']) ? $question['answers'] : array();
            ?>
                    <div class="question-item" data-question-id="<?php echo $index; ?>">
                        <p>
                            <label>Question:</label>
                            <input type="text" name="dfb_questions[<?php echo $index; ?>][question]"
                                value="<?php echo esc_attr($question['question']); ?>" class="question-text" />
                        </p>
                        <div class="answers-container">
                            <h4>Réponses possibles</h4>
                            <div class="answers-list">
                                <?php
                                if (!empty($answers)) {
                                    foreach ($answers as $answer_index => $answer) :
                                ?>
                                        <div class="answer-item">
                                            <input type="text"
                                                name="dfb_questions[<?php echo $index; ?>][answers][<?php echo $answer_index; ?>][text]"
                                                value="<?php echo esc_attr($answer['text']); ?>"
                                                placeholder="Texte de la réponse" />
                                            <select name="dfb_questions[<?php echo $index; ?>][answers][<?php echo $answer_index; ?>][action_type]"
                                                class="answer-action-type">
                                                <option value="next_question" <?php selected($answer['action_type'], 'next_question'); ?>>
                                                    Aller à une question
                                                </option>
                                                <option value="redirect" <?php selected($answer['action_type'], 'redirect'); ?>>
                                                    Rediriger vers un lien
                                                </option>
                                            </select>
                                            <div class="action-value-container">
                                                <?php if ($answer['action_type'] === 'next_question') : ?>
                                                    <select name="dfb_questions[<?php echo $index; ?>][answers][<?php echo $answer_index; ?>][action_value_question]"
                                                        class="question-select">
                                                        <?php foreach ($questions as $q_index => $q) : ?>
                                                            <?php if ($q_index != $index) : ?>
                                                                <option value="<?php echo $q_index; ?>"
                                                                    <?php selected($answer['action_value'], $q_index); ?>>
                                                                    <?php echo esc_html($q['question']); ?>
                                                                </option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else : ?>
                                                    <input type="url"
                                                        name="dfb_questions[<?php echo $index; ?>][answers][<?php echo $answer_index; ?>][action_value_url]"
                                                        value="<?php echo esc_url($answer['action_value']); ?>"
                                                        placeholder="URL de redirection" />
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="button remove-answer">Supprimer la réponse</button>
                                        </div>
                                <?php
                                    endforeach;
                                }
                                ?>
                            </div>
                            <button type="button" class="button add-answer">Ajouter une réponse</button>
                        </div>
                        <button type="button" class="button remove-question">Supprimer la question</button>
                    </div>
            <?php
                endforeach;
            }
            ?>
        </div>
        <button type="button" class="button button-primary" id="add-question">Ajouter une question</button>
    </div>
<?php
}

// Sauvegarde des métadonnées
function dfb_save_questions($post_id)
{
    // Vérification du nonce
    if (!isset($_POST['dfb_questions_nonce']) || !wp_verify_nonce($_POST['dfb_questions_nonce'], 'dfb_save_questions')) {
        return;
    }

    // Vérification de l'autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Vérification des permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Réactiver le hook de sauvegarde
    remove_action('save_post_dfb_form', 'dfb_save_questions');

    // Sauvegarder les questions
    if (isset($_POST['dfb_questions']) && is_array($_POST['dfb_questions'])) {
        $questions = array();
        error_log('DFB - Données reçues : ' . print_r($_POST['dfb_questions'], true));
        foreach ($_POST['dfb_questions'] as $q_index => $question) {
            if (!empty($question['question'])) {
                $answers = array();

                if (isset($question['answers']) && is_array($question['answers'])) {
                    foreach ($question['answers'] as $a_index => $answer) {
                        if (!empty($answer['text'])) {
                            $action_type = sanitize_text_field($answer['action_type']);
                            $action_value = '';

                            // Récupérer la bonne valeur selon le type d'action
                            if ($action_type === 'next_question') {
                                $action_value = sanitize_text_field($answer['action_value_question']);
                            } else if ($action_type === 'redirect') {
                                $action_value = esc_url_raw($answer['action_value_url']);
                            }

                            $answers[$a_index] = array(
                                'text' => sanitize_text_field($answer['text']),
                                'action_type' => $action_type,
                                'action_value' => $action_value
                            );
                        }
                    }
                }

                $questions[$q_index] = array(
                    'question' => sanitize_text_field($question['question']),
                    'answers' => $answers
                );
            }
        }

        // Sauvegarder les données
        update_post_meta($post_id, '_dfb_questions', $questions);

        // Log pour le débogage
        error_log('DFB - Sauvegarde des questions : ' . print_r($questions, true));
    }

    // Réactiver le hook de sauvegarde
    add_action('save_post_dfb_form', 'dfb_save_questions');
}
add_action('save_post_dfb_form', 'dfb_save_questions');

// Enregistrement des assets
function dfb_enqueue_scripts()
{
    wp_enqueue_style('dfb-styles', plugins_url('assets/css/dynamic-form.css', __FILE__));
    wp_enqueue_script('dfb-script', plugins_url('assets/js/dynamic-form.js', __FILE__), array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'dfb_enqueue_scripts');

// Shortcode pour afficher le formulaire
function dfb_display_form_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'id' => 0
    ), $atts);

    if (empty($atts['id'])) {
        return 'Veuillez spécifier un ID de formulaire.';
    }

    $questions = get_post_meta($atts['id'], '_dfb_questions', true);
    if (!is_array($questions)) {
        return 'Aucune question trouvée.';
    }

    ob_start();
?>
    <div class="dfb-form-container" data-form-id="<?php echo esc_attr($atts['id']); ?>">
        <?php foreach ($questions as $index => $question): ?>
            <div class="dfb-question" data-question-index="<?php echo esc_attr($index); ?>">
                <h3><?php echo esc_html($question['question']); ?></h3>
                <?php if (!empty($question['answers'])): ?>
                    <div class="dfb-answers">
                        <?php foreach ($question['answers'] as $answer): ?>
                            <label class="dfb-answer"
                                data-action-type="<?php echo esc_attr($answer['action_type']); ?>"
                                data-action-value="<?php echo esc_attr($answer['action_value']); ?>">
                                <button type="button" class="dfb-answer-button">
                                    <?php echo esc_html($answer['text']); ?>
                                </button>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('dynamic_form', 'dfb_display_form_shortcode');

// Ajouter une colonne pour le shortcode dans la liste des formulaires
function dfb_add_shortcode_column($columns)
{
    // Créez un nouveau tableau avec la colonne shortcode en deuxième position
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key === 'title') {
            $new_columns['title'] = $value; // Titre en première position
            $new_columns['shortcode'] = 'Shortcode'; // Shortcode en deuxième position
        } else {
            $new_columns[$key] = $value;
        }
    }
    return $new_columns;
}
add_filter('manage_edit-dfb_form_columns', 'dfb_add_shortcode_column');

// Remplir la colonne avec le shortcode
function dfb_shortcode_column_content($column, $post_id)
{
    if ($column === 'shortcode') {
        echo '[dynamic_form id="' . esc_attr($post_id) . '"]';
    }
}
add_action('manage_dfb_form_posts_custom_column', 'dfb_shortcode_column_content', 10, 2);
