/* 
@package Abricos
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		R = NS.roles;

	var BW = Brick.mod.widget.Widget;
	var buildTemplate = this.buildTemplate;
	
	var TeamListRowWidget = function(container, team, cfg){
		cfg = L.merge({
			'desctrim': 300,
			'override': null
		}, cfg || {});
		
		TeamListRowWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'row,img',
			'isRowWidget': true,
			'override': cfg['override']
		}, team, cfg);
	};
	YAHOO.extend(TeamListRowWidget, BW, {
		init: function(team, cfg){
			this.team = team;
			this.cfg = cfg;
		},
		buildTData: function(team, cfg){
			return {
				'id': team.id,
				'cnt': 1,
				'tl': team.title,
				'dsc': this.trimDescript(team.descript),
				'mbrcnt': team.memberCount,
				'urlview': team.navigator.URI(),
				'img': L.isNull(team.logo) ? '' : this._TM.replace('img', {
					'urlview': team.navigator.URI(),
					'fid': team.logo,
					'tl': team.title
				})
			};
		},
		trimDescript: function(s){
			var cfg = this.cfg;
			s = s.replace(/\<br\/\>/gi, ' ');
			if (cfg['desctrim'] > 0 && s.length > cfg['desctrim']){
				s = s.substring(0, cfg['desctrim']) +"...";
			}
			return s;
		}
	});

	NS.TeamListRowWidget = TeamListRowWidget;
	
	var TeamListWidget = function(container, list, cfg){
		cfg = L.merge({
			'override': null
		}, cfg || {});
		
		TeamListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'list,empty',
			'override': cfg['override']
		}, list, cfg);
	};
	TeamListWidget.overrides = {};
	
	YAHOO.extend(TeamListWidget, BW, {
		init: function(list, cfg){
			this.list = list;
			this.cfg = cfg;
			this._wList = [];
		},
		destroy: function(){
			this._clearWS();
			TeamListWidget.superclass.destroy.call(this);
		},
		_clearWS: function(){
			var ws = this._wList;
			for (var i=0;i<ws.length;i++){
				ws[i].destroy();
			}
			this._wList = [];
		},
		onClick: function(el, tp){
			return false;
		},
		render: function(){
			this._clearWS();
			
			var __self = this, ws = this._wList;
			
			this.list.foreach(function(team){
				var wRowClass = NS.TeamListWidget.overrides[team.module];
				if (wRowClass){
					ws[ws.length] = new wRowClass(__self.gel('list'), team);
				}else{
					ws[ws.length] = new NS.TeamListRowWidget(__self.gel('list'), team);
				}
			});
			for (var i=0;i<ws.length;i++){
				ws[i].render();
			}
		}		
	});
	NS.TeamListWidget = TeamListWidget;
	
	
	var ModuleTeamListWidget = function(container, modname, cfg){
		cfg = L.merge({
			'override': null
		}, cfg || {});
		
		ModuleTeamListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'module',
			'override': cfg['override']
		}, modname, cfg);
	};
	YAHOO.extend(ModuleTeamListWidget, BW, {
		onLoad: function(modname, cfg){
	
			var NSMod = Brick.mod[modname];
			if (!L.isValue(NSMod)){ return; }
			
			var __self = this;
			NSMod.initManager(function(man){
				man.teamListLoad(function(list){
					__self._onLoadManager(list);
				});
			});
		},
		_onLoadManager: function(list){

			this.elHide('loading');
			this.elShow('rlwrap');
			
			this.listWidget = new NS.TeamListWidget(this.gel('list'), list); 
		}
	});
	NS.ModuleTeamListWidget = ModuleTeamListWidget;
	
	
	var buildTemplate = this.buildTemplate;
	
	var UProfileTeamListWidget = function(container, modname, user, cfg){
		cfg = L.merge({
			'override': null
		}, cfg || {});

		UProfileTeamListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'uprofile',
			'override': cfg['override']
		}, modname, user, cfg);
	};
	YAHOO.extend(UProfileTeamListWidget, BW, {
		init: function(modname, user, cfg){
			this.modname = modname;
			this.user = user;
			this.cfg = cfg;
			
			this.manager = null;
			this._editor = null;
			this.listWidget = null;
		},
		onLoad: function(modname, user, cfg){
			var NSMod = Brick.mod[modname];
			if (!L.isValue(NSMod)){ return; }
			
			var __self = this;
			NSMod.initManager(function(man){
				__self.manager = man;
				__self.reloadList();
			});
		},
		reloadList: function(){
			this.elShow('loading');
			this.elHide('rlwrap,badd');
			
			var __self = this;
			this.manager.teamListLoad(function(list){
				__self._onLoadList(list);
			}, {'memberid': this.user.id});
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['badd']: this.showTeamEditor(); return true;
			}
			return false;
		},
		_onLoadList: function(list){
			this.list = list;

			this.elHide('loading');
			this.elShow('rlwrap');
			
			if (Brick.env.user.id == this.user.id){
				this.elShow('badd');
			}
			
			if (!L.isNull(this.listWidget)){
				this.listWidget.destroy();
			}
			
			this.listWidget = new NS.TeamListWidget(this.gel('list'), this.list); 
		},
		closeEditors: function(){
			if (L.isNull(this._editor)){ return; }
			this._editor.destroy();
			this._editor = null;
			this.elShow('btns,list');
		},
		showTeamEditor: function(){
			this.closeEditors();

			var __self = this, mcfg = this.manager.cfg['teamEditor'];

			this.componentLoad(mcfg['module'], mcfg['component'], function(){
				__self.elHide('btns,list');
				
				__self._editor = new Brick.mod[mcfg['module']][mcfg['widget']](__self.gel('editor'), __self.modname, 0, function(act){
					__self.closeEditors();
					
					if (act == 'save'){ 
						__self.reloadList();
					}
				});
			}, {'hide': 'bbtns', 'show': 'edloading'});
		}
	});
	
	NS.UProfileTeamListWidget = UProfileTeamListWidget;


};