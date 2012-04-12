
// Настройки по умолчанию
var defaults = { 

    // Элемент в котором будем меню
    main_div: '#menu_id',   
    
    // Элемент в котором будет информация о пользователе
    user_div: '#user_id',    
    
    // Урл для аякс запросов
    url: 'http://localhost/app_dev.php/backoffice2', 
    
    // Урл папки с шаблонами
    assets: 'http://localhost/bundles/fireice/templates2',    
    
    // Паддинг блоков меню (для высчитывания суммарной ширины)
    block_padding: 8,   
    
    // Ширина блоков меню
    block_width: 217,    
    
    // Скорость сдвига блоков меню
    animate_speed: 300,       
    
    // Стиль рамки выбранного пункта без потомков
    select_border: '#B4B4B4 dotted 1px',  
    
    // Интервал через который происходят аякс запросы на получение новых сообщений
    messages_interval: 40,
    
    // Хтмл прогресс заставки
    progress_block_html: '<table height="100%"><tr><td align="center" style="vertical-align: middle; font-size: 12px;">'+
'<img src="i/ajax-loader3.gif" width="32" width="32">'+
'<br /><br />'+
'Подождите. Идёт загрузка...'+                               
'</td></tr></table>'
};



var level = 0;                                                              
var selected_item;                                   
var zaglushka = false; 
var zaglushka_ed = false;
var options;
var context_event;
var action; 
var id_action;
var id_module;
var module_type;
var create_module_id;
var first_tab;
var first_language;
var template, history_template;


///////////////////////////////////////////////////////////////////////
//                              Меню                                 //
///////////////////////////////////////////////////////////////////////

// Выделение выбранного пункта
function selectItem(q_level)
{   
    if (zaglushka) return false;
    $('#level_' + q_level + ' li').removeClass('select').css('border', 'none');  
    if ($(selected_item).filter('.parent').length > 0)
    {
        $(selected_item).filter('.parent').parent().addClass('select');	                                                             
    } else {
        $(selected_item).parent().css('border', options.select_border);
    }
}

// Удаление ненужных блоков
function hideBlocks(q_level, childrens)
{   
    var i, div_max_height;
    if ((level-1) > q_level)
    {
        for (i=q_level+1; i<=level; i++)
        {
            $('#level_' + i).remove();	        
        }        
        
        div_max_height = $('#level_0').height() + options.block_padding*2;   
        
        for (i=0; i<=q_level; i++) 
        {
            if (($(options.main_div + ' #level_' + (i+1)).height() + options.block_padding*2) > div_max_height)
            {	
                div_max_height = $(options.main_div + ' #level_' + (i+1)).height() + options.block_padding*2;
            }	        
        }          
        
        $(options.main_div).height( div_max_height );
        
        level = q_level + 1;    
             
        width_sum = (options.block_width + options.block_padding*2) * (level+1); 
        
        if (!childrens || (childrens && width_sum < $(options.main_div).width()))
        {   
            toMoveRight();                                      
        }
    }
}

// Аякс запрос для нового блока
function ajaxQuery(id)
{   
    zaglushka = true; 
    zaglushka_ed = true;
      
    $.ajax({
        url: options.url + 'get_parents/' + id,
        data: '',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            addItemsInBlock(answer);
        }
    });     
}    

