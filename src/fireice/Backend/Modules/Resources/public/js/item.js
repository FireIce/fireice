
function showItemInner(data)
{
    var template = getTemplate(options.assets + '/tree/templates/createadd_node/item_inner.html');    
    
    $('#dialog_id .inner').html(template);
    
    if (data['data'] != 'send_to_prove')
    {    
        var plugin_templates = loadPlugins(data['data']);

        for (var plugin in data['data'])
        {   
            var template = '<script type="text/x-jquery-tmpl">' + plugin_templates[plugin] + '</script>';
            $( template ).tmpl( data['data'][plugin] ).appendTo( '#dialog_id .inner .data' );        
            
            // Если это какой-то особый плагин для которого нужно выполнить специальные действия, то выполнить их...
            if (typeof window['plugin' + ucfirst(data['data'][plugin]['type'])] == 'function')
            {
                eval('plugin' + ucfirst(data['data'][plugin]['type']) + '(data["data"][plugin])');    
            }
        }
          
        if (data['proveeditor'] == 'show')
        {
            $('#dialog_id .inner .proveeditor_button').show().click(function(){            
                proveEditorAction();
            });
        }
    
        if (data['provemaineditor'] == 'show')
        {
            $('#dialog_id .inner .provemaineditor_button').show().click(function(){            
                proveMainEditorAction();
            });    
        }
    
        if (data['sendtoproveeditor'] == 'show')
        {
            $('#dialog_id .inner .sendtoproveeditor_button').show().click(function(){            
                sendToProveEditorAction();
            });     
        }
    
        if (data['sendtoprovemaineditor'] == 'show')
        {
            $('#dialog_id .inner .sendtoprovemaineditor_button').show().click(function(){            
                sendToProveMainEditorAction();
            });
        }
    
        if (data['returnwriter'] == 'show')
        {
            $('#dialog_id .inner .returnwriter_button').show().click(function(){            
                returnWriterAction();
            });    
        }
    
        if (data['returneditor'] == 'show')
        {
            $('#dialog_id .inner .returneditor_button').show().click(function(){            
                returnEditorAction();
            });     
        }    
                    
        $('#dialog_id .history_button').click(function(){ 
        
       
            $.history.load('action/' + getNodeAction(action) + '/id/' + id_action + '/module/' + id_module + '/history/true'); 
        });
        $('#dialog_id .submit_button').click(function(){
            editSubmit();
        });
        $('#dialog_id .cancel_button').click(function(){
            $.history.load('');
        });
    }
    else 
    {
        $('#dialog_id .inner').html('Отправлено на подтверждение');
    }
}

function editSubmit()
{   
    var data, field;
    
    data = '';  
    
    $('#dialog_id .inner input[type=text]').each(function(){        
        field = $(this).attr('name');   
        data += field + '=' + $(this).val() + '&';
    }); 
    
    $('#dialog_id .inner textarea').each(function(){        
        field = $(this).attr('name');     
        
        if ($(this).attr('ckeditor') == 'true')
        {
            var tmp = CKEDITOR.instances[field];
            data += field + '=' + encodeURIComponent(tmp.getData()) + '&';    
        }
        else
        {
            data += field + '=' + encodeURIComponent($(this).val()) + '&';
        }
    });   
    
    $('#dialog_id .inner input[type=checkbox]').each(function(){        
        field = $(this).attr('name');  
        data += field + '=' + (($(this).is(':checked'))?'1':'0') + '&';
    });   
    
    $('#dialog_id .inner input[type=radio]:checked').each(function(){        
        field = $(this).attr('name');   
        data += field + '=' + $(this).val() + '&';
    });     

    $('#dialog_id select').each(function(){        
        field = $(this).attr('name');
        data += field + '=' + $(this).val() + '&';
    });
   
    data = data.slice(0, -1); 

    $.ajax({
        url: options.url + 'dialog_create_edit?act=edit&action=' + action + '&id=' + id_action + '&id_module=' + id_module+ '&language=' + first_language,
        data: data,
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            if (answer === 'ok') {
                showMessage('Сохранено!', '#38bc50');    	                
                getShowNodes(); 
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                
                showMessage('Ошибка!', '#ff0000');
                getEditData_callback(data);
            }             
        }
    });        
}    

