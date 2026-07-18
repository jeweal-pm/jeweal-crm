            
    jQuery(document).ready(function($){
    $("[name='lang-checkbox']").bootstrapSwitch({
        on: 'EN',
        off: 'TH',
        onClass: 'on',
	offClass: 'off'
    });
    $('.hide_menu').on('click',function(){
        $('body').toggleClass('sidebar-collapse');
        $('.show_menu').show();
        $(this).hide();
    })

    $('.show_menu').on('click',function(){
        $('body').toggleClass('sidebar-collapse');
        $('.hide_menu').show();
        $(this).hide();
    })

    })
   