<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pharmacy_EEAT_Admin_Page {

    public static function render_studio() {
        $api_key = get_option('pharmacy_ai_api_key', '');
        ?>
        <div class="wrap pharmacy-ai-wrap" dir="rtl">
            <div class="pharmacy-ai-header">
                <h1>استودیوی فوق‌پیشرفته هوش مصنوعی داروخانه (Content AI Pro)</h1>
                <p class="pharmacy-ai-sub">تولید تخصصی مقالات بلاگ، صفحات محصول ووکامرس (توضیحات و ویژگی‌ها)، توضیحات دسته‌بندی و چت‌بات مراجع علمی پزشکی با هماهنگی رنک‌مث</p>
            </div>

            <?php if (empty($api_key)) : ?>
                <div class="notice notice-warning inline" style="padding: 12px; margin-bottom: 20px; border-radius: 6px;">
                    <p><strong>توجه:</strong> کلید اتصال به هوش مصنوعی (API Key) هنوز در افزونه ثبت نشده است. لطفاً ابتدا از منوی <strong>تنظیمات و E-E-A-T</strong> کلید خود را وارد کنید.</p>
                </div>
            <?php endif; ?>

            <!-- Main Layout with Two Columns: Generator + ChatBot -->
            <div class="pharmacy-two-col-layout">
                <!-- Left Column: Content Generator -->
                <div class="pharmacy-main-col">
                    <div class="pharmacy-ai-card">
                        <!-- Content Type Selector Pills -->
                        <div class="pharmacy-type-selector">
                            <label class="type-pill active">
                                <input type="radio" name="content_type_choice" value="blog_post" checked>
                                <span class="dashicons dashicons-welcome-write-blog"></span> مقاله وبلاگ پزشکی
                            </label>
                            <label class="type-pill">
                                <input type="radio" name="content_type_choice" value="product">
                                <span class="dashicons dashicons-cart"></span> صفحه محصول دارویی/مکمل (ووکامرس)
                            </label>
                            <label class="type-pill">
                                <input type="radio" name="content_type_choice" value="category">
                                <span class="dashicons dashicons-category"></span> توضیحات دسته‌بندی فروشگاه
                            </label>
                        </div>

                        <!-- Source Modes Tabs -->
                        <div class="pharmacy-tabs">
                            <button type="button" class="pharmacy-tab active" data-tab="tab-topic">تالیف و پژوهش علمی</button>
                            <button type="button" class="pharmacy-tab" data-tab="tab-multi-url">چند منبعی (چندین لینک خارجی)</button>
                            <button type="button" class="pharmacy-tab" data-tab="tab-text">ترجمه و بومی‌سازی متن خام</button>
                        </div>

                        <form id="pharmacy-ai-generate-form">
                            <input type="hidden" name="content_type" id="pharmacy_content_type" value="blog_post">
                            <input type="hidden" name="mode" id="pharmacy_mode" value="topic">

                            <!-- Optional Existing Product Link/ID Field -->
                            <div class="pharmacy-field" id="existing_product_row" style="display: none; background: #f0fdf4; padding: 14px 16px; border-radius: 8px; border: 1px dashed #16a34a; margin-bottom: 20px;">
                                <label for="pharmacy_target_product" style="color: #166534;">
                                    <span class="dashicons dashicons-admin-links" style="vertical-align: middle;"></span> اتصال به محصول موجود در سایت (جهت درج خودکار در باکس توضیحات و ویژگی‌ها):
                                </label>
                                <input type="text" id="pharmacy_target_product" name="target_product" placeholder="شناسه محصول (مثال: 5247) یا لینک محصول را وارد کنید (اختیاری)" class="regular-text" style="background:#fff;">
                                <small style="color: #15803d;">اگر شناسه محصول را وارد کنید، اطلاعات به طور خودکار مستقیماً در باکس توضیحات و باکس ویژگی‌های همان محصول ذخیره می‌شود.</small>
                            </div>

                            <!-- Tab 1: Topic -->
                            <div class="pharmacy-tab-content active" id="tab-topic">
                                <div class="pharmacy-field">
                                    <label for="pharmacy_topic">نام دارو، مکمل، بیماری یا کلمه کلیدی اصلی: <span class="req">*</span></label>
                                    <input type="text" id="pharmacy_topic" name="topic" placeholder="مثال: کلاژن پلاس ال لیزین یوروویتال" class="regular-text">
                                    <small>هوش مصنوعی پایگاه‌های معتبر پزشکی را بررسی و ساختار استاندارد را تدوین می‌کند.</small>
                                </div>
                            </div>

                            <!-- Tab 2: Multi URLs -->
                            <div class="pharmacy-tab-content" id="tab-multi-url">
                                <div class="pharmacy-field">
                                    <label>آدرس‌های مراجع خارجی (امکان افزودن چندین منبع همزمان): <span class="req">*</span></label>
                                    <div id="pharmacy-urls-repeater">
                                        <div class="url-input-row">
                                            <input type="url" name="source_urls[]" placeholder="https://www.mayoclinic.org/drugs-supplements/..." class="regular-text" dir="ltr">
                                            <button type="button" class="button btn-remove-url" style="display:none;">&times;</button>
                                        </div>
                                    </div>
                                    <button type="button" class="button button-secondary" id="btn-add-url-row" style="margin-top: 8px;">
                                        <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span> افزودن منبع دوم/سوم (WebMD, Mayo Clinic, Drugs.com, etc.)
                                    </button>
                                    <small style="margin-top: 6px;">هوش مصنوعی متن تمام این لینک‌ها را دانلود، تطبیق و یکپارچه می‌کند.</small>
                                </div>
                            </div>

                            <!-- Tab 3: Text -->
                            <div class="pharmacy-tab-content" id="tab-text">
                                <div class="pharmacy-field">
                                    <label for="pharmacy_source_text">متن منبع انگلیسی یا نکات بروشور دارو:</label>
                                    <textarea id="pharmacy_source_text" name="source_text" rows="5" class="large-text" placeholder="متن مقالات یا مشخصات بروشور را اینجا قرار دهید..."></textarea>
                                </div>
                            </div>

                            <!-- Additional Options: Tone, Words, Audience -->
                            <div class="pharmacy-row">
                                <div class="pharmacy-col">
                                    <div class="pharmacy-field">
                                        <label for="pharmacy_tone">لحن و سبک نگارش (Tone of Voice):</label>
                                        <select id="pharmacy_tone" name="tone">
                                            <option value="medical_consultant" selected>مشاوره‌ای و معتمد داروخانه‌ای (توصیه دکتر داروساز به بیمار)</option>
                                            <option value="clinical_evidence">بالینی، شواهد محور و بسیار دقیق (Evidence-Based)</option>
                                            <option value="commercial_buyer">راهنمای خرید و ترغیب‌کننده با ارزش خرید بالا</option>
                                            <option value="simple_public">فوق‌العاده ساده، روان و عامه‌فهم</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="pharmacy-col">
                                    <div class="pharmacy-field">
                                        <label for="pharmacy_keyword">کلمه کلیدی رنک‌مث (Focus Keyword):</label>
                                        <input type="text" id="pharmacy_keyword" name="focus_keyword" placeholder="مثال: کلاژن پلاس ال لیزین یوروویتال" class="regular-text">
                                    </div>
                                </div>
                            </div>

                            <div class="pharmacy-row">
                                <div class="pharmacy-col">
                                    <div class="pharmacy-field">
                                        <label for="pharmacy_words">حجم محتوا:</label>
                                        <select id="pharmacy_words" name="word_count">
                                            <option value="800">مختصر و متمرکز (حدود ۸۰۰ کلمه - مناسب محصولات و دسته‌بندی)</option>
                                            <option value="1500" selected>استاندارد و جامع (حدود ۱۵۰۰ کلمه)</option>
                                            <option value="2500">راهنمای مرجع و کامل (بیش از ۲۵۰۰ کلمه)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="pharmacy-col">
                                    <div class="pharmacy-field">
                                        <label for="pharmacy_audience">مخاطب هدف:</label>
                                        <select id="pharmacy_audience" name="target_audience">
                                            <option value="general" selected>عموم مصرف‌کنندگان و بیماران</option>
                                            <option value="specialist">پزشکان، داروسازان و کادر درمان</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="pharmacy-action-bar">
                                <button type="submit" id="pharmacy-btn-generate" class="button button-primary button-hero">
                                    <span class="dashicons dashicons-superhero"></span> تولید محتوای هوشمند مطابق E-E-A-T
                                </button>
                                <span class="spinner" id="pharmacy-spinner"></span>
                            </div>
                        </form>
                    </div>

                    <!-- Result Panel -->
                    <div class="pharmacy-ai-card pharmacy-ai-result-card" id="pharmacy-result-container" style="display: none; margin-top: 25px;">
                        <div class="pharmacy-result-header">
                            <h2>پیش‌نمایش محتوا و داده‌های رنک‌مث</h2>
                            <div class="pharmacy-result-actions">
                                <button type="button" class="button button-primary" id="pharmacy-save-draft-btn">
                                    <span class="dashicons dashicons-saved"></span> ذخیره / به‌روزرسانی مستقیم در سایت
                                </button>
                            </div>
                        </div>

                        <!-- Rank Math Fields -->
                        <div class="pharmacy-rankmath-box">
                            <div class="rankmath-box-title">
                                <span class="dashicons dashicons-chart-area"></span> فیلدهای یکپارچه با رنک‌مث (Rank Math SEO)
                            </div>
                            <div class="pharmacy-field">
                                <label>عنوان سئو (SEO Title):</label>
                                <input type="text" id="res_rank_math_title" class="regular-text">
                            </div>
                            <div class="pharmacy-field">
                                <label>توضیحات متا (Meta Description):</label>
                                <textarea id="res_rank_math_desc" rows="2" class="large-text"></textarea>
                            </div>
                            <div class="pharmacy-field">
                                <label>کلمه کلیدی کانونی (Focus Keyword):</label>
                                <input type="text" id="res_rank_math_keyword" class="regular-text">
                            </div>
                        </div>

                        <div class="pharmacy-field">
                            <label>عنوان اصلی:</label>
                            <input type="text" id="res_post_title" class="large-text" style="font-size: 1.1em; font-weight: bold;">
                        </div>

                        <!-- Product Features / Short Description Box (Important!) -->
                        <div class="pharmacy-field" id="res_features_box">
                            <label style="color: #0369a1; font-weight: bold;">
                                <span class="dashicons dashicons-editor-ul" style="vertical-align: middle;"></span> معرفی ویژگی‌های محصول (توضیحات کوتاه / Short Description ووکامرس):
                            </label>
                            <textarea id="res_short_description" rows="4" class="large-text" placeholder="فهرست ویژگی‌های شاخص، ترکیبات و فواید محصول..."></textarea>
                        </div>

                        <div class="pharmacy-field">
                            <label>چکیده نوشته (Excerpt):</label>
                            <textarea id="res_post_excerpt" rows="2" class="large-text"></textarea>
                        </div>

                        <!-- Main Content / Full Product Description Box -->
                        <div class="pharmacy-field">
                            <label style="color: #166534; font-weight: bold;">
                                <span class="dashicons dashicons-editor-alignright" style="vertical-align: middle;"></span> توضیحات کامل محصول / مقاله (HTML):
                            </label>
                            <div id="res_content_preview" class="pharmacy-article-body"></div>
                            <textarea id="res_post_content" style="display: none;"></textarea>
                        </div>

                        <div class="pharmacy-references-box">
                            <h3><span class="dashicons dashicons-book"></span> مراجع علمی استناد شده:</h3>
                            <ul id="res_references_list"></ul>
                        </div>
                    </div>
                </div>

                <!-- Right Column: RankBot / PharmaBot Chat -->
                <div class="pharmacy-side-col">
                    <div class="pharmacy-ai-card pharmacy-chatbot-card">
                        <div class="pharmacy-chatbot-header">
                            <span class="dashicons dashicons-format-chat"></span>
                            <div>
                                <h3>دستیار هوشمند داروساز (PharmaBot)</h3>
                                <small>کشف منابع معتبر، پیشنهاد رفرنس‌ها و تحلیل تخصصی</small>
                            </div>
                        </div>

                        <!-- Chat Messages List -->
                        <div class="pharmacy-chat-messages" id="pharmacy-chat-box">
                            <div class="chat-msg bot-msg">
                                <p>سلام! من دستیار هوشمند داروساز شما هستم. می‌توانید از من بخواهید معتبرترین مراجع پزشکی (PubMed, Mayo Clinic, FDA) را برای یک دارو یا بیماری معرفی کنم، یا لینک‌های مرجع خارجی را استخراج نمایم.</p>
                            </div>
                        </div>

                        <!-- Quick Prompts Buttons -->
                        <div class="pharmacy-chat-quick-prompts">
                            <button type="button" class="quick-prompt-btn" data-prompt="معتبرترین مقالات و لینک‌های علمی مایوکلینیک و پاب‌مد برای داروی ">معرفی مراجع یک دارو</button>
                            <button type="button" class="quick-prompt-btn" data-prompt="تداخلات دارویی خطرناک و مهم این ترکیب را بررسی و منبع معتبر معرفی کن: ">بررسی تداخلات دارویی</button>
                            <button type="button" class="quick-prompt-btn" data-prompt="سرفصل‌های استاندارد پزشکی بر اساس E-E-A-T برای این موضوع بچین: ">طراحی سرفصل مقاله</button>
                        </div>

                        <!-- Chat Input Box -->
                        <div class="pharmacy-chat-input-bar">
                            <textarea id="pharmacy-chat-input" rows="2" placeholder="پیام یا درخواست منبع خود را بنویسید..."></textarea>
                            <button type="button" id="pharmacy-chat-send-btn" class="button button-primary">
                                <span class="dashicons dashicons-arrow-left-alt2"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_settings() {
        if (isset($_POST['pharmacy_ai_save_settings']) && check_admin_referer('pharmacy_ai_settings_action')) {
            update_option('pharmacy_ai_provider', sanitize_text_field($_POST['pharmacy_ai_provider']));
            update_option('pharmacy_ai_api_key', sanitize_text_field($_POST['pharmacy_ai_api_key']));
            update_option('pharmacy_ai_model', sanitize_text_field($_POST['pharmacy_ai_model']));
            update_option('pharmacy_ai_api_proxy', esc_url_raw(trim($_POST['pharmacy_ai_api_proxy'])));
            update_option('pharmacy_ai_reviewer_name', sanitize_text_field($_POST['pharmacy_ai_reviewer_name']));
            update_option('pharmacy_ai_reviewer_code', sanitize_text_field($_POST['pharmacy_ai_reviewer_code']));
            update_option('pharmacy_ai_disclaimer', sanitize_textarea_field($_POST['pharmacy_ai_disclaimer']));
            update_option('pharmacy_ai_github_repo', sanitize_text_field($_POST['pharmacy_ai_github_repo']));
            update_option('pharmacy_ai_github_token', sanitize_text_field($_POST['pharmacy_ai_github_token']));
            echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شدند.</p></div>';
        }

        $provider       = get_option('pharmacy_ai_provider', 'openrouter');
        $api_key        = get_option('pharmacy_ai_api_key', '');
        $model          = get_option('pharmacy_ai_model', 'google/gemini-flash-1.5');
        $api_proxy      = get_option('pharmacy_ai_api_proxy', '');
        $reviewer_name  = get_option('pharmacy_ai_reviewer_name', 'دکتر داروساز');
        $reviewer_code  = get_option('pharmacy_ai_reviewer_code', '۱۲۳۴۵');
        $disclaimer     = get_option('pharmacy_ai_disclaimer', 'اطلاعات ارائه‌شده در این مقاله صرفاً جنبه آموزشی و اطلاع‌رسانی دارد و نباید جایگزین توصیه، تشخیص یا درمان پزشک متخصص و داروساز شود. قبل از مصرف یا تغییر دوز هرگونه دارو حتماً با پزشک یا داروساز مشورت نمایید.');
        $github_repo    = get_option('pharmacy_ai_github_repo', 'melikadavoodi/pharmacy-eeat-ai-writer');
        $github_token   = get_option('pharmacy_ai_github_token', '');
        ?>
        <div class="wrap pharmacy-ai-wrap" dir="rtl">
            <div class="pharmacy-ai-header">
                <h1>تنظیمات دستیار هوش مصنوعی (پشتیبانی همزمان از اوپن‌روتر و ورکر کلادفلر)</h1>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('pharmacy_ai_settings_action'); ?>

                <div class="pharmacy-ai-card">
                    <h2>۱. سرویس‌دهنده هوش مصنوعی (AI Provider)</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pharmacy_ai_provider">انتخاب سرویس‌دهنده:</label></th>
                            <td>
                                <select name="pharmacy_ai_provider" id="pharmacy_ai_provider">
                                    <option value="openrouter" <?php selected($provider, 'openrouter'); ?>>اوپن‌روتر (OpenRouter - پیشنهادی فوری، بدون تحریم و بدون نیاز به پروکسی)</option>
                                    <option value="gemini" <?php selected($provider, 'gemini'); ?>>گوگل جمینای با ورکر کلادفلر (Google Gemini + Cloudflare Worker)</option>
                                    <option value="openai" <?php selected($provider, 'openai'); ?>>اوپن‌ای‌آی رسمی (OpenAI / ChatGPT)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pharmacy_ai_api_key">کلید دسترسی (API Key):</label></th>
                            <td>
                                <input type="password" name="pharmacy_ai_api_key" id="pharmacy_ai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" dir="ltr">
                                <p class="description">برای اوپن‌روتر کلید <code>sk-or-v1-...</code> از سایت openrouter.ai | برای جمینای کلید <code>AIzaSy...</code></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pharmacy_ai_model">مدل هوش مصنوعی:</label></th>
                            <td>
                                <input type="text" name="pharmacy_ai_model" id="pharmacy_ai_model" value="<?php echo esc_attr($model); ?>" class="regular-text" dir="ltr">
                                <p class="description">برای اوپن‌روتر: <code>google/gemini-flash-1.5</code> یا <code>google/gemini-2.0-flash-exp:free</code> | برای جمینای کلادفلر: <code>gemini-1.5-flash</code></p>
                            </td>
                        </tr>
                        <tr id="row_api_proxy">
                            <th scope="row"><label for="pharmacy_ai_api_proxy">آدرس ورکر کلادفلر (برای حالت جمینای):</label></th>
                            <td>
                                <input type="url" name="pharmacy_ai_api_proxy" id="pharmacy_ai_api_proxy" value="<?php echo esc_attr($api_proxy); ?>" class="large-text" dir="ltr" placeholder="https://your-gemini-proxy.workers.dev">
                                <p class="description">اگر حالت جمینای کلادفلر را انتخاب کرده‌اید، آدرس ورکر کلادفلر خود را اینجا قرار دهید تا تحریم گوگل روی هاست نت‌افراز دور زده شود.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="pharmacy-ai-card" style="margin-top: 20px;">
                    <h2>۲. تنظیمات اعتبار پزشکی و اعتماد گوگل (E-E-A-T Signals)</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pharmacy_ai_reviewer_name">نام داروساز ناظر / پزشک مسئول:</label></th>
                            <td>
                                <input type="text" name="pharmacy_ai_reviewer_name" id="pharmacy_ai_reviewer_name" value="<?php echo esc_attr($reviewer_name); ?>" class="regular-text">
                                <p class="description">مثال: دکتر ... (داروساز و موسس داروخانه)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pharmacy_ai_reviewer_code">شماره نظام پزشکی داروساز:</label></th>
                            <td>
                                <input type="text" name="pharmacy_ai_reviewer_code" id="pharmacy_ai_reviewer_code" value="<?php echo esc_attr($reviewer_code); ?>" class="regular-text">
                                <p class="description">درج شماره نظام پزشکی سیگنال بسیار قدرتمندی برای فاکتور اعتماد (Trust) گوگل است.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pharmacy_ai_disclaimer">متن سلب مسئولیت پزشکی (Medical Disclaimer):</label></th>
                            <td>
                                <textarea name="pharmacy_ai_disclaimer" id="pharmacy_ai_disclaimer" rows="4" class="large-text"><?php echo esc_textarea($disclaimer); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="pharmacy-ai-card" style="margin-top: 20px; border-right: 4px solid #0284c7;">
                    <h2>۳. تنظیمات به‌روزرسانی مستقیم از گیت‌هاب (GitHub Direct Auto-Update)</h2>
                    <p style="color: #64748b; font-size: 13.5px;">با فعال بودن این بخش، هر زمان نسخه جدیدی از افزونه روی مخزن گیت‌هاب منتشر شود، اعلان آپدیت مستقیماً در منوی افزونه‌های وردپرس ظاهر شده و با یک کلیک خودکار به‌روزرسانی می‌شود.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pharmacy_ai_github_repo">نام کاربری و مخزن گیت‌هاب (Repository):</label></th>
                            <td>
                                <input type="text" name="pharmacy_ai_github_repo" id="pharmacy_ai_github_repo" value="<?php echo esc_attr($github_repo); ?>" class="regular-text" dir="ltr" placeholder="username/repository-name">
                                <p class="description">مثال: <code>melikadavoodi/pharmacy-eeat-ai-writer</code></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pharmacy_ai_github_token">توکن دسترسی شخصی (GitHub Token):</label></th>
                            <td>
                                <input type="password" name="pharmacy_ai_github_token" id="pharmacy_ai_github_token" value="<?php echo esc_attr($github_token); ?>" class="regular-text" dir="ltr">
                                <p class="description">تنها در صورتی که مخزن شما در گیت‌هاب خصوصی (Private) باشد نیاز به توکن شخصی دارید. برای مخازن عمومی نیازی نیست.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <input type="submit" name="pharmacy_ai_save_settings" id="submit" class="button button-primary" value="ذخیره تنظیمات">
                </p>
            </form>
        </div>
        <?php
    }
}
