
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
      
    var url = options.url + 'users/getusers';
    $.get(url, '', getUsersData_callback);
}
function getUsersData_callback(answer)
{         
    var template = getTemplate(options.assets + '/tree/templates/users/users.html');
    var list_template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/users/users_list.html') + '</script>';    
    
    $('#dialog_id').html( template );
    
    $( list_template ).tmpl( answer ).appendTo( '#users_list_id' );
    
    $('#progress_id').hide();
    
    $('#dialog_id .edit_user').click(function(){     
        $.history.load('action/user_edit/id/' + $(this).attr('id_user'));
    });
    $('#dialog_id .del_user').click(function(){
        deleteUser( $(this).attr('id_user') );        
    });    
    $('#dialog_id .add_button').click(function(){ $.history.load('action/user_add'); });
    $('#dialog_id .cancel_button').click(function(){ $.history.load(''); });    
    
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
   
    var url = options.url + 'users/getuserdata';
    $.get(url, 'id=' + id_user_edit, getUserData_callback);     
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
    
    $('#dialog_id .submit_button').click(function(){ editUserSubmit(); });
    $('#dialog_id .cancel_button').click(function(){ $.history.load('action/users_list'); });    
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
    
    var url = options.url + 'users/edit';
    $.post(url, data, editUserSubmit_callback);      
}
function editUserSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Сохранено!', '#38bc50');
    	                
        $.history.load('action/users_list'); 
        
    } else { showMessage('Ошибка!', '#ff0000'); }    
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
   
    var url = options.url + 'users/getuserdata';
    $.get(url, 'id=-1', getCreateUserData_callback); 
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
    
    $('#dialog_id .submit_button').click(function(){ addUserSubmit(); });
    $('#dialog_id .cancel_button').click(function(){ $.history.load('action/users_list'); });         
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
    
    var url = options.url + 'users/add';
    $.post(url, data, addUserSubmit_callback);        
}
function addUserSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Пользователь добавлен!', '#38bc50');
    	                
        $.history.load('action/users_list'); 
        
    } else { showMessage('Ошибка!', '#ff0000'); }     
}

function deleteUser($id_user)
{
    if (confirm('Вы уверены?'))
    {        
        var url = options.url + 'users/delete';
        $.post(url, 'id=' + $id_user, deleteUser_callback);        
    }
}
function deleteUser_callback(answer)
{    
    if (answer == 'ok')
    {   
        showMessage('Пользователь удалён!', '#38bc50'); 
    	                
        getUsers(); 
        
    } else { showMessage('Ошибка!', '#ff0000'); }     
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
      
    var url = options.url + 'groups/getgroups';
    $.get(url, '', getGroupsData_callback);    
}
function getGroupsData_callback(answer)
{   
    var template = getTemplate(options.assets + '/tree/templates/groups/groups.html');
    var list_template = '<script type="text/x-jquery-tmpl">' + getTemplate(options.assets + '/tree/templates/groups/groups_list.html') + '</script>';    
    
    $('#dialog_id').html( template );
    
    $( list_template ).tmpl( answer ).appendTo( '#groups_list_id' );
    
    $('#progress_id').hide();
    
    $('#dialog_id .edit_group').click(function(){
        $.history.load('action/group_edit/id/' + $(this).attr('id_group'));      
    });
    $('#dialog_id .del_group').click(function(){
        deleteGroup( $(this).attr('id_group') );        
    });    
    $('#dialog_id .add_button').click(function(){ $.history.load('action/group_add'); });
    $('#dialog_id .cancel_button').click(function(){ $.history.load(''); });    
    
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
   
    var url = options.url + 'groups/getgroupdata';
    $.get(url, 'id=' + id_group, getGroupData_callback);  
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
    
    $('#dialog_id .submit_button').click(function(){ editGroupSubmit(); });
    $('#dialog_id .cancel_button').click(function(){ $.history.load('action/groups_list'); });     
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
    
    var url = options.url + 'groups/edit';
    $.post(url, data, editGroupSubmit_callback);              
}
function editGroupSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Сохранено!', '#38bc50');
    	                
        $.history.load('action/groups_list'); 
        
    } else { showMessage('Ошибка!', '#ff0000'); }             
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
   
    var url = options.url + 'groups/getgroupdata';
    $.get(url, 'id=-1', getCreateGroupData_callback);         
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
    
    $('#dialog_id .submit_button').click(function(){ addGroupSubmit(); });
    $('#dialog_id .cancel_button').click(function(){ $.history.load('action/groups_list'); });     
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
    
    var url = options.url + 'groups/add';
    $.post(url, data, addGroupSubmit_callback);      
}
function addGroupSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Сохранено!', '#38bc50');
    	                
        $.history.load('action/groups_list'); 
        
    } else { showMessage('Ошибка!', '#ff0000'); }     
}


