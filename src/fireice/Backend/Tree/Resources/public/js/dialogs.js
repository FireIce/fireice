
///////////////////////////////////////////////////////////////////////
//                            Пользователи                           //
///////////////////////////////////////////////////////////////////////

var id_user_edit;
var id_group_edit;
 
function getUsers()
{   
    setTitle('Список пользователей');
    
    action = undefined;                   
    id_action = undefined;	    
    
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');    
      
    $.ajax({
        url: options.url + 'users/getusers',
        data: '',
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
            
            if (answer === 'error') {
                errorAndToMain('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {
                getUsersData_callback(answer);
            }
        }
    });     
}
function getUsersData_callback(answer)
{         
    var template = getTemplate(options.assets + '/tree/templates/users/users.html');
    var list_template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/users/users_list.html') + '</script>';    
    
    $('#dialog_id').html('');
    $( template ).tmpl( {add: answer.edit_right} ).appendTo('#dialog_id');
    
    for (var i=0; i<answer.list.length; i++) {
        answer.list[i]['edit_right'] = answer.edit_right;
        answer.list[i]['delete_right'] = answer.delete_right;
    }        
    
    $( list_template ).tmpl( answer.list ).appendTo( '#users_list_id' );
    
    $('#progress_id').hide();
    
    $('#dialog_id .edit_user').click(function(){     
        $.history.load('action/user_edit/id/' + $(this).attr('id_user'));
    });
    $('#dialog_id .del_user').click(function(){
        deleteUser( $(this).attr('id_user') );        
    });    
    $('#dialog_id .add_button').click(function(){
        $.history.load('action/user_add');
    });
    $('#dialog_id .cancel_button').click(function(){
        $.history.load('');
    });    
    
    $('#dialog_id').slideDown(100);
}



function editUser(id_user)
{
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');   
    
    id_user_edit = id_user; 
        
    answer = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/users/createedit_user.html') + '</script>';
    
    var arr = new Object();
    
    arr.action_title = 'Редактирование пользователя';
    
    $( answer ).tmpl( arr ).appendTo( '#dialog_id' );
     
    $.ajax({
        url: options.url + 'users/getuserdata',
        data: 'id=' + id_user_edit,
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            if (answer === 'error') {
                errorAndToMain('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                
                getUserData_callback(answer);
            }
        }
    });    
}
function getUserData_callback(answer)
{   
    setTitle('Редактирование пользователя ' + answer.login.value);
    
    var plugin_templates = loadPlugins(answer);
          
    for (var plugin in answer)
    {   
        var template = '<script type="text/x-jquery-tmpl">' + plugin_templates[plugin] + '</script>';
        $( template ).tmpl( answer[plugin] ).appendTo( '#dialog_id .data');
    }
	        
    $('#progress_id').hide();
		
    $('#dialog_id').slideDown(100);
    
    $('#dialog_id .submit_button').click(function(){
        editUserSubmit();
    });
    $('#dialog_id .cancel_button').click(function(){
        $.history.load('action/users_list');
    });    
}
function editUserSubmit()
{    
    data = '';  
    
    $('#dialog_id input[type=text]').each(function(){        
        field = $(this).attr('name');   
        data += field + '=' + $(this).val() + '&';
    });   
    
    $('#dialog_id select').each(function(){        
        field = $(this).attr('name');
        data += field + '=' + $(this).val() + '&';
    });    
    
    data += 'id=' + id_user_edit;
    
    $.ajax({
        url: options.url + 'users/edit',
        data: data,
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            editUserSubmit_callback(answer);
        }
    });    
}
function editUserSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Сохранено!', '#38bc50');
    	                
        $.history.load('action/users_list'); 
        
    } else {
        showMessage('Ошибка!', '#ff0000');
    }    
}