function getHistory(id_mod, language)
{           
    id_module = id_mod;
    
    ckeditorInstancesDestroy();
  
    $('#dialog_id .tab').removeClass('current');
    $('#dialog_id .form .tab').each(function(){
        if ($(this).attr('id_module') == id_mod)
        {
            $(this).addClass('current');
        }
    });      
    
    var template = getTemplate(options.assets + '/tree/templates/createadd_node/item_inner.html');      
    $('#dialog_id .inner').html(template);
    
    $('#dialog_id .data').hide();
    $('#dialog_id .history').html(options.progress_block_html).show();
    
    $('#dialog_id .history_button').hide();
    $('#dialog_id .back_button').show();   
   
    $('#dialog_id .back_button').click(function(){
             
        $('#dialog_id .back_button').hide(); 
        $('#dialog_id .history_button').show(); 

      
        $.history.load('action/' + getNodeAction(action) + '/id/' + id_action + '/module/' + id_module +'/language/'+language);                              
    
    }); 
    
    $('#dialog_id .cancel_button').click(function(){
        $.history.load('');
    });
    
    $.ajax({
        url: options.url + 'get_history?id=' + id_action + '&id_module=' + id_module + '&language=' + language,
        data: '',
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            getHistory_callback(answer);
        }
    });      
}
function getHistory_callback(answer)
{ 
    var template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/createadd_node/history_block.html') + '</script>';
    
    $('#dialog_id .history').html('');
    
    $(template).tmpl(answer).appendTo('#dialog_id .history');
}

// Подтвердить на уровне редактора        
function proveEditorAction()
{
    $.ajax({
        url: options.url + 'prove_editor',
        data: 'id=' + id_action + '&id_module=' + id_module,
        async: false,
        dataType : "json",   
        cache: false, 
        type: 'POST',
        success: function (answer, textStatus) { 
            if (answer == 'ok')
            {
                showMessage('Материал подтверждён!', '#38bc50');
                $('#dialog_id .inner .returnwriter_button').hide();
                $('#dialog_id .inner .proveeditor_button').hide();
            } 
            else if (answer == 'no_save')
            {
                showMessage('Материал не отправлялся вам на подтверждение журналистом!', '#ff0000');
            }
            else 
            {
                showMessage('Ошибка!', '#ff0000');
            } 
        }
    });    
}
 
// Подтвердить на уровне главного редактора 
function proveMainEditorAction()
{
    $.ajax({
        url: options.url + 'prove_maineditor',
        data: 'id=' + id_action + '&id_module=' + id_module,
        async: false,
        dataType : "json",   
        cache: false,   
        type: 'POST',        
        success: function (answer, textStatus) { 
            if (answer == 'ok')
            {
                showMessage('Материал подтверждён и опубликован!', '#38bc50');
                $('#dialog_id .inner .returneditor_button').hide();
                $('#dialog_id .inner .provemaineditor_button').hide();
            } 
            else if (answer == 'no_save')
            {
                showMessage('Материал не отправлялся вам на подтверждение редактором!', '#ff0000');
            }
            else 
            {
                showMessage('Ошибка!', '#ff0000');
            } 
        }
    });      
}
    
