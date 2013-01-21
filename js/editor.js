/* 
@package Abricos
*/

var Component = new Brick.Component();
Component.requires = {};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;

	var buildTemplate = this.buildTemplate;
	
	var UserFindByEmail = function(startCallback, finishCallback){
		this.init(startCallback, finishCallback);
	};
	UserFindByEmail.prototype = {
		init: function(startCallback, finishCallback){
			this.startCallback = startCallback;
			this.finishCallback = finishCallback;

			this._checkUserCache = {};
		},
		find: function(eml){
			if (!NS.emailValidate(eml)){
				return false;
			}
			this._findMethod(eml);
			return true;
		},
		_findMethod: function(eml){
			var chk = this._checkUserCache;
			if (chk[eml] && chk[eml]['isprocess']){
				// этот емайл сейчас уже находится в запросе
				return; 
			}
			
			NS.life(this.startCallback, eml);

			if (chk[eml] && chk[eml]['result']){
				NS.life(this.finishCallback, eml);
				return;
			}
			chk[eml] = { 'isprocess': true };

			var __self = this;

			Brick.ajax('{C#MODNAME}', {
				'data': {
					'do': 'userfindbyemail',
					'email': eml
				},
				'event': function(request){
					var d = request.data,
						user = null;
					if (!L.isNull(d)){
						eml = d['email'];
						user = d['user'];
					}
					chk[eml]['isprocess'] = false;
					chk[eml]['result'] = user;
					NS.life(__self.finishCallback, eml, user);
				}
			});
		}
	};
	NS.UserFindByEmail = UserFindByEmail;
	
	
	var LogoWidget = function(container, filehash){
		filehash = filehash || null;
		this.init(container, filehash);
	};
	LogoWidget.prototype = {
		init: function(container, filehash){
			this.filehash = filehash;
			this.uploadWindow = null;
			buildTemplate(this, 'logo,img');
			var __self = this, TM = this._TM;
				
			container.innerHTML = TM.replace('logo');
			
			E.on(container, 'click', function(e){
				var el = E.getTarget(e);
				if (__self.onClick(el)){ E.preventDefault(e); }
			});
			
			this.render();
		},
		onClick: function(el){
			var tp = this._TId['logo'];
			switch(el.id){
			case tp['bimgfrompc']:
				this.uploadImageFromPC();
				return true;
			case tp['bimgfromfm']:
				this.selectImageFromFM();
				return true;
			case tp['bimgremove']:
				this.removeImage();
				return true;
			}
			return false;
		},		
		render: function(){
			this.setImageByFID(this.filehash);
		},
		uploadImageFromPC: function() {
			if (!L.isNull(this.uploadWindow) && !this.uploadWindow.closed){
				this.uploadWindow.focus();
				return;
			}
			var url = '/{C#MODNAME}/uploadlogo/';
			this.uploadWindow = window.open(
				url, 'teamlogoimage',	
				'statusbar=no,menubar=no,toolbar=no,scrollbars=yes,resizable=yes,width=550,height=500' 
			);
			NS.activeImageList = this;
		},
		selectImageFromFM: function() {
			var TM = this._TM, gel = function(n){ return TM.getEl('logo.'+n); };
			Dom.setStyle(gel('bimgfromfmld'), 'display', '');
			Dom.setStyle(gel('bimgfromfm'), 'display', 'none');

			var __self = this;
			Brick.ff('filemanager', 'api', function(){
				Dom.setStyle(gel('bimgfromfmld'), 'display', 'none');
				Dom.setStyle(gel('bimgfromfm'), 'display', '');
				Brick.mod.filemanager.API.showFileBrowserPanel(function(result){
					__self.setImageByFID(result['file']['id']);
				});
        	});
		},
		removeImage: function(){
			this.setImageByFID(null);
		},
		setImageByFID: function(fid){
			this.imageid = fid;
			var TM = this._TM, gel = function(n){ return TM.getEl('logo.'+n); };
			var el50 = gel('thumb50'),
				el100 = gel('thumb100'),
				el200 = gel('thumb200');
			
			if (L.isNull(fid)){
				el50.innerHTML = el100.innerHTML = el200.innerHTML = '&nbsp;';
				Dom.setStyle(gel('bimgremove'), 'display', 'none');
			}else{
				el50.innerHTML = TM.replace('img', {'fid': fid, 'w': 50, 'h': 50});
				el100.innerHTML = TM.replace('img', {'fid': fid, 'w': 100, 'h': 100});
				el200.innerHTML = TM.replace('img', {'fid': fid, 'w': 200, 'h': 200});
				Dom.setStyle(gel('bimgremove'), 'display', '');
			}
		},
		getValue: function(){
			return this.imageid;
		}
	};
	NS.LogoWidget = LogoWidget;

};