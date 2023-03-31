(function() {
    tinymce.PluginManager.add('my_tinymce_button', function(editor, url) {
      var button_names = ['GPT続き', 'GPT要約', 'GPT関西弁', 'GPT英語', '校正'];
  
      button_names.forEach(function(name, i) {
        editor.addButton('my_tinymce_button' + (i + 1), {
          text: name,
          icon: false,
          onclick: function() {
            var selected_text = editor.selection.getContent({format : 'text'});
            var textToSend = '';
  
            switch (name) {
              case button_names[0]:
                var prompt = '200字程度でこの文章の続きを書いてください。';
                textToSend = selected_text + "\n" + prompt;
                break;
              case button_names[1]:
                var prompt = 'この文章を要約してください。要約なので、元の文章より短くして。';
                textToSend = prompt + "\n" + selected_text;
                break;
              case button_names[2]:
                var prompt = 'この文章を親しみやすい関西弁に直して。';
                textToSend = prompt + "\n" + selected_text;
                break;
              case button_names[3]:
                var prompt = 'この文章を英語に直して。';
                textToSend = prompt + "\n" + selected_text;
                break;
              case button_names[4]:
                var prompt = '改行後の文章を以下の内容で校正して。誤字脱字を直して';
                textToSend = prompt + "\n" + selected_text;
                break;
              default:
                break;
            }
  
            sendSelectedTextToGptApi(textToSend, function(response) {
              if (name !== button_names[0]) {
                editor.selection.setContent('');
              }
              insertAnimatedText(editor, response);
            });
          },
        });
      });
  
      function sendSelectedTextToGptApi(selected_text, callback) {
        var data = {
          'action': 'gpt_tiny_api',
          'get_message': selected_text
        };
        var timeout = 30000; // タイムアウト時間を30秒に設定
  
        // ローディングメッセージを表示
        editor.setProgressState(true);
  
        var xhr = jQuery.ajax({
          url: ajaxurl,
          type: 'POST',
          data: data,
          timeout: timeout, // タイムアウト時間を設定
          success: function(response) {
            // ローディングメッセージを非表示にする
            editor.setProgressState(false, 0);
  
            // GPT APIからの応答を選択された文章の後ろに追記
            var updated_content = response;
  
            // コールバック関数を呼び出して、更新されたコンテンツを返す
            callback(updated_content);
          },
          error: function(xhr, status, error) {
            // エラーが発生した場合、ローディングメッセージを非表示にする
            editor.setProgressState(false, 0);

                    if (status === 'timeout') {
                        // タイムアウトエラーの場合はアラートを表示
                        alert('応答がありません。時間を置いてから再度お試しください。');
                    } else {
                        // その他のエラーの場合はコンソールにエラーメッセージを出力
                        console.error(error);
                    }
                }
            });
        }
        

        function insertAnimatedText(editor, text, interval) {
            if (interval === undefined) {
                interval = 25;
            }
            var characters = text.split('');
            var insertCharacter = function() {
                if (characters.length > 0) {
                    editor.selection.collapse(false);
                    var span = '<span style="background-color: lightgreen;">' + characters.shift() + '</span>';
                    editor.insertContent(span);
                    setTimeout(insertCharacter, interval);
                } else {
                    var content = editor.getContent();
                    content = content.replace(/<span style="background-color: lightgreen;">(.*?)<\/span>/g, '$1');
                    editor.setContent(content);
                }
            };
            insertCharacter();
        }

    });
})();