// Отправить на утверждение редактору
function sendToProveEditorAction()
{
    $.ajax({
        url: options.url + 'sendtoprove_editor',
        data: 'id=' + id_action + '&id_module=' + id_module,
        async: false,
        dataType : "json",   
        cache: false,  
        type: 'POST',        
        success: function (answer, textStatus) {                 
            if (answer == 'ok')
            {
                showMessage('Отправлено на подтверждение!', '#38bc50');
                $('#dialog_id .inner').html('Материал отправлен на подтверждение!');
            } 
            else if (answer == 'no_save')
            {
                showMessage('Сохраните, прежде чем отправлять!', '#ff0000');
            }
            else 
            {
                showMessage('Ошибка!', '#ff0000');
            }  
        }
    }); 
}

// Отправить на утверждение главному редактору
function sendToProveMainEditorAction()
{
    $.ajax({
        url: options.url + 'sendtoprove_maineditor',
        data: 'id=' + id_action + '&id_module=' + id_module,
        async: false,
        dataType : "json",   
        cache: false,   
        type: 'POST',        
        success: function (answer, textStatus) { 
            if (answer == 'ok')
            {
                showMessage('Отправлено на подтверждение!', '#38bc50');
                $('#dialog_id .inner').html('Материал отправлен на подтверждение!');
            } 
            else if (answer == 'no_save')
            {
                showMessage('Сохраните, прежде чем отправлять!', '#ff0000');
            }
            else 
            {
                showMessage('Ошибка!', '#ff0000');
            }  
        }
    });    
}
    
// Вернуть на доработку писателю
function returnWriterAction()
{    
    ckeditorInstancesDestroy();
    
    $('#dialog_id .inner').html(options.progress_block_html);
    
    var template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/createadd_node/return_block.html') + '</script>';    
    
    $('#dialog_id .inner').html('');

    $(template).tmpl({}).appendTo('#dialog_id .inner');
    
    $('#dialog_id .inner .return_button').click(function(){
        
        $.ajax({
            url: options.url + 'return_writer',
            data: 'id=' + id_action + '&id_module=' + id_module + '&comment=' + $('#dialog_id .inner .reason').val(),
            async: false,
            dataType : "json",   
            cache: false,   
            type: 'POST',            
            success: function (answer, textStatus) { 
                if (answer == 'ok')
                {
                    showMessage('Материал отправлен на дорботку!', '#38bc50');
                    $('#dialog_id .inner .returnwriter_button').hide();
                    $('#dialog_id .inner .proveeditor_button').hide();
                } 
                else if (answer == 'no_send')
                {
                    showMessage('Материал не отправлялся вам на подтверждение журналистом!', '#ff0000');
                }
                else 
                {
                    showMessage('Ошибка!', '#ff0000');
                } 
            }
        });         
        
    });
    
    $('#dialog_id .inner .back_button').click(function(){
        showTab(id_module);  
    });         
}

// Вернуть на доработку редактору
function returnEditorAction()
{
    ckeditorInstancesDestroy();
    
    $('#dialog_id .inner').html(options.progress_block_html);
    
    var template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/createadd_node/return_block.html') + '</script>';    
    
    $('#dialog_id .inner').html('');

    $(template).tmpl({}).appendTo('#dialog_id .inner');
    
    $('#dialog_id .inner .return_button').click(function(){    
    
        $.ajax({
            url: options.url + 'return_editor',
            data: 'id=' + id_action + '&id_module=' + id_module + '&comment=' + $('#dialog_id .inner .reason').val(),
            async: false,
            dataType : "json",   
            cache: false,     
            type: 'POST',            
            success: function (answer, textStatus) { 
                if (answer == 'ok')
                {
                    showMessage('Материал отправлен на дорботку!', '#38bc50');
                    $('#dialog_id .inner .returneditor_button').hide();
                    $('#dialog_id .inner .provemaineditor_button').hide();
                } 
                else if (answer == 'no_send')
                {
                    showMessage('Материал не отправлялся вам на подтверждение редактором!', '#ff0000');
                }
                else 
                {
                    showMessage('Ошибка!', '#ff0000');
                } 
            }
        });  
     
    });
    
    $('#dialog_id .inner .back_button').click(function(){
        showTab(id_module);  
    });      
}
