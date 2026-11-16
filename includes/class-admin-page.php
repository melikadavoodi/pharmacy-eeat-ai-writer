<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pharmacy_EEAT_Admin_Page {

    public static function render_studio() {
        $api_key = get_option('pharmacy_ai_api_key', '');
        ?>
        <div class="wrap pharmacy-ai-wrap" dir="rtl">
            <div class="pharmacy-ai-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h1 style="margin-bottom: 5px;">استودیوی فوق‌پیشرفته محتوا، سئو و هوش مصنوعی داروخانه (Content AI Pro v3.7.1)</h1>
                    <p class="pharmacy-ai-sub" style="margin-top: 0;">مجهز به متدولوژی‌های GEO، کپی‌رایتینگ AIDA/PAS، اعتبارسنجی بالینی چندوجهی (متن و عکس محصول)، تصویر برند کاتلر و آزمون خودکار سلامت</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" class="button button-secondary" id="btn-run-diagnostics" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; height: auto; font-weight: bold; border-color: #0284c7; color: #0284c7;">
                        <span class="dashicons dashicons-shield" style="font-size: 18px; width: 18px; height: 18px; vertical-align: middle;"></span> عیب‌یابی و تست خودکار سلامت پلتفرم
                    </button>
                </div>
            </div>

            <!-- Automated Health Diagnostics Box (v3.6.0) -->
            <div id="pharmacy-diagnostics-box" style="display: none; background: #f8fafc; border: 1px solid #cbd5e1; border-right: 5px solid #0284c7; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                    <h3 style="margin: 0; color: #0369a1; font-size: 15px; display: flex; align-items: center; gap: 6px;">
                        <span class="dashicons dashicons-yes-alt" style="color: #0284c7;"></span> گزارش زنده عیب‌یابی و آزمون سلامت پلتفرم
                    </h3>
                    <button type="button" class="button button-small" id="btn-close-diagnostics">بستن گزارش</button>
                </div>
                <div id="diagnostics-steps-list" style="display: grid; gap: 8px; font-size: 13.5px;"></div>
                <div id="diagnostics-summary" style="margin-top: 12px; padding: 10px; border-radius: 6px; font-weight: bold; font-size: 13px; display: none;"></div>
            </div>

            <?php if (empty($api_key)) : ?>
                <div class="notice notice-warning inline" style="padding: 12px; margin-bottom: 20px; border-radius: 6px;">
                    <p><strong>توجه:</strong> کلید اتصال به هوش مصنوعی (API Key) هنوز در افزونه ثبت نشده است. لطفاً ابتدا از منوی <strong>تنظیمات و E-E-A-T</strong> کلید خود را وارد کنید.</p>
                </div>
            <?php endif; ?>

            <!-- Auto-Save & Recovery Banner (v3.2.0) -->
            <div id="pharmacy-restore-banner" style="display: none; background: #f0fdf4; border: 1px solid #86efac; border-right: 5px solid #16a34a; border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <span class="dashicons dashicons-backup" style="color: #16a34a; vertical-align: middle; font-size: 20px;"></span>
                        <strong>بازیابی امن:</strong> یک نسخه ذخیره شده از محتوای تولیدشده قبلی برای <span id="saved-topic-name" style="color: #166534; font-weight: bold;"></span> (ثبت‌شده در ساعت <span id="saved-time"></span>) در حافظه مرورگر شما موجود است.
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="button button-primary" id="btn-restore-last-generated">
                            <span class="dashicons dashicons-controls-repeat" style="vertical-align: middle;"></span> بازیابی این محتوا (بدون مصرف توکن)
                        </button>
                        <button type="button" class="button button-secondary" id="btn-dismiss-restore">نادیده گرفتن</button>
                    </div>
                </div>
            </div>

            <!-- Main Layout with Two Columns: Generator + ChatBot -->
            <div class="pharmacy-two-col-layout">
                <!-- Left Column: Content Generator -->
                <div class="pharmacy-main-col">
                    <div class="pharmacy-ai-card">
                        <!-- Content Type Selector Pills (v3.5.0 Rock-Solid Clickable Buttons) -->
                        <div class="pharmacy-type-selector">
                            <button type="button" class="type-pill active" data-type="blog_post">
                                <span class="dashicons dashicons-welcome-write-blog"></span> مقاله وبلاگ پزشکی
                            </button>
                            <button type="button" class="type-pill" data-type="product">
                                <span class="dashicons dashicons-cart"></span> صفحه محصول دارویی/مکمل (ووکامرس)
                            </button>
                            <button type="button" class="type-pill" data-type="category">
                                <span class="dashicons dashicons-category"></span> توضیحات دسته‌بندی فروشگاه
                            </button>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <!-- Source Modes Tabs -->
                            <div class="pharmacy-tabs" style="margin-bottom: 0;">
                                <button type="button" class="pharmacy-tab active" data-tab="tab-topic">تالیف و پژوهش علمی</button>
                                <button type="button" class="pharmacy-tab" data-tab="tab-multi-url">چند منبعی (چندین لینک خارجی)</button>
                                <button type="button" class="pharmacy-tab" data-tab="tab-text">ترجمه و بومی‌سازی متن خام</button>
                            </div>
                            <button type="button" class="button button-secondary" id="btn-reset-form-top" style="display: inline-flex; align-items: center; gap: 4px; color: #b91c1c; border-color: #fca5a5; font-size: 12px; height: 32px;">
                                <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> پاکسازی فرم / شروع محصول جدید
                            </button>
                        </div>

                        <form id="pharmacy-ai-generate-form">
                            <input type="hidden" name="content_type" id="pharmacy_content_type" value="blog_post">
                            <input type="hidden" name="mode" id="pharmacy_mode" value="topic">

                            <!-- Optional Existing Product Link/ID Field -->
                            <div class="pharmacy-field" id="existing_product_row" style="display: none; background: #f0fdf4; padding: 14px 16px; border-radius: 8px; border: 1px dashed #16a34a; margin-bottom: 20px;">
                                <label for="pharmacy_target_product" style="color: #166534; font-weight: bold;">
                                    <span class="dashicons dashicons-admin-links" style="vertical-align: middle;"></span> اتصال به محصول موجود در سایت (جهت درج خودکار در باکس توضیحات و ویژگی‌ها):
                                </label>
                                <input type="text" id="pharmacy_target_product" name="target_product" placeholder="شناسه محصول (مثال: 5247) یا لینک محصول را وارد کنید (اختیاری)" class="regular-text" style="background:#fff;">
                                <small style="color: #15803d;">اگر شناسه محصول را وارد کنید، اطلاعات به طور خودکار مستقیماً در باکس توضیحات و باکس ویژگی‌های همان محصول ذخیره می‌شود.</small>
                            </div>

                            <!-- Tab 1: Topic -->
                            <div class="pharmacy-tab-content active" id="tab-topic">
                                <div class="pharmacy-field">
                                    <label for="pharmacy_topic">نام دارو، مکمل، بیماری یا کلمه کلیدی اصلی: <span class="req">*</span></label>
                                    <input type="text" id="pharmacy_topic" name="topic" placeholder="مثال: کپسول کلاژن پلاس ال لیزین یوروویتال" class="regular-text">
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

                            <!-- Clinical Ground Truth & Multimodal Vision Box (v3.7.0) -->
                            <div class="pharmacy-ground-truth-box" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-right: 5px solid #16a34a; border-radius: 8px; padding: 14px 18px; margin-bottom: 18px;">
                                <h3 style="margin-top: 0; color: #166534; font-size: 14.5px; display: flex; align-items: center; gap: 6px;">
                                    <span class="dashicons dashicons-forms" style="color: #16a34a;"></span> مشخصات بالینی واقعی و قطعی محصول (منع مطلق خطای دوز و ترکیبات)
                                </h3>
                                <p style="color: #15803d; font-size: 12.5px; margin: 2px 0 10px 0;">
                                    برای اینکه هوش مصنوعی دوز، ترکیبات یا شکل دارو (ویال خوراکی، کپسول، ساشه) را اشتباه ننویسد، می‌توانید مشخصات را بنویسید یا <strong>عکس بروشور / جعبه محصول</strong> را بارگذاری کنید تا مو به مو طبق آن بنویسد:
                                </p>

                                <div class="pharmacy-field" style="margin-bottom: 12px;">
                                    <label for="pharmacy_ground_truth" style="font-weight: 600; color: #14532d; font-size: 13px;">
                                        <span class="dashicons dashicons-edit"></span> دوز دقیق، ترکیبات و نحوه مصرف (متن):
                                    </label>
                                    <textarea id="pharmacy_ground_truth" name="ground_truth" rows="3" class="large-text" placeholder="مثال: ویال خوراکی ۲۵ میلی‌لیتری؛ هر ویال حاوی ۵۰۰۰ میلی‌گرم کلاژن هیدرولیزشده، ۸۰ میلی‌گرم ویتامین C، بیوتین و زینک. بسته ۱۴ عددی. طعم پرتقال. مصرف روزانه ۱ ویال ترجیحاً صبح‌ها."></textarea>
                                </div>

                                <div class="pharmacy-field" style="margin-bottom: 0;">
                                    <label style="font-weight: 600; color: #14532d; font-size: 13px; display: block; margin-bottom: 6px;">
                                        <span class="dashicons dashicons-camera"></span> بارگذاری تصویر جعبه محصول، بروشور یا جدول ترکیبات (عکس):
                                    </label>
                                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                        <input type="file" id="pharmacy_product_image_input" accept="image/*" style="display: none;">
                                        <button type="button" class="button button-secondary" id="btn-upload-product-image" style="display: inline-flex; align-items: center; gap: 6px;">
                                            <span class="dashicons dashicons-upload"></span> انتخاب و بارگذاری عکس محصول (JPG / PNG)
                                        </button>
                                        <button type="button" class="button button-link-delete" id="btn-remove-product-image" style="display: none; color: #dc2626;">حذف عکس</button>
                                    </div>
                                    
                                    <div id="product_image_preview_wrap" style="display: none; margin-top: 10px; align-items: center; gap: 12px; background: #fff; padding: 10px; border-radius: 6px; border: 1px dashed #16a34a;">
                                        <img id="product_image_preview" src="" alt="پیش‌نمایش تصویر محصول" style="max-height: 80px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                        <div>
                                            <strong id="product_image_filename" style="color: #166534; font-size: 12.5px;"></strong>
                                            <div style="color: #475569; font-size: 11.5px; margin-top: 2px;">هوش مصنوعی چندوجهی جدول ترکیبات و دوزها را مستقیماً از روی این تصویر اسکن و تایید می‌کند.</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="product_image_b64" id="pharmacy_product_image_b64" value="">
                                </div>
                            </div>

                            <!-- Strategy Row (Epic v3.0 Feature) -->
                            <div class="pharmacy-row" style="background: #f8fafc; padding: 12px 14px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 16px;">
                                <div class="pharmacy-col" style="flex: 1 1 100%;">
                                    <div class="pharmacy-field" style="margin-bottom: 0;">
                                        <label for="pharmacy_seo_strategy" style="color: #0f172a; font-weight: bold;">
                                            <span class="dashicons dashicons-awards" style="color: #0284c7; vertical-align: middle;"></span> استراتژی سئو و نگارش (SEO & Copywriting Strategy):
                                        </label>
                                        <select id="pharmacy_seo_strategy" name="seo_strategy" style="width: 100%; font-weight: 500;">
                                            <option value="geo_ai_search" selected>بهینه‌سازی برای هوش مصنوعی گوگل و سرچ مدرن (GEO - AI Overviews & Perplexity) 🚀</option>
                                            <option value="cro_conversion">بهینه‌سازی نرخ تبدیل و روانشناسی فروش سلامت (CRO & AIDA/PAS) 🛒</option>
                                            <option value="deep_clinical_eeat">مقاله جامع مرجع و آسمان‌خراش بالینی (Deep Clinical E-E-A-T) 🔬</option>
                                        </select>
                                        <small style="color: #475569;">این تنظیم زاویه دید، پاراگراف لید، جداول مقایسه‌ای و ساختار پاسخ‌دهی را به طور خودکار تعیین می‌کند.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Brand Voice & Identity Section (v3.4.0 Multi-Tone Feature) -->
                            <div class="pharmacy-row" style="background: #faf5ff; padding: 14px 16px; border-radius: 8px; border: 1px solid #f3e8ff; margin-bottom: 16px;">
                                <div class="pharmacy-col" style="flex: 1 1 100%; margin-bottom: 12px;">
                                    <div class="pharmacy-field" style="margin-bottom: 0;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                            <label style="color: #6b21a8; font-weight: bold; margin-bottom: 0;">
                                                <span class="dashicons dashicons-microphone" style="vertical-align: middle;"></span> لحن و شخصیت برند (امکان انتخاب همزمان چند لحن ترکیبی):
                                            </label>
                                            <small style="color: #7e22ce;">می‌توانید ۲ یا چند لحن را همزمان تیک بزنید (مثلاً: زیبایی + مشاوره‌ای)</small>
                                        </div>
                                        <div class="pharmacy-tone-pill-group">
                                            <label class="tone-pill active">
                                                <input type="checkbox" name="tone[]" value="medical_consultant" checked>
                                                <span>🩺 مشاوره‌ای و امین داروساز</span>
                                            </label>
                                            <label class="tone-pill">
                                                <input type="checkbox" name="tone[]" value="beauty_aesthetic">
                                                <span>✨ زیبایی، لوکس و مراقبت پوستی</span>
                                            </label>
                                            <label class="tone-pill">
                                                <input type="checkbox" name="tone[]" value="commercial_buyer">
                                                <span>🛒 راهنمای خرید و ترغیب‌کننده (CRO)</span>
                                            </label>
                                            <label class="tone-pill">
                                                <input type="checkbox" name="tone[]" value="clinical_evidence">
                                                <span>🔬 بالینی و شواهد‌محور</span>
                                            </label>
                                            <label class="tone-pill">
                                                <input type="checkbox" name="tone[]" value="athletic_performance">
                                                <span>⚡ ورزشی و بدنسازی</span>
                                            </label>
                                            <label class="tone-pill">
                                                <input type="checkbox" name="tone[]" value="gentle_family">
                                                <span>👶 مادر، کودک و خانواده</span>
                                            </label>
                                            <label class="tone-pill">
                                                <input type="checkbox" name="tone[]" value="simple_public">
                                                <span>📖 ساده، روان و داستان‌محور</span>
                                            </label>
                                            <label class="tone-pill">
                                                <input type="checkbox" name="tone[]" value="authoritative_leader">
                                                <span>👑 مقتدر و مرجع دارویی کشور</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="pharmacy-col" style="flex: 1 1 50%;">
                                    <div class="pharmacy-field" style="margin-bottom: 0;">
                                        <label for="pharmacy_brand_pov" style="color: #6b21a8; font-weight: bold;">
                                            <span class="dashicons dashicons-admin-users" style="vertical-align: middle;"></span> زاویه دید راوی (POV):
                                        </label>
                                        <select id="pharmacy_brand_pov" name="brand_pov" style="width: 100%;">
                                            <option value="first_plural" selected>اول‌شخص جمع («ما در تیم علمی داروخانه دکتر دارابی...»)</option>
                                            <option value="first_singular">اول‌شخص مفرد («من به عنوان دکتر داروساز شما...»)</option>
                                            <option value="third_neutral">سوم‌شخص بی‌طرف و دانای کل</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="pharmacy-col" style="flex: 1 1 50%;">
                                    <div class="pharmacy-field" style="margin-bottom: 0;">
                                        <label for="pharmacy_reading_level" style="color: #6b21a8; font-weight: bold;">
                                            <span class="dashicons dashicons-welcome-learn-more" style="vertical-align: middle;"></span> سطح تخصص زبان محتوا:
                                        </label>
                                        <select id="pharmacy_reading_level" name="reading_level" style="width: 100%;">
                                            <option value="general" selected>عمومی و روزمره (بدون اصطلاحات پیچیده)</option>
                                            <option value="semi_specialist">نیمه‌تخصصی (ذکر نام‌های علمی با توضیح در پرانتز)</option>
                                            <option value="clinical_specialist">تخصصی بالینی و فارماکولوژی</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="pharmacy-col" style="flex: 1 1 100%; margin-top: 10px;">
                                    <div class="pharmacy-field" style="margin-bottom: 0; background: #fff; padding: 12px 14px; border-radius: 8px; border: 1px solid #f3e8ff;">
                                        <label for="pharmacy_brand_image_strategy" style="color: #6b21a8; font-weight: bold; font-size: 13.5px;">
                                            <span class="dashicons dashicons-awards" style="vertical-align: middle;"></span> استراتژی تصویر برند فیلیپ کاتلر و تمایز رقابتی (Brand Image & Value-Add):
                                        </label>
                                        <select id="pharmacy_brand_image_strategy" name="brand_image_strategy" style="width: 100%; font-weight: 500; margin-top: 6px;">
                                            <option value="beyond_product_holistic" selected>🌟 فراتر از محصول و ارزش جامع (ارائه پروتکل ۳۶۰ درجه سبک زندگی، رژیم مکمل و تمایز بالینی غیرقابل تقلید)</option>
                                            <option value="functional_safety">🛡️ تصویر عملکردی و اصالت بالینی (تاکید بر جذب حداکثری، فرمولاسیون دارویی، عدم سمیت و اصالت ۱۰۰٪ با کد رهگیری)</option>
                                            <option value="emotional_wellbeing">❤️ تصویر احساسی و آرامش‌بخش (تبدیل اضطراب بیماری یا کهولت به حس امنیت، امید واقعی، کرامت و مراقبت دلسوزانه)</option>
                                            <option value="personal_smart_choice">💎 تصویر هویت شخصی و انتخاب آگاهانه (ارتقای هویت خریدار به عنوان فردی هوشمند و متعهد به سلامت اصیل)</option>
                                            <option value="user_lifestyle_story">🏃 تصویر سناریوی کاربر واقعی (انطباق مستقیم با نیازهای روزمره شاغلان، مادران، ورزشکاران یا میانسالان)</option>
                                            <option value="social_prestige">✨ تصویر اجتماعی و جایگاه فاخر (پرستیژ انتخاب برند استاندارد اصیل جهانی و نماد سرآمدی در سلامت فردی)</option>
                                        </select>
                                        <small style="color: #7e22ce; margin-top: 4px; display: block;">چون محصولات شما در تمام داروخانه‌ها موجود است، این استراتژی محتوای شما را به تجربه‌ای فراتر از محصول و ماندگار در ذهن مخاطب تبدیل می‌کند.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Options: Keyword, Words, Audience -->
                            <div class="pharmacy-row">
                                <div class="pharmacy-col" style="flex: 1 1 40%;">
                                    <div class="pharmacy-field">
                                        <label for="pharmacy_keyword">کلمه کلیدی رنک‌مث (Focus Keyword):</label>
                                        <input type="text" id="pharmacy_keyword" name="focus_keyword" placeholder="مثال: کلاژن پلاس ال لیزین یوروویتال" class="regular-text" style="width: 100%;">
                                    </div>
                                </div>
                                <div class="pharmacy-col" style="flex: 1 1 30%;">
                                    <div class="pharmacy-field">
                                        <label for="pharmacy_words">حجم محتوا:</label>
                                        <select id="pharmacy_words" name="word_count" style="width: 100%;">
                                            <option value="800">مختصر و متمرکز (حدود ۸۰۰ کلمه - مناسب محصولات)</option>
                                            <option value="1500" selected>استاندارد و جامع (حدود ۱۵۰۰ کلمه)</option>
                                            <option value="2500">راهنمای مرجع و کامل (بیش از ۲۵۰۰ کلمه)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="pharmacy-col" style="flex: 1 1 30%;">
                                    <div class="pharmacy-field">
                                        <label for="pharmacy_audience">مخاطب هدف:</label>
                                        <select id="pharmacy_audience" name="target_audience" style="width: 100%;">
                                            <option value="general" selected>عموم مصرف‌کنندگان و بیماران</option>
                                            <option value="specialist">پزشکان، داروسازان و کادر درمان</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="pharmacy-action-bar">
                                <button type="submit" id="pharmacy-btn-generate" class="button button-primary button-hero">
                                    <span class="dashicons dashicons-superhero"></span> تولید محتوای هوشمند مطابق GEO و E-E-A-T
                                </button>
                                <span class="spinner" id="pharmacy-spinner"></span>
                            </div>
                        </form>
                    </div>

                    <!-- Result Panel -->
                    <div class="pharmacy-ai-card pharmacy-ai-result-card" id="pharmacy-result-container" style="display: none; margin-top: 25px;">
                        <div class="pharmacy-result-header" style="flex-wrap: wrap; gap: 12px;">
                            <h2>پیش‌نمایش محتوا، رنک‌مث، اسکیما و لینک‌سازی</h2>
                            <div class="pharmacy-result-actions" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <div style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 12px; display: flex; gap: 8px; align-items: center;">
                                    <strong>نحوه ذخیره:</strong>
                                    <label style="margin: 0; cursor: pointer;"><input type="radio" name="pharmacy_save_mode" value="replace" checked> جایگزینی کامل</label>
                                    <label style="margin: 0; cursor: pointer;"><input type="radio" name="pharmacy_save_mode" value="append"> افزودن به انتهای متن فعلی</label>
                                </div>
                                <button type="button" class="button button-secondary" id="btn-copy-full-content">
                                    <span class="dashicons dashicons-clipboard"></span> کپی کل متن توضیحات
                                </button>
                                <button type="button" class="button button-primary" id="pharmacy-save-draft-btn">
                                    <span class="dashicons dashicons-saved"></span> ذخیره / به‌روزرسانی مستقیم در سایت
                                </button>
                                <button type="button" class="button button-secondary" id="btn-start-new-product" style="display: inline-flex; align-items: center; gap: 4px; color: #166534; border-color: #86efac; background: #f0fdf4;">
                                    <span class="dashicons dashicons-plus-alt"></span> ثبت محصول بعدی (پاکسازی فرم)
                                </button>
                            </div>
                        </div>

                        <!-- Scorecard Box (v3.0 Feature) -->
                        <div class="pharmacy-scorecard-box" id="res_scorecard_container" style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="margin: 0; color: #0369a1; font-size: 15px;">
                                    <span class="dashicons dashicons-chart-pie" style="vertical-align: middle;"></span> ارزیابی سلامت محتوا و آمادگی هوش مصنوعی (Scorecard)
                                </h3>
                                <div id="res_score_badges" style="display: flex; gap: 8px;"></div>
                            </div>
                            <ul id="res_score_strengths" style="margin: 0; padding-right: 20px; color: #0c4a6e; font-size: 13px;"></ul>
                        </div>

                        <!-- Rank Math Fields -->
                        <div class="pharmacy-rankmath-box">
                            <div class="rankmath-box-title">
                                <span class="dashicons dashicons-chart-area"></span> فیلدهای یکپارچه با رنک‌مث (Rank Math SEO)
                            </div>
                            <div class="pharmacy-field">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label>عنوان سئو (SEO Title):</label>
                                    <button type="button" class="button button-small btn-quick-copy" data-target="#res_rank_math_title">کپی عنوان سئو</button>
                                </div>
                                <input type="text" id="res_rank_math_title" class="regular-text">
                            </div>
                            <div class="pharmacy-field">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label>توضیحات متا (Meta Description):</label>
                                    <button type="button" class="button button-small btn-quick-copy" data-target="#res_rank_math_desc">کپی توضیحات متا</button>
                                </div>
                                <textarea id="res_rank_math_desc" rows="2" class="large-text"></textarea>
                            </div>
                            <div class="pharmacy-field">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label>کلمه کلیدی کانونی (Focus Keyword):</label>
                                    <button type="button" class="button button-small btn-quick-copy" data-target="#res_rank_math_keyword">کپی کلمه کلیدی</button>
                                </div>
                                <input type="text" id="res_rank_math_keyword" class="regular-text">
                            </div>
                        </div>

                        <!-- Brand Image & Beyond-Product Differentiator Box (v3.5.0) -->
                        <div class="pharmacy-brand-differentiator-box" style="background: #fffbeb; border: 1px solid #fde68a; border-right: 5px solid #d97706; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <h3 style="margin: 0; color: #92400e; font-size: 15px;">
                                    <span class="dashicons dashicons-awards" style="vertical-align: middle;"></span> تمایز تصویر برند و ارزش فراتر از محصول (Brand Image & Value-Add):
                                </h3>
                                <button type="button" class="button button-small btn-quick-copy" data-target="#res_brand_message_text">کپی پیام برند</button>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <span style="color: #b45309; font-weight: bold; font-size: 12.5px;">پیام محوری و ماندگار برند در ذهن مخاطب:</span>
                                <div id="res_brand_message_text" style="background: #fff; padding: 10px 14px; border-radius: 6px; border: 1px dashed #f59e0b; margin-top: 4px; font-weight: 600; color: #78350f;"></div>
                            </div>
                            <div>
                                <span style="color: #b45309; font-weight: bold; font-size: 12.5px;">پروتکل هم‌افزایی و ارزش افزوده فراتر از دارو:</span>
                                <div id="res_beyond_product_text" style="background: #fff; padding: 12px 14px; border-radius: 6px; border: 1px solid #fef3c7; margin-top: 4px; line-height: 1.7; color: #451a03; font-size: 13px;"></div>
                            </div>
                        </div>

                        <div class="pharmacy-field">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <label>عنوان اصلی نوشته / محصول:</label>
                                <button type="button" class="button button-small btn-quick-copy" data-target="#res_post_title">کپی عنوان اصلی</button>
                            </div>
                            <input type="text" id="res_post_title" class="large-text" style="font-size: 1.1em; font-weight: bold;">
                        </div>

                        <!-- Product Features / Short Description Box (Important!) -->
                        <div class="pharmacy-field" id="res_features_box">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <label style="color: #0369a1; font-weight: bold;">
                                    <span class="dashicons dashicons-editor-ul" style="vertical-align: middle;"></span> معرفی ویژگی‌های محصول (توضیحات کوتاه / Short Description بر اساس AIDA/PAS):
                                </label>
                                <button type="button" class="button button-small btn-quick-copy" data-target="#res_short_description">کپی ویژگی‌ها (توضیحات کوتاه)</button>
                            </div>
                            <textarea id="res_short_description" rows="4" class="large-text" placeholder="فهرست ویژگی‌های شاخص، ترکیبات و فواید محصول..."></textarea>
                        </div>

                        <!-- Brand Image & Beyond-Product Differentiator Box (v3.5.0) -->
                        <div class="pharmacy-brand-differentiator-box" style="background: #fffbeb; border: 1px solid #fde68a; border-right: 5px solid #d97706; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <h3 style="margin: 0; color: #92400e; font-size: 15px;">
                                    <span class="dashicons dashicons-awards" style="vertical-align: middle;"></span> تمایز تصویر برند و ارزش فراتر از محصول (Brand Image & Value-Add):
                                </h3>
                                <button type="button" class="button button-small btn-quick-copy" data-target="#res_brand_message_text">کپی پیام برند</button>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <span style="color: #b45309; font-weight: bold; font-size: 12.5px;">پیام محوری و ماندگار برند در ذهن مخاطب:</span>
                                <div id="res_brand_message_text" style="background: #fff; padding: 10px 14px; border-radius: 6px; border: 1px dashed #f59e0b; margin-top: 4px; font-weight: 600; color: #78350f;"></div>
                            </div>
                            <div>
                                <span style="color: #b45309; font-weight: bold; font-size: 12.5px;">پروتکل هم‌افزایی و ارزش افزوده فراتر از دارو:</span>
                                <div id="res_beyond_product_text" style="background: #fff; padding: 12px 14px; border-radius: 6px; border: 1px solid #fef3c7; margin-top: 4px; line-height: 1.7; color: #451a03; font-size: 13px;"></div>
                            </div>
                        </div>

                        <div class="pharmacy-field">
                            <label>چکیده نوشته (Excerpt):</label>
                            <textarea id="res_post_excerpt" rows="2" class="large-text"></textarea>
                        </div>

                        <!-- Main Content / Full Product Description Box -->
                        <div class="pharmacy-field">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <label style="color: #166534; font-weight: bold;">
                                    <span class="dashicons dashicons-editor-alignright" style="vertical-align: middle;"></span> توضیحات کامل محصول / مقاله (HTML شامل جداول و نکات کلیدی):
                                </label>
                                <button type="button" class="button button-small btn-quick-copy" data-target="#res_post_content">کپی متن کامل (HTML)</button>
                            </div>
                            <div id="res_content_preview" class="pharmacy-article-body"></div>
                            <textarea id="res_post_content" style="display: none;"></textarea>
                        </div>

                        <!-- Internal Linking Silo Recommendations (v3.0 Feature) -->
                        <div class="pharmacy-internal-links-box" style="background: #fdf4ff; border: 1px solid #f5d0fe; border-radius: 8px; padding: 14px 18px; margin-top: 20px;">
                            <h3 style="margin-top: 0; color: #86198f; font-size: 15px;">
                                <span class="dashicons dashicons-networking" style="vertical-align: middle;"></span> پیشنهادات پیوند داخلی هوشمند (Internal Linking & Topic Silo):
                            </h3>
                            <div id="res_internal_links_list" style="display: grid; gap: 8px;"></div>
                        </div>

                        <!-- JSON-LD Schema Box (v3.0 Feature) -->
                        <div class="pharmacy-schema-box" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin-top: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <h3 style="margin: 0; color: #1e293b; font-size: 15px;">
                                    <span class="dashicons dashicons-code-standards" style="vertical-align: middle;"></span> کد ساختاریافته اسکیما (JSON-LD Schema Markup):
                                </h3>
                                <button type="button" class="button button-small" id="btn-copy-schema">
                                    <span class="dashicons dashicons-clipboard"></span> کپی کد اسکیما
                                </button>
                            </div>
                            <p style="font-size: 12.5px; color: #64748b; margin-top: 0;">این اسکیما به طور خودکار در دیتابیس ذخیره و در متاتگ‌های صفحه سایت تزریق می‌شود.</p>
                            <textarea id="res_schema_json" rows="6" class="large-text" dir="ltr" readonly style="font-family: monospace; font-size: 12px; background: #fff;"></textarea>
                        </div>

                        <div class="pharmacy-references-box" style="margin-top: 20px;">
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
                                <small>کشف منابع معتبر، تحلیل تداخلات، CRO و سئو پزشکی</small>
                            </div>
                        </div>

                        <!-- Chat Messages List -->
                        <div class="pharmacy-chat-messages" id="pharmacy-chat-box">
                            <div class="chat-msg bot-msg">
                                <p>سلام! من دستیار هوشمند داروساز شما هستم. می‌توانید از من بخواهید معتبرترین مراجع پزشکی (PubMed, Mayo Clinic, FDA) را برای یک دارو یا بیماری معرفی کنم، استراتژی سئو و نگارش بدهم، یا متن‌های شما را با تکنیک‌های CRO بازنویسی کنم.</p>
                            </div>
                        </div>

                        <!-- Quick Prompts Buttons -->
                        <div class="pharmacy-chat-quick-prompts">
                            <button type="button" class="quick-prompt-btn" data-prompt="معتبرترین مقالات و لینک‌های علمی مایوکلینیک و پاب‌مد برای داروی ">معرفی مراجع یک دارو</button>
                            <button type="button" class="quick-prompt-btn" data-prompt="تداخلات دارویی خطرناک و مهم این ترکیب را بررسی و منبع معتبر معرفی کن: ">بررسی تداخلات دارویی</button>
                            <button type="button" class="quick-prompt-btn" data-prompt="ایده‌های کپی‌رایتینگ و تبدیل فروش (CRO) برای صفحه محصول این مکمل: ">مشاوره تبدیل فروش (CRO)</button>
                            <button type="button" class="quick-prompt-btn" data-prompt="سرفصل‌های استاندارد پزشکی بر اساس E-E-A-T و GEO برای این موضوع بچین: ">طراحی سرفصل مقاله</button>
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

            <!-- Instant Fallback Click Handler (Guarantees zero-lag UI responsiveness) -->
            <script>
            (function() {
                function initInstantPills() {
                    var pills = document.querySelectorAll('.type-pill');
                    if (!pills || !pills.length) return;
                    pills.forEach(function(pill) {
                        pill.addEventListener('click', function(e) {
                            e.preventDefault();
                            pills.forEach(function(p) { p.classList.remove('active'); });
                            pill.classList.add('active');
                            var type = pill.getAttribute('data-type') || 'blog_post';
                            var hiddenInput = document.getElementById('pharmacy_content_type');
                            if (hiddenInput) hiddenInput.value = type;

                            var existingRow = document.getElementById('existing_product_row');
                            var wordsInput = document.getElementById('pharmacy_words');
                            var topicInput = document.getElementById('pharmacy_topic');
                            var featuresBox = document.getElementById('res_features_box');

                            if (type === 'product') {
                                if (wordsInput) wordsInput.value = '800';
                                if (topicInput) topicInput.placeholder = 'مثال: کپسول کلاژن پلاس ال لیزین یوروویتال (۶۰ عددی)';
                                if (existingRow) existingRow.style.display = 'block';
                                if (featuresBox) featuresBox.style.display = 'block';
                            } else if (type === 'category') {
                                if (wordsInput) wordsInput.value = '800';
                                if (topicInput) topicInput.placeholder = 'مثال: مکمل‌های تقویت پوست، مو و ناخن';
                                if (existingRow) existingRow.style.display = 'none';
                                if (featuresBox) featuresBox.style.display = 'none';
                            } else {
                                if (wordsInput) wordsInput.value = '1500';
                                if (topicInput) topicInput.placeholder = 'مثال: کپسول گاباپنتین؛ موارد مصرف، دوز و عوارض جانبی';
                                if (existingRow) existingRow.style.display = 'none';
                                if (featuresBox) featuresBox.style.display = 'none';
                            }
                        });
                    });
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initInstantPills);
                } else {
                    initInstantPills();
                }
            })();
            </script>
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
        $model          = get_option('pharmacy_ai_model', 'google/gemini-2.5-flash');
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
                                <p class="description">برای اوپن‌روتر: <code>google/gemini-2.5-flash</code> یا <code>google/gemini-2.5-flash-lite</code> | برای جمینای کلادفلر: <code>gemini-1.5-flash</code></p>
                            </td>
                        </tr>
                        <tr id="row_api_proxy">
                            <th scope="row"><label for="pharmacy_ai_api_proxy">آدرس پروکسی معکوس / ورکر کلادفلر (اختیاری):</label></th>
                            <td>
                                <input type="url" name="pharmacy_ai_api_proxy" id="pharmacy_ai_api_proxy" value="<?php echo esc_attr($api_proxy); ?>" class="large-text" dir="ltr" placeholder="https://your-proxy.workers.dev">
                                <p class="description">اختیاری: اگر اینترنت هاست نت‌افراز با اختلالات مقطعی (مانند خطای cURL 56 یا قطع ارتباط توسط شبکه) روبرو شود، می‌توانید آدرس ورکر کلادفلر خود را اینجا وارد کنید تا تمام درخواست‌های اوپن‌روتر یا جمینای بدون فیلترینگ و با نهایت سرعت عبور کنند.</p>
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