// Вставка в блок полученных аяксом пунктов меню
function addItemsInBlock(answer)
{   
    var i, items, on_click, on_context_menu, selected_item_top, div_max_height;    
              
    if (answer != 'no_user')
    {         
        answer = answer.list;
        items = '';    
                                       
        for (i=0; i<answer.length; i++)
        {               
            // Обработчики onclick
            on_click = 'hideBlocks(' + level + ', ' + ((answer[i].c > 0) ? 'true':'false') + '); selected_item = this; selectItem(' + level + ');';

            if (answer[i].c > 0)
            {
                on_click += ' showEmptyBlock(' + answer[i].i + ');';
            }        
            
            on_context_menu = 'context_event = event; e = event || window.event; e.preventDefault ? e.preventDefault() : (e.returnValue=false); showContextMenu(' +answer[i].i + ', ' + "'" + 'link_' + level + '_' + i + "'" + ');';
        	
            on_dbl_click = 'ckeditorInstancesDestroy(); updateUrl(' + "'node_edit'" + ', ' + answer[i].i + '); return false;';
            
            if (answer[i].i == 1) {
                answer[i].t = '<b>' + answer[i].t + '</b>';
            }
            items += '<li><a item_id="' + answer[i].i + '" id="link_' + level + '_' + i + '" href="noscript" onDblClick="' + on_dbl_click + '" onclick="' + on_click + '; return false;" oncontextmenu="' + on_context_menu + '" class="' + (answer[i].c>0?'parent ':'') + (answer[i].h=='1'?'hidden':'') + '">' + answer[i].t + '</a></li>';    
        }	 
        
        $(options.main_div + ' #level_' + level + ' .block_items').html('<ul>' + items + '</ul>');

        if (level > 0)
        {
            selected_item_top = $('#level_' + level).children('.note').css('top');
            selected_item_top = parseInt( selected_item_top.slice(0, -2) );  
          
            if ($(options.main_div + ' #level_' + level).height() < (selected_item_top + $('#level_' + level).children('.note').height())) 
            {
                $(options.main_div + ' #level_' + level).height( selected_item_top + $('#level_' + level).children('.note').height() );	
            }      
        }   
    
        // Сделаем высоту основного блока в котором находится меню равной высоте самой большой "плашки" меню
        div_max_height = $('#level_0').height() + options.block_padding*2;           
    
        for (i=0; i<=level; i++) 
        {
            if (($(options.main_div + ' #level_' + (i+1)).height() + options.block_padding*2) > div_max_height)
            {	
                div_max_height = $(options.main_div + ' #level_' + (i+1)).height() + options.block_padding*2;
            }	        
        }       
               
        $(options.main_div).height( div_max_height ); 
      
        level++; 
        zaglushka = false;  
        zaglushka_ed = false;     
        
    } else {
        window.location.reload();
    }
}                                         

// Добавление и показ блока (вначале в нём прогресс-заставка)
function showEmptyBlock(id)                                                                                                                       
{
    var note, selected_item_top, left; 

    if (zaglushka) return false;  
    
    note = (level != 0) ? '<div class="note"></div>':'';
    
    if (level > 0)
    {
        left = $('#level_' + (level-1)).css('left');  
        left = parseInt(left.slice(0, -2));  
        left += (options.block_width + options.block_padding*2);  
        
    } else {
        left = 0;
    }
    
    $(options.main_div).append('<div id="level_' + level + '" class="child" style="left: ' + left + 'px">' + note + '<div class="block_items" style="height: 100%;">' + options.progress_block_html + '</div></div>'); 
    
    if (level > 0)
    {
        $('#level_' + level)
        .children('.note')
        .css('top', (selected_item.offsetTop + ((selected_item.offsetHeight - $('#level_' + level).children('.note').height()) / 2)) + 'px');                 
        
        selected_item_top = $('#level_' + level).children('.note').css('top');
        selected_item_top = parseInt( selected_item_top.slice(0, -2) );   
        
        if ($(options.main_div + ' #level_' + level).height() < (selected_item_top + $('#level_' + level).children('.note').height())) 
        {
            $(options.main_div + ' #level_' + level).css( 'min-height', selected_item_top + $('#level_' + level).children('.note').height() + 'px' );
        }      
        
    } else {
        $(options.main_div).height( $('#level_0').height() + options.block_padding*2 );
    }     
    
    toMoveLeft();                                                   
    ajaxQuery(id);
}                                         

