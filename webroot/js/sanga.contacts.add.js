$(function() {

	$(window).on('load', function(){
		var isDisplay = $.localStorage('sanga.contactBlockPersonal');
		$('fieldset#personal_data div').css('display', isDisplay);
		if(isDisplay == 'none') {
			$('fieldset#personal_data legend').addClass('block-closed');
		}

		var isDisplay = $.localStorage('sanga.contactBlockWorkplace');
		$('fieldset#workplace_skills div').css('display', isDisplay);
		if(isDisplay == 'none') {
			$('fieldset#workplace_skills legend').addClass('block-closed');
		}
	});

	$('fieldset#personal_data legend').click(function () {
		$('fieldset#personal_data legend').toggleClass('block-closed');
		$('fieldset#personal_data div').toggle();
		$.localStorage('sanga', {contactBlockPersonal : $('fieldset#personal_data div').css('display')});

	});
	$('fieldset#workplace_skills legend').click(function () {
		$('fieldset#workplace_skills legend').toggleClass('block-closed');
		$('fieldset#workplace_skills div').toggle();
		$.localStorage('sanga', {contactBlockWorkplace : $('fieldset#workplace_skills div').css('display')});
	});

	$('#birth').datepicker({
		showMonthAfterYear: true,
		yearRange: '1900:' + new Date().getFullYear(),
		changeMonth: true,
		changeYear: true
    });
	
	function addSkillSpanAndInput(event, ui){
		var t = $(event.target);
		var tag = "";
		var id = null;
		if (ui.item) {
			tag =  ui.item.label
			id = ui.item.value;
		}
		else{
			tag = $(event.target).val();
		}
		t.parent().append('<span class="tag tag-shared removeable">' + tag + '</span> ');
		if (id) {
			t.parent().append('<input type="hidden" name="skills[][id]" value="' + id + '">');
		} else {
			t.parent().append('<input type="hidden" name="skills[][name]" value="' + tag + '">');
		}
		
		t.val("");
		t.focus();
	}
	
	$(document).on('click', '.removeable', function(){
		//remove acidently added skills
		$(this).next().remove();
		$(this).remove();
	});
	
	$('#skills')
		.bind("keydown", function(event) {
			// don't navigate away from the field on tab when selecting an item
			if ((event.keyCode === $.ui.keyCode.TAB || event.keyCode === $.ui.keyCode.ENTER) && $(this).autocomplete("instance").menu.active) {
				event.preventDefault();
			}
			else if (event.keyCode === $.ui.keyCode.ENTER){
				addSkillSpanAndInput(event, {item : null});
				event.preventDefault();
			}
		})
		.autocomplete({
			minLength : 2,
			source : $.sanga.baseUrl + '/Skills/search',
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select : function(event, ui) {	//when we select something from the dropdown
				this.value = ui.item.label;
				addSkillSpanAndInput(event, ui);
				return false;
			},
			change : function(event, ui){	//when we blur the input or change its value
				addSkillSpanAndInput(event, ui);
				return false;
			}
		});
	
	$('#xzip').autocomplete({
		minLength : 2,
		source : $.sanga.baseUrl + '/Zips/search',
		focus: function() {
			return false;
		},		
		select : function(event, ui) {	//when we select something from the dropdown
			this.value = ui.item.label;
			$('#zip-id').val(ui.item.value);
			return false;
		},
		change : function(event, ui) {
			this.value = ui.item.label;
			$('#zip-id').val(ui.item.value);
			return false;
		}
	});
	
	$('#xworkplace-zip').autocomplete({
		minLength : 2,
		source : $.sanga.baseUrl + '/Zips/search',
		focus: function() {
			return false;
		},		
		select : function(event, ui) {	//when we select something from the dropdown
			this.value = ui.item.label;
			$('#workplace-zip-id').val(ui.item.value);
			return false;
		},
		change : function(event, ui) {
			this.value = ui.item.label;
			$('#workplace-zip-id').val(ui.item.value);
			return false;
		}
	});

	$('#xfamily').autocomplete({
		minLength : 2,
		source : $.sanga.baseUrl + '/Contacts/search',
		html: true,
		focus: function() {
			return false;
		},		
		select : function(event, ui) {	//when we select something from the dropdown
			this.value = ui.item.label.replace(/(<([^>]+)>)/ig,'');		//remove highlight html code;
			$('#family-member-id').val(ui.item.value);
			return false;
		},
		change : function(event, ui) {
			this.value = ui.item.label.replace(/(<([^>]+)>)/ig,'');		//remove highlight html code;
			$('#family-member-id').val(ui.item.value);
			return false;
		}
	});

});