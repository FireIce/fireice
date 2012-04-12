///////////////////////////////////////////////////////////////////////
//                          Полезные функции                         //
///////////////////////////////////////////////////////////////////////

var plugin_templates = [];

// Ставит куку
function setCookie(name, value, days)
{
    if (days) 
    {
        var date = new Date();
        date.setTime(date.getTime() + parseInt(days * 24 * 60 * 60 * 1000));
        var expires = '; expires=' + date.toGMTString();
    
    } else {
        var expires = '';
    }  
  
    document.cookie = name + '=' + value + expires + '; path=/';
}

// Читает куку
function getCookie(name)
{
    name = name + '=';
    list = document.cookie.split(';');
    for (i = 0; i < list.length; i++)
    {
        c = list[i];
        while (c.charAt(0) == ' ') c = c.substring (1, c.length);
        if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
    }
    return false;
}

// Добавления события (для контекстного меню (в jquery нет oncontextmenu))
function addEvent(elem, evType, fn) 
{
    if (elem.addEventListener) 
    {
        elem.addEventListener(evType, fn, false);
    }
    else if (elem.attachEvent) 
    {
        elem.attachEvent('on' + evType, fn)
    }
    else 
    {
        elem['on' + evType] = fn
    }
}
                                                  
// Функция для определения координат указателя мыши
function defPosition(event) 
{
    var x = y = 0;
    if (document.attachEvent != null) 
    { 
        // Internet Explorer & Opera
        x = window.event.clientX + (document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft);
        y = window.event.clientY + (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);
    } 
    else if (!document.attachEvent && document.addEventListener) 
    { 
        // Gecko
        x = event.clientX + window.scrollX;
        y = event.clientY + window.scrollY;
    } 
    else 
    {
    // Do nothing
    }
    return {
        x:x, 
        y:y
    };
} 

// Вывод сообщения
function showMessage(message, color)
{
    $('#message_id').remove();
    
    $message_div = '<div id="message_id" style="padding: 10px; display: none; position: fixed; left: 2px; top: 2px; width: 200px; height: 100px; background-color: ' + color + '; border: #000000 solid 1px"><table height="100%"><tr><td align="center" style="vertical-align: middle; color: #ffffff;">' + message + '</td></tr></table></div>';
        
    $('body').append($message_div);  
    
    $('#message_id').fadeIn(500, function(){
        
        setTimeout('$("#message_id").fadeOut(500, function(){ $(this).remove(); })', 5000);
        
    })    
}

// Вывод сообщения об ошибке и на главную
function errorAndToMain(message)
{
    $('#progress_id').hide();
    $.history.load('');
    showMessage(message, '#ff0000');    
}

function getTemplate(url)
{   
    var template;
    
    $.ajax({
        url: url, 
        async: false,
        dataType : "html",   
        cache: false,                                // Можно будет потом включить !!!
        success: function (answer, textStatus) { 

            template = answer;
        }
    });   
    
    return template;
}

function loadPlugins(data)
{   
    var templates = [];
     
    for (var plugin in data)
    {   
        var temp;
        
        if (plugin_templates[data[plugin]['type']] == undefined)
        {
            $.ajax({
                url: options.assets + '/fireiceplugins' + data[plugin]['type'] + '/templates/editadd.html', 
                async: false,
                dataType : "html",   
                cache: false,                                // Можно будет потом включить !!!
                success: function (answer, textStatus) { 
 
                    temp = answer;
                    plugin_templates[data[plugin]['type']] = temp;   
                }
            });  
            
        } else {
            temp = plugin_templates[data[plugin]['type']];
        }
        
        templates[plugin] = temp;
    }

    return templates;
}

function loadPluginsToc(data)
{
    var templates = [];
            
    $('body').append('<div id="temporary_id" style="display: none;"></div>');
    
    for (var key in data)
    {
        templates[key] = new Object(); 
        
        templates[key]['id_row'] = data[key]['id_row'];
        templates[key]['data'] = {};         
        
        for (var plugin in data[key]['data'])
        {         
            // Если это рабочий плагин "Порядок", то обработаем его отдельно.
            // Шаблон пока пропишем жёстко в коде...
            if (plugin == 'fireice_order')
            {
                answer = '<script type="text/x-jquery-tmpl"><input id_row="' + data[key]['id_row'] + '" type="text" name="${ name }" value="${ value }"></script>';

                $('#temporary_id').html('');
                $( answer ).tmpl( data[key]['data'][plugin] ).appendTo('#temporary_id');

                templates[key]['data'][data[key]['data'][plugin]['name']] = $('#temporary_id').html();                
                
                continue;
            }
                 
            var temp;     
                 
            if (plugin_templates[data[key]['data'][plugin]['type'] + '_toc'] == undefined)
            {
                $.ajax({
                    url: options.assets + '/fireiceplugins' + data[key]['data'][plugin]['type'] + '/templates/toc_content.html', 
                    async: false,
                    dataType : "html",   
                    cache: false,                                // Можно будет потом включить !!!
                    success: function (answer, textStatus) {                 
                       
                        temp = answer;
                        plugin_templates[data[key]['data'][plugin]['type'] + '_toc'] = temp;
                    }
                });     
                
            } else {
                temp = plugin_templates[data[key]['data'][plugin]['type'] + '_toc'];
            }
            
            temp = '<script type="text/x-jquery-tmpl">' + temp + '</script>';

            $('#temporary_id').html('');
            $( temp ).tmpl( data[key]['data'][plugin] ).appendTo('#temporary_id');

            templates[key]['data'][data[key]['data'][plugin]['name']] = $('#temporary_id').html();            
        }
    }
    
    $('#temporary_id').remove();

    return templates;
}