// Сдвиг блоков влево
function toMoveLeft()                
{
    var i, j, width_sum, to_mov, lishnee, left;
  
    width_sum = (options.block_width + options.block_padding*2) * (level+1);     
    if (width_sum > $(options.main_div).width())
    {
        lishnee = width_sum - $(options.main_div).width();           
        to_mov = Math.round(lishnee/(level-2));
              
        for (i=2, j=1; i<=level-1; i++, j++)
        {             
            left = i * (options.block_width + options.block_padding*2);  	// Сколько должно было быть 
            $(options.main_div + ' #level_' + i).animate({
                left: (left - to_mov*j) + 'px'
            }, 
            options.animate_speed, 
            function (){
                $(this).addClass('back')
            });   
        }   
        $(options.main_div + ' #level_' + level).css('left', (left - to_mov*(j-1) + (options.block_width + options.block_padding*2)) + 'px');
    }
}  

// Сдвиг блоков впрaво
function toMoveRight()
{
    var i, width_sum, to_mov, left;
  
    width_sum = (options.block_width + options.block_padding*2) * level;     

    if (width_sum > $(options.main_div).width())
    {
        lishnee = width_sum - $(options.main_div).width();           
        to_mov = Math.round(lishnee/(level-3));                 
        
        for (i=2, j=1; i<=level-2; i++, j++)
        {            
            left = i * (options.block_width + options.block_padding*2);  	// Сколько должно было быть  
            $(options.main_div + ' #level_' + i).animate({
                left: (left - to_mov*j) + 'px'
            }, options.animate_speed);   
        } 
        $(options.main_div + ' #level_' + i).animate({
            left: (left - to_mov*(j-1) + (options.block_width + options.block_padding*2)) + 'px'
        }, 
        options.animate_speed, 
        function (){
            $(this).removeClass('back')
        });
    }
    else
    {
        for (i=2; i<=level; i++)
        {             
            left = i * (options.block_width + options.block_padding*2);  	              
            $(options.main_div + ' #level_' + i).animate({
                left: left + 'px'
            }, 
            options.animate_speed, 
            function (){
                $(this).removeClass('back');
            });            
        }
        $(options.main_div + ' #level_' + (level-1)).css('left', ((level-1)* (options.block_width + options.block_padding*2)) + 'px').removeClass('back');   	    
    }	
}