function addUser()
{
    setTitle('Добавление нового пользователя');
    
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');   
        
    answer = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/users/createedit_user.html') + '</script>';
    
    var arr = new Object();
    
    arr.action_title = 'Создание нового пользователя';

    $( answer ).tmpl( arr ).appendTo( '#dialog_id' );
   
    $.ajax({
        url: options.url + 'users/getuserdata',
        data: 'id=-1',
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            if (answer === 'error') {
                errorAndToMain('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                    
                getCreateUserData_callback(answer);
            }
        }
    });     
}
function getCreateUserData_callback(answer)
{ 
    var plugin_templates = loadPlugins(answer);
          
    for (var plugin in answer)
    {   
        var template = '<script type="text/x-jquery-tmpl">' + plugin_templates[plugin] + '</script>';
        $( template ).tmpl( answer[plugin] ).appendTo( '#dialog_id .data');
    }    	    
    
    $('#progress_id').hide();
		
    $('#dialog_id').slideDown(100);	    
    
    $('#dialog_id .submit_button').click(function(){
        addUserSubmit();
    });
    $('#dialog_id .cancel_button').click(function(){
        $.history.load('action/users_list');
    });         
}
function addUserSubmit()
{
    data = '';  
    
    $('#dialog_id input[type=text]').each(function(){        
        field = $(this).attr('name');   
        data += field + '=' + $(this).val() + '&';
    });     
    
    $('#dialog_id select').each(function(){        
        field = $(this).attr('name');
        data += field + '=' + $(this).val() + '&';
    });      
    
    data = data.slice(0, -1);
      
    $.ajax({
        url: options.url + 'users/add',
        data: data,
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            addUserSubmit_callback(answer);
        }
    });    
}
function addUserSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Пользователь добавлен!', '#38bc50');
    	                
        $.history.load('action/users_list'); 
        
    } else {
        showMessage('Ошибка!', '#ff0000');
    }     
}

function deleteUser($id_user)
{
    if (confirm('Вы уверены?'))
    {        
        $.ajax({
            url: options.url + 'users/delete',
            data: 'id=' + $id_user,
            type: 'post',
            async: true,
            dataType : "json",   
            cache: false,                             
            success: function (answer, textStatus) { 
                
                deleteUser_callback(answer);
            }
        });         
    }
}
function deleteUser_callback(answer)
{    
    if (answer == 'ok') {   
        showMessage('Пользователь удалён!', '#38bc50');     	                
        getUsers(); 
    } else if (answer == 'no_rights') {
        showMessage('Нет прав!', '#ff0000');     
    } else {
        showMessage('Ошибка!', '#ff0000');
    }     
}


///////////////////////////////////////////////////////////////////////
//                               Группы                              //
///////////////////////////////////////////////////////////////////////

function getGroups()
{
    setTitle('Список групп');
    
    action = undefined;                   
    id_action = undefined;    
    
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');    
      
    $.ajax({
        url: options.url + 'groups/getgroups',
        data: '',
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            if (answer === 'error') {
                errorAndToMain('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {
                getGroupsData_callback(answer);
            }            
        }
    });     
}
function getGroupsData_callback(answer)
{   
    var template = getTemplate(options.assets + '/tree/templates/groups/groups.html');
    var list_template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/groups/groups_list.html') + '</script>';    
    
    $('#dialog_id').html('');
    $( template ).tmpl( {add: answer.edit_right} ).appendTo( '#dialog_id' );
    
    for (var i=0; i<answer.list.length; i++) {
        answer.list[i]['edit_right'] = answer.edit_right;
        answer.list[i]['delete_right'] = answer.delete_right;
    }    
    
    $( list_template ).tmpl( answer.list ).appendTo( '#groups_list_id' );
    
    $('#progress_id').hide();
    
    $('#dialog_id .edit_group').click(function(){
        $.history.load('action/group_edit/id/' + $(this).attr('id_group'));      
    });
    $('#dialog_id .del_group').click(function(){
        deleteGroup( $(this).attr('id_group') );        
    });    
    $('#dialog_id .add_button').click(function(){
        $.history.load('action/group_add');
    });
    $('#dialog_id .cancel_button').click(function(){
        $.history.load('');
    });    
    
    $('#dialog_id').slideDown(100);    
}
function editGroup(id_group)
{
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');  
    
    id_group_edit = id_group;
        
    template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/groups/createedit_group.html') + '</script>';
    
    var arr = new Object();
    
    arr.action_title = 'Редактирование Группы';
    
    $( template ).tmpl( arr ).appendTo( '#dialog_id' );
   
    $.ajax({
        url: options.url + 'groups/getgroupdata',
        data: 'id=' + id_group,
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) {                             
            
            if (answer === 'error') {
                errorAndToMain('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                
                getGroupData_callback(answer);
            }            
        }
    });    
}
function getGroupData_callback(answer)
{        
    setTitle('Редактирование группы "' + answer.title.value + '"');
    
    var plugin_templates = loadPlugins(answer);
          
    for (var plugin in answer)
    {   
        var template = '<script type="text/x-jquery-tmpl">' + plugin_templates[plugin] + '</script>';
        $( template ).tmpl( answer[plugin] ).appendTo( '#dialog_id .data');
    }
	        
    $('#progress_id').hide();
		
    $('#dialog_id').slideDown(100);
    
    $('#dialog_id .submit_button').click(function(){
        editGroupSubmit();
    });
    $('#dialog_id .cancel_button').click(function(){
        $.history.load('action/groups_list');
    });     
}
function editGroupSubmit()
{
    data = '';  
    
    $('#dialog_id input[type=text]').each(function(){        
        field = $(this).attr('name');   
        data += field + '=' + $(this).val() + '&';
    });   
    
    $('#dialog_id select').each(function(){        
        field = $(this).attr('name');
        data += field + '=' + $(this).val() + '&';
    });    
    
    $('#dialog_id input[type=checkbox]').each(function(){        
        field = $(this).attr('name');  
        data += field + '=' + (($(this).is(':checked'))?'1':'0') + '&';
    });      
    
    data += 'id=' + id_group_edit;    
     
    $.ajax({
        url: options.url + 'groups/edit',
        data: data,
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            editGroupSubmit_callback(answer);
        }
    });    
}
function editGroupSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Сохранено!', '#38bc50');
    	                
        $.history.load('action/groups_list'); 
        
    } else {
        showMessage('Ошибка!', '#ff0000');
    }             
}

