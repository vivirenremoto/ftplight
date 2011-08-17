function addUpload(){
	var newfile = document.createElement('input');
	newfile.setAttribute('type', 'file');
	newfile.setAttribute('name', 'upload[]');
	document.getElementById('uploads').appendChild( newfile );
}


function selectAll(){
	inputs = document.getElementsByTagName('input');
	num_checks = -1;
	num_checked = 0;
	for ( var i in inputs ){
		if( inputs[i].type == 'checkbox' ){
			num_checks++;
			if( inputs[i].checked ) num_checked++;
		}
	}
	mark = !( num_checks == num_checked );	
	for ( var i in inputs ){
		if( inputs[i].type == 'checkbox' ) inputs[i].checked = mark;
	}
}

function newDir(){
	if( new_dir = prompt('Nueva carpeta', '') ){
		document.location = BASE_URL + '&new_dir=' + new_dir;
	}
}