// Самый первый аякс запрос - нужно выяснить были ли открыты блоки ранее. Если да то показать их
function getShowNodes()
{   
    $(options.main_div).html( options.progress_block_html );
    	
    $.ajax({
        url: options.url + 'get_shownodes',
        data: '',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            getShowNodes_callback(answer);
        }
    });     
}     
function getShowNodes_callback(answer)
{   
    var i, j, k, on_click, on_context_menu, items, left, note, is_select, selected_item_top, div_max_height;                                                                  
    on_click = [];
        
    if (answer != 'no_user')
    {    
        $(options.main_div).html('');   
        $(options.user_div).html(answer.user);
               
        answer = answer.list;
        
        if (answer.length > 0)
        {        
            for	(i=0; i<answer.length; i++)
            {   
                // Формируем все полученные блоки разом
                items = '';
                for (j=0; j<answer[i].length; j++)
                {
                    // Определим должен ли этот пункт быть "выбранным"
                    is_select = false;
                    if (i < answer.length-1)
                    {
                        id = answer[i][j].i;
                        for (k=0; k<answer[i+1].length; k++)
                        {                            
                            if (answer[i+1][k].p == id)
                            { 
                                is_select = true;         
                                break;
                            }
                        }                	                
                    }  

                    // Ставим обработчики событий
                    on_click = 'hideBlocks(' + i + ', ' + ((answer[i][j].c > 0) ? 'true':'false') + '); selected_item = this; selectItem(' + i + ');';

                    if (answer[i][j].c > 0)
                    {
                        on_click += ' showEmptyBlock(' + answer[i][j].i + ');';
                    }                       
                    
                    on_context_menu = 'context_event = event; e = event || window.event; e.preventDefault ? e.preventDefault() : (e.returnValue=false); showContextMenu(' + answer[i][j].i + ', ' + "'" + 'link_' + i + '_' + j + "'" + ');';

                    on_dbl_click = 'ckeditorInstancesDestroy(); updateUrl(' + "'node_edit'" + ', ' + answer[i][j].i + '); return false;';
                    
                    if (answer[i][j].i == 1) {
                        answer[i][j].t = '<b>' + answer[i][j].t + '</b>';
                    }
                    
                    items += '<li' + (is_select?' class="select"':'') + '><a onDblClick="' + on_dbl_click + '" oncontextmenu="' + on_context_menu + '" onclick="' + on_click + '; return false;" item_id="' + answer[i][j].i + '" id="link_' + i + '_' + j + '" href="noscript" class="' + (answer[i][j].c>0?'parent ':'') + (answer[i][j].h=='1'?'hidden':'') + '">' + answer[i][j].t + '</a></li>';
                }	

                note = (i != 0) ? '<div class="note"></div>':'';

                if (i > 0)
                {
                    left = $('#level_' + (i-1)).css('left');  
                    left = parseInt(left.slice(0, -2));  
                    left += (options.block_width + options.block_padding*2);  
                
                } else {
                    left = 0;
                }

                $(options.main_div).append('<div id="level_' + i + '" class="child" style="left: ' + left + 'px">' + note + '<div class="block_items" style="height: 100%;"><ul>' + items + '</ul></div></div>');             
            }        
        
            level = i - 1;       
         
            div_max_height = $('#level_0').height() + options.block_padding*2;
        
            // Расставим указатели "стрелочки"
            for (i=0; i<level; i++)
            {
                is_select = $('#level_' + i + ' li[class=select]')[0];    
        	
                $('#level_' + (i+1))
                .children('.note')
                .css('top', (is_select.offsetTop + ((is_select.offsetHeight - $('#level_' + (i+1)).children('.note').height()) / 2)) + 'px');          	
            
                selected_item_top = $('#level_' + (i+1)).children('.note').css('top');
                selected_item_top = parseInt( selected_item_top.slice(0, -2) );   
            
                if ($(options.main_div + ' #level_' + (i+1)).height() < (selected_item_top + $('#level_' + (i+1)).children('.note').height())) 
                {
                    $(options.main_div + ' #level_' + (i+1)).height( selected_item_top + $('#level_' + (i+1)).children('.note').height() );	
                }           	        	    
        
                if (($(options.main_div + ' #level_' + (i+1)).height() + options.block_padding*2) > div_max_height)
                {	
                    div_max_height = $(options.main_div + ' #level_' + (i+1)).height() + options.block_padding*2;
                }
            } 
                                             
            $(options.main_div).height( div_max_height );        
        
            toMoveLeft();
            level++;
            zaglushka_ed = false;      
        
        } else {
            showEmptyBlock(1);
        }     
    } else {
        window.location.reload();
    }
}  

///////////////////////////////////////////////////////////////////////
//                        Контекстное меню                           //
///////////////////////////////////////////////////////////////////////

// Аякс запрос на контекстное меню             
function showContextMenu(id, origID)                                                          
{	
    $.ajax({
        url: options.url + 'context_menu/' + id,
        data: '',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            showContextMenu_callback(answer);
        }
    });    
    
    $('#context_menu').remove();    
    $('body').append('<div id="context_menu" class="context" style="display: none;"></div>');
    $('#context_menu').css('top', defPosition(context_event).y + "px").css('left', defPosition(context_event).x + "px").html( options.progress_block_html ).fadeIn(150);
}  
function showContextMenu_callback(answer)
{  
    var i, items, func, on_click;	  
    
    items = '';  
    
    for (i=0; i<answer.length; i++)                                      
    {              
        if (answer[i].action == 'edit')
        {
            on_click = 'ckeditorInstancesDestroy(); updateUrl(' + "'node_" + answer[i].action + "'" + ', ' + answer[i].id + ', ' + answer.language+')';  
        }
        else if (answer[i].action == 'create')
        {
            on_click = 'updateUrl(' + "'node_create_1'" + ', ' + answer[i].id + ', ' + answer.language + ')'; 
        }        
        else if (answer[i].action == 'remove')
        {
            on_click = 'deleteNode(' + answer[i].id + ');';
        }
        else if (answer[i].action == 'rights')
        {
            on_click = "$.history.load('action/rights_list/id/" + answer[i].id + ', ' + answer.language + "')";
        }     
        else if (answer[i].action == 'hidenode')
        {
            on_click = "hideNode(" + answer[i].id + ");";
        }   
        else if (answer[i].action == 'shownode')
        {
            on_click = "showNode(" + answer[i].id + ");";
        }         
    	
        items += '<a id="context_' + i + '" href="noscript" onclick="' + on_click + '; return false;">' + answer[i].title + '</a>';	    
    }  

    $('#context_menu').html( items ); 
} 

