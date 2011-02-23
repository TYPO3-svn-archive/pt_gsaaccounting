

$(document).ready(function() { 
// add new widget called repeatHeaders 
    $.tablesorter.addWidget({ 
        id: 'repeatHeaders', 
        format: function(table) { 
             
            var tableBodyLength = table.tBodies[0].rows.length; 
			//alert("Anzahl Datensaetze ")             
			//alert("Anzahl Datensaetze: "+tableBodyLength)             
            // cache headers 
            if(!this.headers) { 
                var h = this.headers = [];  
                $("thead th",table).each(function() { 
                    h.push( 
                        "<th>" + $(this).text() + "</th>" 
                    ); 
                     
                }); 
            }     
             
            for(var i=0; i < tableBodyLength; i++) { 
                // insert a copy of the table head every 10th row 
                if(i%10 == 9) { 
					//alert("i: "+i)
                    $("tbody > tr:eq(" + i + ")",table).before( 
                        $("<tr></tr>").html(this.headers.join("")) 
                     
                    );     
	                tableBodyLength++
                } 
            } 
        } 
    }); 
      
    // call the tablesorter plugin 
    $("#outstandingItems").tablesorter({ 
        // sort on 3. column, order asc 
        //widgets: ['zebra','repeatHeaders'] 
        widgets: ['zebra','repeatHeaders'] //, sortList:[[2,0]]
    }); 
	
	$("#doBook").click(function() {
		return confirm($('input[@name="bookPrompt"]').val());
	});
	
	$("#doCredit").click(function() {
		$('#outstandingPositions tbody tr').each(function() {
			var bookIt = parseInt($('.book_it input', this).val());
			var quantity = parseInt($('.quantity', this).text());
			var noOutstanding = parseInt($('.noOutstanding', this).text());
			if (bookIt > noOutstanding) {
				alert($('.articleName', this).text()+': '+$('input[@name="msg_errorQuantity"]').val());
				$('.book_it input', this).val('0');
			}
		});
		return confirm($('input[@name="creditPrompt"]').val());
	});
	
});


function selectAllRadiosValue(value,name) {
    //alert('value '+value);
    for(i=0; i<document.manage_form.elements.length; i++) {
		if (document.manage_form.elements[i].name == name) {
	         //alert('name '+document.manage_form.elements[i].name);
			if (document.manage_form.elements[i].checked == true) {
				checkval = true;
			} else {
				checkval = false;
			}		
		}
	}
    for(i=0; i<document.manage_form.elements.length; i++) {
        if (document.manage_form.elements[i].type == 'radio') {
        	//alert('radio '+document.manage_form.elements[i].value);
        	if (document.manage_form.elements[i].value == value) {
        		document.manage_form.elements[i].checked = true; 
        	}
        }
    }
}
function doAction(actionvar){
	document.manage_form.actionvar.value = actionvar;
	document.manage_form.submit();
}

function doActionConfirm(actionvar,frage) {
	if (confirm(frage)) {
		doAction(actionvar);
	}else {
		return false;
	}
}

