

function pluginClipart(data)
{
    // Обработчик нажатия кнопки выбора картинки
    $('#dialog_id .inner .data .upload_' + data['name'] + ' .upload_select').click(function(){                 
        createFinderClipart(this, data['name']); 
    });       
                
    // Обработчик нажатия кнопки добавления картинки
    $('#dialog_id .inner .data .upload_' + data['name'] + ' .add_image').click(function(){
                    
        // Получаем шаблон
        var temp = $('#dialog_id .inner .data .upload_' + data['name'] + ' .images').children('div:last').html();
 
        // Меняем значения 
        var myRe = new RegExp('name="' + data['name'] + '\\[(\\d)+\\]\\[original_alt\\]"');
        var myArray = myRe.exec(temp);       
        var next_id = parseInt(myArray[1]) + 1;
                    
        temp = temp.replace(new RegExp('name="' + data['name'] + '\\[' + (next_id-1) + '\\]', 'g'),'name="' + data['name'] + '[' + next_id + ']');
        temp = temp.replace('class="clipart_orig_' + (next_id-1) + '"', 'class="clipart_orig_' + next_id + '"');
        temp = temp.replace('input_class="clipart_orig_' + (next_id-1) + '"', 'input_class="clipart_orig_' + next_id + '"');		
        temp = temp.replace('class="clipart_big_' + (next_id-1) + '"', 'class="clipart_big_' + next_id + '"');
        temp = temp.replace('input_class="clipart_big_' + (next_id-1) + '"', 'input_class="clipart_big_' + next_id + '"');
        temp = temp.replace('class="clipart_small_' + (next_id-1) + '"', 'class="clipart_small_' + next_id + '"');
        temp = temp.replace('input_class="clipart_small_' + (next_id-1) + '"', 'input_class="clipart_small_' + next_id + '"');		
		
        temp = temp.replace(new RegExp('value=".*?"', 'g'), 'value=""');

        // Вставляем на страницу
        $('<div style="border: #000000 solid 1px; height: 90px; padding: 20px; margin-top:20px;">' + temp + '</div>').appendTo('#dialog_id .inner .data .upload_' + data['name'] + ' .images');                    
                    
        // Вешаем обработчик нажатия кнопки
        $('#dialog_id .inner .data .upload_' + data['name'] + ' .images').children('div:last').children('div').children('button').click(function(){
            createFinderClipart(this, data['name']);                         
        });
    }); 
}

function createFinderClipart(th, plugin_name)
{
    var finder = new CKFinder();
	finder.basePath = '../';
    finder.startupPath = "Images:/";
	finder.selectActionFunction = function(fileUrl, data2)
    { 
        $('#dialog_id .inner .data .upload_' + plugin_name + ' .' + data2.selectActionData).val(fileUrl);
    };
	finder.selectActionData = $(th).attr('input_class');
	finder.selectThumbnailActionFunction = function(){ return false; };
	finder.popup();        
}