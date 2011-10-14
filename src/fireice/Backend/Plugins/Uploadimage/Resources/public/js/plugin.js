

function pluginUploadimage(data)
{
    // Обработчик нажатия кнопки выбора картинки
    $('#dialog_id .inner .data .upload_' + data['name'] + ' .upload_select').click(function(){                 
        createFinder(this, data['name']); 
    });       
                
    // Обработчик нажатия кнопки добавления картинки
    $('#dialog_id .inner .data .upload_' + data['name'] + ' .add_image').click(function(){
                    
        // Получаем шаблон
        var temp = $('#dialog_id .inner .data .upload_' + data['name'] + ' .images div:last').html();

        // Меняем значения 
        var myRe = new RegExp('name="' + data['name'] + '\\[(\\d)+\\]\\[alt\\]"');
        var myArray = myRe.exec(temp);       
        var next_id = parseInt(myArray[1]) + 1;
                    
        temp = temp.replace(new RegExp('name="' + data['name'] + '\\[' + (next_id-1) + '\\]', 'g'),'name="' + data['name'] + '[' + next_id + ']');
        temp = temp.replace('class="uploadimage_' + (next_id-1) + '"', 'class="uploadimage_' + next_id + '"');
        temp = temp.replace('input_class="uploadimage_' + (next_id-1) + '"', 'input_class="uploadimage_' + next_id + '"');
        temp = temp.replace(new RegExp('value=".*?"', 'g'), 'value=""');

        // Вставляем на страницу
        $('<div>' + temp + '</div>').appendTo('#dialog_id .inner .data .upload_' + data['name'] + ' .images');                    
                    
        // Вешаем обработчик нажатия кнопки
        $('#dialog_id .inner .data .upload_' + data['name'] + ' .images div:last').children('button').click(function(){                        
	        createFinder(this, data['name']);                         
        });
    }); 
}

function createFinder(th, plugin_name)
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