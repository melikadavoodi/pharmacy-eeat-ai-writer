<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pharmacy_EEAT_AI_Service {

    public static function generate_content($params) {
        $api_provider = get_option('pharmacy_ai_provider', 'openrouter');
        $api_key      = get_option('pharmacy_ai_api_key', '');
        $model        = !empty($params['model']) ? sanitize_text_field($params['model']) : get_option('pharmacy_ai_model', 'google/gemini-2.5-flash');
        $proxy_base   = get_option('pharmacy_ai_api_proxy', '');

        if (empty($api_key)) {
            return new WP_Error('missing_key', 'کلید API در تنظیمات افزونه ثبت نشده است. لطفاً ابتدا به منوی "تنظیمات و E-E-A-T" بروید.');
        }

        $content_type    = isset($params['content_type']) ? sanitize_text_field($params['content_type']) : 'blog_post';
        $mode            = isset($params['mode']) ? sanitize_text_field($params['mode']) : 'topic';
        $topic           = isset($params['topic']) ? sanitize_text_field($params['topic']) : '';
        $source_urls     = isset($params['source_urls']) ? (array) $params['source_urls'] : array();
        $source_text     = isset($params['source_text']) ? sanitize_textarea_field($params['source_text']) : '';
        $focus_keyword   = isset($params['focus_keyword']) ? sanitize_text_field($params['focus_keyword']) : '';
        $word_count      = isset($params['word_count']) ? intval($params['word_count']) : 1500;
        $tone            = isset($params['tone']) ? sanitize_text_field($params['tone']) : 'medical_consultant';
        $brand_pov       = isset($params['brand_pov']) ? sanitize_text_field($params['brand_pov']) : 'first_plural';
        $reading_level        = isset($params['reading_level']) ? sanitize_text_field($params['reading_level']) : 'general';
        $seo_strategy         = isset($params['seo_strategy']) ? sanitize_text_field($params['seo_strategy']) : 'geo_ai_search';
        $brand_image_strategy = isset($params['brand_image_strategy']) ? sanitize_text_field($params['brand_image_strategy']) : 'beyond_product_holistic';
        $ground_truth         = isset($params['ground_truth']) ? sanitize_textarea_field($params['ground_truth']) : '';
        $product_image_b64    = isset($params['product_image_b64']) ? $params['product_image_b64'] : '';
        $target_audience      = isset($params['target_audience']) ? sanitize_text_field($params['target_audience']) : 'general';
        $temperature          = isset($params['temperature']) ? floatval($params['temperature']) : 0.7;
        if ($temperature < 0.1 || $temperature > 1.0) {
            $temperature = 0.7;
        }

        $reviewer_name  = get_option('pharmacy_ai_reviewer_name', 'دکتر داروساز');
        $reviewer_code  = get_option('pharmacy_ai_reviewer_code', '۱۲۳۴۵');
        $disclaimer_txt = get_option('pharmacy_ai_disclaimer', 'اطلاعات ارائه‌شده در این مقاله صرفاً جنبه آموزشی و اطلاع‌رسانی دارد و نباید جایگزین توصیه، تشخیص یا درمان پزشک متخصص و داروساز شود. قبل از مصرف یا تغییر دوز هرگونه دارو حتماً با پزشک یا داروساز مشورت نمایید.');

        // Multi-Source fetching
        $fetched_sources_text = '';
        $clean_urls = array();
        if ($mode === 'multi_url' && !empty($source_urls)) {
            foreach ($source_urls as $idx => $raw_url) {
                $raw_url = esc_url_raw(trim($raw_url));
                if (!empty($raw_url)) {
                    $clean_urls[] = $raw_url;
                    $fetched = self::fetch_url_content($raw_url);
                    if (!is_wp_error($fetched) && !empty($fetched)) {
                        $fetched_sources_text .= "\n--- منبع شماره " . ($idx + 1) . " ({$raw_url}) ---\n" . $fetched . "\n";
                    }
                }
            }
        } elseif ($mode === 'translate' && !empty($source_text)) {
            $fetched_sources_text = $source_text;
        }

        $system_instruction = self::build_system_prompt($content_type, $tone, $brand_pov, $reading_level, $seo_strategy, $brand_image_strategy, $ground_truth, $product_image_b64, $reviewer_name, $reviewer_code, $disclaimer_txt);
        $user_prompt        = self::build_user_prompt($content_type, $mode, $topic, $focus_keyword, $word_count, $target_audience, $tone, $seo_strategy, $brand_image_strategy, $ground_truth, $product_image_b64, $fetched_sources_text, $clean_urls);

        $max_tokens = 4200;
        if ($word_count <= 600) {
            $max_tokens = 3200;
        } elseif ($word_count <= 1000) {
            $max_tokens = 4200;
        } elseif ($word_count <= 1600) {
            $max_tokens = 5000;
        } else {
            $max_tokens = 6000;
        }

        if ($api_provider === 'openrouter') {
            return self::call_openrouter($api_key, $model, $system_instruction, $user_prompt, $proxy_base, $product_image_b64, $temperature, $max_tokens);
        } elseif ($api_provider === 'gemini') {
            return self::call_gemini($api_key, $model, $system_instruction, $user_prompt, $proxy_base, $product_image_b64, $temperature, $max_tokens);
        } else {
            return self::call_openai($api_key, $model, $system_instruction, $user_prompt, $proxy_base, $temperature, $max_tokens);
        }
    }

    public static function chat_message($messages) {
        $api_provider = get_option('pharmacy_ai_provider', 'openrouter');
        $api_key      = get_option('pharmacy_ai_api_key', '');
        $model        = !empty($params['model']) ? sanitize_text_field($params['model']) : get_option('pharmacy_ai_model', 'google/gemini-2.5-flash');
        $proxy_base   = get_option('pharmacy_ai_api_proxy', '');

        if (empty($api_key)) {
            return new WP_Error('missing_key', 'کلید API تنظیم نشده است.');
        }

        $system_prompt = "شما 'رنک‌بات داروساز (PharmaBot)' هستید؛ یک دستیار هوشمند و متخصص ارشد داروسازی بالینی، فارماکولوژی، کپی‌رایتینگ سلامت و سئو پزشکی مدرن مطابق با استانداردهای گوگل E-E-A-T، GEO و تکنیک‌های تبدیل فروش (CRO).
وظیفه شما:
۱. کمک به کارشناس محتوا و دکتر داروساز در کشف جدیدترین مراجع علمی بین‌المللی (PubMed, Mayo Clinic, WebMD, FDA, Drugs.com, MedlinePlus).
۲. تحلیل علمی تداخلات دارویی، منع مصرف، دوزهای مجاز و مقایسه برندهای مکمل‌های دارویی موجود در بازار ایران.
۳. پیشنهاد زاویه دید جذاب، استراتژی لینک‌سازی داخلی و پاسخ به ابهامات خریداران داروها.
۴. زبان شما فارسی روان، فاخر، با رعایت دقیق نیم‌فاصله‌ها و بدون کلمات ترجمه‌ای رباتیک است.";

        if ($api_provider === 'openrouter') {
            return self::call_openrouter_chat($api_key, $model, $system_prompt, $messages, $proxy_base);
        } elseif ($api_provider === 'gemini') {
            return self::call_gemini_chat($api_key, $model, $system_prompt, $messages, $proxy_base);
        } else {
            return self::call_openai_chat($api_key, $model, $system_prompt, $messages, $proxy_base);
        }
    }

    private static function fetch_url_content($url) {
        $response = wp_remote_get($url, array(
            'timeout'    => 25,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ));

        if (is_wp_error($response)) {
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return '';
        }

        $clean = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $body);
        $clean = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $clean);
        $clean = strip_tags($clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return mb_substr(trim($clean), 0, 10000);
    }

    private static function build_system_prompt($content_type, $tone, $brand_pov, $reading_level, $seo_strategy, $brand_image_strategy, $ground_truth, $product_image_b64, $reviewer_name, $reviewer_code, $disclaimer_txt) {
        $tones_desc = array(
            'medical_consultant'   => 'مشاوره‌ای، دلسوزانه و امین داروخانه‌ای (همانند گفت‌وگوی صمیمانه، گرم و اطمینان‌بخش دکتر داروساز پشت پیشخوان با بیمار)',
            'clinical_evidence'    => 'بسیار علمی، بالینی، آکادمیک و متکی بر کارآزمایی‌های بالینی رسمی بدون مبالغه (Evidence-Based Medicine)',
            'commercial_buyer'     => 'ترغیب‌کننده و راهنمای خرید هوشمندانه با تاکید بر ارزش خرید، اصالت کالا و تخفیف‌ها (Conversion CRO & AIDA)',
            'beauty_aesthetic'     => 'زیبایی، لوکس، لطیف، امیدبخش و درماتولوژیک (مخصوص پوست، مو، کلاژن، جوانسازی و محصولات مراقبتی)',
            'athletic_performance' => 'ورزشی، پرانرژی، انگیزشی و صریح (مخصوص مکمل‌های بدنسازی، آمینواسیدها، انرژی، توان و ریکاوری)',
            'gentle_family'        => 'بسیار ملایم، آرامش‌بخش، محتاط و ایمن (مخصوص مکمل‌های بارداری، نوزادان، کودکان و سالمندان)',
            'simple_public'        => 'بسیار ساده، عامه‌فهم، روان و داستان‌محور بدون هیچ اصطلاح گیج‌کننده پزشکی',
            'authoritative_leader' => 'مقتدر، قاطع، پرچمدار و پیشرو در صنعت داروسازی ایران با اعتمادبه‌نفس حداکثری'
        );

        // Support for Multi-Tone / Hybrid Brand Voice
        $selected_tones = is_array($tone) ? $tone : array_filter(array_map('trim', explode(',', (string)$tone)));
        if (empty($selected_tones)) {
            $selected_tones = array('medical_consultant');
        }

        $tones_text_arr = array();
        foreach ($selected_tones as $t_key) {
            if (isset($tones_desc[$t_key])) {
                $tones_text_arr[] = $tones_desc[$t_key];
            }
        }
        if (count($tones_text_arr) > 1) {
            $selected_tone_desc = "لحن ترکیبی و چندگانه برند (Hybrid Brand Voice): لطفاً متن را با تلفیقی هماهنگ، طبیعی و هنرمندانه از این لحن‌ها بنویسید به طوری که ویژگی‌های هر دو لحن در متن حس شود:\n- " . implode("\n- همچنین: ", $tones_text_arr);
        } else {
            $selected_tone_desc = !empty($tones_text_arr) ? $tones_text_arr[0] : $tones_desc['medical_consultant'];
        }

        // Brand Point of View (POV)
        $pov_desc = array(
            'first_plural'   => 'اول‌شخص جمع: از طرف تیم علمی داروخانه دکتر دارابی صحبت کنید (مثال: «ما در داروخانه آنلاین دکتر دارابی همواره توصیه می‌کنیم...» یا «تیم داروسازان ما...»)',
            'first_singular' => 'اول‌شخص مفرد: با لحن مستقیم دکتر داروساز به عنوان مشاور اختصاصی بیمار بنویسید (مثال: «من به عنوان دکتر داروساز به شما پیشنهاد می‌کنم...»)',
            'third_neutral'  => 'سوم‌شخص بی‌طرف و دانای کل: لحن تحلیلی، بی‌طرف و علمی بدون ضمیر شخصی (مثال: «بررسی‌ها نشان می‌دهد این مکمل...»)'
        );
        $selected_pov_desc = $pov_desc[$brand_pov] ?? $pov_desc['first_plural'];

        // Reading / Expertise Level
        $level_desc = array(
            'general'             => 'عمومی و کاربردی: تمام مفاهیم علمی را به ساده‌ترین زبان ممکن و بدون نیاز به دانش قبلی توضیح دهید.',
            'semi_specialist'     => 'نیمه‌تخصصی: نام ترکیبات و مکانیزم‌های دارویی را با توضیح ساده فارسی در پرانتز بیاورید تا هم برای افراد آگاه و هم عموم مفید باشد.',
            'clinical_specialist' => 'تخصصی بالینی و فارماکولوژیک: متنی عمیق با اصطلاحات دقیق پزشکی مناسب کادر درمان و بیماران پیگیر.'
        );
        $selected_level_desc = $level_desc[$reading_level] ?? $level_desc['general'];

        // Kotler Brand Image Matrix & Uncopyable Differentiation (v3.5.0)
        $brand_image_map = array(
            'beyond_product_holistic' => "دکترین فراتر از محصول و ارزش جامع (Beyond the Product - ارزش غیرقابل تقلید):
- چون این محصول در تمام داروخانه‌های کشور موجود است، بازتولید بروشور بی‌ارزش است. محتوای شما باید یک 'پروتکل ۳۶۰ درجه سبک زندگی و سلامت' باشد که هیچ رقیبی قادر به کپی‌برداری از آن نباشد.
- الزامات:
  ۱. بیولوژی و سینرژی جذب (Nutritional Synergy & Chronobiology): چه مواد غذایی جذب این دارو را چند برابر می‌کنند و چه چیزهایی اثر آن را خنثی می‌کنند؟ بهترین ساعت بیولوژیک مصرف در شبانه‌روز کدام است؟
  ۲. عادات تغذیه‌ای و تمرینات مکمل همراه برای دستیابی به حداکثر بازدهی بالینی.
  ۳. پایش بالینی و جدول زمانی نتایج واقعی (Timeline of Results): در هفته اول، ماه اول و پایان دوره مصرف دقیقاً چه انتظاراتی باید داشت.
  ۴. کادر تمایز بالینی: درون 'content' حتماً یک کادر برجسته با کلاس 'pharmacy-brand-differentiator' قرار دهید با عنوان «پروتکل جذب حداکثری و ارزش اختصاصی داروخانه دکتر دارابی».",

            'functional_safety' => "تصویر عملکردی و اصالت بالینی (Functional & Clinical Trust):
- تمرکز بر رفع دغدغه اصالت دارو در بازار ایران: اصالت ۱۰۰٪ کالا، شرایط استاندارد نگهداری در دمای کنترل‌شده انبار دارویی، برچسب رهگیری و کنترل اصالت غذا و دارو (TTAC).
- تشریح دقیق فارماکوکینتیک، عدم ایجاد سمیت دارویی، تضمین عاری بودن از ناخالصی و تطبیق با استانداردهای دارونامه (USP/BP).
- درون 'content' یک کادر 'pharmacy-brand-differentiator' با عنوان «ضمانت اصالت و سلامت بیولوژیک داروخانه دکتر دارابی» درج شود.",

            'emotional_wellbeing' => "تصویر احساسی و آرامش‌بخش (Emotional Wellbeing & Empathy):
- بر پایه اصل اسکات کوک: «یک برند آن چیزی نیست که ما به مشتری می‌گوییم؛ بلکه آن چیزی است که مشتریان به یکدیگر می‌گویند.»
- تبدیل حس اضطراب بیماری یا پیری به احساس امنیت، آرامش، کرامت انسانی، امید واقعی و مراقبت دلسوزانه.
- مخاطب باید حس کند مشاور داروساز امین و مهربانی در کنار او نشسته و دغدغه‌های روحی‌اش را درک می‌کند.
- درون 'content' یک کادر 'pharmacy-brand-differentiator' با عنوان «پیام امید و مراقبت تیم داروسازی دکتر دارابی» درج شود.",

            'personal_smart_choice' => "تصویر هویت شخصی و سبک زندگی آگاهانه (Personal Identity & Smart Choice):
- ارتقای هویت فردی: انتخاب این محصول از داروخانه دکتر دارابی نشانه درایت، سواد سلامت و خودمراقبتی هوشمندانه خریدار است.
- درون 'content' یک کادر 'pharmacy-brand-differentiator' با عنوان «انتخاب هوشمندانه برای سلامت اصیل» درج شود.",

            'user_lifestyle_story' => "تصویر سناریوی کاربر واقعی (User Persona & Real-Life Context):
- ترسیم داستان روزمره افرادی که با این چالش مواجه‌اند (مادر پرمشغله، کارمند دچار خستگی مزمن، ورزشکار، سالمند باانگیزه).
- نشان دادن اینکه این محصول چگونه گلوگاه‌های زندگی روزمره را حل می‌کند.
- درون 'content' یک کادر 'pharmacy-brand-differentiator' با عنوان «تجربه واقعی بهبود کیفیت زندگی» درج شود.",

            'social_prestige' => "تصویر اجتماعی و نمادین (Social Prestige & Health Leadership):
- ارتقای جایگاه اجتماعی و پرستیژ فرد در انتخاب استاندارد اصیل جهانی GMP اروپا و نماد سرآمدی و رهبری در سلامت فردی.
- درون 'content' یک کادر 'pharmacy-brand-differentiator' با عنوان «استاندارد برتر و جایگاه سلامت پایدار» درج شود."
        );
        $selected_brand_image_instruction = $brand_image_map[$brand_image_strategy] ?? $brand_image_map['beyond_product_holistic'];

        // SEO & Copywriting Strategy Directives
        $strategy_instructions = '';
        if ($seo_strategy === 'geo_ai_search') {
            $strategy_instructions = "استراتژی ویژه سئو: بهینه‌سازی موتورهای هوش مصنوعی (GEO - Generative Engine Optimization & AI Overviews).
- شروع مستقیم پاراگراف اول با یک تعریف صریح و جامع از موجودیت (Entity Definition) به گونه‌ای که پاسخ خلاصه گوگل و پرپلکسیتی دقیقاً از این پاراگراف استخراج شود.
- ایجاد یک کادر جمع‌بندی نکات کلیدی (Key Takeaways) در ابتدای متن با کلاس 'pharmacy-takeaways-box'.
- طراحی جدول مقایسه‌ای شفاف (HTML Table) و لیست‌های نشانه‌دار فشرده برای موتورهای مولد پاسخ.
- گنجاندن حداقل ۳ سوال و پاسخ رایج (FAQ) در انتهای متن با نگارش مستقیم و صریح.";
        } elseif ($seo_strategy === 'cro_conversion') {
            $strategy_instructions = "استراتژی ویژه کپی‌رایتینگ: افزایش نرخ تبدیل و روانشناسی فروش سلامت (CRO & Marketing Copywriting - AIDA/PAS).
- استفاده از فرمول مشکل > تشدید درد بیمار > راه‌حل علمی (PAS Formula).
- تبدیل ویژگی‌های تخصصی و شیمیایی ترکیبات به منافع ملموس روزمره بیمار (Feature-to-Benefit Transformation).
- رفع تردیدها و دغدغه‌های ذهنی خریدار دارو و مکمل (تضمین اصالت کالا، تاریخ انقضای معتبر، مشاوره داروساز، مجوز سازمان غذا و دارو / IRC).
- دعوت به اقدام (CTA) هوشمندانه، حرفه‌ای و اطمینان‌بخش در انتهای متن.";
        } else {
            $strategy_instructions = "استراتژی ویژه پزشکی: مقاله آسمان‌خراش بالینی و مرجعیت حداکثری E-E-A-T (Deep Clinical Authority).
- پوشش عمیق فارماکوکینتیک، مکانیسم عمل سلولی، گروه‌های پرخطر (بارداری، شیردهی، سالمندان، اطفال)، تداخلات فارماکولوژیک و فارماکودینامیک.
- بررسی دوزهای درمانی و علائم مسمومیت دارویی.
- استناد دقیق به رفرنس‌های تراز اول دنیا با ساختار آکادمیک و غنی.";
        }

        // Content Type Instructions
        $type_instructions = '';
        if ($content_type === 'product') {
            $type_instructions = "نوع سند: صفحه محصول دارویی/مکمل فروشگاه ووکامرس (WooCommerce Product Page) - حجم بهینه: حدود ۸۰۰ تا ۹۰۰ کلمه خوش‌خوان، متمرکز، بدون پرگویی، با نرخ تبدیل بالا (CRO) و سئوی E-E-A-T.

معماری ساختار محتوایی الزامی صفحه محصول:
۱. مقدمه گیرا، کنجکاوی‌برانگیز و نیازمحور (Hook & Core Need):
   - پاراگراف آغازین باید مستقیماً انگشت روی دغدغه یا نیاز واقعی خریدار بگذارد (مثلاً دغدغه شادابی پوست و خطوط ریز، یا دردهای مفصلی یا ریزش مو) و حس کنجکاوی را برانگیزد تا کاربر ترغیب به خواندن ادامه متن شود.
   - معرفی صریح و ۲ تا ۳ جمله‌ای محصول به عنوان راه‌حل بالینی بدون سلام و احوالپرسی یا کلیشه‌های ماشینی.

۲. عناوین H2 و H3 الزامی (کاملاً پویا و متناسب با اندیکاسیون‌های واقعی همان محصول):
   - <h2>[نام دقیق محصول] چیست؟</h2> (معرفی فشرده فرم دارویی، برند، کشور سازنده و استاندارد تولید).
   - <h2>ترکیبات و دوز مواد مؤثره</h2> (جدول یا لیست شفاف مقادیر واقعی ترکیبات؛ بر اساس شواهد ورودی یا بروشور).
   - <h2>[نام محصول] برای چیست؟ (کاربردهای بالینی)</h2>:
     * زیرعنوان‌های H3 باید کاملاً پویا و متناسب با کاربردهای درمانی و زیبایی واقعی همان محصول تدوین شوند (مثلاً برای کلاژن: الاستیسیته، چروک و غضروف‌ها؛ برای محصول مو: تقویت فولیکول و ضخامت تار مو؛ برای مولتی‌ویتامین: انرژی و ایمنی).
   - <h2>طریقه و بهترین زمان مصرف</h2> (راهنمای اجرایی سریع: نحوه مصرف با آب، همراه غذا، و بهترین ساعت بیولوژیک شبانه‌روز).
   - <h2>طول دوره مصرف و انتظارات واقعی</h2> (دیدگاه صادقانه بالینی: اثربخشی تدریجی است نه معجزه یک‌شبه؛ ارزیابی منصفانه دوز مانند کافی بودن یا نبودن دوز بر اساس نیاز فرد).
   - <h2>عوارض و موارد منع مصرف</h2> (نکات ایمنی کوتاه و صریح: بارداری، شیردهی، افراد با شرایط خاص و تداخلات).
   - <h2>راهنمای خرید، قیمت و اصالت از داروخانه دکتر دارابی</h2> (پاسخ به ۳ سوال کلیدی خریدار: نحوه تشخیص محصول اصل با برچسب کنترل اصالت TTAC سازمان غذا و دارو و پیامک به ۲۰۰۰۸۸۲۲، شفافیت تعرفه مصوب رسمی، و مزایای واقعی داروخانه دکتر دارابی شامل تضمین انبارش دارویی GSP در دمای کنترل‌شده و مشاوره تخصصی رایگان تیم داروسازان).
   - <h2>سؤالات متداول خریداران درباره [نام محصول]</h2> (۳ الی ۴ پرسش و پاسخ کوتاه، کاربردی و صادقانه).

۳. سیستم پیوند هوشمند خوشه‌ای (Hub & Spoke Internal Linking):
   - برای اینکه صفحه محصول خلوت بماند و مشتری سردرگم نشود، در ۲ یا ۳ نقطه کلیدی متن (مثلاً در فواید یا ترکیبات)، یک ارجاع طبیعی به مطالعه بیشتر در مقاله بلاگ یا مشاهده سایر محصولات در دسته‌بندی قرار دهید.
   - مثال: «برای بررسی عمیق‌تر سازوکار زیستی، می‌توانید مقاله [بررسی تخصصی ترکیبات مکمل] را مطالعه فرمایید یا به دسته [مکمل‌های تخصصی] در داروخانه مراجعه کنید.»
   - این پیشنهادات پیوند باید حتماً در آرایه 'internal_links' در شیء JSON خروجی نیز با انکر تکست، موضوع هدف و دلیل سئو آورده شوند.";
        } elseif ($content_type === 'category') {
            $type_instructions = "نوع سند: توضیحات و لندینگ دسته‌بندی فروشگاه داروخانه (Category / Taxonomy Page).
شامل راهنمای خرید تخصصی، مقایسه انواع مکمل‌ها بر اساس نیاز کاربر، پاسخ به سوالات تکرارشونده و معیارهای انتخاب بهترین برند.";
        } else {
            $type_instructions = "نوع سند: مقاله جامع و مرجع وبلاگ پزشکی و داروسازی (Educational Blog Post).
شامل مقدمه گیرا، کاربردها و فواید درمانی، نحوه عملکرد در بدن، دوز و نحوه مصرف، عوارض جانبی تفکیک‌شده (شایع/نادر)، تداخلات با داروها و غذاها، احتیاط‌های ویژه، پرسش‌های متداول (FAQ) و نتیجه‌گیری.";
        }

        // Clinical Ground Truth & Zero-Hallucination Directive (v3.7.0)
        $ground_truth_rule = '';
        if (!empty($ground_truth) || !empty($product_image_b64)) {
            $ground_truth_rule = "
*** الزامات حیاتی و قطعی حقیقت بالینی محصول (Clinical Ground Truth & Zero-Hallucination) ***:
کاربر اطلاعات موثق و قطعی محصول (دوزها، ترکیبات، شکل دارویی مانند ویال خوراکی، ساشه، قرص یا کپسول، و نحوه مصرف) را به صورت متن یا تصویر بارگذاری کرده است.
قوانین غیرقابل نقض:
۱. شما موظفید ۱۰۰٪ و بدون کوچک‌ترین دخل و تصرف، تمام اطلاعات دوزبندی، مقادیر میلی‌گرم/میکروگرم، ترکیبات فعال و شکل دارو را دقیقاً و عیناً مطابق با این مشخصات تنظیم نمایید.
۲. هذیان و حدس زدن دوزها اکیداً ممنوع است. هیچ عدد یا ادعایی که در مشخصات قطعی یا تصویر نیامده را تولید نکنید.
۳. در جدول مقادیر و ترکیبات (Supplement Facts) درون 'content'، مقادیر دقیق را دقیقاً طبق این ورودی‌ها بنویسید.
";
        }

        return "شما یک متخصص ارشد فارماکولوژی بالینی، نویسنده مقالات پزشکی تراز اول، و مشاور سئو پزشکی (Medical SEO & GEO Specialist) مطابق دستورالعمل‌های رسمی گوگل رنک‌مث (Rank Math) و فاکتورهای اعتماد E-E-A-T هستید.

{$ground_truth_rule}

{$type_instructions}

{$strategy_instructions}

لحن و هویت برند (Brand Voice): {$selected_tone_desc}.
زاویه دید راوی (Point of View): {$selected_pov_desc}.
سطح تخصصی زبان (Language Level): {$selected_level_desc}.

استراتژی ساخت تصویر برند و ارزش فراتر از محصول (Kotler Brand Image & Differentiation):
{$selected_brand_image_instruction}

قوانین حیاتی نگارش کاملاً انسانی، طبیعی، خلاقانه و بدون رد پای هوش مصنوعی (Human-Like & Organic Writing):
۱. ممنوعیت مطلق کلمات کلیشه‌ای، اغراق‌آمیز و تبلیغاتی هوش مصنوعی (AI Cliché Blacklist):
   - استفاده از واژه‌های پرطمطراق و زرد هوش مصنوعی در تمام فیلدها (عنوان، متن، توضیحات کوتاه، سوالات متداول) مطلقاً ممنوع است:
     «فوق‌العاده»، «ایده‌آل»، «بی‌نظیر»، «معجزه‌آسا»، «شگفت‌انگیز»، «انقلابی»، «چشمگیر»، «منحصربه‌فرد»، «خیره‌کننده»، «جادویی»، «شگرف».
   - استفاده از قیدهای تاکیدی تحمیلی و تکراری هوش مصنوعی اکیداً ممنوع است:
     «حتماً»، «دقیقاً»، «مسلماً»، «بی‌شک»، «بدیهی است که»، «همان‌طور که می‌دانید»، «لازم به ذکر است»، «شایان ذکر است»، «در واقع».
   - انسان متخصص و پزشک داروساز با طمأنینه، استدلال و شواهد صحبت می‌کند نه با قسم خوردن و ادعاهای غلوآمیز.
۲. آغاز زنده و طبیعی متن (Natural Opening):
   - شروع‌های قالبی و ماشینی مانند «در دنیای پرشتاب امروز...»، «امروزه یکی از دغدغه‌های افراد...»، «پوست یکی از مهم‌ترین ارگان‌های بدن است...» ممنوع است.
   - شروع باید دقیقاً شبیه گفت‌وگوی یک داروساز فهیم و باتجربه با مراجع داروخانه باشد: مستقیم رفتن سراغ صورت‌مسئله یا دغدغه واقعی فرد، بیان مکانیسم بیولوژیک بدون حاشیه، یا یک زاویه دید نوآورانه.
۳. ریتم طبیعی جملات و تنوع ساختاری (Sentence Rhythm & Variety):
   - از نوشتن جملات یکنواخت با طول برابر پرهیز کنید. جملات کوتاه، صریح و شفاف را با بندهای توصیفی و تحلیلی در هم بیامیزید تا لحن نوشته پویا و گوش‌نواز باشد.
۴. صداقت بالینی و واقع‌بینی صادقانه (Honest & Balanced Tone):
   - هیچ مکمل یا دارویی معجزه یک‌شبه نمی‌کند. نویسنده انسانی با مخاطب صادق است: بگوید اثر این محصول چقدر تدریجی است، برای چه شرایطی موثر است و در چه مواردی نباید انتظار غیرواقعی داشت. این واقع‌بینی و انصاف، عمیق‌ترین اعتماد و فروش پایدار را ایجاد می‌کند.
۵. دایره واژگان غنی، اصیل و پخته فارسی:
   - از ترکیب‌های ترجمه‌ای ماشینی دوری کنید و از کلمات طبیعی فارسی استفاده کنید (به جای «این مکمل به شما کمک می‌کند تا احساس بهتری داشته باشید»، بنویسید «این مکمل کمبود ذخایر سلولی را جبران کرده و روند بازسازی بافت را تسهیل می‌کند»).

اصول ویرایشی و زبان فارسی (Persian Voice & Iranian Market Rules):
۱. تمام متن باید به زبان فارسی فاخر، شیوا، گرم و تخصصی نگارش شود.
۲. رعایت دقیق نیم‌فاصله‌ها در تمام کلمات مرکب و افعال الزامی است (مثال: «می‌شود»، «داروخانه‌ای»، «ترکیب‌ها»، «دسته‌بندی»، «به‌ویژه»).
۳. از ترجمه‌های تحت‌اللفظی و کلیشه‌های ماشینی مانند «در این مقاله به بررسی...»، «امیدواریم مفید واقع شده باشد» یا «در دنیای امروز...» مطلقاً خودداری کنید. محتوا باید بلافاصله با ارزش علمی و پاسخ به نیاز کاربر آغاز شود.
۴. قانون طلایی قالب JSON: در تمام کدهای HTML درون فیلدهای JSON، برای صفات و اتریبیوت‌ها حتماً از علامت کوتیشن تکی (') استفاده کنید مانند: <div class='pharmacy-eeat-reviewer-box'> تا شیء JSON به هیچ وجه خراب نشود.
۵. درج دو کادر رسمی پزشکی در انتهای متن:
   - کادر تایید داروساز:
     <div class='pharmacy-eeat-reviewer-box'><strong>تایید و بازبینی علمی توسط:</strong> {$reviewer_name} (شماره نظام پزشکی: {$reviewer_code})</div>
   - کادر سلب مسئولیت:
     <div class='pharmacy-eeat-disclaimer-box'><strong>سلب مسئولیت پزشکی:</strong> {$disclaimer_txt}</div>

۵. تولید بهینه‌سازی رنک‌مث:
   - 'rank_math_title': عنوان جذاب با طول زیر ۶۰ کاراکتر دارای کلمه کلیدی اصلی.
   - 'rank_math_description': متا دیسکریپشن ترغیب‌کننده و شامل Call to Action با طول ۱۳۰ تا ۱۵۵ کاراکتر.
   - 'rank_math_focus_keyword': کلمه کلیدی اصلی.

۶. استراتژی پیشرفته پیوند داخلی (Internal Linking Silo):
   - پیشنهاد ۳ تا ۵ انکرتکست و موضوع داخلی مرتبط با داروخانه (مانند مقالات مکمل‌های هم‌افزا، دسته‌بندی‌های مرتبط، یا داروهای مشابه) جهت ساخت ساختار تاپیک کلاستر.

۷. تولید اسکیما ساختاریافته معتبر (JSON-LD Schema Markup):
   - تولید اسکیما کامل بر اساس نوع محتوا (MedicalWebPage یا Product به همراه FAQPage).

۸. ارزیابی سلامت محتوا (SEO & Quality Scorecard):
   - ارائه امتیاز و توصیه‌های کوتاه در مورد E-E-A-T، آمادگی هوش مصنوعی (GEO)، و خوانایی.

۹. استناد به منابع علمی معتبر و لینک‌های کلیک‌خور (Evidence-Based Citations & Direct Links):
   - در فیلد 'references' و در انتهای محتوا، باید مراجع تراز اول پزشکی (مانند مقالات PubMed/NCBI با ذکر PMID، مونوگراف‌های Mayo Clinic، راهنماهای رسمی FDA، WebMD یا Drugs.com) با عنوان دقیق و لینک واقعی اینترنتی درج شوند.
   - هر مرجع در آرایه 'references' باید به صورت تگ لینک HTML کلیک‌خور باشد تا پزشک و بیمار بتوانند مستقیماً روی آن کلیک کرده و منبع اصلی را بررسی کنند:
     مثال: <code>&lt;a href=\"https://pubmed.ncbi.nlm.nih.gov/...\" target=\"_blank\" rel=\"noopener noreferrer\"&gt;PubMed: عنوان پژوهش بالینی یا مقاله&lt;/a&gt;</code>

فرمت خروجی الزامی:
شما باید منحصراً و بدون کوچک‌ترین متن یا عبارت اضافی قبل و بعد، یک شیء JSON استاندارد و معتبر بازگردانید:
{
  \"title\": \"عنوان کامل و جذاب مقاله یا محصول\",
  \"short_description\": \"فهرست گلوله‌ای ویژگی‌های شاخص و مزایای برجسته برای باکس ویژگی‌های ووکامرس (HTML ul/li)\",
  \"brand_message\": \"پیام محوری و ماندگار برند داروخانه دکتر دارابی متناسب با استراتژی تصویر برند انتخابی\",
  \"beyond_product_value\": \"خلاصه نکات طلایی پروتکل سبک زندگی، هم‌افزایی جذب و ارزش افزوده فراتر از دارو\",
  \"rank_math_title\": \"عنوان سئو رنک‌مث (زیر ۶۰ کاراکتر)\",
  \"rank_math_description\": \"توضیحات متای رنک‌مث (۱۳۰ تا ۱۵۵ کاراکتر)\",
  \"rank_math_focus_keyword\": \"کلمه کلیدی کانونی\",
  \"excerpt\": \"خلاصه و چکیده در ۲-۳ جمله جذاب\",
  \"content\": \"متن کامل با تگ‌های معنایی HTML (h2, h3, p, table, ul, blockquote)، کادر نکات کلیدی و کادرهای E-E-A-T\",
  \"references\": [
    \"<a href=\\\"https://pubmed.ncbi.nlm.nih.gov/...\\\" target=\\\"_blank\\\" rel=\\\"noopener\\\">PubMed: عنوان مقاله بالینی</a>\",
    \"<a href=\\\"https://www.mayoclinic.org/drugs-supplements/...\\\" target=\\\"_blank\\\" rel=\\\"noopener\\\">Mayo Clinic: مونوگراف علمی و دوزبندی</a>\"
  ],
  \"faq_items\": [
    {\"question\": \"سوال پرکاربرد ۱؟\", \"answer\": \"پاسخ علمی و صریح...\"},
    {\"question\": \"سوال ۲؟\", \"answer\": \"پاسخ صریح...\"}
  ],
  \"internal_links\": [
    {\"anchor\": \"انکر تکست پیشنهادی ۱\", \"target_topic\": \"صفحه یا محصول هدف پیشنهادی\", \"reason\": \"علت ارتباط موضوعی\"},
    {\"anchor\": \"انکر تکست پیشنهادی ۲\", \"target_topic\": \"صفحه هدف ۲\", \"reason\": \"علت ارتباط\"},
    {\"anchor\": \"انکر تکست پیشنهادی ۳\", \"target_topic\": \"صفحه هدف ۳\", \"reason\": \"علت ارتباط\"}
  ],
  \"schema_json\": {
    \"@context\": \"https://schema.org\",
    \"@type\": \"MedicalWebPage\",
    \"name\": \"عنوان صفحه\",
    \"description\": \"توضیح کوتاه\"
  },
  \"scorecard\": {
    \"eeat_score\": 95,
    \"geo_readiness\": \"بسیار بالا\",
    \"readability\": \"عالی\",
    \"key_strengths\": [\"تعریف صریح در پاراگراف لید\", \"شامل جدول دوزبندی و منع مصرف\", \"تاییدیه داروساز ناظر\"]
  }
}";
    }

    private static function build_user_prompt($content_type, $mode, $topic, $focus_keyword, $word_count, $target_audience, $tone, $seo_strategy, $brand_image_strategy, $ground_truth, $product_image_b64, $sources_text, $clean_urls) {
        $prompt = "لطفاً با توجه به استراتژی‌های مدرن تعیین‌شده، محتوای تخصصی زیر را تدوین کرده و در قالب شیء JSON خواسته شده تحویل دهید:\n\n";
        $prompt .= "- نوع سند: " . $content_type . "\n";
        $prompt .= "- موضوع / نام دارو یا محصول: " . ($topic ? $topic : 'بر اساس منابع ورودی') . "\n";
        if ($focus_keyword) {
            $prompt .= "- کلمه کلیدی کانونی (Focus Keyword): " . $focus_keyword . "\n";
        }
        $prompt .= "- استراتژی محتوا: " . $seo_strategy . "\n";
        $prompt .= "- استراتژی تصویر برند و تمایز رقابتی: " . $brand_image_strategy . "\n";
        $prompt .= "- حجم تقریبی متن: " . $word_count . " کلمه\n";
        $prompt .= "- مخاطب هدف: " . $target_audience . "\n";

        if (!empty($ground_truth)) {
            $prompt .= "\n\n*** مشخصات بالینی قطعی و واقعی محصول (دوز دقیق، ترکیبات و شکل دارویی - منع مطلق هذیان) ***:\n" . $ground_truth . "\n(تمام دوزها، مقادیر میلی‌گرم، ترکیبات و نحوه مصرف باید ۱۰۰٪ منطبق با این متن باشند.)\n";
        }
        if (!empty($product_image_b64)) {
            $prompt .= "\n\n*** تصویر بروشور / جعبه محصول ضمیمه شده است ***:\nلطفاً تمام اطلاعات دوزبندی، جدول ترکیبات (Supplement Facts)، فرم دارویی (ویال/کپسول) و دستور مصرف را با دقت بینایی (Multimodal Vision) مستقیماً از روی تصویر ضمیمه‌شده خوانده و مو به مو در جدول و متن درج نمایید.\n";
        }

        if ($mode === 'multi_url' || $mode === 'translate') {
            $prompt .= "\n- ترکیب چندمنبعی (Multi-Source Synthesis & Fact-Checking): لطفاً داده‌های موجود در مراجع خارجی زیر را با دانش روز داروسازی تلفیق، اعتبارسنجی و به زبان فارسی فاخر و بومی تدوین نمایید:\n";
            if (!empty($clean_urls)) {
                $prompt .= "آدرس منابع ورودی:\n" . implode("\n", $clean_urls) . "\n";
            }
            $prompt .= "متن خام مراجع:\n" . $sources_text . "\n";
        } else {
            $prompt .= "\n- دستور تالیف و پژوهش بالینی: از معتبرترین پایگاه‌های شواهد پزشکی (PubMed, Mayo Clinic, FDA, Drugs.com) برای نگارش جامع و مستند استفاده شود.\n";
        }

        return $prompt;
    }

    private static function call_openrouter($api_key, $model, $system_instruction, $user_prompt, $proxy_base = '', $product_image_b64 = '', $temperature = 0.7, $max_tokens = 3500) {
        $endpoint = !empty($proxy_base) ? rtrim($proxy_base, '/') . '/chat/completions' : 'https://openrouter.ai/api/v1/chat/completions';

        $user_content = $user_prompt;
        if (!empty($product_image_b64)) {
            $user_content = array(
                array(
                    'type' => 'text',
                    'text' => $user_prompt,
                ),
                array(
                    'type'      => 'image_url',
                    'image_url' => array(
                        'url' => $product_image_b64,
                    ),
                ),
            );
        }

        $body_data = array(
            'model'    => $model,
            'messages' => array(
                array('role' => 'system', 'content' => $system_instruction),
                array('role' => 'user', 'content' => $user_content),
            ),
            'response_format' => array('type' => 'json_object'),
            'temperature'     => floatval($temperature),
            'max_tokens'      => intval($max_tokens),
        );

        $max_retries = 3;
        $last_error  = '';

        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            $response = wp_remote_post($endpoint, array(
                'headers'     => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                    'HTTP-Referer'  => 'https://drdarabipharmacy.com',
                    'X-Title'       => 'Dr Darabi Pharmacy AI Writer Pro',
                    'Connection'    => 'close',
                    'Accept'        => 'application/json',
                ),
                'body'        => wp_json_encode($body_data),
                'timeout'     => 120,
                'httpversion' => '1.1',
                'sslverify'   => false,
            ));

            if (is_wp_error($response)) {
                $last_error = $response->get_error_message();
                if ($attempt < $max_retries) {
                    sleep(2 * $attempt);
                    continue;
                }
                return new WP_Error('api_error', 'خطا در ارتباط با اوپن‌روتر پس از ' . $max_retries . ' بار تلاش: ' . $last_error . ' (نکته: در صورت تکرار اختلال اینترنت هاست، می‌توانید در منوی تنظیمات افزونه از آدرس پروکسی ورکر کلادفلر استفاده کنید).');
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if ($code !== 200) {
                $err_msg = isset($data['error']['message']) ? $data['error']['message'] : "کد وضعیت HTTP: {$code}";

                // Auto-recover if OpenRouter complains about affordable tokens vs requested max_tokens
                if (preg_match('/can only afford (\d+)/i', $err_msg, $matches)) {
                    $affordable = intval($matches[1]);
                    if ($affordable >= 1000 && $body_data['max_tokens'] > $affordable) {
                        $body_data['max_tokens'] = max(1000, $affordable - 100);
                        sleep(1);
                        continue;
                    }
                }

                return new WP_Error('openrouter_error', 'خطای اوپن‌روتر: ' . $err_msg);
            }

            $raw_text = $data['choices'][0]['message']['content'] ?? '';
            return self::parse_json_result($raw_text);
        }

        return new WP_Error('api_error', 'خطا در ارتباط با اوپن‌روتر: ' . $last_error);
    }

    private static function call_openrouter_chat($api_key, $model, $system_prompt, $messages, $proxy_base = '') {
        $endpoint = !empty($proxy_base) ? rtrim($proxy_base, '/') . '/chat/completions' : 'https://openrouter.ai/api/v1/chat/completions';

        $formatted = array(array('role' => 'system', 'content' => $system_prompt));
        foreach ($messages as $msg) {
            $formatted[] = array(
                'role'    => ($msg['role'] === 'user') ? 'user' : 'assistant',
                'content' => sanitize_text_field($msg['content'])
            );
        }

        $body_data = array(
            'model'       => $model,
            'messages'    => $formatted,
            'temperature' => 0.6,
            'max_tokens'  => 2000,
        );

        $max_retries = 3;
        $last_error  = '';

        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            $response = wp_remote_post($endpoint, array(
                'headers'     => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                    'HTTP-Referer'  => 'https://drdarabipharmacy.com',
                    'X-Title'       => 'Dr Darabi Pharmacy AI Chat',
                    'Connection'    => 'close',
                    'Accept'        => 'application/json',
                ),
                'body'        => wp_json_encode($body_data),
                'timeout'     => 60,
                'httpversion' => '1.1',
                'sslverify'   => false,
            ));

            if (is_wp_error($response)) {
                $last_error = $response->get_error_message();
                if ($attempt < $max_retries) {
                    sleep(2);
                    continue;
                }
                return new WP_Error('api_error', 'خطا در چت پس از چند تلاش: ' . $last_error);
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['error'])) {
                return new WP_Error('openrouter_error', $data['error']['message'] ?? 'خطای ناشناخته');
            }

            $reply = $data['choices'][0]['message']['content'] ?? '';
            return array('reply' => $reply);
        }

        return new WP_Error('api_error', 'خطا در ارتباط با اوپن‌روتر: ' . $last_error);
    }

    private static function call_gemini($api_key, $model, $system_instruction, $user_prompt, $proxy_base = '', $product_image_b64 = '', $temperature = 0.7, $max_tokens = 4000) {
        $base = !empty($proxy_base) ? rtrim($proxy_base, '/') : 'https://generativelanguage.googleapis.com';
        $endpoint = "{$base}/v1beta/models/{$model}:generateContent?key={$api_key}";

        $safety_settings = array(
            array('category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'),
            array('category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'),
            array('category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'),
            array('category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'),
        );

        $user_parts = array(
            array('text' => $user_prompt)
        );

        if (!empty($product_image_b64)) {
            if (preg_match('/^data:([^;]+);base64,(.+)$/', $product_image_b64, $matches)) {
                $user_parts[] = array(
                    'inline_data' => array(
                        'mime_type' => $matches[1],
                        'data'      => $matches[2]
                    )
                );
            }
        }

        $body_data = array(
            'system_instruction' => array(
                'parts' => array(
                    array('text' => $system_instruction)
                )
            ),
            'contents' => array(
                array(
                    'parts' => $user_parts
                )
            ),
            'safetySettings' => $safety_settings,
            'generationConfig' => array(
                'response_mime_type' => 'application/json',
                'temperature'        => floatval($temperature),
                'maxOutputTokens'    => intval($max_tokens),
            )
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($body_data),
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'خطا در ارتباط با سرور جمینای: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code === 403) {
            return new WP_Error('gemini_403', 'خطای کد ۴۰۳: سرورهای گوگل جمینای آی‌پی ایران را مسدود کرده‌اند. لطفاً از گزینه "اوپن‌روتر" در تنظیمات استفاده کنید یا آدرس ورکر کلادفلر را در فیلد پروکسی قرار دهید.');
        }

        if ($code !== 200) {
            $err_msg = isset($data['error']['message']) ? $data['error']['message'] : "کد وضعیت HTTP: {$code}";
            return new WP_Error('gemini_error', 'خطای ای‌پی‌آی جمینای: ' . $err_msg);
        }

        $raw_text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return self::parse_json_result($raw_text);
    }

    private static function call_gemini_chat($api_key, $model, $system_prompt, $messages, $proxy_base = '') {
        $base = !empty($proxy_base) ? rtrim($proxy_base, '/') : 'https://generativelanguage.googleapis.com';
        $endpoint = "{$base}/v1beta/models/{$model}:generateContent?key={$api_key}";

        $safety_settings = array(
            array('category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'),
            array('category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'),
            array('category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'),
            array('category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'),
        );

        $contents = array();
        foreach ($messages as $msg) {
            $role = ($msg['role'] === 'user') ? 'user' : 'model';
            $contents[] = array(
                'role'  => $role,
                'parts' => array(array('text' => sanitize_text_field($msg['content'])))
            );
        }

        $body_data = array(
            'system_instruction' => array(
                'parts' => array(array('text' => $system_prompt))
            ),
            'contents' => $contents,
            'safetySettings' => $safety_settings,
            'generationConfig' => array(
                'temperature' => 0.6,
            )
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($body_data),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return new WP_Error('gemini_error', $data['error']['message'] ?? 'خطای ناشناخته');
        }

        $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return array('reply' => $reply);
    }

    private static function call_openai($api_key, $model, $system_instruction, $user_prompt, $proxy_base = '', $temperature = 0.7, $max_tokens = 4000) {
        $base = !empty($proxy_base) ? rtrim($proxy_base, '/') : 'https://api.openai.com/v1';
        $endpoint = "{$base}/chat/completions";

        $body_data = array(
            'model'    => $model,
            'messages' => array(
                array('role' => 'system', 'content' => $system_instruction),
                array('role' => 'user', 'content' => $user_prompt),
            ),
            'response_format' => array('type' => 'json_object'),
            'temperature'     => floatval($temperature),
            'max_tokens'      => intval($max_tokens),
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => wp_json_encode($body_data),
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'خطا در ارتباط با اوپن‌ای‌آی: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            $err_msg = isset($data['error']['message']) ? $data['error']['message'] : "کد وضعیت HTTP: {$code}";
            return new WP_Error('openai_error', 'خطای ای‌پی‌آی: ' . $err_msg);
        }

        $raw_text = $data['choices'][0]['message']['content'] ?? '';
        return self::parse_json_result($raw_text);
    }

    private static function parse_json_result($raw_text) {
        if (empty($raw_text)) {
            return new WP_Error('empty_result', 'پاسخی از مدل دریافت نشد.');
        }

        $clean_json = trim($raw_text);
        // Remove markdown code fences
        $clean_json = preg_replace('/^```(?:json)?\s*/i', '', $clean_json);
        $clean_json = preg_replace('/\s*```$/', '', $clean_json);

        // 1. Try finding outer braces
        $first_brace = strpos($clean_json, '{');
        $last_brace  = strrpos($clean_json, '}');
        if ($first_brace !== false && $last_brace !== false && $last_brace > $first_brace) {
            $candidate = substr($clean_json, $first_brace, $last_brace - $first_brace + 1);
            $parsed = json_decode($candidate, true);
            if ($parsed && is_array($parsed) && !empty($parsed['content'])) {
                return $parsed;
            }
        } else {
            $candidate = $clean_json;
        }

        // 2. Try standard decode
        $parsed = json_decode($clean_json, true);
        if ($parsed && is_array($parsed) && !empty($parsed['content'])) {
            return $parsed;
        }

        // 3. Try fixing unescaped line breaks and control characters inside strings
        $repaired = preg_replace('/(?<!\\\\)\r?\n/', '\n', $candidate);
        $parsed = json_decode($repaired, true);
        if ($parsed && is_array($parsed) && !empty($parsed['content'])) {
            return $parsed;
        }

        // 4. Try auto-closing cut-off JSON (if token limit ended mid-stream)
        $auto_closed = rtrim($clean_json, " \t\n\r,") . '"}';
        $parsed = json_decode($auto_closed, true);
        if ($parsed && is_array($parsed) && !empty($parsed['content'])) {
            return $parsed;
        }
        $auto_closed2 = rtrim($clean_json, " \t\n\r,") . '"} }';
        $parsed = json_decode($auto_closed2, true);
        if ($parsed && is_array($parsed) && !empty($parsed['content'])) {
            return $parsed;
        }

        // 5. Robust Regex Extraction Fallback (Ensures user NEVER gets a JSON format error!)
        $extracted = array();

        if (preg_match('/"title"\s*:\s*"([^"]+)"/u', $clean_json, $m)) {
            $extracted['title'] = trim($m[1]);
        }
        if (preg_match('/"rank_math_title"\s*:\s*"([^"]+)"/u', $clean_json, $m)) {
            $extracted['rank_math_title'] = trim($m[1]);
        }
        if (preg_match('/"rank_math_description"\s*:\s*"([^"]+)"/u', $clean_json, $m)) {
            $extracted['rank_math_description'] = trim($m[1]);
        }
        if (preg_match('/"rank_math_focus_keyword"\s*:\s*"([^"]+)"/u', $clean_json, $m)) {
            $extracted['rank_math_focus_keyword'] = trim($m[1]);
        }
        if (preg_match('/"short_description"\s*:\s*"(.*?)(?:"\s*,\s*"|"\s*,\s*\n\s*"|"\s*\})/us', $clean_json, $m)) {
            $extracted['short_description'] = stripcslashes($m[1]);
        }
        if (preg_match('/"excerpt"\s*:\s*"([^"]+)"/u', $clean_json, $m)) {
            $extracted['excerpt'] = trim($m[1]);
        }
        if (preg_match('/"brand_message"\s*:\s*"([^"]+)"/u', $clean_json, $m)) {
            $extracted['brand_message'] = trim($m[1]);
        }
        if (preg_match('/"beyond_product_value"\s*:\s*"(.*?)(?:"\s*,\s*"|"\s*,\s*\n\s*"|"\s*\})/us', $clean_json, $m)) {
            $extracted['beyond_product_value'] = stripcslashes($m[1]);
        }
        if (preg_match('/"content"\s*:\s*"(.*?)(?:"\s*,\s*"|"\s*,\s*\n\s*"|"\s*\})/us', $clean_json, $m)) {
            $extracted['content'] = stripcslashes($m[1]);
        }

        if (!empty($extracted['content'])) {
            return $extracted;
        }

        return new WP_Error('invalid_json', 'خطای تجزیه خروجی مدل. متن دریافت شده: ' . mb_substr($raw_text, 0, 150));
    }
}
