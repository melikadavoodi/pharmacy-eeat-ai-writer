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

        $content_type  = isset($params['content_type']) ? sanitize_text_field($params['content_type']) : 'blog_post';
        $mode          = isset($params['mode']) ? sanitize_text_field($params['mode']) : 'topic';
        $topic         = isset($params['topic']) ? sanitize_text_field($params['topic']) : '';
        $source_urls   = isset($params['source_urls']) ? (array) $params['source_urls'] : array();
        $source_text   = isset($params['source_text']) ? sanitize_textarea_field($params['source_text']) : '';
        $focus_keyword = isset($params['focus_keyword']) ? sanitize_text_field($params['focus_keyword']) : '';
        $word_count    = isset($params['word_count']) ? intval($params['word_count']) : 1500;
        $tone          = isset($params['tone']) ? sanitize_text_field($params['tone']) : 'medical_consultant';
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

        $system_instruction = self::build_system_prompt($content_type, $tone, $reviewer_name, $reviewer_code, $disclaimer_txt);
        $user_prompt        = self::build_user_prompt($content_type, $mode, $topic, $focus_keyword, $word_count, $target_audience, $tone, $fetched_sources_text, $clean_urls);

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

        $system_prompt = "شما 'رنک‌بات داروساز (PharmaBot)' هستید؛ یک دستیار هوشمند و متخصص ارشد داروسازی بالینی، فارماکولوژی و سئو پزشکی مطابق با استانداردهای گوگل E-E-A-T.
