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
		L = YAHOO.lang;

	var BW = Brick.mod.widget.Widget
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

	var ModuleTeamListWidget = function(container, cfg){
		cfg = L.merge({
			'modName': '{C#MODNAME}',
			'filterByUser': 0,
			'override': null
		}, cfg || {});

		ModuleTeamListWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'module',
			'override': cfg['override']
		}, cfg);
	};
	YAHOO.extend(ModuleTeamListWidget, BW, {
		init: function(cfg){
			this.cfg = cfg;
			
			this.manager = null;
			this._editor = null;
			this.listWidget = null;
		},
		onLoad: function(cfg){
			var __self = this;
			Brick.mod.team.Manager.init(cfg['modName'], function(man){
				__self.manager = man;
				__self.reloadList();
			});
		},
		reloadList: function(){
			this.elShow('loading');
			this.elHide('rlwrap');

			var cfgList = {}, cfg = this.cfg;
			if (cfg['filterByUser'] > 0){
				cfgList = {
					'memberid': cfg['filterByUser']
				};
			}
			
			var __self = this;
			this.manager.teamListLoad(function(list){
				__self._onLoadList(list);
			}, cfgList);
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['badd']: this.showTeamCreateWizard(); return true;
			}
			return false;
		},
		_onLoadList: function(list){
			this.list = list;

			this.elHide('loading');
			this.elShow('rlwrap');
			
			var cfg = this.cfg;
			
			if (cfg['filterByUser'] > 0 && Brick.env.user.id != cfg['filterByUser']){
				this.elHide('badd');
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
		showTeamCreateWizard: function(){
			this.closeEditors();

			var __self = this, cfg = this.cfg;

			this.componentLoad('{C#MODNAME}', 'teameditor', function(){
				__self.elHide('btns,list');
				
				__self._editor = new NS.TeamCreateWizardWidget(__self.gel('editor'), {
					'modName': cfg['modName'],
					'callback': function(act){
						__self.closeEditors();
						
						if (act == 'save'){ 
							__self.reloadList();
						}
					} 
				});
			}, {'hide': 'bbtns', 'show': 'edloading'});
		}
	});
	
	NS.ModuleTeamListWidget = ModuleTeamListWidget;

};