function addGroup()
{
    setTitle('Добавление новой группы');
    
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');     
        
    template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/groups/createedit_group.html') + '</script>';
    
    var arr = new Object();
    
    arr.action_title = 'Добавление новой Группы';
    
    $( template ).tmpl( arr ).appendTo( '#dialog_id' );
   
    $.ajax({
        url: options.url + 'groups/getgroupdata',
        data: 'id=-1',
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                                        
            if (answer === 'error') {
                errorAndToMain('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                
                getCreateGroupData_callback(answer);
            }                 
        }
    });    
}
function getCreateGroupData_callback(answer)
{
    var plugin_templates = loadPlugins(answer);
          
    for (var plugin in answer)
    {   
        var template = '<script type="text/x-jquery-tmpl">' + plugin_templates[plugin] + '</script>';
        $( template ).tmpl( answer[plugin] ).appendTo( '#dialog_id .data');
    }
	        
    $('#progress_id').hide();
		
    $('#dialog_id').slideDown(100);
    
    $('#dialog_id .submit_button').click(function(){
        addGroupSubmit();
    });
    $('#dialog_id .cancel_button').click(function(){
        $.history.load('action/groups_list');
    });     
}

function addGroupSubmit()
{
    data = '';  
    
    $('#dialog_id input[type=text]').each(function(){        
        field = $(this).attr('name');   
        data += field + '=' + $(this).val() + '&';
    });   
    
    $('#dialog_id select').each(function(){        
        field = $(this).attr('name');
        data += field + '=' + $(this).val() + '&';
    });    
    
    $('#dialog_id input[type=checkbox]').each(function(){        
        field = $(this).attr('name');  
        data += field + '=' + (($(this).is(':checked'))?'1':'0') + '&';
    });      
    
    data = data.slice(0, -1);    
    
    $.ajax({
        url: options.url + 'groups/add',
        data: data,
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) {                             
            
            if (answer === 'error') {
                errorAndToMain('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                
                addGroupSubmit_callback(answer);
            }            
        }
    });    
}
function addGroupSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Сохранено!', '#38bc50');
    	                
        $.history.load('action/groups_list'); 
        
    } else {
        showMessage('Ошибка!', '#ff0000');
    }     
}


