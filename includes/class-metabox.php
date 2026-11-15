<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pharmacy_EEAT_Metabox {

    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_box'));
    }

    public static function add_meta_box() {
        add_meta_box(
            'pharmacy_eeat_quick_tools',
            'دستیار پزشکی هوش مصنوعی (رنک‌مث و E-E-A-T)',
            array(__CLASS__, 'render_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public static function render_box($post) {
        $reviewer_name = get_option('pharmacy_ai_reviewer_name', 'دکتر داروساز');
        $reviewer_code = get_option('pharmacy_ai_reviewer_code', '۱۲۳۴۵');
        ?>
        <div class="pharmacy-metabox-content" dir="rtl">
            <p style="font-size: 13px; color: #555;">ابزارهای سریع هوش مصنوعی برای ارتقای سئو و اعتبار مقاله جاری:</p>

            <button type="button" class="button button-secondary pharmacy-quick-btn" id="btn-quick-meta-gen" data-post-id="<?php echo esc_attr($post->ID); ?>" style="width: 100%; margin-bottom: 8px; text-align: right;">
                <span class="dashicons dashicons-search" style="vertical-align: middle;"></span> تولید متای سئو برای رنک‌مث
            </button>

            <button type="button" class="button button-secondary pharmacy-quick-btn" id="btn-insert-reviewer" style="width: 100%; margin-bottom: 8px; text-align: right;">
                <span class="dashicons dashicons-businessman" style="vertical-align: middle;"></span> درج باکس تایید داروساز
            </button>

            <button type="button" class="button button-secondary pharmacy-quick-btn" id="btn-insert-disclaimer" style="width: 100%; text-align: right;">
                <span class="dashicons dashicons-warning" style="vertical-align: middle;"></span> درج باکس سلب مسئولیت
            </button>

            <div id="pharmacy-quick-status" style="margin-top: 10px; font-size: 12px; display: none;"></div>
        </div>
        <?php
    }
}