////////////////////////////////////////////////////////////////////
//                          Диалоги                               //
////////////////////////////////////////////////////////////////////

function showSelectModule(id_act, act)
{	
    $('#dialog_id').hide().html('');
	
    action = act;                   
    id_action = id_act;	
	
    $('#progress_id').show();

    $.ajax({
        url: options.url + 'get_modules/' + id_action,
        data: '',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                
                showSelectModule_callback(answer);
            }             
        }
    });     
}
function showSelectModule_callback(answer)
{      
    setTitle(answer['node_title'] + '. Создание потомка');

    var template = getTemplate(options.assets + '/tree/templates/createadd_node/select_module.html');

    $.tmpl( template, answer).appendTo("#dialog_id");   
   
    $('#progress_id').hide();	
    $('#dialog_id').slideDown(100);		   
    
    $('#dialog_id .submit_button').click(function(){
        selectModuleSubmit();
    });       
}
function selectModuleSubmit()
{
    var field;
    var data = '';	
    
    $('#dialog_id select').each(function(){        
        field = $(this).attr('name');
        data += field + '=' + $(this).val();
        create_module_id = $(this).val();
    });	    
    
    $('#dialog_id').hide().html('');
    $('#progress_id').show();
 
    $.ajax({
        url: options.url + 'node_create',
        data: 'id=' + id_action + '&' + data,
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            if (answer === 'no_rights') {
                errorAndToMain('Нет прав!', '#ff0000');
            } else {                
                showCreate(answer);
            }  
        }
    });     
}

function showCreate(id_node) 
{       
    $.history.load('action/node_create_2/id/' + id_node);
}

function showEdit(id_act, act)
{   
    $('#dialog_id').hide().html('');
	
    action = act;                   
    id_action = id_act;	
	
    $('#progress_id').show();  
    
    $.ajax({
        url: options.url + 'get_node_modules',
        data: 'id=' + id_action,
        async: false,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 
                
            getNodeModules_callback(answer);
        }
    });             
}
function getNodeModules_callback(answer) 
{   
    if (answer == 'error')
    {
        $('#progress_id').hide();
        showMessage('Ошибка!', '#ff0000');        
        $.history.load('');
        
        return false;
    }          
     
    first_tab = answer.modules[0]['id'];
    module_type = answer.modules[0]['module_type'];
    first_language = answer.modules[0]['language'];
    if (action == 'edit')
        answer.dialog_caption = 'Редактирование';
    else if (action == 'create')
        answer.dialog_caption = 'Создание потомка';
    
    setTitle(answer['node_title'] + '. ' + answer.dialog_caption);
        
    var template = getTemplate(options.assets + '/tree/templates/createadd_node/main_window.html');
    
    $( template ).tmpl( answer ).appendTo( '#dialog_id' );    
    
    $('#dialog_id .form .tab').click(function(){
        
        
        module_type = $(this).attr('module_type');
        first_language = $(this).attr('language');
        $.history.load('action/' + getNodeAction(action) + '/id/' + id_action + '/module/' + $(this).attr('id_module')+'/language/'+$(this).attr('language')); 
    
    });   
    
    $('#progress_id').hide();	
    $('#dialog_id').show();	      
} 
function showTab(id_mod, lang, is_show_row )
{ 
    if (id_mod !== undefined) {
        ckeditorInstancesDestroy();
    
        $('#dialog_id .tab').removeClass('current');
        $('#dialog_id .form .tab').each(function(){
            if ($(this).attr('id_module') == id_mod)
            {
                module_type = $(this).attr('module_type');
                $(this).addClass('current');
            }
        });        
    
        id_module = id_mod;
    
        $('#dialog_id .inner').html(options.progress_block_html);   
    
        if (is_show_row !== true)
        {
            $.ajax({
                url: options.url + 'dialog_create_edit',
                data: 'act=show&id=' + id_action + '&id_module=' + id_module + '&language=' + lang, 
                async: true,
                dataType : "json",   
                cache: false,                             
                success: function (answer, textStatus) { 

                    if (answer === 'error') {
                        errorAndToMain('Ошибка!', '#ff0000');
                    } else if (answer === 'no_rights') {
                        errorAndToMain('Нет прав!', '#ff0000');
                    } else {                
                        if (module_type == 'item')
                            showItemInner(answer);
                        else if (module_type == 'list')
                            showListInner(answer.data);  
                    }                 
                }
            });    
        }
    }
}