function deleteGroup(id_group)
{
    if (confirm('Вы уверены?'))
    {         
        $.ajax({
            url: options.url + 'groups/delete',
            data: 'id=' + id_group,
            type: 'post',
            async: true,
            dataType : "json",   
            cache: false,                             
            success: function (answer, textStatus) { 
                
                deleteGroup_callback(answer);
            }
        });        
    }
}
function deleteGroup_callback(answer)
{    
    if (answer == 'ok')
    {   
        showMessage('Группа удалена!', '#38bc50');     	                
        getGroups();
    } else if (answer == 'no_rights') {
        showMessage('Нет прав!', '#ff0000');
    } else {
        showMessage('Ошибка!', '#ff0000');
    }            
}


///////////////////////////////////////////////////////////////////////
//                               Права                               //
///////////////////////////////////////////////////////////////////////

function rights(id)
{   
    id_action = id;	    
    
    $('#progress_id').show();
    $('#dialog_id').hide();    
    $('#dialog_id').html('');
    
    var tmp;
    
    $.ajax({
        url: options.url + 'rights/getmodules',
        data: 'id=' + id,
        async: false,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                                       
            if (answer === 'error') {
                errorAndToMain('Ошибка!', '#ff0000');
                tmp = false;
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
                tmp = false;
            } else {                
                tmp = getRightsData_callback(answer);
            }               
        }
    });     

    return tmp;
}

function getRightsData_callback(answer)
{  
    if (answer == 'error')
    {   
        showMessage('Ошибка!', '#ff0000');    	  
        $('#progress_id').hide();
        $.history.load('');

        return false;
    }      

    setTitle(answer.node_title + '. Установка прав');
    
    var template = getTemplate(options.assets + '/tree/templates/rights_node/main_window.html');
    
    $( template ).tmpl( answer ).appendTo( '#dialog_id' );   

    $('#dialog_id .inner').html(options.progress_block_html);
    $('#dialog_id .cancel_button').click(function(){
        $.history.load('');
    });
    $('#dialog_id input[type=radio]').click(function(){
        $.history.load('action/rights_list/id/' + id_action + '/module/' + $(this).attr('module_id'));
    });
    
    $('#progress_id').hide();	
    $('#dialog_id').show();	 
         
    return answer['modules'][0]['id'];
}

function showModuleRights(id_mod)
{   
    id_module = id_mod;
    
    $.ajax({
        url: options.url + 'rights/getusers',
        data: 'id_node=' + id_action + '&id_module=' + id_module,
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            if (answer === 'error') {
                errorAndToMain('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                
                showModuleRights_callback(answer);
            }                 
        }
    });    
}
function showModuleRights_callback(answer)
{   
    var template = '<div>' + getTemplate(options.assets + '/tree/templates/rights_node/users.html') + '</div>';
    var list_template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/rights_node/users_list.html') + '</script>'; 
    
    $('#dialog_id .inner').html( template );
    
    $( list_template ).tmpl( answer ).appendTo( '#users_list_id' );
    
    $('#users_list_id .edit_rights').click(function(){
        $.history.load('action/edit_rights/id/' + id_action + '/module/' + id_module + '/user/' + $(this).attr('id_user'));
    });
}

function editRights(id_act, id_mod, id_user)
{
    id_action = id_act;
    id_user_edit = id_user;
    id_module = id_mod;
    
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');              
   
    $.ajax({
        url: options.url + 'rights/getuser',
        data: 'id_node=' + id_action + '&id_module=' + id_module + '&id_user=' + id_user,
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            if (answer === 'error') {
                errorAndToMain('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                
                editRights_callback(answer);
            }             
        }
    });    
}
function editRights_callback(answer)
{   
    if (answer == 'error')
    {   
        showMessage('Ошибка!', '#ff0000');    	  
        $('#progress_id').hide();
        $.history.load('');

        return false;
    }      
    
    setTitle('Установка прав для пользователя ' + answer.user);
                
    var template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/rights_node/edit_rights.html') + '</script>';
    
    var arr = new Object();   
    
    $( template ).tmpl( answer ).appendTo( '#dialog_id' );    
    
    var tmp = new Object(); 
    tmp['rights'] = answer.rights;
    
    var plugin_templates = loadPlugins(tmp);
          
    var template = '<script type="text/x-jquery-tmpl">' + plugin_templates['rights'] + '</script>';
    $( template ).tmpl( answer['rights'] ).appendTo( '#dialog_id .data');
	        
    $('#progress_id').hide();
		
    $('#dialog_id').slideDown(100);

    $('#dialog_id .submit_button').click(function(){
        editRightsSubmit();
    });
    $('#dialog_id .cancel_button').click(function(){
        $.history.load('action/rights_list/id/' + id_action);
    });              
}

