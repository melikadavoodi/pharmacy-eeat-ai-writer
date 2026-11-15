/* Pharmacy EEAT AI Writer Pro JS */
jQuery(document).ready(function ($) {

    // Content Type Selector Pills
    $('.type-pill input[type="radio"]').on('change', function () {
        $('.type-pill').removeClass('active');
        $(this).closest('.type-pill').addClass('active');
        var val = $(this).val();
        $('#pharmacy_content_type').val(val);

        if (val === 'product') {
            $('#pharmacy_words').val('800');
            $('#pharmacy_tone').val('commercial_buyer');
            $('#pharmacy_topic').attr('placeholder', 'مثال: کپسول زینک پلاس ۱۰ میلی‌گرم یوروویتال (۶۰ عددی)');
        } else if (val === 'category') {
            $('#pharmacy_words').val('800');
            $('#pharmacy_tone').val('commercial_buyer');
            $('#pharmacy_topic').attr('placeholder', 'مثال: مکمل‌های تقویت سیستم ایمنی و سرماخوردگی');
        } else {
            $('#pharmacy_words').val('1500');
            $('#pharmacy_tone').val('medical_consultant');
            $('#pharmacy_topic').attr('placeholder', 'مثال: قرص فاموتیدین؛ موارد مصرف، دوز و عوارض جانبی');
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

    // Generate Article AJAX
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
                    $('#res_post_title').val(d.title || '');
                    $('#res_rank_math_title').val(d.rank_math_title || d.title || '');
                    $('#res_rank_math_desc').val(d.rank_math_description || d.excerpt || '');
                    $('#res_rank_math_keyword').val(d.rank_math_focus_keyword || $('#pharmacy_keyword').val() || '');
                    $('#res_post_excerpt').val(d.excerpt || '');
                    $('#res_post_content').val(d.content || '');
                    $('#res_content_preview').html(d.content || '');

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

    // Save Directly to Draft AJAX
    $('#pharmacy-save-draft-btn').on('click', function () {
        var $btn = $(this);
        var postTitle = $('#res_post_title').val();
        var postContent = $('#res_post_content').val();
        var postExcerpt = $('#res_post_excerpt').val();
        var rmTitle = $('#res_rank_math_title').val();
        var rmDesc = $('#res_rank_math_desc').val();
        var rmKeyword = $('#res_rank_math_keyword').val();
        var contentType = $('#pharmacy_content_type').val();

        if (!postTitle || !postContent) {
            alert('عنوان یا متن محتوا خالی است!');
            return;
        }

        $btn.prop('disabled', true).text('در حال ذخیره در پیش‌نویس‌های وردپرس...');

        $.ajax({
            url: pharmacyAiData.ajax_url,
            type: 'POST',
            data: {
                action: 'pharmacy_ai_save_draft',
                nonce: pharmacyAiData.nonce,
                content_type: contentType,
                title: postTitle,
                content: postContent,
                excerpt: postExcerpt,
                rank_math_title: rmTitle,
                rank_math_desc: rmDesc,
                rank_math_keyword: rmKeyword
            },
            success: function (res) {
                if (res.success && res.data.edit_url) {
                    $btn.text('با موفقیت ذخیره شد!').css('background', '#166534');
                    var notice = '<div class="notice notice-success inline" style="margin-top: 15px; padding: 12px; border-radius: 8px;">' +
                        '<p><strong>نوشته با موفقیت ذخیره شد و فیلدهای متای رنک‌مث ست گردید!</strong></p>' +
                        '<p><a href="' + res.data.edit_url + '" class="button button-primary" target="_blank">ویرایش در ادیتور وردپرس</a> ' +
                        '<a href="' + res.data.preview_url + '" class="button button-secondary" target="_blank">مشاهده پیش‌نمایش</a></p>' +
                        '</div>';
                    $('#pharmacy-result-container').prepend(notice);
                } else {
                    $btn.prop('disabled', false).text('ذخیره مستقیم در پیش‌نویس‌های وردپرس');
                    alert('خطا در ذخیره نوشته: ' + (res.data ? res.data : 'ناشناخته'));
                }
            },
            error: function () {
                $btn.prop('disabled', false).text('ذخیره مستقیم در پیش‌نویس‌های وردپرس');
                alert('خطا در ارتباط با سرور.');
            }
        });
    });

    // ChatBot Logic (PharmaBot)
    var chatHistory = [];

    function sendChatMessage(text) {
        if (!text.trim()) return;

        // Append user msg to UI
        $('#pharmacy-chat-box').append('<div class="chat-msg user-msg"><p>' + escapeHtml(text) + '</p></div>');
        $('#pharmacy-chat-input').val('');
        scrollChatToBottom();

        chatHistory.push({ role: 'user', content: text });

        // Add loading bot message
        var $loadingMsg = $('<div class="chat-msg bot-msg loading"><p>در حال جستجو و بررسی مراجع...</p></div>');
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
