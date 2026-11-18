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
            'دستیار هوش مصنوعی داروخانه (رنک‌مث، محتوا و ویژگی‌ها)',
            array(__CLASS__, 'render_box'),
            array('post', 'page', 'product'),
            'side',
            'high'
        );
    }

    public static function render_box($post) {
        $is_product = ($post->post_type === 'product');
        ?>
        <div class="pharmacy-metabox-content" dir="rtl">
            <?php if ($is_product) : ?>
                <p style="font-size: 13px; color: #166534; font-weight: bold; margin-top: 0;">
                    <span class="dashicons dashicons-cart" style="vertical-align: middle;"></span> دستیار هوشمند محصول ووکامرس:
                </p>

                <div style="margin-bottom: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 8px;">
                    <label for="pharmacy_quick_secondary_keywords" style="font-size: 11.5px; font-weight: bold; display: block; margin-bottom: 4px; color: #166534;">کلمات کلیدی مکمل یا سرفصل‌ها (اختیاری):</label>
                    <input type="text" id="pharmacy_quick_secondary_keywords" placeholder="مثال: قیمت، طریقه مصرف، عوارض، اصل و تقلبی..." style="width: 100%; font-size: 12px; border-radius: 4px;">
                </div>

                <button type="button" class="button button-primary pharmacy-quick-btn" id="btn-quick-fill-all-product" data-post-id="<?php echo esc_attr($post->ID); ?>" style="width: 100%; margin-bottom: 8px; text-align: center; background: #00a389; border-color: #008f77;">
                    <span class="dashicons dashicons-superhero" style="vertical-align: middle;"></span> تولید یکجای همه بخش‌ها (توضیحات + ویژگی‌ها + رنک‌مث)
                </button>

                <button type="button" class="button button-secondary pharmacy-quick-btn" id="btn-quick-desc-product" data-post-id="<?php echo esc_attr($post->ID); ?>" style="width: 100%; margin-bottom: 8px; text-align: right;">
                    <span class="dashicons dashicons-editor-alignright" style="vertical-align: middle;"></span> تولید و درج در توضیحات کامل محصول
                </button>

                <button type="button" class="button button-secondary pharmacy-quick-btn" id="btn-quick-features-product" data-post-id="<?php echo esc_attr($post->ID); ?>" style="width: 100%; margin-bottom: 8px; text-align: right;">
                    <span class="dashicons dashicons-editor-ul" style="vertical-align: middle;"></span> تولید و درج در باکس معرفی ویژگی‌های محصول
                </button>
            <?php endif; ?>

            <p style="font-size: 12.5px; color: #555; margin-bottom: 6px;">ابزارهای سئو و اعتبارسنجی:</p>

            <button type="button" class="button button-secondary pharmacy-quick-btn" id="btn-quick-meta-gen" data-post-id="<?php echo esc_attr($post->ID); ?>" style="width: 100%; margin-bottom: 8px; text-align: right;">
                <span class="dashicons dashicons-search" style="vertical-align: middle;"></span> تنظیم متای سئو برای رنک‌مث
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