function editRightsSubmit()
{
    data = '';         
    
    $('#dialog_id input[type=checkbox]').each(function(){        
        field = $(this).attr('name');  
        data += field + '=' + (($(this).is(':checked'))?'1':'0') + '&';
    });      
    
    data += 'id_node=' + id_action + '&id_module=' + id_module + '&id_user=' + id_user_edit;   
       
    $.ajax({
        url: options.url + 'rights/edit',
        data: data,
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            editRightsSubmit_callback(answer);
        }
    });     
}

function editRightsSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Права изменены!', '#38bc50'); 
    	                
        $.history.load('action/rights_list/id/' + id_action); 
        
    } else {
        showMessage('Ошибка!', '#ff0000');
    }      
}


///////////////////////////////////////////////////////////////////////
//                             Сообщения                             //
///////////////////////////////////////////////////////////////////////


function getNewMessages()
{        
    $.ajax({
        url: options.url + 'messages/get_new_messages',
        data: '',
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            getNewMessages_callback(answer);
        }
    });    
}
function getNewMessages_callback(answer)
{
    if (answer > 0)
    {
        $('#messages_link_id b').html(answer);
        $('#messages_link_id span').show();     
        
    } else {
        $('#messages_link_id span').hide();
    }
}

function getMessages()
{
    setTitle('Список сообщений');
    
    action = undefined;                   
    id_action = undefined;    
    
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');    
      
    $.ajax({
        url: options.url + 'messages/getmessages',
        data: '',
        type: 'get',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            getMessages_callback(answer);
        }
    });     
}
function getMessages_callback(answer)
{   
    var template = getTemplate(options.assets + '/tree/templates/messages/messages.html');
    var list_template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/messages/messages_list.html') + '</script>';      
    
    $('#dialog_id').html( template );
    
    $( list_template ).tmpl( answer ).appendTo( '#messages_list_id' );
    
    $('#progress_id').hide();
    
    $('#dialog_id .view_mess').click(function(){
        $.history.load('action/message/id/' + $(this).attr('id_mess'));      
    });
    $('#dialog_id .del_mess').click(function(){       
        deleteMessage($(this).attr('id_mess'));      
    });    
    $('#dialog_id .cancel_button').click(function(){
        $.history.load('');
    });    
    
    $('#dialog_id').slideDown(100);    
}

function getMessage(id)
{
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');      
    
    $.ajax({
        url: options.url + 'messages/getmessage',
        data: 'id=' + id,
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            getMessage_callback(answer);
        }
    });     
}
function getMessage_callback(answer)
{
    if (answer == 'error')
    {   
        showMessage('Ошибка!', '#ff0000');    	  
        $('#progress_id').hide();
        $.history.load('');

        return false;
    }     
    
    getNewMessages();
    
    template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/messages/message.html') + '</script>';
    
    $( template ).tmpl( answer ).appendTo( '#dialog_id' ); 
    
    $('#progress_id').hide();
		
    $('#dialog_id').slideDown(100);

    $('#dialog_id .cancel_button').click(function(){
        $.history.load('action/messages_list');
    });     
}

function deleteMessage(id)
{
    if (confirm('Вы уверены?'))
    {          
        $.ajax({
            url: options.url + 'messages/delmessage',
            data: 'id=' + id,
            type: 'post',
            async: true,
            dataType : "json",   
            cache: false,                             
            success: function (answer, textStatus) { 
                
                deleteMessage_callback(answer);
            }
        });        
    }
}
function deleteMessage_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Сообщение удалено!', '#38bc50'); 
    	                
        getMessages();
        getNewMessages();
        
    } else {
        showMessage('Ошибка!', '#ff0000');
    }  
}




// --------------------------------------------------------------------------------------------

$(document).ready(function(){
    
    $('#messages_link_id').click(function(){
        $.history.load('action/messages_list');
    });    
    
    $('#users_link_id').click(function(){
        $.history.load('action/users_list');
    });
    
    $('#groups_link_id').click(function(){
        $.history.load('action/groups_list');
    });         
    
});