function updateUrl(action, id)
{
    $.history.load('action/' + action + '/id/' + id);
}

function showOpenDialog(hash)
{   
    var params = [];
    
    if (hash.length > 0)
    {            
        var tmp = hash.split('/');
        
        for (var i=0; i<tmp.length; i+=2)
        {               
            params[tmp[i]] = tmp[i+1];
        }
    }
           
    if (params['action'] == 'node_create_1')
    {
        showSelectModule(params['id'], 'create_1');
    }
    else if (params['action'] == 'node_create_2')
    {    
        if (params['id'] != id_action)
            showEdit(params['id'], 'create');
        
        if (params['history'] != 'true')
        {        
            var is_show_row = (params['edit_row'] != undefined || params['add_row'] != undefined) ? true : false;
            if (params['module'] != undefined)
                showTab(params['module'], params['language'], is_show_row);
            else
                showTab(first_tab, first_language, is_show_row);       
        
            if (params['edit_row'] != undefined)
                editCreateRow(params['edit_row'], 'edit', first_language);
        
            if (params['add_row'] == 'true')
                editCreateRow(-1, 'add', first_language);  
        }
        else
        {
            getHistory(params['module'], first_language);    
        }          
    }
    else if (params['action'] == 'node_edit')
    {   
        if (params['id'] != id_action || params['action'] != 'node_' + action)
            showEdit(params['id'], 'edit');  
        
        if (params['history'] != 'true')
        {
            var is_show_row = (params['edit_row'] != undefined || params['add_row'] != undefined) ? true : false;
            if (params['module'] != undefined)
                showTab(params['module'], params['language'], is_show_row);
            else
                showTab(first_tab, first_language, is_show_row);       

            if (params['edit_row'] != undefined)
                editCreateRow(params['edit_row'], 'edit', first_language);
        
            if (params['add_row'] == 'true')
                editCreateRow(-1, 'add', first_language);     
        }
        else
        {
            getHistory(params['module'], params['language']);    
        }      
    }
    else if (params['action'] == 'users_list')
    {
        getUsers();
    }
    else if (params['action'] == 'user_add')
    {
        addUser();
    }    
    else if (params['action'] == 'user_edit' && params['id'] != undefined)
    {
        editUser( params['id'] );
    }      
    else if (params['action'] == 'groups_list')
    {
        getGroups();
    }   
    else if (params['action'] == 'group_add')
    {
        addGroup();
    }    
    else if (params['action'] == 'group_edit' && params['id'] != undefined)
    {
        editGroup( params['id'] );
    }
    else if (params['action'] == 'rights_list' && params['id'] != undefined)
    {   
        var module = rights( params['id'] );   

        if (false !== module) {
            if (params['module'] != undefined)
                showModuleRights(params['module']);
            else
                showModuleRights(module);  
        }
    }
    else if (params['action'] == 'edit_rights' && params['id'] != undefined && params['module'] != undefined && params['user'] != undefined)
    {
        editRights(params['id'], params['module'], params['user']);        
    } 
    else if (params['action'] == 'messages_list')
    {
        getMessages();
    }  
    else if (params['action'] == 'message' && params['id'] != undefined)
    {
        getMessage(params['id']);
    }     
}
 
// Уничнтожает все ckeditor-ы
function ckeditorInstancesDestroy()
{
    if (CKEDITOR.instances !== undefined)
    {
        for (var key in CKEDITOR.instances)
        {
            var tmp = CKEDITOR.instances[key];
            tmp.destroy();         
        }   
    }
}

// Преобразует первый символ строки в верхний регистр
function ucfirst(str)
{
    var f = str.charAt(0).toUpperCase();
    return f + str.substr(1, str.length-1);    
}

// Установка title
function setTitle(text)
{
    var title = $('title').html();
    
    title = title.replace(new RegExp('^(\\S+?\\s: backoffice)(.*?)$', 'g'), '$1');

    if (text != '')
        $('document title').html(title + ' : ' + text);
    else
        $('document title').html(title);
}

//Возвращает строку для .history.load()
function getNodeAction(action)
{
    var act_tmp = 'node_' + action;
    if (act_tmp == 'node_create')
        act_tmp = 'node_create_2';

    return act_tmp;
}
