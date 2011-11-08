
function showListInner(data)
{   
    var arr = {};
    
    arr.head = data[0]['data'];    
    
    if (data[0]['id_row'] !== 'null')
        arr.data = loadPluginsToc(data);
    
    // Определение значений colspan
    var i = 0;
    var left, right;
    
    for (var plugin in arr.head)
    {
        if (left !== undefined)
            right++;
        
        if (plugin == 'fireice_order')
        {
            left = i;
            right = 1;
        }
        
        i++;        
    }
    
    if (left !== undefined && right !== undefined)
    {
        arr.colspans = {};
        arr.colspans.left = left;
        arr.colspans.right = right;        
    }

    var list_temp = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/createadd_node/list_inner.html') + '</script>';      
    
    $('#dialog_id .inner').html('');
    $( list_temp ).tmpl( arr ).appendTo( '#dialog_id .inner' );      

    $('#dialog_id .inner .edit_button').click(function(){
        var act_tmp = 'node_' + action;
        if (act_tmp == 'node_create')
            act_tmp = 'node_create_2';
        $.history.load('action/' + act_tmp + '/id/' + id_action + '/module/' + id_module + '/edit_row/' + $(this).attr('row_id'));
    });
    
    $('#dialog_id .inner .add_button').click(function(){
        var act_tmp = 'node_' + action;
        if (act_tmp == 'node_create')
            act_tmp = 'node_create_2';
        $.history.load('action/' + act_tmp + '/id/' + id_action + '/module/' + id_module + '/add_row/true');
    });   
    
    $('#dialog_id .inner .delete_button').click(function(){
        deleteRow($(this).attr('row_id'));
    });     

    $('#dialog_id .inner .cancel_button').click(function(){
        $.history.load('');
    }); 
    
    $('#dialog_id .inner .orders_button').click(function(){
        updateOrders();
    });      
}

function editCreateRow(row_id, act)
{
    $('#dialog_id .inner').html(options.progress_block_html);
    
    $.ajax({
        url: options.url + 'dialog_create_edit',
        data: 'act=get_row&id=' + id_action + '&id_module=' + id_module + '&row_id=' + row_id,
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
            
            if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                
                editCreateRow_callback(answer, row_id, act);
            }             
        }
    });       
}
function editCreateRow_callback(data, row_id, act)
{
    var plugin_templates = loadPlugins(data);
    var template = getTemplate(options.assets + '/tree/templates/createadd_node/item_inner.html');    
    
    $('#dialog_id .inner').html(template);    
    
    for (var plugin in data)
    {   
        var template = '<script type="text/x-jquery-tmpl">' + plugin_templates[plugin] + '</script>';
        $( template ).tmpl( data[plugin] ).appendTo( '#dialog_id .inner .data' );
        
        // Если это какой-то особый плагин для которого нужно выполнить специальные действия, то выполнить их...
        if (typeof window['plugin' + ucfirst(data[plugin]['type'])] == 'function')
        {
            eval('plugin' + ucfirst(data[plugin]['type']) + '(data[plugin])');    
        }        
    }     

    $('#dialog_id .inner .submit_button').click(function(){
        editCreateRowSubmit(row_id, act)
    });
    
    $('#dialog_id .inner .cancel_button').click(function(){
        var act_tmp = 'node_' + action;
        if (act_tmp == 'node_create')
            act_tmp = 'node_create_2';
        $.history.load('action/' + act_tmp + '/id/' + id_action + '/module/' + id_module);
    });      
}

function editCreateRowSubmit(row_id, act)
{   
    var data, field;    

    data = '';     
    
    $('#dialog_id .inner .data input[type=text]').each(function(){        
        field = $(this).attr('name');   
        data += field + '=' + $(this).val() + '&';
    }); 
    
    $('#dialog_id .inner .data input[type=hidden]').each(function(){        
        field = $(this).attr('name');   
        data += field + '=' + $(this).val() + '&';
    });     

    $('#dialog_id .inner select').each(function(){        
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
      
    data = data.slice(0, -1);
     
    $.ajax({
        url: options.url + 'dialog_create_edit?act=edit&action=' + act + '&id=' + id_action + '&id_module=' + id_module + '&id_row=' + row_id,
        data: data,
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            editCreateRowSubmit_callback(answer);
        }
    });       
}
function editCreateRowSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Сохранено!', '#38bc50');

        var act_tmp = 'node_' + action;
        if (act_tmp == 'node_create')
            act_tmp = 'node_create_2';

        $.history.load('action/' + act_tmp + '/id/' + id_action + '/module/' + id_module);
        
    } else {
        showMessage('Ошибка!', '#ff0000');
    }    
}

function deleteRow(id_row)
{
    if (confirm('Вы уверены?'))
    {           
        $.ajax({
            url: options.url + 'dialog_create_edit?act=delete_row&id=' + id_action + '&id_module=' + id_module + '&id_row=' + id_row,
            data: '',
            type: 'post',
            async: true,
            dataType : "json",   
            cache: false,                             
            success: function (answer, textStatus) { 
                
                if (answer == 'ok') {   
                    showMessage('Удалено!', '#38bc50');        
                    showTab(id_module);
                } else if (answer == 'no_rights') {                    
                    showMessage('Нет прав!', '#ff0000');
                } else {
                    showMessage('Ошибка!', '#ff0000');
                }    
            }
        });         
    }         
}

function updateOrders()
{
    var field;
    var data = '';
    
    $('#dialog_id .inner input[name=fireice_order]').each(function(){        
        field = 'order[' + $(this).attr('id_row') + ']';   
        data += field + '=' + $(this).val() + '&';
    });   
    
    data = data.slice(0, -1);
    
    $.ajax({
        url: options.url + 'update_orders?id=' + id_action + '&id_module=' + id_module,
        data: data,
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            if (answer == 'ok') {   
                showMessage('Сохранено!', '#38bc50');        
                showTab(id_module);        
            } else {
                showMessage('Ошибка!', '#ff0000');
            } 
        }
    });      
}
