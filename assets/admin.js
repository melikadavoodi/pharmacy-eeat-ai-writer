/* Pharmacy EEAT AI Writer Pro JS - Version 3.0.0 */
jQuery(document).ready(function ($) {

    // Multi-Tone Brand Pill Toggle (v3.4.0)
    $(document).on('change', '.tone-pill input[type="checkbox"]', function () {
        $(this).closest('.tone-pill').toggleClass('active', $(this).is(':checked'));
        var checkedCount = $('.tone-pill input[type="checkbox"]:checked').length;
        if (checkedCount === 0) {
            $(this).prop('checked', true).closest('.tone-pill').addClass('active');
            alert('حداقل باید یک لحن برای محتوا انتخاب شده باشد.');
        }
    });

    // Content Type Selector Pills
    $('.type-pill input[type="radio"]').on('change', function () {
        $('.type-pill').removeClass('active');
        $(this).closest('.type-pill').addClass('active');
        var val = $(this).val();
        $('#pharmacy_content_type').val(val);

        if (val === 'product') {
            $('#pharmacy_words').val('800');
            $('#pharmacy_tone').val('commercial_buyer');
            $('#pharmacy_seo_strategy').val('cro_conversion');
            $('#pharmacy_topic').attr('placeholder', 'مثال: کپسول کلاژن پلاس ال لیزین یوروویتال (۶۰ عددی)');
            $('#existing_product_row').slideDown(250);
            $('#res_features_box').show();
        } else if (val === 'category') {
            $('#pharmacy_words').val('800');
            $('#pharmacy_tone').val('commercial_buyer');
            $('#pharmacy_seo_strategy').val('geo_ai_search');
            $('#pharmacy_topic').attr('placeholder', 'مثال: مکمل‌های تقویت پوست، مو و ناخن');
            $('#existing_product_row').slideUp(250);
            $('#res_features_box').hide();
        } else {
            $('#pharmacy_words').val('1500');
            $('#pharmacy_tone').val('medical_consultant');
            $('#pharmacy_seo_strategy').val('geo_ai_search');
            $('#pharmacy_topic').attr('placeholder', 'مثال: کپسول گاباپنتین؛ موارد مصرف، دوز و عوارض جانبی');
            $('#existing_product_row').slideUp(250);
            $('#res_features_box').hide();
        }
    });

    // Tab Switching
    $('.pharmacy-tab').on('click', function () {
        var target = $(this).data('tab');
        $('.pharmacy-tab').removeClass('active');
        $(this).addClass('active');

        $('.pharmacy-tab-content').removeClass('active');
        $('#' + target).addClass('active');

        if (target === 'tab-topic') {
            $('#pharmacy_mode').val('topic');
        } else if (target === 'tab-multi-url') {
            $('#pharmacy_mode').val('multi_url');
        } else if (target === 'tab-text') {
            $('#pharmacy_mode').val('translate');
        }
    });

    // Multi-URL Repeater: Add / Remove
    $('#btn-add-url-row').on('click', function () {
        var row = '<div class="url-input-row">' +
            '<input type="url" name="source_urls[]" placeholder="https://www.webmd.com/drugs/..." class="regular-text" dir="ltr">' +
            '<button type="button" class="button btn-remove-url">&times;</button>' +
            '</div>';
        $('#pharmacy-urls-repeater').append(row);
        updateRemoveButtons();
    });

    $(document).on('click', '.btn-remove-url', function () {
        $(this).closest('.url-input-row').remove();
        updateRemoveButtons();
    });

    function updateRemoveButtons() {
        var rows = $('#pharmacy-urls-repeater .url-input-row');
        if (rows.length > 1) {
            rows.find('.btn-remove-url').show();
        } else {
            rows.find('.btn-remove-url').hide();
        }
    }

    // Global stored generation state
    var currentGenerationData = {};

    // Check for previous Auto-Save on page load
    checkAutoSave();

    function saveAutoSave(d, topic) {
        try {
            var payload = {
                data: d,
                topic: topic || $('#pharmacy_topic').val() || 'بدون عنوان',
                time: new Date().toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' }),
                date: new Date().toLocaleDateString('fa-IR')
            };
            localStorage.setItem('pharmacy_ai_autosave', JSON.stringify(payload));
        } catch (e) {
            console.log('LocalStorage save error:', e);
        }
    }

    function checkAutoSave() {
        try {
            var raw = localStorage.getItem('pharmacy_ai_autosave');
            if (raw) {
                var payload = JSON.parse(raw);
                if (payload && payload.data && payload.data.content) {
                    $('#saved-topic-name').text(payload.topic);
                    $('#saved-time').text(payload.time + ' (' + payload.date + ')');
                    $('#pharmacy-restore-banner').slideDown(250);
                }
            }
        } catch (e) {
            console.log('LocalStorage read error:', e);
        }
    }

    $('#btn-restore-last-generated').on('click', function () {
        try {
            var raw = localStorage.getItem('pharmacy_ai_autosave');
            if (raw) {
                var payload = JSON.parse(raw);
                if (payload && payload.data) {
                    renderContentResult(payload.data);
                    if (payload.topic && !$('#pharmacy_topic').val()) {
                        $('#pharmacy_topic').val(payload.topic);
                    }
                    $('#pharmacy-restore-banner').slideUp(200);
                    alert('محتوای قبلی با موفقیت و بدون مصرف حتی یک توکن بازیابی شد!');
                }
            }
        } catch (e) {
            alert('خطا در بازیابی محتوا.');
        }
    });

    $('#btn-dismiss-restore').on('click', function () {
        $('#pharmacy-restore-banner').slideUp(200);
    });

    function renderContentResult(d) {
        currentGenerationData = d;

        $('#res_post_title').val(d.title || '');
        $('#res_rank_math_title').val(d.rank_math_title || d.title || '');
        $('#res_rank_math_desc').val(d.rank_math_description || d.excerpt || '');
        $('#res_rank_math_keyword').val(d.rank_math_focus_keyword || $('#pharmacy_keyword').val() || '');
        $('#res_short_description').val(d.short_description || d.excerpt || '');
        $('#res_post_excerpt').val(d.excerpt || '');
        $('#res_post_content').val(d.content || '');
        $('#res_content_preview').html(d.content || '');

        // Render Scorecard
        if (d.scorecard) {
            var sc = d.scorecard;
            var badgeHtml = '<span class="score-badge" style="background:#0284c7;color:#fff;padding:3px 8px;border-radius:12px;font-size:11.5px;font-weight:bold;">E-E-A-T: ' + (sc.eeat_score || '90') + '%</span>';
            badgeHtml += '<span class="score-badge" style="background:#16a34a;color:#fff;padding:3px 8px;border-radius:12px;font-size:11.5px;font-weight:bold;">GEO: ' + (sc.geo_readiness || 'آماده هوش مصنوعی') + '</span>';
            badgeHtml += '<span class="score-badge" style="background:#8b5cf6;color:#fff;padding:3px 8px;border-radius:12px;font-size:11.5px;font-weight:bold;">خوانایی: ' + (sc.readability || 'عالی') + '</span>';
            $('#res_score_badges').html(badgeHtml);

            var strengthsHtml = '';
            if (sc.key_strengths && Array.isArray(sc.key_strengths)) {
                sc.key_strengths.forEach(function (s) {
                    strengthsHtml += '<li>' + s + '</li>';
                });
            }
            $('#res_score_strengths').html(strengthsHtml);
            $('#res_scorecard_container').show();
        } else {
            $('#res_scorecard_container').hide();
        }

        // Render Internal Linking Suggestions
        var $linksList = $('#res_internal_links_list');
        $linksList.empty();
        if (d.internal_links && Array.isArray(d.internal_links) && d.internal_links.length > 0) {
            d.internal_links.forEach(function (link) {
                var itemHtml = '<div style="background:#fff;border:1px solid #f0abfc;border-radius:6px;padding:8px 12px;display:flex;justify-content:space-between;align-items:center;">' +
                    '<div><strong>انکر تکست:</strong> <span class="badge-anchor" style="background:#fae8ff;color:#86198f;padding:2px 8px;border-radius:4px;font-weight:bold;">' + link.anchor + '</span> &larr; <em>' + (link.target_topic || '') + '</em>' +
                    '<div style="font-size:11.5px;color:#6b7280;margin-top:2px;">' + (link.reason || '') + '</div></div>' +
                    '<button type="button" class="button button-small btn-copy-anchor" data-anchor="' + escapeHtml(link.anchor) + '">کپی انکر</button>' +
                    '</div>';
                $linksList.append(itemHtml);
            });
            $('.pharmacy-internal-links-box').show();
        } else {
            $('.pharmacy-internal-links-box').hide();
        }

        // Render Schema JSON-LD
        if (d.schema_json) {
            var schemaString = typeof d.schema_json === 'object' ? JSON.stringify(d.schema_json, null, 2) : d.schema_json;
            $('#res_schema_json').val(schemaString);
            $('.pharmacy-schema-box').show();
        } else {
            $('.pharmacy-schema-box').hide();
        }

        // Render references
        var $refList = $('#res_references_list');
        $refList.empty();
        if (d.references && Array.isArray(d.references)) {
            d.references.forEach(function (ref) {
                $refList.append('<li>' + ref + '</li>');
            });
        } else {
            $refList.append('<li>مراجع علمی معتبر در انتهای متن مستند شده‌اند.</li>');
        }

        $('#pharmacy-result-container').slideDown(400);
        $('html, body').animate({
            scrollTop: $('#pharmacy-result-container').offset().top - 40
        }, 500);
    }

    // Generate Article / Product AJAX
    $('#pharmacy-ai-generate-form').on('submit', function (e) {
        e.preventDefault();

        var mode = $('#pharmacy_mode').val();
        var topic = $('#pharmacy_topic').val();

        if (mode === 'topic' && !topic.trim()) {
            alert('لطفاً نام دارو، محصول یا موضوع را وارد کنید.');
            $('#pharmacy_topic').focus();
            return;
        }

        var $btn = $('#pharmacy-btn-generate');
        var $spinner = $('#pharmacy-spinner');
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');

        var formData = $(this).serializeArray();
        formData.push({ name: 'action', value: 'pharmacy_ai_generate_article' });
        formData.push({ name: 'nonce', value: pharmacyAiData.nonce });

        $.ajax({
            url: pharmacyAiData.ajax_url,
            type: 'POST',
            data: formData,
            success: function (res) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');

                if (res.success && res.data) {
                    var d = res.data;
                    saveAutoSave(d, topic);
                    renderContentResult(d);
                } else {
                    alert('خطا در تولید محتوا: ' + (res.data ? res.data : 'پاسخ نامعتبر دریافت شد.'));
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                alert('خطا در برقراری ارتباط با سرور.');
            }
        });
    });

    // Copy Schema Button
    $('#btn-copy-schema').on('click', function () {
        var schemaText = $('#res_schema_json').val();
        if (!schemaText) return;

        navigator.clipboard.writeText(schemaText).then(function () {
            var $btn = $('#btn-copy-schema');
            var oldText = $btn.html();
            $btn.text('کپی شد! ✓').css('color', '#166534');
            setTimeout(function () {
                $btn.html(oldText).css('color', '');
            }, 2000);
        });
    });

    // Quick Copy Buttons for Fields
    $(document).on('click', '.btn-quick-copy', function () {
        var targetSelector = $(this).data('target');
        var textToCopy = $(targetSelector).val() || $(targetSelector).text() || '';
        var $btn = $(this);
        var originalText = $btn.text();

        if (!textToCopy) {
            alert('متنی برای کپی یافت نشد!');
            return;
        }

        navigator.clipboard.writeText(textToCopy).then(function () {
            $btn.text('کپی شد! ✓').css({ 'background': '#166534', 'color': '#fff', 'border-color': '#166534' });
            setTimeout(function () {
                $btn.text(originalText).css({ 'background': '', 'color': '', 'border-color': '' });
            }, 1800);
        });
    });

    // Copy Full Content HTML Button
    $('#btn-copy-full-content').on('click', function () {
        var fullText = $('#res_post_content').val();
        var $btn = $(this);
        var originalHtml = $btn.html();

        if (!fullText) {
            alert('متنی برای کپی وجود ندارد!');
            return;
        }

        navigator.clipboard.writeText(fullText).then(function () {
            $btn.html('<span class="dashicons dashicons-yes"></span> کل متن کپی شد!').css({ 'background': '#166534', 'color': '#fff' });
            setTimeout(function () {
                $btn.html(originalHtml).css({ 'background': '', 'color': '' });
            }, 2000);
        });
    });

    // Copy Anchor Text
    $(document).on('click', '.btn-copy-anchor', function () {
        var anchor = $(this).data('anchor');
        var $btn = $(this);
        navigator.clipboard.writeText(anchor).then(function () {
            $btn.text('کپی شد!').css('color', '#166534');
            setTimeout(function () {
                $btn.text('کپی انکر').css('color', '');
            }, 1500);
        });
    });

    // Save Directly to Draft or Update Existing Product AJAX
    $('#pharmacy-save-draft-btn').on('click', function () {
        var $btn = $(this);
        var postTitle = $('#res_post_title').val();
        var postContent = $('#res_post_content').val();
        var shortDesc = $('#res_short_description').val();
        var postExcerpt = $('#res_post_excerpt').val();
        var rmTitle = $('#res_rank_math_title').val();
        var rmDesc = $('#res_rank_math_desc').val();
        var rmKeyword = $('#res_rank_math_keyword').val();
        var contentType = $('#pharmacy_content_type').val();
        var targetProduct = $('#pharmacy_target_product').val();
        var saveMode = $('input[name="pharmacy_save_mode"]:checked').val() || 'replace';
        var schemaJson = $('#res_schema_json').val();
        var internalLinks = currentGenerationData.internal_links ? JSON.stringify(currentGenerationData.internal_links) : '';
        var scorecard = currentGenerationData.scorecard ? JSON.stringify(currentGenerationData.scorecard) : '';

        if (!postTitle || !postContent) {
            alert('عنوان یا متن محتوا خالی است!');
            return;
        }

        $btn.prop('disabled', true).text('در حال ذخیره / به‌روزرسانی...');

        $.ajax({
            url: pharmacyAiData.ajax_url,
            type: 'POST',
            data: {
                action: 'pharmacy_ai_save_draft',
                nonce: pharmacyAiData.nonce,
                content_type: contentType,
                target_product: targetProduct,
                save_mode: saveMode,
                title: postTitle,
                content: postContent,
                short_description: shortDesc,
                excerpt: postExcerpt,
                rank_math_title: rmTitle,
                rank_math_desc: rmDesc,
                rank_math_keyword: rmKeyword,
                schema_json: schemaJson,
                internal_links: internalLinks,
                scorecard: scorecard
            },
            success: function (res) {
                if (res.success && res.data.edit_url) {
                    var modeText = (saveMode === 'append') ? ' (افزوده شده به انتهای محتوای فعلی)' : '';
                    var successMsg = res.data.is_update ? 
                        ('محصول «' + (res.data.post_title || '') + '» (شناسه: ' + res.data.post_id + ') با موفقیت به‌روزرسانی شد!' + modeText) : 
                        ('محتوای جدید با شناسه ' + res.data.post_id + ' با موفقیت ذخیره شد!');

                    $btn.text('با موفقیت انجام شد!').css('background', '#166534');
                    var notice = '<div class="notice notice-success inline" style="margin-top: 15px; padding: 14px 18px; border-radius: 8px; border-right: 5px solid #16a34a;">' +
                        '<p style="font-size: 14px; margin-top: 0;"><strong>' + successMsg + '</strong></p>' +
                        '<p style="margin-bottom: 0;"><a href="' + res.data.edit_url + '" class="button button-primary" target="_blank">رفتن مستقیم به ویرایش محصول در ووکامرس</a> ' +
                        '<a href="' + res.data.preview_url + '" class="button button-secondary" target="_blank">مشاهده در سایت</a></p>' +
                        '</div>';
                    $('#pharmacy-result-container').prepend(notice);
                } else {
                    $btn.prop('disabled', false).text('ذخیره / به‌روزرسانی مستقیم');
                    alert('خطا در ذخیره: ' + (res.data ? res.data : 'ناشناخته'));
                }
            },
                        '<p><a href="' + res.data.edit_url + '" class="button button-primary" target="_blank">رفتن مستقیم به ویرایش نوشته / محصول</a> ' +
                        '<a href="' + res.data.preview_url + '" class="button button-secondary" target="_blank">مشاهده در سایت</a></p>' +
                        '</div>';
                    $('#pharmacy-result-container').prepend(notice);
                } else {
                    $btn.prop('disabled', false).text('ذخیره / به‌روزرسانی مستقیم');
                    alert('خطا در ذخیره: ' + (res.data ? res.data : 'ناشناخته'));
                }
            },
            error: function () {
                $btn.prop('disabled', false).text('ذخیره / به‌روزرسانی مستقیم');
                alert('خطا در ارتباط با سرور.');
            }
        });
    });

    // Single Product Edit Screen Quick Actions
    function handleProductQuickAction(postId, actionType, btnSelector) {
        var $btn = $(btnSelector);
        var originalText = $btn.text();
        var $status = $('#pharmacy-quick-status');
        $btn.prop('disabled', true).text('در حال تولید با متد CRO و E-E-A-T...');
        $status.show().text('لطفاً چند ثانیه صبر کنید...').css('color', '#0284c7');

        $.ajax({
            url: pharmacyAiData.ajax_url,
            type: 'POST',
            data: {
                action: 'pharmacy_ai_product_quick_action',
                nonce: pharmacyAiData.nonce,
                post_id: postId,
                action_type: actionType
            },
            success: function (res) {
                $btn.prop('disabled', false).text(originalText);
                if (res.success && res.data) {
                    var d = res.data;
                    $status.text(d.message || 'انجام شد! صفحه را ذخیره/بروزرسانی کنید.').css('color', '#166534');

                    // Update TinyMCE Main Content (both Visual and HTML mode)
                    if (d.content) {
                        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content') && !tinyMCE.get('content').isHidden()) {
                            tinyMCE.get('content').setContent(d.content);
                        }
                        if ($('#content').length) {
                            $('#content').val(d.content).trigger('change');
                        }
                    }

                    // Update WooCommerce Short Description / Excerpt (both Visual and HTML mode)
                    if (d.short_description) {
                        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('excerpt') && !tinyMCE.get('excerpt').isHidden()) {
                            tinyMCE.get('excerpt').setContent(d.short_description);
                        }
                        if ($('#excerpt').length) {
                            $('#excerpt').val(d.short_description).trigger('change');
                        }
                    }

                    // Real-Time Rank Math Redux Dispatch
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('rank-math')) {
                        try {
                            if (d.rank_math_title) {
                                wp.data.dispatch('rank-math').updateTitle(d.rank_math_title);
                            }
                            if (d.rank_math_desc) {
                                wp.data.dispatch('rank-math').updateDescription(d.rank_math_desc);
                            }
                            if (d.rank_math_keyword) {
                                wp.data.dispatch('rank-math').updateFocusKeywords(d.rank_math_keyword);
                            }
                        } catch (e) {
                            console.log('Rank Math dispatch:', e);
                        }
                    }

                    // Fallback to legacy/hidden inputs if present
                    if (d.rank_math_title && $('#rank_math_title').length) {
                        $('#rank_math_title').val(d.rank_math_title).trigger('change');
                    }
                    if (d.rank_math_desc && $('#rank_math_description').length) {
                        $('#rank_math_description').val(d.rank_math_desc).trigger('change');
                    }
                    if (d.rank_math_keyword && $('#rank_math_focus_keyword').length) {
                        $('#rank_math_focus_keyword').val(d.rank_math_keyword).trigger('change');
                    }

                    alert('اطلاعات با موفقیت تولید و در باکس‌های محصول و اسنیپت رنک‌مث جایگذاری شد. اکنون روی دکمه آبی «به‌روزرسانی» کلیک کنید تا در دیتابیس ذخیره شود.');
                } else {
                    $status.text('خطا: ' + (res.data || 'ناشناخته')).css('color', '#dc2626');
                }
            },
            error: function () {
                $btn.prop('disabled', false).text(originalText);
                $status.text('خطا در ارتباط با سرور.').css('color', '#dc2626');
            }
        });
    }

    $('#btn-quick-fill-all-product').on('click', function () {
        handleProductQuickAction($(this).data('post-id'), 'all', '#btn-quick-fill-all-product');
    });
    $('#btn-quick-desc-product').on('click', function () {
        handleProductQuickAction($(this).data('post-id'), 'desc', '#btn-quick-desc-product');
    });
    $('#btn-quick-features-product').on('click', function () {
        handleProductQuickAction($(this).data('post-id'), 'features', '#btn-quick-features-product');
    });

    // ChatBot Logic (PharmaBot)
    var chatHistory = [];

    function sendChatMessage(text) {
        if (!text.trim()) return;

        $('#pharmacy-chat-box').append('<div class="chat-msg user-msg"><p>' + escapeHtml(text) + '</p></div>');
        $('#pharmacy-chat-input').val('');
        scrollChatToBottom();

        chatHistory.push({ role: 'user', content: text });

        var $loadingMsg = $('<div class="chat-msg bot-msg loading"><p>در حال تحلیل شواهد پزشکی و مراجع...</p></div>');
        $('#pharmacy-chat-box').append($loadingMsg);
        scrollChatToBottom();

        $.ajax({
            url: pharmacyAiData.ajax_url,
            type: 'POST',
            data: {
                action: 'pharmacy_ai_chat',
                nonce: pharmacyAiData.nonce,
                messages: chatHistory
            },
            success: function (res) {
                $loadingMsg.remove();
                if (res.success && res.data && res.data.reply) {
                    var reply = res.data.reply;
                    chatHistory.push({ role: 'model', content: reply });
                    var formatted = formatChatReply(reply);
                    $('#pharmacy-chat-box').append('<div class="chat-msg bot-msg"><p>' + formatted + '</p></div>');
                } else {
                    $('#pharmacy-chat-box').append('<div class="chat-msg bot-msg"><p style="color:#dc2626;">خطا: ' + (res.data || 'پاسخی دریافت نشد.') + '</p></div>');
                }
                scrollChatToBottom();
            },
            error: function () {
                $loadingMsg.remove();
                $('#pharmacy-chat-box').append('<div class="chat-msg bot-msg"><p style="color:#dc2626;">خطا در ارتباط با سرور.</p></div>');
                scrollChatToBottom();
            }
        });
    }

    $('#pharmacy-chat-send-btn').on('click', function () {
        var txt = $('#pharmacy-chat-input').val();
        sendChatMessage(txt);
    });

    $('#pharmacy-chat-input').on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChatMessage($(this).val());
        }
    });

    $('.quick-prompt-btn').on('click', function () {
        var p = $(this).data('prompt');
        $('#pharmacy-chat-input').val(p).focus();
    });

    function scrollChatToBottom() {
        var box = document.getElementById('pharmacy-chat-box');
        if (box) {
            box.scrollTop = box.scrollHeight;
        }
    }

    function escapeHtml(string) {
        return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function formatChatReply(text) {
        var escaped = escapeHtml(text);
        return escaped.replace(/\n/g, '<br>');
    }

    // Quick Metabox Actions in Post Edit Screen
    $('#btn-quick-meta-gen').on('click', function () {
        var postId = $(this).data('post-id');
        var $status = $('#pharmacy-quick-status');
        $status.show().text('در حال بهینه‌سازی متای رنک‌مث با هوش مصنوعی...').css('color', '#0284c7');

        $.ajax({
            url: pharmacyAiData.ajax_url,
            type: 'POST',
            data: {
                action: 'pharmacy_ai_optimize_meta',
                nonce: pharmacyAiData.nonce,
                post_id: postId
            },
            success: function (res) {
                if (res.success) {
                    $status.text('متای سئو با موفقیت به‌روزرسانی شد! صفحه را ذخیره/بروزرسانی کنید.').css('color', '#166534');
                    var d = res.data;
                    if (d && typeof wp !== 'undefined' && wp.data && wp.data.dispatch('rank-math')) {
                        try {
                            if (d.rank_math_title) {
                                wp.data.dispatch('rank-math').updateTitle(d.rank_math_title);
                            }
                            if (d.rank_math_desc) {
                                wp.data.dispatch('rank-math').updateDescription(d.rank_math_desc);
                            }
                        } catch (e) {
                            console.log('Rank Math dispatch error:', e);
                        }
                    }
                } else {
                    $status.text('خطا: ' + res.data).css('color', '#dc2626');
                }
            },
            error: function () {
                $status.text('خطا در ارتباط با سرور.').css('color', '#dc2626');
            }
        });
    });

    $('#btn-insert-reviewer').on('click', function () {
        var boxHtml = '\n<div class="pharmacy-eeat-reviewer-box"><strong>تایید و بازبینی علمی توسط:</strong> ' + pharmacyAiData.reviewer_name + ' (شماره نظام پزشکی: ' + pharmacyAiData.reviewer_code + ')</div>\n';
        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
            var block = wp.blocks.createBlock('core/html', { content: boxHtml });
            wp.data.dispatch('core/block-editor').insertBlocks(block);
        } else if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
            tinyMCE.activeEditor.insertContent(boxHtml);
        } else {
            $('#content').val($('#content').val() + boxHtml);
        }
        $('#pharmacy-quick-status').show().text('باکس تایید داروساز به محتوا اضافه شد.').css('color', '#166534');
    });

    $('#btn-insert-disclaimer').on('click', function () {
        var boxHtml = '\n<div class="pharmacy-eeat-disclaimer-box"><strong>سلب مسئولیت پزشکی:</strong> ' + pharmacyAiData.disclaimer + '</div>\n';
        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
            var block = wp.blocks.createBlock('core/html', { content: boxHtml });
            wp.data.dispatch('core/block-editor').insertBlocks(block);
        } else if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
            tinyMCE.activeEditor.insertContent(boxHtml);
        } else {
            $('#content').val($('#content').val() + boxHtml);
        }
        $('#pharmacy-quick-status').show().text('باکس سلب مسئولیت به محتوا اضافه شد.').css('color', '#166534');
    });

});
