
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
        $('<div style="border: #000000 solid 1px; padding: 20px; margin-top:20px;">' + temp + '</div>').appendTo('#dialog_id .inner .data .upload_' + data['name'] + ' .images');                    
                    
        if (data['resize'] == 1)
        {
            $('#dialog_id .inner .data .upload_' + data['name'] + ' .images').children('div:last').children('div:eq(1)').children('span').hide();   
            $('#dialog_id .inner .data .upload_' + data['name'] + ' .images').children('div:last').children('div:eq(2)').children('span').hide();
            $('#dialog_id .inner .data .upload_' + data['name'] + ' .images').children('div:last').children('a.type_setting').html('Ручная настройка');
            $('#dialog_id .inner .data .upload_' + data['name'] + ' .images').children('div:last').children('a.type_setting').data('type', 'manually');
			$('#dialog_id .inner .data .upload_' + data['name'] + ' .images').children('div:last').children('input[type=hidden]').val('auto');
        }
		else
		{
		    $('#dialog_id .inner .data .upload_' + data['name'] + ' .images').children('div:last').children('input[type=hidden]').val('manually');
		}
                    
        // Вешаем обработчик нажатия кнопки
        $('#dialog_id .inner .data .upload_' + data['name'] + ' .images').children('div:last').children('div').find('button').click(function(){
            createFinderClipart(this, data['name']);                         
        });     
        
        // Вешаем обработчик нажатия ссылки
        $('#dialog_id .inner .data .upload_' + data['name'] + ' .images').children('div:last').children('a').click(function(){		   
            onClickLink(this);				
            return false;                        
        });	        
    }); 
	
    if (data['resize'] == 1)
    {
        $('#dialog_id .inner .data .upload_' + data['name'] + ' .images div a.type_setting').each(function(i){

			if (data['value'][i]['type_setting'] == 'manually')
			    next = 'auto';
		    else
			    next = 'manually';
			
			$(this).data('type', next);
        });
    }
    
	// Вешаем обработчик нажатия ссылки "Ручная настройка"
    $('#dialog_id .inner .data .upload_' + data['name'] + ' .type_setting').click(function(){        
		onClickLink(this);				
        return false;		
    }); 	
}

function onClickLink(th)
{
	if ($(th).data('type') == undefined || $(th).data('type') == 'manually') 
	{		
		$(th).parent('div').children('div').children('span').show();
        $(th).html('Автоматическая настройка');
		$(th).data('type', 'auto');
        $(th).parent('div').children('input[type=hidden]').val('manually');
	} 
	else if ($(th).data('type') == 'auto')
	{
		$(th).parent('div').children('div').children('span').hide();
        $(th).html('Ручная настройка');
		$(th).data('type', 'manually');	
        $(th).parent('div').children('input[type=hidden]').val('auto');
	}	    
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