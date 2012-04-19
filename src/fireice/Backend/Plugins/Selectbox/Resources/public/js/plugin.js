
function pluginSelectbox(data)
{
    if (typeof data['ajax'] == 'object')
    {
        $('#dialog_id .inner .data select[name=' + data['name'] + ']').data('ajax', data['ajax']);
        
        $('#dialog_id .inner .data select[name=' + data['name'] + ']').change(function(data){                 

            var ajax = $(this).data('ajax');
            
            for (var key in ajax)
            {
                $('#dialog_id .inner .data .no_data').remove();
                $('#dialog_id .inner .data select[name=' + ajax[key]['target'] + ']').show();
                $('#dialog_id .inner .data select[name=' + ajax[key]['target'] + ']').attr('disabled', 'disabled');
                $('#dialog_id .inner .submit_button').attr('disabled', 'disabled');
            }
            
            for (var key in ajax)
            {                            
                var params = [];
                var target = [];
                
                params[key] = ajax[key]['params'];
                target[key] = ajax[key]['target'];           
               
                var parametres = '';
                for (var key3 in params[key])
                {
                    parametres += 'params[' + key3 + ']=' + $('#dialog_id .inner .data select[name=' + params[key][key3] + ']').val() + '&';                                        
                }
               
                $.ajax({
                    url: options.url + 'ajax_load?plugin=' + target[key] + '&' + parametres + 'id=' + id_action + '&id_module=' + id_module+'&language=' + language,
                    data: '',
                    async: false,
                    dataType : "json",   
                    cache: false,                             
                    success: function (answer, textStatus) { 
                                 
                        var tmp = '';
                        for (var key2 in answer)                    
                        {
                            tmp +=  '<option value="' + key2 + '">' + answer[key2]['value'] + '</option>';   
                        }
                    
                        $('#dialog_id .inner .data select[name=' + target[key] + ']').html(tmp);
                    }
                });  
            }    
            
            for (var key in ajax)
            {
                $('#dialog_id .inner .data select[name=' + ajax[key]['target'] + ']').attr('disabled', false);
                $('#dialog_id .inner .submit_button').attr('disabled', false);
                
                if ($('#dialog_id .inner .data select[name=' + ajax[key]['target'] + '] option').length <= 1)
                {
                    $('#dialog_id .inner .data select[name=' + ajax[key]['target'] + ']').hide();
                    $('#dialog_id .inner .data select[name=' + ajax[key]['target'] + ']').after('<div class="no_data">Нет данных</div>');    
                }    
            }            
        });   
    }
    
    if ($('#dialog_id .inner .data select[name=' + data['name'] + '] option').length <= 1)
    {
        $('#dialog_id .inner .data select[name=' + data['name'] + ']').hide();
        $('#dialog_id .inner .data select[name=' + data['name'] + ']').after('<div class="no_data">Нет данных</div>');              
    }    
}