function deleteNode(id_node)
{
    if (confirm('Вы уверены?'))
    {        
        $.ajax({
            url: options.url + 'node_remove',
            data: 'id=' + id_node,
            type: 'post',
            async: true,
            dataType : "json",   
            cache: false,                             
            success: function (answer, textStatus) { 

                if (answer === 'error') {
                    showMessage('Ошибка!', '#ff0000');
                } else if (answer === 'no_rights') {
                    showMessage('Нет прав!', '#ff0000');
                } else {                
                    showMessage('Узел удалён!', '#38bc50');     	                
                    getShowNodes(); 
                }                 
            }
        });         
    }   
}

function hideNode(id)
{   
    $.ajax({
        url: options.url + 'hide_node/' + id,
        data: '',
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            if (answer === 'error') {
                showMessage('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                showMessage('Нет прав!', '#ff0000');
            } else {                
                showMessage('Узел скрыт!', '#38bc50');     	                
                getShowNodes(); 
            }              
        }
    });     
}

function showNode(id)
{
    $.ajax({
        url: options.url + 'show_node/' + id,
        data: '',
        type: 'post',
        async: true,
        dataType : "json",   
        cache: false,                             
        success: function (answer, textStatus) { 

            if (answer === 'error') {
                showMessage('Ошибка!', '#ff0000');
            } else if (answer === 'no_rights') {
                showMessage('Нет прав!', '#ff0000');
            } else {                
                showMessage('Узел открыт!', '#38bc50');     	                
                getShowNodes(); 
            }             
        }
    });     
}


$.showMenu = function(params) {

    options = $.extend({}, defaults, params);
    
    getShowNodes();     
  
    $(document).mouseup(function(){
        $('#context_menu').hide();	
    }); 
    
    setInterval(getNewMessages, options.messages_interval*1000);

    $.history.init(function(url) {
                
        if (url != "")
            showOpenDialog(url);
        else
        {            
            action = undefined;                   
            id_action = undefined;	   

            $('#dialog_id').slideUp(100, function(){
                $(this).html('')
            }); 
            setTitle('');
            
            ckeditorInstancesDestroy();
        }
    
    }, {
        unescape: "/"
    });
    
    // В ИЕ ресайз срабатывает даже когда просто появляются линейки прокрутки (но ширина окна не меняется) - поэтому начинает глючить
    var IE = /*@cc_on ! @*/ false;
    if (IE)
    {
        $(options.main_div).resize(function(){
            toMoveRight();
        });
    }
    else
    {
        $(window).resize(function(){
            toMoveRight();
        });
    }
}
                            