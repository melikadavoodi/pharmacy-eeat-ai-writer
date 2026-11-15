<?php
/**
 * Plugin Name: دستیار هوشمند محتوای داروخانه و سئو (Pharmacy Content AI Pro)
 * Plugin URI: https://drdarabipharmacy.com
 * Description: سیستم پیشرفته تولید محتوای مقالات بلاگ، محصولات ووکامرس و دسته‌بندی‌ها با متدولوژی‌های GEO (هوش مصنوعی گوگل)، CRO، کپی‌رایتینگ AIDA/PAS، استانداردهای بالینی E-E-A-T، اسکیما JSON-LD، بازیابی خودکار (Auto-Save) و پیوند داخلی هوشمند.
 * Version: 3.2.0
 * Author: تیم سئو و هوش مصنوعی دارابی
 * Author URI: https://drdarabipharmacy.com
 * Text Domain: pharmacy-eeat-ai
 * Domain Path: /languages
 * GitHub Plugin URI: melikadavoodi/pharmacy-eeat-ai-writer
 * Primary Branch: main
 * Release Asset: true
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PHARMACY_EEAT_VERSION', '3.2.0');
define('PHARMACY_EEAT_PATH', plugin_dir_path(__FILE__));
define('PHARMACY_EEAT_URL', plugin_dir_url(__FILE__));

require_once PHARMACY_EEAT_PATH . 'includes/class-ai-service.php';
require_once PHARMACY_EEAT_PATH . 'includes/class-admin-page.php';
require_once PHARMACY_EEAT_PATH . 'includes/class-metabox.php';
require_once PHARMACY_EEAT_PATH . 'includes/class-github-updater.php';

class Pharmacy_EEAT_Plugin {

    private static $instance = null;
    private $github_updater;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_head', array($this, 'output_frontend_schema'), 20);

        // AJAX Hooks
        add_action('wp_ajax_pharmacy_ai_generate_article', array($this, 'ajax_generate_article'));
        add_action('wp_ajax_pharmacy_ai_save_draft', array($this, 'ajax_save_draft'));
        add_action('wp_ajax_pharmacy_ai_optimize_meta', array($this, 'ajax_optimize_meta'));
        add_action('wp_ajax_pharmacy_ai_chat', array($this, 'ajax_chat'));
        add_action('wp_ajax_pharmacy_ai_product_quick_action', array($this, 'ajax_product_quick_action'));

        // Init Metabox
        Pharmacy_EEAT_Metabox::init();

        // Init GitHub Direct Auto-Updater
        if (is_admin()) {
            $this->github_updater = new Pharmacy_EEAT_GitHub_Updater(__FILE__);
        }
    }

    public function register_admin_menus() {
        add_menu_page(
            'دستیار محتوای داروخانه',
            'هوش مصنوعی داروخانه',
            'manage_options',
            'pharmacy-ai-studio',
            array('Pharmacy_EEAT_Admin_Page', 'render_studio'),
            'dashicons-plus-alt',
            25
        );

        add_submenu_page(
            'pharmacy-ai-studio',
            'استودیوی تولید محتوا',
            'استودیوی تولید محتوا',
            'manage_options',
            'pharmacy-ai-studio',
            array('Pharmacy_EEAT_Admin_Page', 'render_studio')
        );

        add_submenu_page(
            'pharmacy-ai-studio',
            'تنظیمات و E-E-A-T',
            'تنظیمات و E-E-A-T',
            'manage_options',
            'pharmacy-ai-settings',
            array('Pharmacy_EEAT_Admin_Page', 'render_settings')
        );
    }

    public function enqueue_admin_assets($hook) {
        wp_enqueue_style(
            'pharmacy-eeat-admin-css',
            PHARMACY_EEAT_URL . 'assets/admin.css',
            array(),
            PHARMACY_EEAT_VERSION
        );

        wp_enqueue_script(
            'pharmacy-eeat-admin-js',
            PHARMACY_EEAT_URL . 'assets/admin.js',
            array('jquery'),
            PHARMACY_EEAT_VERSION,
            true
        );

        wp_localize_script('pharmacy-eeat-admin-js', 'pharmacyAiData', array(
            'ajax_url'       => admin_url('admin-ajax.php'),
            'nonce'          => wp_create_nonce('pharmacy_ai_nonce'),
            'reviewer_name'  => get_option('pharmacy_ai_reviewer_name', 'دکتر داروساز'),
            'reviewer_code'  => get_option('pharmacy_ai_reviewer_code', '۱۲۳۴۵'),
            'disclaimer'     => get_option('pharmacy_ai_disclaimer', 'اطلاعات ارائه‌شده در این مقاله صرفاً جنبه آموزشی دارد و نباید جایگزین تشخیص پزشک شود.')
        ));
    }

    public function enqueue_frontend_assets() {
        if (is_singular()) {
            wp_add_inline_style('wp-block-library', '
                .pharmacy-takeaways-box {
                    background: #f8fafc;
                    border: 1px solid #cbd5e1;
                    border-right: 5px solid #0284c7;
                    padding: 16px 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                }
                .pharmacy-takeaways-box h4 {
                    margin-top: 0;
                    margin-bottom: 10px;
                    color: #0369a1;
                    font-size: 16px;
                }
                .pharmacy-takeaways-box ul {
                    margin-bottom: 0;
                    padding-right: 20px;
                }
                .pharmacy-eeat-reviewer-box {
                    background: #f0fdf4;
                    border-right: 4px solid #16a34a;
                    padding: 14px 18px;
                    border-radius: 8px;
                    margin: 25px 0 15px 0;
                    color: #166534;
                    font-size: 14px;
                }
                .pharmacy-eeat-disclaimer-box {
                    background: #fef2f2;
                    border-right: 4px solid #ef4444;
                    padding: 14px 18px;
                    border-radius: 8px;
                    margin: 15px 0 25px 0;
                    color: #991b1b;
                    font-size: 13px;
                    line-height: 1.7;
                }
            ');
        }
    }

    public function output_frontend_schema() {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }

        $schema_json = get_post_meta($post_id, '_pharmacy_ai_json_ld', true);
        if (!empty($schema_json)) {
            echo "\n<!-- Pharmacy AI Content Pro Structured Data (GEO / Schema.org) -->\n";
            echo '<script type="application/ld+json">' . wp_unslash($schema_json) . "</script>\n";
        }
    }

    public function ajax_generate_article() {
        check_ajax_referer('pharmacy_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('شما دسترسی لازم برای تولید محتوا را ندارید.');
        }

        $source_urls = array();
        if (isset($_POST['source_urls']) && is_array($_POST['source_urls'])) {
            foreach ($_POST['source_urls'] as $u) {
                if (!empty($u)) {
                    $source_urls[] = esc_url_raw($u);
                }
            }
        }

        $params = array(
            'content_type'    => sanitize_text_field($_POST['content_type'] ?? 'blog_post'),
            'mode'            => sanitize_text_field($_POST['mode'] ?? 'topic'),
            'topic'           => sanitize_text_field($_POST['topic'] ?? ''),
            'source_urls'     => $source_urls,
            'source_text'     => sanitize_textarea_field($_POST['source_text'] ?? ''),
            'focus_keyword'   => sanitize_text_field($_POST['focus_keyword'] ?? ''),
            'word_count'      => intval($_POST['word_count'] ?? 1500),
            'tone'            => sanitize_text_field($_POST['tone'] ?? 'medical_consultant'),
            'seo_strategy'    => sanitize_text_field($_POST['seo_strategy'] ?? 'geo_ai_search'),
            'target_audience' => sanitize_text_field($_POST['target_audience'] ?? 'general'),
        );

        $result = Pharmacy_EEAT_AI_Service::generate_content($params);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    public function ajax_save_draft() {
        check_ajax_referer('pharmacy_ai_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('دسترسی مجاز نیست.');
        }

        $content_type      = sanitize_text_field($_POST['content_type'] ?? 'blog_post');
        $target_product    = sanitize_text_field($_POST['target_product'] ?? '');
        $title             = sanitize_text_field($_POST['title'] ?? '');
        $content           = wp_kses_post($_POST['content'] ?? '');
        $short_description = wp_kses_post($_POST['short_description'] ?? '');
        $excerpt           = sanitize_textarea_field($_POST['excerpt'] ?? '');

        if (!empty($short_description)) {
            $excerpt = $short_description;
        }

        $rm_title   = sanitize_text_field($_POST['rank_math_title'] ?? '');
        $rm_desc    = sanitize_text_field($_POST['rank_math_desc'] ?? '');
        $rm_keyword = sanitize_text_field($_POST['rank_math_keyword'] ?? '');

        $schema_json    = isset($_POST['schema_json']) ? wp_unslash($_POST['schema_json']) : '';
        $internal_links = isset($_POST['internal_links']) ? wp_unslash($_POST['internal_links']) : '';
        $scorecard      = isset($_POST['scorecard']) ? wp_unslash($_POST['scorecard']) : '';

        if (empty($title) || empty($content)) {
            wp_send_json_error('عنوان یا متن محتوا نمی‌تواند خالی باشد.');
        }

        // If user entered a target product ID or URL, update existing post
        $existing_id = 0;
        if (!empty($target_product)) {
            $raw_target = trim($target_product);
            if (is_numeric($raw_target)) {
                $existing_id = intval($raw_target);
            } elseif (preg_match('/[?&](?:p|post)=([0-9]+)/', $raw_target, $m)) {
                $existing_id = intval($m[1]);
            } else {
                $existing_id = url_to_postid($raw_target);
            }
        }

        $save_mode = sanitize_text_field($_POST['save_mode'] ?? 'replace');

        if ($existing_id > 0 && ($existing_post = get_post($existing_id))) {
            $final_content = $content;
            if ($save_mode === 'append' && !empty($existing_post->post_content)) {
                $final_content = $existing_post->post_content . "\n\n" . $content;
            }

            $update_data = array(
                'ID'           => $existing_id,
                'post_content' => $final_content,
                'post_excerpt' => $excerpt,
            );
            if (!empty($title)) {
                $update_data['post_title'] = $title;
            }
            wp_update_post($update_data);
            $post_id = $existing_id;
            $post_title_result = $existing_post->post_title;
        } else {
            $post_type = ($content_type === 'product' && post_type_exists('product')) ? 'product' : 'post';
            $post_data = array(
                'post_title'   => $title,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_status'  => 'draft',
                'post_type'    => $post_type,
                'post_author'  => get_current_user_id(),
            );
            $post_id = wp_insert_post($post_data);
            $post_title_result = $title;
        }

        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }

        // Save Rank Math SEO fields
        if (!empty($rm_title)) {
            update_post_meta($post_id, 'rank_math_title', $rm_title);
        }
        if (!empty($rm_desc)) {
            update_post_meta($post_id, 'rank_math_description', $rm_desc);
        }
        if (!empty($rm_keyword)) {
            update_post_meta($post_id, 'rank_math_focus_keyword', $rm_keyword);
        }

        // Save Schema and Insights metadata
        if (!empty($schema_json)) {
            update_post_meta($post_id, '_pharmacy_ai_json_ld', $schema_json);
        }
        if (!empty($internal_links)) {
            update_post_meta($post_id, '_pharmacy_ai_internal_links', $internal_links);
        }
        if (!empty($scorecard)) {
            update_post_meta($post_id, '_pharmacy_ai_scorecard', $scorecard);
        }

        wp_send_json_success(array(
            'post_id'     => $post_id,
            'is_update'   => ($existing_id > 0),
            'post_title'  => $post_title_result ?? $title,
            'edit_url'    => admin_url('post.php?post=' . $post_id . '&action=edit'),
            'preview_url' => get_permalink($post_id) ?: get_preview_post_link($post_id),
        ));
    }

    public function ajax_product_quick_action() {
        check_ajax_referer('pharmacy_ai_nonce', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $action_type = sanitize_text_field($_POST['action_type'] ?? 'all');

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('دسترسی نامعتبر است.');
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('محصول یافت نشد.');
        }

        $params = array(
            'content_type'    => 'product',
            'mode'            => 'topic',
            'topic'           => $post->post_title,
            'focus_keyword'   => get_post_meta($post_id, 'rank_math_focus_keyword', true) ?: $post->post_title,
            'word_count'      => 800,
            'tone'            => 'commercial_buyer',
            'seo_strategy'    => 'cro_conversion',
            'target_audience' => 'general',
        );

        $result = Pharmacy_EEAT_AI_Service::generate_content($params);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        $content           = $result['content'] ?? '';
        $short_description = $result['short_description'] ?? ($result['excerpt'] ?? '');
        $rm_title          = $result['rank_math_title'] ?? '';
        $rm_desc           = $result['rank_math_description'] ?? '';
        $rm_keyword        = $result['rank_math_focus_keyword'] ?? '';
        $schema_json       = isset($result['schema_json']) ? (is_array($result['schema_json']) ? wp_json_encode($result['schema_json'], JSON_UNESCAPED_UNICODE) : $result['schema_json']) : '';

        // Update database directly
        $update_data = array('ID' => $post_id);
        if ($action_type === 'all' || $action_type === 'desc') {
            $update_data['post_content'] = $content;
        }
        if ($action_type === 'all' || $action_type === 'features') {
            $update_data['post_excerpt'] = $short_description;
        }
        wp_update_post($update_data);

        if (!empty($rm_title)) {
            update_post_meta($post_id, 'rank_math_title', $rm_title);
        }
        if (!empty($rm_desc)) {
            update_post_meta($post_id, 'rank_math_description', $rm_desc);
        }
        if (!empty($rm_keyword)) {
            update_post_meta($post_id, 'rank_math_focus_keyword', $rm_keyword);
        }
        if (!empty($schema_json)) {
            update_post_meta($post_id, '_pharmacy_ai_json_ld', $schema_json);
        }

        wp_send_json_success(array(
            'content'           => $content,
            'short_description' => $short_description,
            'rank_math_title'   => $rm_title,
            'rank_math_desc'    => $rm_desc,
            'message'           => 'اطلاعات با متدولوژی هوشمند کپی‌رایتینگ و CRO با موفقیت در محصول و رنک‌مث ذخیره شد!'
        ));
    }

    public function ajax_chat() {
        check_ajax_referer('pharmacy_ai_nonce', 'nonce');

        $messages = isset($_POST['messages']) ? (array) $_POST['messages'] : array();
        if (empty($messages)) {
            wp_send_json_error('پیامی دریافت نشد.');
        }

        $res = Pharmacy_EEAT_AI_Service::chat_message($messages);
        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        wp_send_json_success($res);
    }

    public function ajax_optimize_meta() {
        check_ajax_referer('pharmacy_ai_nonce', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('دسترسی مجاز نیست.');
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('نوشته یافت نشد.');
        }

        $clean_text = wp_strip_all_tags($post->post_content);
        $summary = mb_substr($clean_text, 0, 1500);

        $params = array(
            'mode'          => 'topic',
            'topic'         => 'تولید متای سئو برای: ' . $post->post_title . "\nخلاصه متن:\n" . $summary,
            'focus_keyword' => get_post_meta($post_id, 'rank_math_focus_keyword', true),
            'word_count'    => 300,
            'seo_strategy'  => 'geo_ai_search',
        );

        $result = Pharmacy_EEAT_AI_Service::generate_content($params);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['rank_math_title'])) {
            update_post_meta($post_id, 'rank_math_title', sanitize_text_field($result['rank_math_title']));
        }
        if (!empty($result['rank_math_description'])) {
            update_post_meta($post_id, 'rank_math_description', sanitize_text_field($result['rank_math_description']));
        }

        wp_send_json_success(array(
            'rank_math_title' => $result['rank_math_title'] ?? '',
            'rank_math_desc'  => $result['rank_math_description'] ?? '',
            'message'         => 'متای رنک‌مث با موفقیت آپدیت شد.'
        ));
    }
}

Pharmacy_EEAT_Plugin::get_instance();
