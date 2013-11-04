/* 
@package Abricos
*/

var Component = new Brick.Component();
Component.requires = {};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;

	var BW = Brick.mod.widget.Widget,
		buildTemplate = this.buildTemplate;
	
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
	
	
	var TeamTypeSelectWidget = function(container, manager, cfg){
		cfg = L.merge({
			'value': '',
			'onChange': null
		}, cfg || {});
		
		TeamTypeSelectWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'select,option'
		}, manager, cfg);
	};
	YAHOO.extend(TeamTypeSelectWidget, BW, {
		buildTData: function(manager, cfg){
			var lst = "", TM = this._TM;
			
			manager.initData.typeInfoList.foreach(function(tType){
				lst += TM.replace('option', {
					'id': tType.name,
					'tl': tType.title
				});
			});
			return {
				'rows': lst
			};
		},
		onLoad: function(manager, cfg){
			this.setValue(cfg['value']);

			var __self = this;
			E.on(this.gel('id'), 'change', function(e){
				NS.life(cfg['onChange'], __self.getValue());
			});
		},
		setValue: function(value){
			this.elSetValue('id', value);
		},
		getValue: function(){
			return this.gel('id').value;
		}
	});
	NS.TeamTypeSelectWidget = TeamTypeSelectWidget;
	
	var MemberSelectWidget = function(container, memberList, cfg){
		cfg = L.merge({
			'exclude': null,
			'value': '',
			'onChange': null
		}, cfg || {});
		
		MemberSelectWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'select,option'
		}, memberList, cfg);
	};
	YAHOO.extend(MemberSelectWidget, BW, {
		buildTData: function(memberList, cfg){
			var lst = "", TM = this._TM;

			var exc = L.isArray(cfg['exclude']) ? cfg['exclude'] : [];

			memberList.foreach(function(member){
				for (var i=0;i<exc.length;i++){
					if (member.id == exc[i]){ return; }
				}
				
				var user = Brick.mod.uprofile.viewer.users.get(member.id);
				if (!L.isValue(user)){ return; }
				
				lst += TM.replace('option', {
					'id': user.id,
					'tl': user.getUserName()
				});
			});
			return {
				'rows': lst
			};
		},
		onLoad: function(memberList, cfg){
			this.setValue(cfg['value']);

			var __self = this;
			E.on(this.gel('id'), 'change', function(e){
				NS.life(cfg['onChange'], __self.getValue());
			});
		},
		setValue: function(value){
			this.elSetValue('id', value);
		},
		getValue: function(){
			return this.gel('id').value;
		}
	});
	NS.MemberSelectWidget = MemberSelectWidget;	


};