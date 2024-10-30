var csb_botezatu_captchaJsModule = {
	toggleCheckbox: function() {
		var checkboxes = document.getElementsByClassName('csb_botezatu_robot_checkbox');
		if( checkboxes.length > 0 ) {
			var checkbox = checkboxes[0];
			checkbox.checked = !checkbox.checked;
			var elRobotRow = document.getElementsByClassName('csb_botezatu_robot_row');
			for (var i = 0; i < elRobotRow.length; i++) {
				elRobotRow[i].innerHTML = "Loading ...";
			}
			this.loadCaptchaImage();
		}
	},

    loadCaptchaImage: function() {
		var elements_invalid = document.getElementsByClassName("csb_botezatu_captcha_invalid_elem");
		for( var i = elements_invalid.length - 1; i >= 0; i-- ) {
			elements_invalid[i].remove();
		}	
        var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
        xhr.open('POST', csb_botezatu_ajax_object.ajax_url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if( xhr.readyState === 4 && xhr.status === 200 ) {
                csb_botezatu_captchaJsModule.removeCapthaInitialStyle();
 				var elResp = document.getElementsByClassName('csb_botezatu_robot_row');
				for( var i = 0; i < elResp.length; i++ ) {
					elResp[i].innerHTML = xhr.response;
				}					
                csb_botezatu_captchaJsModule.updateInputCaptcha();
            } else {
                csb_botezatu_captchaJsModule.removeCapthaInitialStyle();
  				var elResp = document.getElementsByClassName('csb_botezatu_robot_row');
				for( var i = 0; i < elResp.length; i++ ) {
					elResp[i].innerHTML = "Error";
				}				
            }
        };
        var data = 'action=csb_botezatu_generate_captcha';
        xhr.send(data);
    },

	removeCapthaInitialStyle: function() {
		var elements = document.querySelectorAll('.csb_botezatu_robot_container');
		for( var i = 0; i < elements.length; i++ ) {
			var element = elements[i];		 
			element.style.setProperty('background-color', 'initial', 'important');
			element.style.setProperty('cursor', 'initial', 'important');
			element.style.setProperty('border', 'initial', 'important');
			element.style.setProperty('border-radius', 'initial', 'important');
 			element.style.setProperty('padding-top', 'initial', 'important');
			element.style.setProperty('padding-bottom', 'initial', 'important');
			element.style.setProperty('margin-top', 'initial', 'important');
 			//element.style.setProperty('margin-bottom', 'initial', 'important');
 		}
	},

    randomIntFromInterval: function(min, max) {
        return Math.floor(Math.random() * (max - min + 1) + min);
    },

    updateInputCaptcha: function() {
        var n = this.randomIntFromInterval(11111, 99999);
        this.doUpdateFieldWithVal(n);
    },

	doUpdateFieldWithVal: function(n) {
		var inputElements = document.getElementsByClassName('csb_botezatu_wp_token_site_id_value');
		for(var i = 0; i < inputElements.length; i++) {
			inputElements[i].value = n;
		}
	},
	
	toggleMoreImg: function(el) {
 		var imgE = document.getElementById(el);
		if( imgE.style.display==='block' ){
			imgE.style.display='none';
		} else {
			imgE.style.display='block';
		}
	}		
	
};