وظیفه شما:
۱. کمک به کارشناس محتوا و دکتر داروساز در کشف جدیدترین مراجع علمی (PubMed, Mayo Clinic, WebMD, FDA, Drugs.com, MedlinePlus).
۲. پیشنهاد لینک‌ها و منابع معتبر بین‌المللی مرتبط با هر دارو، بیماری یا مکمل درخواستی.
۳. طوفان فکری، تحلیل تداخلات دارویی، دوزهای بالینی، و بازنویسی متن‌ها.
۴. لحن شما حرفه‌ای، مودبانه، علمی و به زبان فارسی روان است. همواره برای ادعاهای علمی نام پایگاه‌های موثق را ذکر کنید.";

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

    private static function build_system_prompt($content_type, $tone, $reviewer_name, $reviewer_code, $disclaimer_txt) {
        $tones_desc = array(
            'medical_consultant' => 'مشاوره‌ای، دلسوزانه و قابل‌اعتماد داروخانه‌ای (مثل پاسخ مستقیم دکتر داروساز به بیمار)',
            'clinical_evidence'  => 'بسیار علمی، بالینی، دقیق و منطبق با شواهد رسمی (Evidence-Based Medicine)',
            'commercial_buyer'   => 'ترغیب‌کننده، آگاهی‌بخش و راهنمای خرید هوشمندانه مکمل/دارو با تاکید بر ارزش خرید و کیفیت',
            'simple_public'      => 'فوق‌العاده ساده، عامه‌فهم و بدون اصطلاحات پیچیده پزشکی'
        );
        $selected_tone_desc = $tones_desc[$tone] ?? $tones_desc['medical_consultant'];

        $type_instructions = '';
        if ($content_type === 'product') {
            $type_instructions = "نوع محتوا: توضیحات صفحه محصول دارویی/مکمل فروشگاهی (WooCommerce Product Page).
وظیفه ویژه شما:
۱. تولید فیلد 'short_description': متن معرفی کوتاه و فهرست ویژگی‌های شاخص محصول (Bullet points شامل ترکیبات اصلی، فواید برجسته و ساخت کشور/برند) برای قرارگیری در باکس ویژگی‌های محصول ووکامرس.
۲. تولید فیلد 'content': توضیحات کامل و جامع محصول شامل معرفی، جدول دقیق ترکیبات و دوز (HTML Table)، نحوه و زمان مصرف، هشدارهای منع مصرف، تداخلات دارویی و پرسش‌های متداول خریداران (FAQ).";
        } elseif ($content_type === 'category') {
            $type_instructions = "نوع محتوا: متن لندینگ یا توضیحات دسته‌بندی فروشگاه داروخانه (Taxonomy / Category Description).
شامل راهنمای جامع خرید، معرفی کاربرد دسته، معیارهای انتخاب بر اساس سن/جنسیت و سوالات متداول.";
        } else {
            $type_instructions = "نوع محتوا: مقاله جامع وبلاگ پزشکی و دارویی (Blog Post).
شامل مقدمه، مکانیسم اثر، موارد مصرف، دوز و نحوه مصرف، عوارض جانبی تفکیک‌شده (شایع/نادر)، تداخلات دارویی، منع مصرف، هشدارهای بارداری و سوالات متداول.";
        }

        return "شما یک متخصص ارشد داروسازی بالینی، نویسنده محتوای پزشکی، و کارشناس ارشد سئو رنک‌مث (Rank Math) و استانداردهای گوگل E-E-A-T هستید.

{$type_instructions}

لحن نگارش: {$selected_tone_desc}.

اصول الزامی که باید مو به مو رعایت شوند:
۱. در صورت وجود چند منبع ورودی، داده‌های آنها را تلفیق، اعتبارسنجی و نکات تکمیلی را اضافه کنید.
۲. استناد دقیق به منابع معتبر جهانی (مانند Mayo Clinic, WebMD, PubMed, FDA, Drugs.com, MedlinePlus).
۳. قرار دادن دو کادر ویژه در انتهای مقالات یا محصولات:
   - کادر تایید داروساز:
     <div class=\"pharmacy-eeat-reviewer-box\"><strong>تایید و بازبینی علمی توسط:</strong> {$reviewer_name} (شماره نظام پزشکی: {$reviewer_code})</div>
   - کادر سلب مسئولیت:
     <div class=\"pharmacy-eeat-disclaimer-box\"><strong>سلب مسئولیت پزشکی:</strong> {$disclaimer_txt}</div>
۴. بهینه‌سازی دقیق برای رنک‌مث (Rank Math):
   - تولید تگ عنوان سئو جذاب با طول کمتر از ۶۰ کاراکتر.
   - تولید توضیحات متای کلیک‌خور و دارای کلمه کلیدی با طول بین ۱۳۰ تا ۱۶۰ کاراکتر.

خروجی شما باید منحصراً و بدون هیچ متن یا کدهای اضافی، یک شیء JSON با ساختار زیر باشد:
{
  \"title\": \"عنوان صفحه یا محصول\",
  \"short_description\": \"معرفی ویژگی‌های کلیدی، فواید برجسته و نکات شاخص برای باکس ویژگی‌های محصول ووکامرس (HTML list ul/li)\",
  \"rank_math_title\": \"عنوان سئو رنک‌مث\",
  \"rank_math_description\": \"توضیحات متای رنک‌مث\",
  \"rank_math_focus_keyword\": \"کلمه کلیدی کانونی\",
  \"excerpt\": \"چکیده در چند خط کوتاه\",
  \"content\": \"محتوای کامل به صورت HTML شامل تگ‌های مناسب، جداول و بخش‌ها\",
  \"references\": [\"مرجع ۱ با آدرس یا مشخصات\", \"مرجع ۲\"]
}";
    }

    private static function build_user_prompt($content_type, $mode, $topic, $focus_keyword, $word_count, $target_audience, $tone, $sources_text, $clean_urls) {
        $prompt = "لطفاً با توجه به مشخصات زیر محتوا را تدوین و در قالب JSON درخواستی بازگردانید:\n";
        $prompt .= "- نوع سند: " . $content_type . "\n";
        $prompt .= "- موضوع / نام دارو یا محصول: " . ($topic ? $topic : 'بر اساس منابع ورودی') . "\n";
        if ($focus_keyword) {
            $prompt .= "- کلمه کلیدی کانونی سئو: " . $focus_keyword . "\n";
        }
        $prompt .= "- حجم تقریبی مورد نیاز: " . $word_count . " کلمه\n";
        $prompt .= "- مخاطب هدف: " . $target_audience . "\n";

        if ($mode === 'multi_url' || $mode === 'translate') {
            $prompt .= "\n- دستور چندمنبعی (Multi-Source Synthesis): لطفاً داده‌های موجود در مراجع زیر را مقایسه، اعتبارسنجی و به زبان فارسی علمی و سئو شده بازآفرینی کنید:\n";
            if (!empty($clean_urls)) {
                $prompt .= "آدرس منابع:\n" . implode("\n", $clean_urls) . "\n";
            }
            $prompt .= "متن استخراج‌شده از منابع:\n" . $sources_text . "\n";
        } else {
            $prompt .= "\n- دستور تالیف: از پایگاه‌های دانش و شواهد پزشکی بین‌المللی برای تدوین جامع این مبحث با استناد به منابع استفاده کنید.\n";
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
            'max_tokens'      => 4000,
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
                'HTTP-Referer'  => 'https://drdarabipharmacy.com',
                'X-Title'       => 'Dr Darabi Pharmacy AI Writer',
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
            'max_tokens'      => 4000,
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

        $clean_json = preg_replace('/^```(?:json)?\s*/i', '', trim($raw_text));
        $clean_json = preg_replace('/\s*```$/', '', $clean_json);

        $parsed = json_decode($clean_json, true);
        if (!$parsed || !isset($parsed['content'])) {
            return new WP_Error('invalid_json', 'پاسخ هوش مصنوعی در قالب استاندارد نبود. متن دریافتی: ' . mb_substr($raw_text, 0, 100));
        }

        return $parsed;
    }
}
