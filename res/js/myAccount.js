

$(document).ready(function() { 
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
                    var align=$(this).attr("align");
                    if(align) {
	                    h.push( 
	                        "<th align="+align+">" + $(this).text() + "</th>" 
	                    ); 
					} else {
	                    h.push( 
	                        "<th>" + $(this).text() + "</th>" 
	                    ); 
                    } 
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
    $("#myAccount").tablesorter({ 
        // sort on 3. column, order asc 
        //widgets: ['zebra','repeatHeaders'] 
        widgets: ['zebra','repeatHeaders'] //, sortList:[[2,0]]
    }); 

	$("#doBook").click(function() {
		return confirm($('input[@name="bookPrompt"]').val());
	});
	
});


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