function deleteGroup(id_group)
{
    if (confirm('Вы уверены?'))
    {        
        var url = options.url + 'groups/delete';
        $.post(url, 'id=' + id_group, deleteGroup_callback);        
    }
}
function deleteGroup_callback(answer)
{    
    if (answer == 'ok')
    {   
        showMessage('Группа удалена!', '#38bc50'); 
    	                
        getGroups();
        
    } else { showMessage('Ошибка!', '#ff0000'); }     
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
                
            tmp = getRightsData_callback(answer);
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
    $('#dialog_id .cancel_button').click(function(){ $.history.load(''); });
    $('#dialog_id input[type=radio]').click(function(){ $.history.load('action/rights_list/id/' + id_action + '/module/' + $(this).attr('module_id')); });
    
    $('#progress_id').hide();	
    $('#dialog_id').show();	 
         
    return answer['modules'][0]['id'];
}

function showModuleRights(id_mod)
{   
    id_module = id_mod;
    
    var url = options.url + 'rights/getusers';
    $.get(url, 'id_node=' + id_action + '&id_module=' + id_module, showModuleRights_callback);      
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
   
    var url = options.url + 'rights/getuser';
    $.get(url, 'id_node=' + id_action + '&id_module=' + id_module + '&id_user=' + id_user, editRights_callback);    
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

    $('#dialog_id .submit_button').click(function(){ editRightsSubmit(); });
    $('#dialog_id .cancel_button').click(function(){ $.history.load('action/rights_list/id/' + id_action); });              
}

function editRightsSubmit()
{
    data = '';         
    
    $('#dialog_id input[type=checkbox]').each(function(){        
        field = $(this).attr('name');  
        data += field + '=' + (($(this).is(':checked'))?'1':'0') + '&';
    });      
    
    data += 'id_node=' + id_action + '&id_module=' + id_module + '&id_user=' + id_user_edit;   
    
    var url = options.url + 'rights/edit';
    $.post(url, data, editRightsSubmit_callback);         
}

function editRightsSubmit_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Права изменены!', '#38bc50'); 
    	                
        $.history.load('action/rights_list/id/' + id_action); 
        
    } else { showMessage('Ошибка!', '#ff0000'); }      
}


///////////////////////////////////////////////////////////////////////
//                             Сообщения                             //
///////////////////////////////////////////////////////////////////////


function getNewMessages()
{      
    var url = options.url + 'messages/get_new_messages';
    $.get(url, '', getNewMessages_callback);    
}
function getNewMessages_callback(answer)
{
    if (answer > 0)
    {
        $('#messages_link_id b').html(answer);
        $('#messages_link_id span').show();     
        
    } else { $('#messages_link_id span').hide();  }
}

function getMessages()
{
	setTitle('Список сообщений');
    
    action = undefined;                   
    id_action = undefined;    
    
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');    
      
    var url = options.url + 'messages/getmessages';
    $.get(url, '', getMessages_callback);    
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
    $('#dialog_id .cancel_button').click(function(){ $.history.load(''); });    
    
    $('#dialog_id').slideDown(100);    
}

function getMessage(id)
{
    $('#progress_id').show();
    $('#dialog_id').slideUp(100).html('');      
   
    var url = options.url + 'messages/getmessage';
    $.post(url, 'id=' + id, getMessage_callback);     
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

    $('#dialog_id .cancel_button').click(function(){ $.history.load('action/messages_list'); });     
}

function deleteMessage(id)
{
    if (confirm('Вы уверены?'))
    {        
        var url = options.url + 'messages/delmessage';
        $.post(url, 'id=' + id, deleteMessage_callback);        
    }
}
function deleteMessage_callback(answer)
{
    if (answer == 'ok')
    {   
        showMessage('Сообщение удалено!', '#38bc50'); 
    	                
        getMessages();
        getNewMessages();
        
    } else { showMessage('Ошибка!', '#ff0000'); }  
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