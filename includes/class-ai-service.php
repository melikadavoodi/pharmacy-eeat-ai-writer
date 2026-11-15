<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pharmacy_EEAT_AI_Service {

    public static function generate_content($params) {
        $api_provider = get_option('pharmacy_ai_provider', 'openrouter');
        $api_key      = get_option('pharmacy_ai_api_key', '');
        $model        = get_option('pharmacy_ai_model', 'google/gemini-2.5-flash');
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
        $seo_strategy    = isset($params['seo_strategy']) ? sanitize_text_field($params['seo_strategy']) : 'geo_ai_search';
        $target_audience = isset($params['target_audience']) ? sanitize_text_field($params['target_audience']) : 'general';

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

        $system_instruction = self::build_system_prompt($content_type, $tone, $seo_strategy, $reviewer_name, $reviewer_code, $disclaimer_txt);
        $user_prompt        = self::build_user_prompt($content_type, $mode, $topic, $focus_keyword, $word_count, $target_audience, $tone, $seo_strategy, $fetched_sources_text, $clean_urls);

        if ($api_provider === 'openrouter') {
            return self::call_openrouter($api_key, $model, $system_instruction, $user_prompt);
        } elseif ($api_provider === 'gemini') {
            return self::call_gemini($api_key, $model, $system_instruction, $user_prompt, $proxy_base);
        } else {
            return self::call_openai($api_key, $model, $system_instruction, $user_prompt, $proxy_base);
        }
    }

    public static function chat_message($messages) {
        $api_provider = get_option('pharmacy_ai_provider', 'openrouter');
        $api_key      = get_option('pharmacy_ai_api_key', '');
        $model        = get_option('pharmacy_ai_model', 'google/gemini-2.5-flash');
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
            return self::call_openrouter_chat($api_key, $model, $system_prompt, $messages);
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

    private static function build_system_prompt($content_type, $tone, $seo_strategy, $reviewer_name, $reviewer_code, $disclaimer_txt) {
        $tones_desc = array(
            'medical_consultant' => 'مشاوره‌ای، دلسوزانه، علمی و قابل‌اعتماد داروخانه‌ای (همانند گفت‌وگوی صمیمی و تخصصی دکتر داروساز در داروخانه)',
            'clinical_evidence'  => 'بسیار علمی، بالینی، دقیق و متکی بر کارآزمایی‌های بالینی رسمی (Evidence-Based Medicine)',
            'commercial_buyer'   => 'ترغیب‌کننده، آگاهی‌بخش، شفاف‌ساز مزایا و راهنمای خرید هوشمندانه با تکیه بر اصالت و ایمنی داروخانه',
            'simple_public'      => 'فوق‌العاده ساده، عامه‌فهم، بدون ابهام و بدون اصطلاحات گیج‌کننده پزشکی'
        );
        $selected_tone_desc = $tones_desc[$tone] ?? $tones_desc['medical_consultant'];

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
            $type_instructions = "نوع سند: صفحه محصول دارویی/مکمل فروشگاه ووکامرس (WooCommerce Product Page).
بخش‌های الزامی:
۱. فیلد 'short_description': متن خلاصه و ویژگی‌های شاخص در قالب فهرست گلوله‌ای (HTML ul/li) شامل: ترکیبات موثره، مزیت‌های اثبات‌شده، کشور سازنده و شرکت، فرم محصول (قرص/کپسول/شربت) و نحوه مصرف پیشنهادی.
۲. فیلد 'content': توضیحات کامل و ساختاریافته محصول شامل: معرفی کاربرد، جدول دقیق مقادیر ترکیبات به همراه درصد نیاز روزانه (HTML Table)، مکانیسم اثر، روش و زمان ایده‌آل مصرف، هشدارهای منع مصرف و تداخلات، و سوالات متداول.";
        } elseif ($content_type === 'category') {
            $type_instructions = "نوع سند: توضیحات و لندینگ دسته‌بندی فروشگاه داروخانه (Category / Taxonomy Page).
شامل راهنمای خرید تخصصی، مقایسه انواع مکمل‌ها بر اساس نیاز کاربر، پاسخ به سوالات تکرارشونده و معیارهای انتخاب بهترین برند.";
        } else {
            $type_instructions = "نوع سند: مقاله جامع و مرجع وبلاگ پزشکی و داروسازی (Educational Blog Post).
شامل مقدمه گیرا، کاربردها و فواید درمانی، نحوه عملکرد در بدن، دوز و نحوه مصرف، عوارض جانبی تفکیک‌شده (شایع/نادر)، تداخلات با داروها و غذاها، احتیاط‌های ویژه، پرسش‌های متداول (FAQ) و نتیجه‌گیری.";
        }

        return "شما یک متخصص ارشد فارماکولوژی بالینی، نویسنده مقالات پزشکی تراز اول، و مشاور سئو پزشکی (Medical SEO & GEO Specialist) مطابق دستورالعمل‌های رسمی گوگل رنک‌مث (Rank Math) و فاکتورهای اعتماد E-E-A-T هستید.

{$type_instructions}

{$strategy_instructions}

لحن نگارش: {$selected_tone_desc}.

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

    private static function build_user_prompt($content_type, $mode, $topic, $focus_keyword, $word_count, $target_audience, $tone, $seo_strategy, $sources_text, $clean_urls) {
        $prompt = "لطفاً با توجه به استراتژی‌های مدرن تعیین‌شده، محتوای تخصصی زیر را تدوین کرده و در قالب شیء JSON خواسته شده تحویل دهید:\n\n";
        $prompt .= "- نوع سند: " . $content_type . "\n";
        $prompt .= "- موضوع / نام دارو یا محصول: " . ($topic ? $topic : 'بر اساس منابع ورودی') . "\n";
        if ($focus_keyword) {
            $prompt .= "- کلمه کلیدی کانونی (Focus Keyword): " . $focus_keyword . "\n";
        }
        $prompt .= "- استراتژی محتوا: " . $seo_strategy . "\n";
        $prompt .= "- حجم تقریبی متن: " . $word_count . " کلمه\n";
        $prompt .= "- مخاطب هدف: " . $target_audience . "\n";

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

    private static function call_openrouter($api_key, $model, $system_instruction, $user_prompt) {
        $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

        $body_data = array(
            'model'    => $model,
            'messages' => array(
                array('role' => 'system', 'content' => $system_instruction),
                array('role' => 'user', 'content' => $user_prompt),
            ),
            'response_format' => array('type' => 'json_object'),
            'temperature'     => 0.4,
            'max_tokens'      => 8000,
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
                'HTTP-Referer'  => 'https://drdarabipharmacy.com',
                'X-Title'       => 'Dr Darabi Pharmacy AI Writer Pro',
            ),
            'body'    => wp_json_encode($body_data),
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'خطا در ارتباط با اوپن‌روتر: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            $err_msg = isset($data['error']['message']) ? $data['error']['message'] : "کد وضعیت HTTP: {$code}";
            return new WP_Error('openrouter_error', 'خطای اوپن‌روتر: ' . $err_msg);
        }

        $raw_text = $data['choices'][0]['message']['content'] ?? '';
        return self::parse_json_result($raw_text);
    }

    private static function call_openrouter_chat($api_key, $model, $system_prompt, $messages) {
        $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

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

        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
                'HTTP-Referer'  => 'https://drdarabipharmacy.com',
                'X-Title'       => 'Dr Darabi Pharmacy AI Chat',
            ),
            'body'    => wp_json_encode($body_data),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return new WP_Error('openrouter_error', $data['error']['message'] ?? 'خطای ناشناخته');
        }

        $reply = $data['choices'][0]['message']['content'] ?? '';
        return array('reply' => $reply);
    }

    private static function call_gemini($api_key, $model, $system_instruction, $user_prompt, $proxy_base = '') {
        $base = !empty($proxy_base) ? rtrim($proxy_base, '/') : 'https://generativelanguage.googleapis.com';
        $endpoint = "{$base}/v1beta/models/{$model}:generateContent?key={$api_key}";

        $safety_settings = array(
            array('category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'),
            array('category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'),
            array('category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'),
            array('category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'),
        );

        $body_data = array(
            'system_instruction' => array(
                'parts' => array(
                    array('text' => $system_instruction)
                )
            ),
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $user_prompt)
                    )
                )
            ),
            'safetySettings' => $safety_settings,
            'generationConfig' => array(
                'response_mime_type' => 'application/json',
                'temperature'        => 0.4,
                'maxOutputTokens'    => 8192,
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

    private static function call_openai($api_key, $model, $system_instruction, $user_prompt, $proxy_base = '') {
        $base = !empty($proxy_base) ? rtrim($proxy_base, '/') : 'https://api.openai.com/v1';
        $endpoint = "{$base}/chat/completions";

        $body_data = array(
            'model'    => $model,
            'messages' => array(
                array('role' => 'system', 'content' => $system_instruction),
                array('role' => 'user', 'content' => $user_prompt),
            ),
            'response_format' => array('type' => 'json_object'),
            'temperature'     => 0.4,
            'max_tokens'      => 8000,
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
        if (preg_match('/"content"\s*:\s*"(.*?)(?:"\s*,\s*"|"\s*,\s*\n\s*"|"\s*\})/us', $clean_json, $m)) {
            $extracted['content'] = stripcslashes($m[1]);
        }

        if (!empty($extracted['content'])) {
            return $extracted;
        }

        return new WP_Error('invalid_json', 'خطای تجزیه خروجی مدل. متن دریافت شده: ' . mb_substr($raw_text, 0, 150));
    }
}
